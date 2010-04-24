<?php

/*
	Plugin Name: Joind.in
	Plugin URL: http://joind.in/about/wordpress
	Description: Pull data from joind.in into your wordpress sidebar
	Version: 1.2
	Author: lornajane
	Author URI: http://lornajane.net
*/

function joindin_widget_init() {
	register_widget('Joindin_Widget');
}

add_action('widgets_init','joindin_widget_init');

function joindin_activate() {
	global $wpdb;

	// create a table to cache data into
	$table_name = $wpdb->prefix . 'joindin';
	$sql = 'CREATE TABLE ' . $table_name . ' (
		id int primary key auto_increment,
		time timestamp,
		host varchar(255) not null,
		type varchar(255) not null,
		action varchar(255) not null,
		params varchar(3000),
		results text)';

	$wpdb->query($sql);

	// add a cron job to clean up the data each day
	wp_schedule_event(time(), 'daily', 'joindin_expire_data', array('where_sql' => 'WHERE time < DATESUB(NOW(), INTERVAL 1 DAY)'));
	return true;
}

/**
 * function to work around some issues I had with wp_clear_scheduled_hook
 * where the arguments were a nested away from where they needed to be
 * to match
 */
function joindin_clear_scheduled_hook( $hook ) {
    $args = array_slice( func_get_args(), 1 );
    if (is_array($args) && count($args) > 0) {
        $args = $args[0];
    }
    while ( $timestamp = wp_next_scheduled( $hook, $args ) ) {
        wp_unschedule_event( $timestamp, $hook, $args );
    }
}

function joindin_deactivate() {
	global $wpdb;

	// create a table to cache data into
	$table_name = $wpdb->prefix . 'joindin';
	$sql = 'DROP TABLE ' . $table_name ;
	$wpdb->query($sql);

	// clean up any cron jobs
	joindin_clear_scheduled_hook('joindin_expire_data', array('where_sql' => 'WHERE time < DATESUB(NOW(), INTERVAL 1 DAY)'));

	return true;
}
register_activation_hook(__FILE__, 'joindin_activate');
register_deactivation_hook(__FILE__, 'joindin_deactivate');

function joindin_expire_data($where_sql) {
	global $wpdb;

	$wpdb->query("DELETE FROM " . $wpdb->prefix . "joindin " . $where_sql);
}

/*
 * Main Widget Class
 */

class Joindin_Widget extends WP_Widget {
	public function Joindin_Widget() {
		// set up the widget properties
		$widget_ops = array('classname' => joindin, 'description' => 'Pull data from joind.in onto your sidebar');

		// define widget options
		$control_ops = array("foo" => "bar");

		// create the widget
		$this->WP_Widget('joindin_widget', 'Joind.in', $widget_ops, $control_ops);

	}

	public function widget($args, $instance) {
		extract($args);

		// wrap up presentation in theme pieces
		echo $before_widget;
		if($instance['title']) {
			echo $before_title . $instance['title'] . $after_title;
		}

		// main content
		switch($instance['show']) {
			case "hot_events":
					$this->output_hot_events($instance['url'], $instance['limit']);
					break;
			case "talks_by_event":
					$this->output_talks_by_event($instance['url'], $instance['event_id'], $instance['random'], $instance['limit']);
					break;
			default:
					echo "no events found";
					break;
		}

		// end presentation wrapping
		echo $after_widget;
	}

	public function update($new_instance, $old_instance) {
		// start with the old settings, then selectively override
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['url'] = strip_tags($new_instance['url']);
		$instance['show'] = strip_tags($new_instance['show']);
		$instance['event_id'] = (int)$new_instance['event_id'];
		$instance['limit'] = (int)$new_instance['limit'];
		$instance['random'] = (bool)$new_instance['random'];

		return $instance;
	}

	public function form($instance) {
		$defaults = array('title' => 'Joind.In', 'url' => 'http://joind.in/','show' => 'hot_events', "limit" => 5, "random" => true);
		$instance = wp_parse_args( (array) $instance, $defaults );

		// now output the form
		?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>">Display Title:</label>
		<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'url' ); ?>">Joind.in URL:</label>
		<input id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo $instance['url']; ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'show' ); ?>">Show:</label>
		<select id="<?php echo $this->get_field_id( 'show' ); ?>" name="<?php echo $this->get_field_name( 'show' ); ?>">	
			<option<?php if ( 'hot_events' == $instance['show'] ) echo ' selected="selected"'; ?> value="hot_events">Hot Events</option>
			<option<?php if ( 'talks_by_event' == $instance['show'] ) echo ' selected="selected"'; ?> value="talks_by_event">Talks For Event</option>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'event_id' ); ?>">Event ID:</label>
		<input id="<?php echo $this->get_field_id( 'event_id' ); ?>" name="<?php echo $this->get_field_name( 'event_id' ); ?>" value="<?php echo $instance['event_id']; ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>">Max Entries to Display:</label>
		<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" value="<?php echo $instance['limit']; ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'random' ); ?>">Randomise Entry Ordering:</label>
		<input id="<?php echo $this->get_field_id( 'random' ); ?>" name="<?php echo $this->get_field_name( 'random' ); ?>" type="checkbox" <?php if ( $instance['random'] ) echo ' checked="checked"'; ?> />
		</p>

		
	<?php
	}

    protected function joindin_request($host, $type, $action, $params) {
		global $wpdb;

		/*
		// allegedly needed for WP 3.0
		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC. '/class-http.php' );
		*/

		// hit the cache
		$result = $wpdb->get_var("SELECT results from " . $wpdb->prefix . "joindin WHERE
			host = '" . $wpdb->escape($host) . "' AND
			type = '" . $wpdb->escape($type) . "' AND
			action = '" . $wpdb->escape($action) . "' AND
			params = '" . $wpdb->escape(serialize($params)) . "' AND
			time > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE)
			ORDER BY time DESC
			LIMIT 1");

		if($result) {
			return unserialize($result);
		}

		// we don't have cached info - get the live data

        $req = new StdClass();
        $req->request = new StdClass();

        $req->request->action->type = $action;
        if (is_array($params)) {
            $req->request->action->data = new StdClass();
            foreach( $params as $k=>$v ) { 
                $req->request->action->data->$k = $v; 
            }   
        }   

        $payload = json_encode($req);

		$api_url = $host.'/api/'.urlencode($type);
		$headers = array( 'Content-Type' => "application/json" );
		$request = new WP_Http;
		$response = $request->request( $api_url , array( 'method' => 'POST', 'body' => $payload, 'headers' => $headers ));
        $result = json_decode($response['body']);

		if(!empty($result)) {
			// store in the cache
			$sql = "INSERT INTO " . $wpdb->prefix . "joindin (host, action, type, params, results) VALUES
				('" . $wpdb->escape($host) . "', 
				'" . $wpdb->escape($action) . "', 
				'" . $wpdb->escape($type) . "', 
				'" . $wpdb->escape(serialize($params)) . "', 
				'" . $wpdb->escape(serialize($result)) . "')";

			$stored = $wpdb->query($sql);
		}

		// use the data
		return $result;
	}

	protected function output_hot_events($url, $limit) {
		$result = $this->joindin_request($url, 'event', 'getlist', array("event_type" => "hot"));
		$row_count = 0;
		if($result) {
			foreach($result as $event) {
				$rowcount++;
				echo "<p><a href=\"$url/event/view/" . $event->ID . "\">" . $event->event_name . "</a></p>\n";

				// did we display enough records yet? If so, stop
				if($rowcount >= $limit) break;
			}
		} else {
			echo "no events";
		}
		return true;
	}

	protected function output_talks_by_event($url, $event_id, $random, $limit) {
		$result = $this->joindin_request($url, 'event', 'gettalks', array("event_id" => $event_id));
		$rowcount = 0; // count how many records we printed
		if($result) {
			if($random) {
				// pick records out of order
				$total = count($result) - 1; // upper limit of number to randomise
				$used = array(); // array to hold all used elements
				$fail = 0; // count failures - so we can break out of an infinite loop if needs be
				while($rowcount < $limit) {
					// if we have had a few fails, bail
					if($fail > 20) {
						break;
					}

					// pick an array element
					$element = rand(0, $total);

					// did we already print this one?
					if(in_array($element, $used)) { 
						$fail++;
						continue;
					}

					$talk = $result[$element];

					// ignore social events
					if($talk->tcid == 'Social Event') {
						$fail++;
						continue;
					}

					echo "<p><a href=\"$url/talk/view/" . $talk->ID . "\">" . $talk->talk_title. "</a></p>\n";
					$rowcount++;
					$fail = 0;
					$used[] = $element;
				}
			} else {
				// just iterate
				foreach($result as $talk) {
					// ignore social events
					if($talk->tcid == 'Social Event') continue;

					$rowcount++;
					echo "<p><a href=\"$url/talk/view/" . $talk->ID . "\">" . $talk->talk_title. "</a></p>\n";

					// did we display enough records yet? If so, stop
					if($rowcount >= $limit) break;
				}
			}
		} else {
			echo "no talks";
		}
		return true;
	}
}

=== Joind.in Wordpress Plugin ===
Contributors: lornajane
Donate link: http://pledgie.com/campaigns/7742
Tags: event, talk, session, conference
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 1.2

A flexible, configurable widget to add either events from the joind.in site or sessions for a particular event on that site to your sidebar

== Description ==
This plugin is a sidebar widget which draws data from the joind.in website, or any other site exposing the same API.  Joind.in is an open source event management/feedback site written in PHP - see [http://joind.in](http://joind.in).

Features:
*	sidebar widget
*	shows hot events or talks at a given event
*	customisable title
*	database caching - avoid repeated and slow API calls to joind.in

== Installation ==
1. Upload joindin.php to your plugins directory or install using the wizard
1. On your plugins list, you should see it listed as "Joind.in"; activate it.  Activation creates a new database table called wp_joindin which is used to cache data (if you use a different table prefix it will pick this up).
1. On the widgets screen you can now drag a joind.in widget onto your sidebar(s) and configure it.  It has a title and also the URL of the joind.in site - only change this URL if you want to point to your own or an alternative installation of joind.in (it assumes the API is at /api/).  Choose what your plugin should display.  Hot events shows the upcoming events shown in the system.  Talks for event shows a list of sessions for the event identified by the ID entered in the "event id" field (get this by viewing the event on the main website and taking the number from the URL).  Finally you can limit how many records are displayed.

== Frequently Asked Questions ==

= Can the plugin integrate with other sites? = 
Currently the plugin only works against the joind.in API.  This is an open source project so you can run another copy of the code and point your plugin to that if you like, but no other APIs are supported.

= How can I get other information from joind.in to display? =
Comment on the plugin page what you want to do and why - someone may help!

== Screenshots==
1. joindin_wp1.png
2. joindin_wp2.png

== Changelog ==

= 1.0 =

= 1.1 =
Correcting a PHP short tag - thanks to @phpcodemonkey for the bug report

= 1.2 =
Fixing a **major** bug in this plugin, which registered the cron events repeatedly.  If you are upgrading please deactivate and reactivate your plugin after you upgrade!
Switching over to using the wordpress http handling rather than requiring curl (thanks to James Collins who commented on my blog suggesting this)

== Upgrade Notice ==
Please deactivate and reactivate your plugin if upgrading from versions < 1.2

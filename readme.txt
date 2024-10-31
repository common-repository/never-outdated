=== Never Outdated ===
Contributors: tesial
Tags: notification, version, email, blog, cms, websites, wordpress, plugin, update, check
Requires at least: 2.7.0
Tested up to: 3.4.2
Stable tag: 1.0.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Never let your WordPress be outdated, receive an email whenever a new version of your favorite CMS is available!

== Description ==

Never let your WordPress be outdated, receive an email whenever a new version of your favorite CMS is available!

In order to use this plugin, you must first freely register on the related website: [NeverOutdated](http://www.neveroutdated.com/ "NeverOutdated.com").
Then you will be able to add as many blogs as you wish. It’s free !

Periodically, our server will gather informations about your blog then it will compare them with the latest available (and stable) version and will notify you if you've missed an update. Other notifications will be sent to you to remind you periodically.

[NeverOutdated](http://www.neveroutdated.com/ "NeverOutdated.com") has been developped by [Tesial](http://www.tesial.be/ "Tesial.be")

**Information gathered**:

* WordPress database version
* WordPress version
* Plugin list
	* Plugin name
	* Plugin version

== Installation ==

1. Upload the plugin using the Plugin Manager built into WordPress
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Freely register on [NeverOutdated](http://www.neveroutdated.com/ "NeverOutdated.com") to generate a secure key
1. Configure your plugin by setting up the secure key 

== Frequently Asked Questions ==

= Which information are gathered from my WordPress =

Solely technical information about your blog such as database version, WordPress version and installed plugin's version.

= How are these information gathered =

By gently asking to the WordPress API only.

= How many CMS could I configure ? =

As much as you wish. There is no limit.

= How much does it cost ? =

It’s totally free !

= Could I see all my websites configuration ? =

Connect to [NeverOutdated](http://www.neveroutdated.com/ "NeverOutdated.com") and go to your dashboard to see the complete websites list.

== Screenshots ==

1. Go to [NeverOutdated](http://www.neveroutdated.com/ "NeverOutdated.com") and register in order to add your blogs.

2. This is how a dashboard looks like. All your blogs, their versions, the latest available version and finally the status.

== Changelog ==

= 1.0.2 =
* Avoid false error due to other plugin interacting with wordpress while accessing directly to the plugin

= 1.0.1 =
* Plugin upgrade was considered as direct access, which was wrong
* Do not delete plugin configuration if de-activated
* Minor fix to match website update to 2.2

= 1.0 =
* Initial version
* Gather WordPress version and database version
* Gather Plugin's versions and names


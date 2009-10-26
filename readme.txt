=== FortuneKookie Widget ===
Plugin Name: 		FortuneKookie Fortunes
Contributors: 		blendium
Version:			0.9.0.0
Requires at least:	2.7
Tested up to:		2.8.5
Stable tag: 		trunk
Tags:		 		fortune cookie, fortune cookie message, fortunekookie

This WordPress plugin adds a sidebar widget to display a random fortune cookie fortune.

== Description ==

This WordPress plugin adds a sidebar widget to display a random fortune cookie fortune. The database hosted on FortuneKookie.com has over 1500 unique fortunes and each fortune includes the front message, the back word(s), and the lucky numbers.

== Installation ==

In order to setup this widget you must find your fortunekookie code.

1. Upload the `fortunekookie` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to your widget editor, Design -> Widgets and Add FortuneKookie to your sidebar by clicking 'Add' next to "FortuneKookie"
4. Go back to your widget configuration and click edit next to "FortuneKookie: FortuneKookie Fortune" now on the right side menu.
5. Copy and paste your Secret Code: dbc6f4b1aa48acc5c8ceb9dae38a91af
6. Change any other configuration settings here such as Widget Title and Show (Show back of fortune and/or show lucky numbers).
7. Click "Save", drag your Widget to the location you want it on your widget list and click "Save Changes"

Congratulations! Your FortuneKookie widget is now working!

FortuneKookie may work on WordPress releases prior to 2.7 but this has not been tested.

== Frequently Asked Questions ==

= What does this widget do? =
This widget interfaces with FortuneKookie.com and pulls a random fortune cookie message from its database. Then, displays the fortune on your sidebar of your blog.

= Can I display more than one fortune at a time? =
Not in this version of the widget

= What is the secret code? =
This code allows the FortuneKookie servers to track the source of the API call. In the initial version the 32-digit secret code is the same for everyone, it is dbc6f4b1aa48acc5c8ceb9dae38a91af.


== Screenshots ==

1. FortuneKookie_blog.png - Fully Install FortuneKookie widget
2. FortuneKookie_config.png - View of the configuration screen


== Changelog ==

= 0.9.0.0 =
	* Support to toggle show / hide of fortune cookie back of message
	* Support to toggle show / hide of fortune cookie lucky numbers

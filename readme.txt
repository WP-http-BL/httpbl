=== http:BL WordPress Plugin ===
Contributors: janstepien
Tags: comments, spam
Requires at least: 2.0
Tested up to: 2.0.3
Stable tag: trunk

http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the Project Honey Pot database.

== Description ==

http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the Project Honey Pot database. Thanks to http:BL API you can quickly check whether your visitor is an email harvester, a comment spammer or any other malicious creature. Communication with verification server is done via DNS request mechanism, which makes the query and response even quicker. Now, thanks to http:BL WordPress Plugin any potentially harmful clients are denied from accessing your blog and therefore abusing it.

== Installation ==

1. Get an archive with the most recent version of http:BL WordPress Plugin.
1. Uncompress it to your wp-content/plugins directory.
1. Activate the plugin in the administration panel.
1. Open the plugin’s configuration subpage and enter your Access Key and configure available options accordingly to your preferences.
1. If you'd like http:BL WordPress Plugin to log any suspicious visitors create a proper table in your database. Its structure is available in file *httpbl_log.sql*.
1. Save settings and enjoy.

== Frequently Asked Questions ==

= Does http:BL WordPress Plugin work with WordPress MU? =

Yes, it does, but it needs a small modification in the source code. Find a line containing *plugins.php* and change it to *wpmu-admin.php*. It should work fine.

= Is there an easy way to contact with the developer of this plugin? =

Of course there is. Visit [the original blog post where the plugin has been announced](http://stepien.com.pl/2007/04/28/httpbl_wordpress_plugin/) and post your comment over there.

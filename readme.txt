=== http:BL WordPress Plugin ===
Contributors: janstepien, madeinthayaland, M66B, BrianLayman, szepe.viktor
Tags: comments, spam, http:BL
Requires at least: 2.0
Tested up to: 3.3.1
Stable tag: 1.9.1.2

http:BL WordPress Plugin allows you to verify IP addresses of clients
connecting to your blog against the Project Honey Pot database.

== Description ==

http:BL WordPress Plugin allows you to verify IP addresses of clients
connecting to your blog against the Project Honey Pot database. Thanks to
http:BL API you can quickly check whether your visitor is an email harvester,
a comment spammer or any other malicious creature. Communication with
verification server is done via DNS request mechanism, which makes the query
and response even quicker. Now, thanks to http:BL WordPress Plugin any
potentially harmful clients are denied from accessing your blog and therefore
abusing it.

This plugin is in the process of being refreshed. Compatibility with current 
versions of WordPress is unknown. Versions prior to 2.0 should be used only 
with extreme caution.  There are known security issues and vulnerabilities.


= New Maintainers =

Viktor Szépe and Brian Layman are now maintaining this plugin.  
If you have any suggestions or comments, please add them on the WordPress Support Forum here:
https://wordpress.org/support/plugin/httpbl

Or as a GitHub issue here:
https://github.com/WP-http-BL/httpbl/issues


== Installation ==

1. Get an archive with the most recent version of http:BL WordPress Plugin.
1. Uncompress the `httpbl` directory from the archive to your
`wp-content/plugins` directory.
1. Activate the plugin in the administration panel.
1. Open the plugin’s configuration subpage and enter your Access Key and
configure available options accordingly to your preferences.
1. Save settings and enjoy.

== Frequently Asked Questions ==

= Does http:BL WordPress Plugin work with WordPress MU? =

Yes, it does. If you don't want to give your bloggers an access to the
plugin's configuration page, you have to modify the source code slightly. Find
a line containing `plugins.php` and change it to `wpmu-admin.php`. It should
work fine. If it does not, do not hesitate to inform me about it or provide
your own patch.

= Is there an easy way to contact the developer of this plugin? =

Of course there is. Visit [author's website](http://stepien.cc/~jan) in order
to find his e-mail address and Jabber ID.

== Changelog ==
= 1.9.1.x =
* No changes. Webhook Sync test.

= 1.9.1 =
* New Maintainer initial check in.

= 1.9 =
* Patches from Eric Seiler including
  * an update to new roles model
  * less notices with WP_DEBUG == true
  * a missing call to httpbl_check_log_table

= 1.8 =
* If a honey pot link is specified an invisible link will be inserted on every page automatically to help the project
* Fixed combinations of specific and generic threat types
* Added upgrade notice to documentation
* Added changelog to documentation

= 1.7 =
* Added options to specify threat level per threat type

== Upgrade Notice ==

= 1.9 =
Minor changes and updates. See the change log for details.

= 1.8 =
Adding honey pot, fixed threat combinations, updated documentation.

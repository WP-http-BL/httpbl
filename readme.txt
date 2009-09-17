=== http:BL WordPress Plugin ===
Contributors: janstepien, madeinthayaland, M66B
Tags: comments, spam
Requires at least: 2.0
Tested up to: 2.8.4
Stable tag: 1.6

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


= Your Feedback Matters =

Bugs to report? Feature requests? Criticism? New ideas? We want to hear from
you! Do not hesitate. Get in touch with us and share your views.

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

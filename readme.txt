=== Simple Picasa Album Embedder ===
Contributors: caekcraft
Donate link: http://www.caekcraft.co.uk/simple-picasa-album-embedder/
Tags: picasa, picture, gallery, image, google, API, embed, album
Requires at least: 2.7
Tested up to: 3.1.3
Stable tag: 1.0.7

A plugin to display one private album on your blog with no styling to allow you to customize it.

== Description ==

Ever searched for the perfect gallery plugin only to find all of them a bit too much, or not exactly right for your design?

Me too. This is why I wrote this. The end result is an unordered list of your pictures as thumbnails linking to the original
size pictures.

It will connect to your picasa albums (so a google account is a must). It will get a list of all your albums (even the private ones). Because of this, the plugin will need to store your google credentials. These  are stored encrypted in the DB though. It will ask you where you want to display the gallery.

Be aware though, that you will have to style the images yourself. I recommend a lightbox plugin, as all the pictures have a rel=[dude] added to them (so that they count as one set of images).

What is to come:
<ul>
<li>Remote authentication (even if your db is compromised, the key is not stored there, so they will not be to get your data from the cyphers)</li>
<li>Multiple albums</li>
<li>Shortcodes</li>
<li>Some simple styling (entirely your choice)</li>
</ul>


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the folder 'simple-picasa-album-embedder' to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Input your codes on the Settings menu, chose your album, and the page you want this to appear.

== Frequently Asked Questions ==

The FAQ can be found on the [dedicated page for this plugin](http://www.caekcraft.co.uk/simple-picasa-album-embedder/ "Simple Picasa Album Embedder").

== Screenshots ==

1. This is what happens when Zend is not installed. Install and activate [Zend Gdata Interfaces](http://wordpress.org/extend/plugins/zend-gdata-interfaces/ "Zend Gdata Interfaces") for this to work.
2. Once Zend is up, it will check if username and password are matching. If not, this is what you will see.
3. Username and password match! Yay! This is the interface.
4. Gets all your albums. Select one.
5. Gets all your pages. Select one.
6. This is an example output.

== Changelog ==
= 1.0.7 =
* cleaned up readme, fixed changelog
* fixed version info in the plugin file (.php)

= 1.0.6 =
* added a new screenshot, cleaned up tag

= 1.0.5 =
* Bugfix: tags were not opened-closed in the right order, this caused unnecessary &lt;ul&gt; tags to be added early on in the code.
* Readme.txt cleanup

= 1.0 =
* First version, nothing changed.

== Upgrade Notice ==
= 1.0.7 =
No new / changed functionality, only cosmetic improvements.

= 1.0.6 =
No new / changed functionality, only cosmetic improvements.

= 1.0.5 =
Previous version has a tag balancing issue at output. Results in broken semantics, upgrade is essential.

= 1.0 =
No need to upgrade yet.
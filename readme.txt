=== Taxonomy Extra Tools ===
Contributors: 3dolab
Tags: taxonomy, custom taxonomy, permalink, custom post type, custom post types, archive, post type, post types
License: GPLv2
Requires at least: 3.4
Tested up to: 4.0
Stable tag: 0.2

Enable main archives for your custom Taxonomies and get the most from your custom Post Types by enabling the Extra Tools advanced functions

== Description ==

Taxonomy Extra Tools makes you able to:

* activate a main root archive page, with a clean permalink rewrite structure, for your custom and built-in taxonomies (e.g. mysite.com/custom-taxonomy/, mysite.com/category/ )
please note that WordPress does not provide this kind of page template by default, as a specific term is usually required by the query
* simply add your custom and built-in taxonomies archive to the nav menu
* choose if you like to display the terms and/or their associated (custom) posts as a flat list or maintain the hierarchy levels between parent and child terms
* easily style the output term list by adding a filter from your theme's functions.php (the Twenties default theme series is already supported)
* activate a filter to manipulate the post title and insert (custom) taxonomies terms according to a text replacement string
* activate the "post extra" one-to-many relationship by selecting the custom post types that can be associated to posts
* use the "Recent Extras" widget to show the entries from the selected post types belonging to the current post
* use the "Current Terms" widget to show the terms from the selected taxonomies assigned to the current post

Custom Taxonomies and Post Types must be registered/created previously: the [Types](http://wordpress.org/extend/plugins/types/) plugin is strongly recommended.

The [Categories Images](http://wordpress.org/plugins/categories-images/) plugin can be used to add/assign images to categories and custom taxonomies.

Make sure the permalink rewrite structure works as expected by checking through the [Rewrite Rules Inspector](http://wordpress.org/plugins/rewrite-rules-inspector/) plugin.

*[banner image by Proyecto Agua](https://www.flickr.com/photos/microagua/3654846926)*

== Installation ==

1. Upload 'taxonomy-extra-tools' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Check the settings administration menu

== Frequently Asked Questions ==

= How can I display a proper title in the archive page? =
It really depends on the theme in use. You can check against the global variable $tet_bingo and set the title in the template as the taxonomy name.
You can directly copy the taxonomy.php files provided in this plugin's /template subdirectory to your Twenty Fourteen / Thirteen / Twelve folder or use them as a reference in customizing other themes.

= How can I display the taxonomy terms and/or extra post type entries, related to the current post, only in posts? =
Add the corresponding widgets from the admin screen, check the "current post" option and then set the visibility accordingly (through Jetpack, Widget Logic or other similar functions).

= How can I select custom post types as recipients of the "Extra" post types? =
Unfortunately, at the moment it is not yet possible to select other post types than the default post, but it could be possible in a further release.

== Screenshots ==

1. the root Taxonomy Archive page @ mysite.com/taxonomy/ => mysite.com/colours/
2. the Admin Settings page
3. the Title Filter in action, the Recent Extras and Current Terms widgets

== Changelog ==

= 0.2 =
* Bugfix: Extra post metabox
* Bugfix: Nav menu admin
* Added .POT file

= 0.1 =
* First release
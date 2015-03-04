=== Taxonomy Extra Tools ===
Contributors: 3dolab
Tags: taxonomy, custom taxonomy, permalink, title, nav menu, menu, archive, custom post type, custom post types, post type, post types
License: GPLv2
Requires at least: 3.4
Tested up to: 4.1
Stable tag: 0.4

Enable main archives with clean titles, permalinks and nav menu items for your custom Taxonomies by enabling the Extra Tools advanced functions

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
* use the conditional tag *is_tax_root_archive()* to check whether the loop belongs to a taxonomy main root archive page
* get a clean HTML title tag with the taxonomy name in main taxonomy root archives
* use the %%taxonomy_name%% replacement in conjunction with the WordPress SEO plugin by Yoast

Custom Taxonomies and Post Types must be registered/created previously: the [Types](http://wordpress.org/extend/plugins/types/) plugin is strongly recommended.

The [Categories Images](http://wordpress.org/plugins/categories-images/) plugin can be used to add/assign images to categories and custom taxonomies.

Make sure the permalink rewrite structure works as expected by checking through the [Rewrite Rules Inspector](http://wordpress.org/plugins/rewrite-rules-inspector/) plugin.

*[banner image by Proyecto Agua](https://www.flickr.com/photos/microagua/3654846926)*

== Installation ==

1. Upload 'taxonomy-extra-tools' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Check the settings administration menu

== Frequently Asked Questions ==

= How can I display a proper title in the taxonomy archive pages? =
This plugin automatically applies a filter to the HTML title tag shown in the browser bar, compatibility with the WordPress SEO plugin by Yoast is further improved with the additional %%taxonomy_name%% replacement.
As for the taxonomy archive page, it really depends on the theme in use. First you basically need a proper taxonomy.php template file just as archive.php or category.php in your theme's directory.
Then you can check against the conditional tag `is_tax_root_archive()` or the global variable `$tet_bingo` and set the title in the template as the taxonomy name, instead of the term title or whatever.
You can directly copy the taxonomy.php files provided in this plugin's /template subdirectory to your Twenty Fourteen / Thirteen / Twelve folder or use them as a reference in customizing other themes.

= How can I style the terms output displayed in the taxonomy archive root page? =
Hook to the filters `tet_term_display_before`, `tet_term_display_output`, `tet_term_display_after` applied to the function *tet_taxonomy_archive_show_children_terms*: 
quite like the native *get_the_term_list*, but it does run automatically at loop start and with more parameters *($term_link, $term_object, $taxonomy, $index, $count, $max_num_pages, $parent, $show_meta)*

= How can I display the taxonomy terms and/or extra post type entries, related to the current post, only in posts? =
Add the corresponding widgets from the admin screen, check the "current post" option and then set the visibility accordingly (through Jetpack, Widget Logic or other similar functions).

= How can I select custom post types as recipients of the "Extra" post types? =
Unfortunately, no other type than the default post can be selected: this is meant as a simple connection feature, while the [Posts 2 Posts](https://wordpress.org/plugins/posts-to-posts/) plugin is better suited to create more complex many-to-many relationships between posts of any type and users.

= How can I display the taxonomies associated to a certain post? =
You can directly use the function `tet_render_taxonomies( $classes, $taxonomies, $tax_separator, $term_separator, $link, $echo, $limit, $show_meta )` or wrap and hook it to a filter, 
with additional filters available *(tet_render_taxonomies_link, tet_render_taxonomies_sep,tet_render_taxonomies_sep2)*

== Screenshots ==

1. the root Taxonomy Archive page @ mysite.com/taxonomy/ => mysite.com/colours/
2. the Admin Settings page
3. the Title Filter in action, the Recent Extras and Current Terms widgets

== Changelog ==

= 0.4 =
* Improved compatibility with WP SEO by Yoast
* Bugfix: globals in the is_tax_root_archive() conditional tag

= 0.3 =
* Additional parameters in terms list
* Improved output filters
* Added function tet_render_taxonomies
* Added is_tax_root_archive()

= 0.2 =
* Bugfix: Extra post metabox
* Bugfix: Nav menu admin
* Added .POT file

= 0.1 =
* First release
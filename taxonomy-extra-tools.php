<?php
/*
Plugin Name: Taxonomy Extra Tools
Plugin URI: http://www.3dolab.net/blog/dev/
Description: Enable main archives for your custom Taxonomies and get the most from your custom Post Types by enabling the Extra Tools advanced functions
Author: 3dolab
Author URI: http://www.3dolab.net/
Author Email: info@3dolab.net
Version: 0.4
License: GPLv2
Text Domain:  taxonomy-extra-tools
Domain Path:  /languages/
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

// Make sure that no info is exposed if file is called directly -- Idea taken from Akismet plugin
if ( !function_exists( 'add_action' ) ) {
	echo "This page cannot be called directly.";
	exit;
}

function register_taxonomy_extra_tools() {

	require_once( dirname( __FILE__ ) .'/admin/settings.php');
	
	load_plugin_textdomain( 'taxonomy-extra-tools', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	$default_options = array(
		'title_post_types' => array(),
		'title_term_sep' => ', ',
		'title_rewrite_pattern' => '%title%',
		'title_append_taxonomies' => false,
		'archive_taxonomies' => array(),
		'archive_tax_child' => false,
		'archive_tax_hide_child' => false,
		'archive_tax_child_posts' => false,
		'archive_tax_posts' => false,
		'extra_post_types' => array()
	);
	
	$options = get_option('tet_options');
	if(isset($options) && !empty($options)){
		foreach($default_options as $option => $value){
			if(!isset($options[$option]))
				$options[$option] = $value;
		}
		update_option('tet_options',$options);
	}elseif(!isset($options) || empty($options))
		update_option('tet_options',$default_options);
		
	if($options['archive_tax_posts'] != true)
		add_action( 'pre_get_posts', 'tet_taxonomy_archive_query_hide', 99 );
	
	if(!empty($options['archive_taxonomies']))
		require_once( dirname( __FILE__ ) .'/admin/nav-menu.php');
	if(class_exists('Taxonomy_Archive_Links'))
		Taxonomy_Archive_Links::init();
		//add_action( 'wp_ajax_taxonomy_archive_menu', array( 'Taxonomy_Archive_Links', 'ajax_add_taxonomy' ) );
	
	require_once( dirname( __FILE__ ) .'/includes/query-filters.php');
	require_once( dirname( __FILE__ ) .'/includes/template-filters.php');
	require_once( dirname( __FILE__ ) .'/includes/title-filters.php');
	
	add_action('admin_menu', 'tet_options_menu');
	add_action( 'admin_menu', 'tet_add_extra_to_post_metaboxes' );
	add_action( 'admin_menu', 'tet_extra_post_type_metabox' );
	//add_action( 'admin_head', 'wpec_daily_deal_metaboxes' );
	add_action( 'generate_rewrite_rules', 'tet_taxonomy_archive_permalink' );
	add_action( 'parse_tax_query', 'tet_taxonomy_archive_tax_query' );
	add_action( 'save_post', 'tet_save_post_extra_data', 10, 2 );
	add_filter( 'wp_insert_post_data','tet_post_extra_pre_update', 100, 2 );
	add_filter('the_title', 'tet_modified_post_title');
	add_filter('wp_title', 'tet_taxonomy_archive_wp_title', 10, 3);
	add_filter('wpseo_title', 'tet_taxonomy_archive_wp_title', 10, 3);
	add_action( 'loop_start', 'tet_taxonomy_archive_show_children_terms' );
	
	if($options['title_append_taxonomies']==true)
		add_filter('the_content', 'tet_append_taxonomies');

	$current_theme = wp_get_theme();
	if($current_theme == 'Twenty Fourteen' || $current_theme == 'Twenty Thirteen' || $current_theme == 'Twenty Twelve')
		add_filter( 'tet_term_display_output', 'tet_taxonomy_term_loop_template', 10, 3 );
	//register_widget('tet_Widget_Recent_Extras');
	//add_action( 'after_setup_theme', 'tet_widgets_setup' );
}
add_action( 'plugins_loaded', 'register_taxonomy_extra_tools', 99 );

function tet_taxonomy_archive_permalink( $wp_rewrite ) {
	if ( get_option('permalink_structure') ) {
		$args = array(
		  'public'   => true,
		  //'_builtin' => false	  
		); 
		$output = 'objects'; // or names
		$operator = 'and'; // 'and' or 'or'
		$taxonomies = get_taxonomies( $args, $output, $operator );
		//$taxonomies = get_taxonomies(); 
		if ( $taxonomies ) {
			$options = get_option('tet_options');
			$active_taxonomies = $options['archive_taxonomies'];
			if(!$active_taxonomies)
				$active_taxonomies = array();
			foreach ( $taxonomies  as $taxname => $taxonomy ) {
				if(in_array($taxname, $active_taxonomies)){
					$slug = $taxonomy->rewrite['slug'];
					unset($wp_rewrite->rules[$slug.'(/[0-9]+)?/?$']);

					$wp_rewrite->rules = array(
						//$slug.'(/[0-9]+)?/?$' => $wp_rewrite->index . '?'.$taxonomy->query_var.'&paged=' . $wp_rewrite->preg_index( 1 ),
						$slug.'/?$' => $wp_rewrite->index . '?'.$taxonomy->query_var.'&paged=' . $wp_rewrite->preg_index( 1 ),
						$slug.'/page/?([0-9]{1,})/?$' => $wp_rewrite->index . '?'.$taxonomy->query_var.'&paged=' . $wp_rewrite->preg_index( 1 ),
					) + $wp_rewrite->rules;
				}
			}
		}
	}
}

function tet_widgets_setup() {
	add_action( 'widgets_init', 'tet_widgets_init' );
}
function tet_widgets_init() {
	register_widget( 'tet_Widget_Recent_Extras' );
	register_widget( 'tet_Widget_Current_Terms' );
}
require_once( dirname( __FILE__ ) .'/widgets/recent-extras.php');
require_once( dirname( __FILE__ ) .'/widgets/current-terms.php');
//add_action( 'widgets_init',create_function( '', 'return register_widget("tet_Widget_Recent_Extras");' ) );
//add_action( 'widgets_init',create_function( '', 'return register_widget("tet_Widget_Current_Terms");' ) );
add_action( 'widgets_init', 'tet_widgets_init' );
?>
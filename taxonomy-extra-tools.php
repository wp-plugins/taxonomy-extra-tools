<?php
/*
Plugin Name: Taxonomy Extra Tools
Plugin URI: http://www.3dolab.net/blog/dev/
Description: Enable main archives for your custom Taxonomies and get the most from your custom Post Types by enabling the Extra Tools advanced functions
Author: 3dolab
Author URI: http://www.3dolab.net/
Author Email: info@3dolab.net
Version: 0.1
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
		
	add_action('admin_menu', 'tet_options_menu');
	add_action( 'admin_menu', 'tet_add_extra_to_post_metaboxes' );
	add_action( 'admin_menu', 'tet_extra_post_type_metabox' );
	//add_action( 'admin_head', 'wpec_daily_deal_metaboxes' );
	add_action( 'generate_rewrite_rules', 'tet_taxonomy_archive_permalink' );
	add_action( 'parse_tax_query', 'tet_taxonomy_archive_tax_query' );
	add_action( 'save_post', 'tet_save_post_extra_data', 10, 2 );
	add_filter( 'wp_insert_post_data','tet_post_extra_pre_update', 100, 2 );
	add_filter('the_title', 'tet_modified_post_title');
	add_filter('wp_title', 'tet_taxonomy_archive_wp_title', 1, 3);
	add_action( 'loop_start', 'tet_taxonomy_archive_show_children_terms' );

	$current_theme = wp_get_theme();
	if($current_theme == 'Twenty Fourteen' || $current_theme == 'Twenty Thirteen' || $current_theme == 'Twenty Twelve')
		add_filter( 'tet_term_display_output', 'tet_taxonomy_term_loop_template', 10, 3 );
	//register_widget('tet_Widget_Recent_Extras');
	//add_action( 'after_setup_theme', 'tet_widgets_setup' );
}
add_action( 'plugins_loaded', 'register_taxonomy_extra_tools', 99 );

function tet_modified_post_title ($title, $sep=', ') {
	global $post;
	//$queried_object = get_queried_object();
	  
	if ( !is_admin() && in_the_loop() ) {
		
		$taxonomies = get_object_taxonomies( $post );
		$options = get_option('tet_options');
		//$active_taxonomies = $options['title_taxonomies'];
		$active_post_types = $options['title_post_types'];
		if(!empty($options['title_term_sep']))
			$sep = $options['title_term_sep'];
		if(!$active_post_types)
			$active_post_types = array();
		$title_rewrite_pattern = $options['title_rewrite_pattern'];
		if(!$title_rewrite_pattern)
			$title_rewrite_pattern = '%title%';
		if (in_array($post->post_type, $active_post_types)){		
			if(!empty($taxonomies)) {
				$title = str_replace( '%title%', $title, $title_rewrite_pattern);
				foreach($taxonomies as $taxonomy) {
					//if(in_array($active_taxonomy,$taxonomies)) {
						$terms = get_the_terms( $post->ID, $taxonomy );
						$this_postofextra = get_post_meta( $post->ID, '_post_of_extra', true );
						$postofextra = false;
						if($this_postofextra)
							$postofextra = get_post($this_postofextra);
						if($postofextra)
							$title = $postofextra->post_title.$sep.$title;
						$term_array = $title_array = array();
						$termname = $termtitle = '';
						$i = 0;
						if ( $terms && ! is_wp_error( $terms ) ) {	
							$i++;							
							if(count($terms)==1){	
								$term = reset($terms);
								$termname = $term->name;
								$term_array[] = $term->name;
							} elseif(count($terms)<4) {	
								$count = 0;
								$termname = $parent = false;

								foreach($terms as $term){
									if(!in_array($term->name, $term_array)){
										$count++;
										if($term->parent){
											$parent = $term->parent;
											$children = $term;
										}
										if($count!=1)
											$termname .= $sep;

										$termname .= $term->name;
										$term_array[] = $term->name;
										
										if($parent){	
											$parenterm = get_term($parent, $taxonomy);
											if ( $parenterm && ! is_wp_error( $parenterm ) ){
												$termname .= $sep.$parenterm->name;
												$term_array[] = $parenterm->name;
											}
											// check term->parent: what to do? show only 1st level and hide children or show everything?
										}
									}
								}
							}
							
							//$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
							//$title = wp_kses_decode_entities($title);
							if(strpos($title, ' &#8211; '))
								$title_array = explode(' &#8211; ',$title);
							elseif(strpos($title, ' - '))
								$title_array = explode(' - ',$title);
							elseif(strpos($title, ', '))
								$title_array = explode(', ',$title);
							elseif(strpos($title, ','))
								$title_array = explode(',',$title);
							$termtitle = '';
							if(!empty($term_array) && !empty($title_array)){
								$compare = array_intersect($term_array, $title_array);
								if(empty($compare))
									$termtitle .= $termname;
							} elseif($termname != $title)
								$termtitle .= $termname;
						}
						$title = str_replace( '%'.$taxonomy.'%', $termtitle, $title );
					//}
				}
			}
		}
	}								
	//return esc_html(htmlentities($title));
	return $title;
}

function tet_taxonomy_archive_wp_title( $title, $sep, $seplocation ) {
	if ( is_tax() ) {
		global $wpdb, $wp_locale, $wp_query;

		$t_sep = '%WP_TITLE_SEP%'; // Temporary separator, for accurate flipping, if necessary
		
		$options = get_option('tet_options');
		$active_taxonomies = $options['archive_taxonomies'];
		if(!$active_taxonomies)
			$active_taxonomies = array();
		if( isset($wp_query->query_vars['taxonomy']) && !empty($wp_query->query_vars['taxonomy']) && in_array($wp_query->query_vars['taxonomy'], $active_taxonomies) )
			$taxonomy = $wp_query->query_vars['taxonomy'];
		elseif(isset($wp_query->query['category_name']) && in_array('category', $active_taxonomies))
			$taxonomy = 'category';
		elseif(isset($wp_query->query['tag']) && in_array('tag', $active_taxonomies))
			$taxonomy = 'post_tag';
		if( $taxonomy && isset($wp_query->query_vars[$taxonomy]) && empty($wp_query->query_vars[$taxonomy]) && in_array($taxonomy, $active_taxonomies) ) {
			$tax = get_taxonomy( $taxonomy );
			$title = $tax->labels->name;
		} elseif (isset($wp_query->query_vars[$taxonomy]) && !empty($wp_query->query_vars[$taxonomy]) && in_array($taxonomy, $active_taxonomies) ) {
			$term = get_queried_object();
			if ( $term ) {
				$tax = get_taxonomy( $term->taxonomy );
				$title = single_term_title( $tax->labels->singular_name . $t_sep, false );
			}
		}
		$prefix = '';
		if ( !empty($title) )
			$prefix = " $sep ";

		// Determines position of the separator and direction of the breadcrumb
		if ( 'right' == $seplocation ) { // sep on right, so reverse the order
			$title_array = explode( $t_sep, $title );
			$title_array = array_reverse( $title_array );
			$title = implode( " $sep ", $title_array ) . $prefix;
		} else {
			$title_array = explode( $t_sep, $title );
			$title = $prefix . implode( " $sep ", $title_array );
		}
	}	
	return $title;
}

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

function tet_taxonomy_archive_tax_query( $query ) {

	if ( ! empty( $query->query_vars ) )
		$q = $query->query_vars;
	else
		return;

	$tax_query = array();
	$args = array(
	  'public'   => true,
	  //'_builtin' => false	  
	); 
	$output = 'objects'; // or names
	$options = get_option('tet_options');
	$active_taxonomies = $options['archive_taxonomies'];
	$taxonomies = get_taxonomies( $args, $output );
	//$taxonomies = get_taxonomies();
	if(!$active_taxonomies)
		$active_taxonomies = array();
	elseif(!is_array($active_taxonomies))
		$active_taxonomies = array($active_taxonomies);
	foreach ( $taxonomies as $taxonomy => $t ) {

		if ( !in_array($taxonomy, $active_taxonomies) )
			continue;
			
		$tax_query_defaults = array(
			'taxonomy' => $taxonomy,
			'field' => 'slug',
		);
		$tax_query_by_id = array(
			'taxonomy' => $taxonomy,
			'field' => 'id',
		);
		if ( $t->query_var && isset( $q[$t->query_var] ) && empty( $q[$t->query_var] ) && isset( $query->query[$t->query_var] ) && empty( $query->query[$t->query_var] ) ) {

			// need to set an option (another function filtering parse_query?): 
			// show posts vs hide them and only generate the page, no need to exclude child
			$terms = get_terms($taxonomy, array('parent'=>0));

			if ( !empty($terms) && !is_wp_error($terms) ) {
				$includes = array();
				foreach ( $terms as $term ) {
					$includes[] = $term->term_id;						
				}
				if(!empty($includes))
					$tax_query[] = array_merge( $tax_query_by_id, array(
						'include_children' => 1,
						'terms' => $includes
					) );
			} 

		} elseif ( $t->query_var && isset( $q[$t->query_var] ) && !empty( $q[$t->query_var] ) ) {
			if ( isset( $t->rewrite['hierarchical'] ) && $t->rewrite['hierarchical'] ) {
				$q[$t->query_var] = wp_basename( $q[$t->query_var] );
			}
			$term = $q[$t->query_var];
			if(isset($options['archive_tax_child_posts']) && $options['archive_tax_child_posts'])
				$include_children = 1;
			else
				$include_children = 0;
			if ( strpos($term, '+') !== false ) {
				$terms = preg_split( '/[+]+/', $term );
				$termcount = 0;
				foreach ( $terms as $term ) {
					$tax_query[] = array_merge( $tax_query_defaults, array(
						'include_children' => $include_children,
						'terms' => array( $term )
					) );
					if(is_numeric($term))
						$field = 'id';
					elseif(is_string($term) && !ctype_lower($term))
						$field = 'name';
					elseif(is_string($term) && ctype_lower($term))
						$field = 'slug';
					$termobj = get_term_by($field, $term, $taxonomy);
					if($termobj->count > $termcount)
						$termcount = $termobj->count;
					$childs = get_terms($taxonomy, array('parent'=>$termobj->term_id));
					//print_r($childs);
					if(!empty($childs)&&!is_wp_error($childs)){
						foreach($childs as $childobj){
							if($childobj->count > $termcount)
								$termcount = $termobj->count;
						}
					}
				}
			} else {
				// excluding children would not affect posts with BOTH terms
				$tax_query[] = array_merge( $tax_query_defaults, array(
					'include_children' => $include_children,
					'terms' => preg_split( '/[,]+/', $term )
				) );
				if(is_numeric($term))
					$field = 'id';
				elseif(is_string($term) && !ctype_lower($term))
					$field = 'name';
				elseif(is_string($term) && ctype_lower($term))
					$field = 'slug';
				$termobj = get_term_by($field, $term, $taxonomy);
				$termcount = $termobj->count;
				$childs = get_terms($taxonomy, array('parent'=>$termobj->term_id));
										
				if(!empty($childs)&&!is_wp_error($childs)){
					foreach($childs as $childobj){
						$termcount += $childobj->count;
					}
				}
			}
			//add_filter('post_limits_request', 'tet_filter_query_limit', 10, 2);
			if($termcount < 1)
				add_action('template_redirect', 'tet_remove_404_on_tax_archive');
			
		}
		if(!isset($taxqueryobj))
			$taxqueryobj = $t;
	}
	if(!empty($tax_query)) {
		//$query->queried_object = $taxqueryobj;
		//$query->queried_object_id = 0;
		$query->tax_query = new WP_Tax_Query( $tax_query );
	}
	//echo '<pre>'; print_r($tax_query); echo '</pre>';
}

function tet_taxonomy_archive_query_hide( $query ) {
	if ( ! empty( $query->tax_query ) && ($query->is_tax() || $query->is_category() || $query->is_tag()) && $query->is_archive() && $query->is_main_query() )
		$tq = $query->tax_query;
	else
		return;

	global $tet_bingo;
	$tet_bingo = false;
	//echo '<pre>'.print_r($tq).'</pre>';
	foreach($tq->queries as $index => $single_tax_query) {
		if( isset($single_tax_query['taxonomy']) && !empty($single_tax_query['taxonomy']) && (!isset($single_tax_query['terms']) || !empty($single_tax_query['terms'])) ){
			$taxonomy = $single_tax_query['taxonomy'];
			$taxobj = get_taxonomy($taxonomy);
			$options = get_option('tet_options');
			$active_taxonomies = $options['archive_taxonomies'];
			if(!$active_taxonomies)
				$active_taxonomies = array();
			if ( ( (isset($query->query[$taxonomy]) && empty($query->query[$taxonomy]) ) || ( isset($query->query[$taxobj->query_var]) && empty($query->query[$taxobj->query_var]) ) ) && isset($query->query_vars[$taxobj->query_var]) && empty($query->query_vars[$taxobj->query_var]) && in_array($taxonomy, $active_taxonomies) )
				$tet_bingo = true;
		}
	}

	if($tet_bingo)	{
	//echo '<pre>bingo</pre>';
		//$query->set( 'taxonomy', $taxonomy);
		//$query->set( $taxobj->query_var, null);
		$query->set( 'posts_per_page', 0);
		$query->set( 'posts_per_archive_page', 0);
		$query->set( 'showposts', 0);		
		global $taxpaged;
		if( !isset($taxpaged) )
			$taxpaged = $query->get( 'paged' );
		$query->set( 'paged', 0);
		//$query->set( 'post_count', 1);
		//$query->post_count = 1;
		/*
		$query->set( 'is_tax', true);
		$query->is_tax = true;
		$query->set( 'is_404', false);
		$query->is_404 = false;
		$query->set( 'is_archive', true);
		*/
		add_filter('post_limits_request', 'tet_filter_query_limit', 10, 2);
		add_action('template_redirect', 'tet_remove_404_on_tax_archive');
	}
	/*
	echo '<pre>1';
	print_r($query);
	echo '</pre>';
	*/
}
function tet_filter_query_limit( $limits, $query ) {
	global $tet_bingo;
	if($tet_bingo && $query->is_main_query()){
		$limits = 'LIMIT 0';
	}
	return $limits;
}
function tet_close_comments_dummy_post ($open, $post_id ){
	if($post_id==0){
		$open = false;
	}
	return $open;
}
function tet_remove_404_on_tax_archive( ) {
	global $wp_query;
	$wp_query->post_count = 1;
	$postarr = array( 'ID' => 0, 'post_author' => 1, 'post_date' => '2000-01-01 00:00', 'post_content' => '', 'post_type' => '' );
	add_filter( 'comments_open', 'tet_close_comments_dummy_post', 10, 2);
	$postobj = (object) $postarr;
	//echo '<pre>post:'; print_r($postobj); echo '</pre>';
	$wp_query->posts = array( $postobj);
}
function tet_taxonomy_archive_show_children_terms( $query ) {
	if(!($query)){
		global $wp_query;
		$query = $wp_query;
	}
	/*
	echo '<pre>2';
	print_r($query);
	echo '</pre>';
	*/
	$output = '';
	if ( ! empty( $query->query_vars ) )
		$q = $query->query_vars;
	else
		return;
	if( (!in_the_loop() && !is_main_query()) || ( !is_tax() && !is_tag() && !is_category() ) )
		return;
		
	//if ( is_tax() && isset($query->tax_query) && !empty($query->tax_query) )
	//queried object may be used instead
	//$term = $query->queried_object;
	//$term_id = $query->queried_object_id;

	$options = get_option('tet_options');
	$active_taxonomies = $options['archive_taxonomies'];
	if(!$active_taxonomies)
		$active_taxonomies = array();

	if ( ( isset($q['taxonomy']) && !empty($q['taxonomy']) && isset($q['term']) && !empty($q['term']) ) ) {
		$taxonomy = $q['taxonomy'];
		$term = $q['term'];
		$taxobj = get_taxonomy($taxonomy);
		if( isset($q[$taxobj->query_var]) && $q[$taxobj->query_var] == $term && in_array($taxonomy, $active_taxonomies) ){
			if(is_numeric($term))
				$field = 'id';
			elseif(is_string($term) && !ctype_lower($term))
				$field = 'name';
			elseif(is_string($term) && ctype_lower($term))
				$field = 'slug';
			$termobj = get_term_by( $field, $term, $taxonomy );
			//if($termobj->parent != 0){
				if($field == 'id')
					$parent = $term;
				else
					$parent = $termobj->term_id;
			//}
		}		
	} elseif(isset($q['taxonomy']) && !empty($q['taxonomy']) && (!isset($q['term']) || empty($q['term']))){
		$taxonomy = $q['taxonomy'];
		$taxobj = get_taxonomy($taxonomy);
		if( isset($q[$taxobj->query_var]) && empty($q[$taxobj->query_var]) && in_array($taxonomy, $active_taxonomies) )
			$parent = 0;
	}elseif(isset($q['category_name']) && !empty($q['category_name']) && isset($query->query['category_name']) && !empty($query->query['category_name']) && in_array('category', $active_taxonomies)){
		$taxonomy = 'category';
		$termobj = get_term_by( 'slug', $q['category_name'], $taxonomy );
		$parent = $termobj->term_id;				
	}elseif(isset($query->query['category_name']) && empty($query->query['category_name']) && in_array('category', $active_taxonomies)){
		$taxonomy = 'category';
		$parent = 0;
		$query->set( 'category_name', null);	
	}elseif(isset($q['tag']) && !empty($q['tag']) && isset($query->query['tag']) && !empty($query->query['tag']) && in_array('post_tag', $active_taxonomies)){
		$taxonomy = 'post_tag';
		$termobj = get_term_by( 'slug', $q['tag'], $taxonomy );
		$parent = $termobj->term_id;
	}elseif(isset($query->query['tag']) && empty($query->query['tag']) && in_array('post_tag', $active_taxonomies)){
		$taxonomy = 'post_tag';
		$parent = 0;
		$query->set( 'tag', null);
	}
	if(isset($parent)){
		if(isset($options['archive_tax_child']) && $options['archive_tax_child'])
			$args = array( 'child_of'=> $parent );
		else
			$args = array('parent' => $parent);
		$childs = get_terms( $taxonomy, $args );
	}else $childs = array();
	
	//$query->set( 'is_404', false);
	//$query->set( 'is_archive', true);
	if(!empty($childs) && !is_wp_error($childs)){

		//echo '<div class="row archive-posts vw-isotope post-box-list">';
		global $taxpaged;
		if( isset($taxpaged) && !empty($taxpaged) )
			$paged = $taxpaged;
		elseif( isset($q['paged']) && is_numeric($q['paged']) && !empty($q['paged']) )
			$paged = $q['paged'];
		else
			$paged = 1;
		if(isset($q['posts_per_page'])&& is_numeric($q['posts_per_page']))
			$ppp = $q['posts_per_page'];
		else
			$ppp = 0;
		$i = 0;

		$query->max_num_pages = ceil(count($childs)/$ppp);
		foreach($childs as $child){
			$i++;
			if( $ppp == 0 || ( $i <= $ppp * $paged && $i > $ppp * ( $paged - 1 ) ) ){
				$termlink = '<a href="'.get_term_link($child->slug, $taxonomy).'" title="'.$child->name .'">'.$child->name.'</a>';
				$output .= apply_filters('tet_term_display_output', $termlink, $child, $taxonomy );
				if ($output == $termlink){
					$before = '';
					$after = '';
					$output = $before.$termlink.$after;
				}
			}
		}
		$output .= '<br style="clear:both" />';
		//$query->set( 'posts_per_page', 0 );
		echo $output;
	}
}

function tet_taxonomy_term_loop_template ($output, $term, $taxonomy) {
	$output = '';
	$output .= '<article id="term-'.$term->term_id.'" class="'. implode(' ', get_post_class()).'">';
	$imageurl = false;
	if (function_exists('z_taxonomy_image_url'))
		$imageurl = z_taxonomy_image_url($term->term_id, 'post-thumbnail');
	if($imageurl){
		$current_theme = wp_get_theme();
		if($current_theme == 'Twenty Thirteen')
			$output .= '<div class="entry-thumbnail">';
		$output .= '<a class="post-thumbnail" href="'.get_term_link($term->slug, $taxonomy).'">';
		$output .= '<img src="'.$imageurl.'" />';
		$output .= '</a>';
		if($current_theme == 'Twenty Thirteen')
			$output .= '</div>';
	}
	$output .= '<header class="entry-header"><h1 class="entry-title">';
	$output .= '<a href="'.get_term_link($term->slug, $taxonomy).'" title="'.sprintf( esc_attr__('Permalink to %s', 'taxonomy-extra-tools'), $term->name ).'" rel="bookmark">'.$term->name.'</a>';
	$output .= '</h1></header>';
	$output .= '<div class="entry-summary">';
	$output .= $term->description;
	$output .= '</div><!-- .entry-summary -->';
	$output .= '</article>';
	return $output;
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
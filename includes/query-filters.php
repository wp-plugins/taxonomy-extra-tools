<?php 

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
function tet_remove_404_on_tax_archive( ) {
	global $wp_query;
	$wp_query->post_count = 1;
	$postarr = array( 'ID' => 0, 'post_author' => 1, 'post_date' => '2000-01-01 00:00', 'post_content' => '', 'post_type' => '' );
	add_filter( 'comments_open', 'tet_close_comments_dummy_post', 10, 2);
	$postobj = (object) $postarr;
	//echo '<pre>post:'; print_r($postobj); echo '</pre>';
	$wp_query->posts = array( $postobj );
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
			if($termobj){
				if($field == 'id')
					$parent = $term;
				else
					$parent = $termobj->term_id;
			}
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
		$before = apply_filters('tet_term_display_before', '', $taxonomy );
		$after = apply_filters('tet_term_display_after', '', $taxonomy );
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

		if($ppp != 0)
			$numpages = ceil(count($childs)/$ppp);
		else
			$numpages = 0;
		$query->max_num_pages = $numpages;
		foreach($childs as $child){
			$i++;
			if( $ppp == 0 || ( $i <= $ppp * $paged && $i > $ppp * ( $paged - 1 ) ) ){
				$termlink = '<a href="'.get_term_link($child->slug, $taxonomy).'" title="'.$child->name .'">'.$child->name.'</a>';
				$output .= apply_filters('tet_term_display_output', $termlink, $child, $taxonomy, $i, count($childs), $numpages, $parent, true );
				if ($output == $termlink){
					$output = $termlink;
				}
			}
		}
		//$output .= '<br style="clear:both" />';
		//$query->set( 'posts_per_page', 0 );
		echo $before.$output.$after;
	}
}
?>
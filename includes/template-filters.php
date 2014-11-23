<?php 

function tet_close_comments_dummy_post ($open, $post_id ){
	if($post_id==0){
		$open = false;
	}
	return $open;
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

function is_tax_root_archive() {
	global $tet_bingo;
	$tq = array();
	if ( !empty( $wp_query->tax_query ) && $wp_query->is_tax() && $wp_query->is_archive() && $wp_query->is_main_query() )
		$tq = $wp_query->tax_query;
	
	//if(!isset($tet_bingo))
		$tet_bingo = false;
	if(!empty($tq))
		foreach($tq->queries as $single_tax_query) {
			if( isset($single_tax_query['taxonomy']) && !empty($single_tax_query['taxonomy']) && (!isset($single_tax_query['terms']) || !empty($single_tax_query['terms'])) ){
				$taxonomy = $single_tax_query['taxonomy'];
				if ( isset($wp_query->query[$taxonomy]) && empty($wp_query->query[$taxonomy]) && isset($wp_query->query_vars[$taxonomy]) && empty($wp_query->query_vars[$taxonomy]) )
					$tet_bingo = true;
			}
		}
	return $tet_bingo;
}

function tet_render_taxonomies( $classes='', $tax=null, $sep=' ', $sep2=' ', $link=true, $echo=true, $limit=3, $meta=false ) {
	if(!empty($tax) && !is_array($tax))
		$tax = array($tax);
	$post_id = get_the_ID();
	$post_title = get_the_title(); 
	$object = get_post_type($post_id);
	$taxonomies = get_object_taxonomies( $object );
	$sep = apply_filters('tet_render_taxonomies_sep', $sep);
	$sep2 = apply_filters('tet_render_taxonomies_sep2', $sep2);
	$output = '';
	$i = 0;
	if(is_numeric($limit))
		$limit = intval($limit);
	else
		$limit = 3;
	if(!empty($taxonomies)){
		if(!empty($tax))
			$taxonomies = array_intersect_key($tax, $taxonomies);
		$terms = $prevterms = array();
		foreach($taxonomies as $taxonomy){
			$i++;
			if ( ( !empty($tax) && !in_array($taxonomy, $tax) ) || 'post_tag' == $taxonomy || 'category' == $taxonomy || 'post_format' == $taxonomy )
				continue;
			//$taxterms = wp_get_post_terms( $post_id, $taxonomy );
			//if(!empty($taxterms))
				//$terms = array_merge($terms, $taxterms);
			$terms = wp_get_post_terms( $post_id, $taxonomy );
			$j = 0;
			if(!empty( $terms ) && !is_wp_error($terms) ){
				$parenterms = array();
				foreach( $terms as $term ) {
					if( $term->parent == 0 && strtolower($term->name) != strtolower($post_title) )
						$parenterms[] = $term;
				}
				if(!empty($parenterms) && count($parenterms) < $limit){
					if($i > 1 && $i < (count($taxonomies)+1) && !empty( $prevterms ))
						$output .= $sep2;
					foreach( $parenterms as $parenterm ) {
						/*
						if($taxonomy == 'edition') {
							$termname = $parenterm->slug;
							if(function_exists('pll_default_language')) {
								$deflang = pll_default_language();
								$termlang = pll_get_term_language($parenterm->term_id);
								if($termlang!=$deflang)
									$termname = str_replace('-'.$termlang,'',$parenterm->slug);
							}
						} elseif($taxonomy == 'artist') {
							$t_id = $parenterm->term_id;
							$term_meta = get_option( "taxonomy_$t_id" );
							$termname = $parenterm->name;
							if($meta && isset($term_meta['nationality']) && !empty($term_meta['nationality']))
								$termname .= '<br /><small>'.$term_meta['nationality'].'</small>';
						}else
						*/
							$termname = $parenterm->name;
						$j++;
						$htmlink = '';
						if($link)
							$htmlink .= '<a class="'.$classes.'" href="'.get_term_link($parenterm->slug, $taxonomy).'" title="' . esc_attr( sprintf( __( "View all posts in %s", 'taxonomy-extra-tools' ), $termname ) ) . '" rel="taxonomy">';
						$htmlink .= $termname;
						if($link)
							$htmlink .= '</a>';
						$htmlink = apply_filters('tet_render_taxonomies_link', $htmlink, $parenterm, $taxonomy, $classes);
						$output .= $htmlink;
						if($j < count($parenterms))
							$output .= $sep;
						$terms[] = $parenterm;
					}
					$prevterms = $terms;
				} elseif(!empty($parenterms) && count($parenterms) >= $limit){						
					$taxobj = get_taxonomy($term->taxonomy);
					if($taxobj)
						$taxtitle = $taxobj->labels->name;
					/*
					if($taxonomy == 'edition')
						$output .= __('Various Editions', 'taxonomy-extra-tools');
						//$output .= sprintf(_x( 'Various %s', 'various feminine', 'taxonomy-extra-tools' ), $taxtitle);
					elseif($taxonomy == 'artist')
						$output .= __('Various Artists', 'taxonomy-extra-tools');
						//$output .= sprintf(_x( 'Various %s', 'various masculine', 'taxonomy-extra-tools' ), $taxtitle);
					elseif($taxonomy == 'location')
						$output .= __('Various Locations', 'taxonomy-extra-tools');
					*/
				} else
					$prevterms = array();
			} else
				$prevterms = array();
			//if($i < count($taxonomies) && !empty( $terms ))
				//$output .= $sep2;
		}
		//$terms = array_unique($terms);
	}
	if ($echo)
		echo $output;
	else
		return $output;
}
function tet_append_taxonomies($content) {
	if(!is_singular())
		return $content;
	$tax_render = tet_render_taxonomies( 'taxonomy', null, ' ', ' ', true, false, $limit=3, true );
	$content .= '<div class="taxonomies">'.$tax_render.'</div>';
	return $content;
}
?>
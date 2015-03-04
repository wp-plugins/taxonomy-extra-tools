<?php 
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

function tet_taxonomy_archive_wp_title( $title, $sep = '', $seplocation = '' ) {
	if ( is_tax() ) {
		global $wpdb, $wp_locale, $wp_query;		
		
		$t_sep = '%WP_TITLE_SEP%'; // Temporary separator, for accurate flipping, if necessary
		
		$default_title_array = explode(' '.$sep.' ', $title);
		
		if ( '' === trim( $seplocation ) )
			$seplocation = ( is_rtl() ) ? 'left' : 'right';
		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) && class_exists( 'WPSEO_Replace_Vars' ) && $sep == '' ) {
			$sep = wpseo_replace_vars( '%%sep%%', array() );
			$sep = ' ' . trim( $sep ) . ' ';
			$default_title_array = explode($sep, $title);		
		}
		
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
			$taxname = $tax->labels->name;
			foreach($default_title_array as $i => $title_bit){
				$tax_terms = get_terms($taxonomy, array('fields'=>'names'));
				if(!empty($tax_terms) && !is_wp_error($tax_terms) && in_array($title_bit,$tax_terms))
					unset($default_title_array[$i]);
			}
			if(!in_array($taxname,$default_title_array))
				array_unshift($default_title_array,$taxname);
			if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) && class_exists( 'WPSEO_Frontend' ) ){
				//global $wpseo_front;
				//$seo_title = $wpseo_front->get_title_from_options( 'title-tax-'.$taxonomy );
				//$pattern = $wpseo_front->options['title-tax-'.$taxonomy];
				$pattern = WPSEO_Frontend::get_instance()->get_title_from_options('title-tax-'.$taxonomy);
				if(strpos($pattern,'%%taxonomy_name%%')!==false){
					$pattern = str_replace('%%taxonomy_name%%',$taxname,$pattern);
					//$pattern = str_replace('%%ct_'.$taxname.'%%',$taxname,$pattern);
					$pattern = str_replace('%%term_title%% %%sep%%','',$pattern);
					$pattern = str_replace('%%term_title%% ','',$pattern);
					$pattern = str_replace('%%term_title%%','',$pattern);
				}else
					$pattern = str_replace('%%term_title%%',$taxname,$pattern);
				$pattern = str_replace('%%term_description%%','',$pattern);
				$term = get_queried_object();
				$seo_title = wpseo_replace_vars( $pattern, $term );
				/*
				//$seo_title = wpseo_replace_vars( $wpseo_front->options['title-tax-'.$taxonomy], array() );
				$seo_term_name = wpseo_replace_vars( '%%term_title%%', array() );
				$seo_term_desc = wpseo_replace_vars( '%%term_description%%', array() );
				//$seo_title = str_replace($seo_term_name,$taxname,$seo_title);
				//$seo_title = str_replace($seo_term_desc,'',$seo_title);
				*/
				//return $seo_title;
				return esc_html( strip_tags( stripslashes( $seo_title ) ) );
			}
		} elseif (isset($wp_query->query_vars[$taxonomy]) && !empty($wp_query->query_vars[$taxonomy]) && in_array($taxonomy, $active_taxonomies) ) {
			$term = get_queried_object();
			if ( $term ) {
				$tax = get_taxonomy( $term->taxonomy );
				//$title = single_term_title( $tax->labels->singular_name . $t_sep, false );
				$termname = $term->name;
				if(!in_array($termname,$default_title_array))
					array_unshift($default_title_array,$termname);
				if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) && class_exists( 'WPSEO_Frontend' ) ){
					//global $wpseo_front;
					//$pattern = $wpseo_front->options['title-tax-'.$taxonomy];
					$pattern = WPSEO_Frontend::get_instance()->get_title_from_options('title-tax-'.$taxonomy);
					$pattern = str_replace('%%taxonomy_name%%',$tax->labels->name,$pattern);
					$pattern = str_replace('%%term_title%%',$termname,$pattern);
					$seo_title = wpseo_replace_vars( $pattern, $term );
					return esc_html( strip_tags( stripslashes( $seo_title ) ) );
				}
			}
		}
		$title = implode(' '.$sep.' ', $default_title_array);
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
?>
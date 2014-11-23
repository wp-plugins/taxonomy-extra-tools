<?php
class tet_Widget_Current_Terms extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_current_terms', 'description' => __( "Your current post taxonomy terms",'taxonomy-extra-tools') );
		parent::__construct('current-terms', __('Current Terms','taxonomy-extra-tools'), $widget_ops);
		$this->alt_option_name = 'widget_current_terms';

		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		global $post;
		
		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_current_terms', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);

		$current_taxonomy = $this->_get_current_taxonomy($instance);
		
		if ( !empty($instance['title']) ) {
			$title = $instance['title'];
		} else {
			if ( 'post_tag' == $current_taxonomy ) {
				$title = __('Tags');
			} else {
				$tax = get_taxonomy($current_taxonomy);
				$title = $tax->labels->name;
			}
		}
		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
		$show_count = isset( $instance['show_count'] ) ? $instance['show_count'] : false;
		$show_description = isset( $instance['show_description'] ) ? $instance['show_description'] : false;
		$show_thumbnail = isset( $instance['show_thumbnail'] ) ? $instance['show_thumbnail'] : false;
		$current = isset( $instance['current'] ) ? $instance['current'] : false;

		if($current)
			$terms = get_the_terms( $post->ID, $current_taxonomy );
		else
			$terms = get_terms( $current_taxonomy );
		$i = 0;		

		if (!empty( $terms )) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul>
		<?php foreach ( $terms as $term ) : ?>
			<li>
			<?php if ( $show_thumbnail && function_exists('z_taxonomy_image_url') ) : ?>
				<?php $imageurl = z_taxonomy_image_url($term->term_id, 'thumbnail');
				if (!empty($imageurl)) : ?>
				<div class="term-thumbnail"><?php echo get_term_link($term->slug, $current_taxonomy); ?><img src="<?php echo $imageurl; ?>" /></a></div>
				<?php endif; ?>
			<?php endif; ?>
				<a href="<?php echo get_term_link($term->slug, $current_taxonomy); ?>"><?php echo $term->name; ?></a>
			<?php if ( $show_count ) : ?>
				<span class="term-count"><?php echo $term->count; ?></span>
			<?php endif; ?>
			<?php if ( $show_description && ! empty( $term->description ) ) : ?>
				<div class="term-description"><?php echo $term->description; ?></div>
			<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_recent_posts', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_count'] = isset( $new_instance['show_count'] ) ? (bool) $new_instance['show_count'] : false;
		$instance['show_description'] = isset( $new_instance['show_description'] ) ? (bool) $new_instance['show_description'] : false;
		$instance['show_thumbnail'] = isset( $new_instance['show_thumbnail'] ) ? (bool) $new_instance['show_thumbnail'] : false;
		//$instance['taxonomy'] = isset( $new_instance['taxonomy'] ) ? $new_instance['taxonomy'] : 'post_tag';
		$instance['taxonomy'] = stripslashes($new_instance['taxonomy']);
		$instance['current'] = isset( $new_instance['current'] ) ? (bool) $new_instance['current'] : false;
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_current_terms']) )
			delete_option('widget_current_terms');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_current_terms', 'widget');
	}

	function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$current = isset($instance['current'] )  ? (bool) $instance['current'] : false;
		$show_count = isset( $instance['show_count'] ) ? (bool) $instance['show_count'] : false;
		$show_description = isset( $instance['show_description'] ) ? (bool) $instance['show_description'] : false;
		$show_thumbnail = isset( $instance['show_thumbnail'] ) ? (bool) $instance['show_thumbnail'] : false;
		$taxonomy = isset( $instance['taxonomy'] ) ? $instance['taxonomy'] : 'category';
		$current_taxonomy = $this->_get_current_taxonomy($instance);
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:','taxonomy-extra-tools' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:','taxonomy-extra-tools' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Display post count?','taxonomy-extra-tools' ); ?></label></p>
		
		<p><input class="checkbox" type="checkbox" <?php checked( $show_thumbnail ); ?> id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"><?php _e( 'Display term thumbnail?','taxonomy-extra-tools' ); ?></label></p>
		
		<p><input class="checkbox" type="checkbox" <?php checked( $show_description ); ?> id="<?php echo $this->get_field_id( 'show_description' ); ?>" name="<?php echo $this->get_field_name( 'show_description' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_description' ); ?>"><?php _e( 'Display term description?','taxonomy-extra-tools' ); ?></label></p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $current ); ?> id="<?php echo $this->get_field_id('current'); ?>" name="<?php echo $this->get_field_name('current'); ?>" />
			<label for="<?php echo $this->get_field_id('current'); ?>"><?php _e('Only Terms related to the current post','taxonomy-extra-tools'); ?></label>
		</p>

		<p><label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:') ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
		<?php foreach ( get_taxonomies() as $taxonomy ) :
					$tax = get_taxonomy($taxonomy);
					if ( !$tax->show_tagcloud || empty($tax->labels->name) )
						continue;
		?>
			<option value="<?php echo esc_attr($taxonomy) ?>" <?php selected($taxonomy, $current_taxonomy) ?>><?php echo $tax->labels->name; ?></option>
		<?php endforeach; ?>
		</select></p><?php
	}

	function _get_current_taxonomy($instance) {
		if ( !empty($instance['taxonomy']) && taxonomy_exists($instance['taxonomy']) )
			return $instance['taxonomy'];

		return 'post_tag';
	}
}
?>
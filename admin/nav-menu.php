<?php
defined( 'ABSPATH' ) OR exit;
/*
	Shamelessly based on the WordPress Post Type Archive Links plugin
	https://github.com/stephenharris/WordPress-Post-Type-Archive-Links
	v.1.2 by Stephen Harris (contact@stephenharris.info)	
 */

// Load at the default priority of 10

// add_action( 'plugins_loaded', array( 'Taxonomy_Archive_Links', 'init' ) );

class Taxonomy_Archive_Links {
	/**
	 * Instance of the class
	 * @static
	 * @access protected
	 * @var object
	 */
	protected static $instance;

	/**
	 * Nonce Value
	 * @var string
	 */
	public $nonce = 'tet_nonce';

	/**
	 * ID of the custom metabox
	 * @var string
	 */
	public $metabox_id = 'tet-metabox';

	/**
	 * ID of the custom post type list items
	 * @var string
	 */
	public $metabox_list_id = 'taxonomy-archive-checklist';

	/**
	 * Instantiates the class
	 * @return object $instance
	 */
	public static function init()
	{
		is_null( self :: $instance ) AND self :: $instance = new self;
		return self :: $instance;
	}


	/**
	 * Constructor.
	 * @return \Post_Type_Archive_Links
	 */
	public function __construct() {
		
			add_action( 'admin_init', array( $this, 'add_meta_box' ) );
			
			add_action( 'admin_head-nav-menus.php', array( $this, 'setup_admin_hooks' ) );

			add_filter( 'wp_setup_nav_menu_item',  array( $this, 'setup_taxonomy_item' ) );

			add_filter( 'wp_nav_menu_objects', array( $this, 'maybe_make_current' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );
			
			add_action( "wp_ajax_" . $this->nonce, array( $this, 'ajax_add_taxonomy' ) );

	}


	/**
	 * Adds all callbacks to the appropriate filters & hooks in the admin UI.
	 * Only loads on the admin UI nav menu management page.
	 * @return void
	 */
	public function setup_admin_hooks() {
		
		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );
	}


	/**
	 * Adds the meta box to the menu page
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			$this->metabox_id,
			__( 'Taxonomy Archives', 'taxonomy-extra-tools' ),
			array( $this, 'metabox' ),
			'nav-menus',
			'side',
			'low'
		);
	}


	/**
	 * Scripts for AJAX call
	 * Only loads on nav-menus.php
	 * @param  string $hook Page Name
	 * @return void
	 */
	public function metabox_script( $hook ) {
		if ( 'nav-menus.php' !== $hook )
			return;

		wp_register_script(
			'tet-ajax-script',
			plugins_url( 'metabox.js', __FILE__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ).'metabox.js' ),
			true
		);
		wp_enqueue_script( 'tet-ajax-script' );

		// Add nonce variable
		wp_localize_script(
			'tet-ajax-script',
			'tet_obj',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( $this->nonce ),
				'metabox_id' => $this->metabox_id,
				'metabox_list_id' => $this->metabox_list_id,
				'action'     => $this->nonce
			)
		);
	}


	/**
	 * MetaBox Content Callback
	 * @return string $html
	 */
	public function metabox() {
		global $nav_menu_selected_id;

		// Get post types
		$taxonomies = get_taxonomies(
			array(
				'public'	=> true,
				//'_builtin' => false
			),
			'object'
		);
		$options = get_option('tet_options');
		if(!empty($options['archive_taxonomies'])) {
			$html = '<ul id="'. $this->metabox_list_id .'">';
			$active = $options['archive_taxonomies'];
			foreach ( $taxonomies as $name => $tax ) {
				if( in_array($name,$active) )
					$html .= sprintf(
						'<li><label><input type="checkbox" value ="%s" />&nbsp;%s</label></li>',
						esc_attr( $tax->name ),
						esc_attr( $tax->labels->name )
					);
			}
			$html .= '</ul>';

			// 'Add to Menu' button
			$html .= '<p class="button-controls"><span class="add-to-menu">';
			$html .= '<input type="submit"'. disabled( $nav_menu_selected_id, 0, false ) .' class="button-secondary
				  submit-add-to-menu right" value="'. esc_attr( 'Add to Menu' ) .'" 
				  name="add-taxonomy-menu-item" id="submit-taxonomy-archives" />';
			$html .= '<span class="spinner"></span>';
			$html .= '</span></p>';
			
			print $html;
		}
	}

	/**
	 * AJAX Callback to create the menu item and add it to menu
	 * @return string $HTML built with walk_nav_menu_tree()
	 */
	public function ajax_add_taxonomy()
	{

		$this->is_allowed();

		// Create menu items and store IDs in array
		$item_ids = array();
		foreach ( array_values( $_POST['taxonomies'] ) as $tax )
		{
			$taxonomy = get_taxonomy( $tax );

			if( ! $taxonomy )
				continue;

			$menu_item_data= array(
				'menu-item-title'	  	=> esc_attr( $taxonomy->labels->name ),
				'menu-item-type'   		=> 'taxonomy-archive',
				'menu-item-object'		=> $taxonomy->name,
				'menu-item-object_id'	=> $taxonomy->query_var,
				'menu-item-url'    		=> get_site_url( null, $taxonomy->rewrite['slug'] )
			);

			// Collect the items' IDs.
			$item_ids[] = wp_update_nav_menu_item( 0, 0, $menu_item_data );
		}

		// If there was an error die here
		is_wp_error( $item_ids ) AND die( '-1' );

		// Set up menu items
		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj = wp_setup_nav_menu_item( $menu_obj );
				// don't show "(pending)" in ajax-added items
				$menu_obj->label = $menu_obj->title;

				$menu_items[] = $menu_obj;
			}
		}
		//echo '<pre>'; echo get_site_url( null, $taxonomy->rewrite['slug'] ); print_r($menu_items); echo '</pre>';
		// Needed to get the Walker up and running
		require_once ABSPATH.'wp-admin/includes/nav-menu.php';

		// This gets the HTML to returns it to the menu
		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after'       => '',
				'before'      => '',
				'link_after'  => '',
				'link_before' => '',
				'walker'      => new Walker_Nav_Menu_Edit
			);

			echo walk_nav_menu_tree(
				$menu_items,
				0,
				(object) $args
			);
		}

		// Finally don't forget to exit
		exit;

	}


	/**
	 * Is the AJAX request allowed and should be processed?
	 * @return void
	 */
	public function is_allowed() {
		// Capability Check
		! current_user_can( 'edit_theme_options' ) AND die( '-1' );

		// Nonce check
		check_ajax_referer( $this->nonce, 'nonce' );

		// Is a post type chosen?
		empty( $_POST['taxonomies'] ) AND exit;
	}


	/**
	 * Assign menu item the appropriate url
	 * @param  object $menu_item
	 * @return object $menu_item
	 */
	public function setup_taxonomy_item( $menu_item ) {
		if ( $menu_item->type !== 'taxonomy-archive' )
			return $menu_item;

		$taxonomy = $menu_item->object;
		$taxobj = get_taxonomy($taxonomy);
		$menu_item->url = get_site_url( null, $taxobj->rewrite['slug'] );

		return $menu_item;
	}


	/**
	 * Make post type archive link 'current'
	 * @uses   Post_Type_Archive_Links :: get_item_ancestors()
	 * @param  array $items
	 * @return array $items
	 */
	public function maybe_make_current( $items ) {
		foreach ( $items as $item ) {
			if ( 'taxonomy-archive' !== $item->type )
				continue;

			$taxonomy = $item->object;
			if (!is_tax( $taxonomy ))
				continue;

			// Make item current
			$item->current = true;
			$item->classes[] = 'current-menu-item';

			// Loop through ancestors and give them 'parent' or 'ancestor' class
			$active_anc_item_ids = $this->get_item_ancestors( $item );
			foreach ( $items as $key => $parent_item ) {
				$classes = (array) $parent_item->classes;

				// If menu item is the parent
				if ( $parent_item->db_id == $item->menu_item_parent ) {
					$classes[] = 'current-menu-parent';
					$items[ $key ]->current_item_parent = true;
				}

				// If menu item is an ancestor
				if ( in_array( intval( $parent_item->db_id ), $active_anc_item_ids ) ) {
					$classes[] = 'current-menu-ancestor';
					$items[ $key ]->current_item_ancestor = true;
				}

				$items[ $key ]->classes = array_unique( $classes );
			}
		}

		return $items;
	}


	/**
	 * Get menu item's ancestors
	 * @param  object $item
	 * @return array  $active_anc_item_ids
	 */
	public function get_item_ancestors( $item ) {
		$anc_id = absint( $item->db_id );

		$active_anc_item_ids = array();
		while (
			$anc_id = get_post_meta( $anc_id, '_menu_item_menu_item_parent', true )
			AND ! in_array( $anc_id, $active_anc_item_ids )
		)
			$active_anc_item_ids[] = $anc_id;

		return $active_anc_item_ids;
	}
}

<?php
function tet_options_menu() {
	add_options_page(__('Taxonomy Extra Tools settings', 'taxonomy-extra-tools'), __('Taxonomy Extra Tools', 'taxonomy-extra-tools'), 'manage_options', 'tet-options', 'tet_options');
	add_action('admin_init', 'tet_register_settings');
}

function tet_register_settings() {
	register_setting('tet_options', 'tet_options', 'tet_options_validate');
	add_settings_section('tet_settings', __('Advanced settings for custom Taxonomies and Post Types', 'taxonomy-extra-tools'), 'tet_settings_text', 'tet-options');

	add_settings_field('tet_set_taxonomy_archive_rewrite', __('Enable Taxonomy Archive', 'taxonomy-extra-tools'), 'tet_set_taxonomy_archive_rewrite', 'tet-options', 'tet_settings');
	add_settings_field('tet_set_extra_post_types', __('Enable Post Extra', 'taxonomy-extra-tools'), 'tet_set_extra_post_types', 'tet-options', 'tet_settings');
	add_settings_field('tet_set_title_post_types', __('Enable Title filter', 'taxonomy-extra-tools'), 'tet_set_title_post_types', 'tet-options', 'tet_settings');
}

function tet_settings_text() {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$types = is_plugin_active('types');
	if($types)
		$typeslink = admin_url('admin.php?page=wpcf-ctt');
	else
		$typeslink = admin_url('plugin-install.php?tab=plugin-information&plugin=types');
	$catimg = is_plugin_active('categories-images');
	if($catimg)
		$catimglink = admin_url('edit-tags.php?taxonomy=category');
	else
		$catimglink = admin_url('plugin-install.php?tab=plugin-information&plugin=categories-images');
	$rewrules = is_plugin_active('rewrite-rules-inspector');
	if($rewrules)
		$rewruleslink = admin_url('tools.php?page=rewrite-rules-inspector');
	else
		$rewruleslink = admin_url('plugin-install.php?tab=plugin-information&plugin=rewrite-rules-inspector');
	echo '<p><i>'.sprintf(__('Create your custom Post Types and Taxonomies using the plugin %s', 'taxonomy-extra-tools' ), '<a href="'.$typeslink.'" title="Types plugin">Types</a>').'</i><br />';
	echo '<i>'.sprintf(__('Add Images to Categories and Custom Taxonomies using the plugin %s', 'taxonomy-extra-tools' ), '<a href="'.$catimglink.'" title="Types plugin">Categories Images</a>').'</i><br />';
	echo '<i>'.sprintf(__('Flush any residual permalink structure rules using the plugin %s', 'taxonomy-extra-tools' ), '<a href="'.$rewruleslink.'" title="Types plugin">Rewrite Rules Inspector</a>').'</i></p>';
}

function tet_set_title_post_types() {
	echo '<p>'.__('Please select the Post Types you want to filter the title', 'taxonomy-extra-tools').'</p>';
	$options = get_option('tet_options');
	//$disabled_types = array('post', 'attachment', 'page', 'revision', 'nav_menu_item');
	$disabled_types = array();
	$title_rewrite_pattern = '%title%';
	$title_term_sep = ', ';

	if(isset($options['title_rewrite_pattern']))
		$title_rewrite_pattern = $options['title_rewrite_pattern'];
	if(isset($options['title_term_sep']))
		$title_term_sep = $options['title_term_sep'];
	foreach (get_post_types() as $type) : if (in_array($type, $disabled_types)) continue; ?>
		<input type="checkbox" name="tet_options[title_post_types][<?php echo $type; ?>]" value="<?php echo $type; ?>" <?php checked(isset($options['title_post_types'][$type])); ?> /> <?php echo $type;?><br />
	<?php endforeach; ?>
	<?php echo '<p>'.__('Please enter the separator between terms', 'taxonomy-extra-tools').' <i>'.__('(default: ", ")','taxonomy-extra-tools').'</i></p>'; ?>
		<input type="text" name="tet_options[title_term_sep]" size="4" value="<?php echo $title_term_sep; ?>" /><br />
	<?php
	echo '<p>'.__('Please enter the structure of your custom title using placeholders', 'taxonomy-extra-tools').'</p>'; ?>
		<input type="text" name="tet_options[title_rewrite_pattern]" value="<?php echo $title_rewrite_pattern; ?>" /><br />
	<?php echo '<p><i>'.__('Available placeholders:', 'taxonomy-extra-tools').'</i> ';
	$disabled_taxonomies = array('nav_menu', 'link_category', 'post_format');
	$count = 0;
	$taxonomies = get_taxonomies();
	echo '%title%';
	foreach ($taxonomies as $tax) : if (in_array($tax, $disabled_taxonomies)) continue;
		$count++;
		if($count>=1 && $count<=(count($taxonomies)-count($disabled_taxonomies)))
			echo ', ';
		echo '%'.$tax.'%';
	endforeach;
	echo '</p>';
}

function tet_set_taxonomy_archive_rewrite() {
	echo '<p>'.__('Please select the Taxonomies you want to have a root archive page with a clean permalink', 'taxonomy-extra-tools').'</p>';
	$options = get_option('tet_options');
	//print_r($options);
	$child = $options['archive_tax_child'];
	$hidechild = $options['archive_tax_hide_child'];
	$subposts = $options['archive_tax_posts'];
	$childposts = $options['archive_tax_child_posts'];
	$taxonomies = $options['archive_taxonomies'];
	$disabled_taxonomies = array('nav_menu', 'link_category', 'post_format');

	foreach (get_taxonomies() as $tax) : if (in_array($tax, $disabled_taxonomies)) continue; ?>
		<input type="checkbox" name="tet_options[archive_taxonomies][<?php echo $tax; ?>]" value="<?php echo $tax; ?>" <?php checked(isset($taxonomies[$tax])); ?> /> <?php echo $tax;?><br />
	<?php endforeach; ?>
	<?php echo '<p>'.__('Then you could style the output and replicate your post loop by adding a filter from your template/functions.php file,', 'taxonomy-extra-tools').'<br />
		'.__('e.g.', 'taxonomy-extra-tools').' <code>add_filter("tet_term_display_output", "your_custom_filter_fuction", 10, 3);</code><br />
		<i>'.__('3 is for the parameters: $output, $term_object, $taxonomy','taxonomy-extra-tools').'</i><br />
		'.__('The default Twenty Fourteen / Thirteen / Twelve themes are already supported.', 'taxonomy-extra-tools').' <br />
		<i>'.__('You may need to further customize your template (archive.php, taxonomy.php, category.php, etc)','taxonomy-extra-tools').'</i></p><br />'; ?>
	<input type="checkbox" name="tet_options[archive_tax_child]" value="1" <?php checked($child); ?> />
	<?php _e('check if you want to display child terms in the root taxonomy archive / parent term pages', 'taxonomy-extra-tools'); ?><br />
	<input type="checkbox" name="tet_options[archive_tax_hide_child]" value="1" <?php checked($hidechild); ?> />
	<?php _e('check if you want to hide child terms from the parent term page', 'taxonomy-extra-tools'); ?><br />
	<i><?php _e('checking both the two settings above will enable a "flat view"', 'taxonomy-extra-tools'); ?></i><br />
	<input type="checkbox" name="tet_options[archive_tax_child_posts]" value="1" <?php checked($childposts); ?> />
	<?php _e('check if you want to display the posts of child terms in the parent term pages', 'taxonomy-extra-tools'); ?><br />
	<input type="checkbox" name="tet_options[archive_tax_posts]" value="1" <?php checked($subposts); ?> />
	<?php _e('check if you want to display posts in the root taxonomy archive / parent term pages', 'taxonomy-extra-tools'); ?>&nbsp;
	<i><?php _e('(but why?? unexpected effects on pagination)', 'taxonomy-extra-tools'); ?></i><br />
	<?php
}

function tet_set_extra_post_types() {
	// position before/after title, apply filter without a new option
	echo '<p>'.__('Please select the Post Types you want to enable as Post related extras', 'taxonomy-extra-tools').'</p>';
	$options = get_option('tet_options');
	$disabled_types = array('post', 'attachment', 'page', 'revision', 'nav_menu_item');
	$args = array('public' => true, '_builtin' => false);
	$types = get_post_types($args);
	if(!empty($types)):
		foreach ($types as $type) : if (in_array($type, $disabled_types)) continue; ?>
		<input type="checkbox" name="tet_options[extra_post_types][<?php echo $type; ?>]" value="<?php echo $type; ?>" <?php checked(isset($options['extra_post_types'][$type])); ?> /> <?php echo $type;?><br />
	<?php endforeach; ?>
	<?php else:
		echo '<p><i>'.__('No Available Post Types', 'taxonomy-extra-tools').'</i></p>';
	endif;
}	

function tet_options_validate($input) {
	return $input;
}

function tet_options() {
	if (!current_user_can('manage_options'))
		wp_die(__( 'You do not have sufficient permissions to access this page.'));
		$options = get_option('tet_options');
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Taxonomy Extra Tools', 'taxonomy-extra-tools'); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields('tet_options'); ?>
			<?php do_settings_sections('tet-options'); ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

function tet_post_extra_metabox( $post ) {
    $this_postofextra = get_post_meta( $post->ID, '_post_of_extra', true );
	
?>
	<p><?php _e( 'Select a Post', 'taxonomy-extra-tools' ); ?>:</p>
		<input type="hidden" name="post_extra_nonce" id="post_extra_nonce" value="<?php echo wp_create_nonce( 'post_extra_metabox' );?>" />
		<select name="postofextra_id" id="postofextra_id">
			<?php
				global $post;
				$postargs = array( 'posts_per_page' => -1, 'post_type' => 'post', 'orderby' => 'title', 'order' => 'ASC', 'post_parent'  => '', 'post_status' => 'publish' );
				$postofextraes = get_posts($postargs);
				// $postofextraes = get_posts('post_type=taxonomy-extra-tools-vendor');
				if (empty($postofextraes))
					echo "<option value=''>".__( '--No Posts Created--', 'taxonomy-extra-tools' )."</option>";
				else
					echo "<option value=''>".__( '--Select One--', 'taxonomy-extra-tools' )."</option>";
				foreach( (array) $postofextraes as $postofextra ) :
			?>
			   <option value="<?php echo $postofextra->ID?>" <?php selected( $this_postofextra, $postofextra->ID ); ?>><?php echo $postofextra->post_title; ?></option>
			<?php endforeach; ?>
		</select>
<?php
}
function tet_display_post_extras( $post ) {
	$options = get_option('tet_options');
	//$active_taxonomies = $options['title_taxonomies'];
	if(isset($options['extra_post_types']))
		$active_post_types = $options['extra_post_types'];
	$extras = array();
	if(!empty($active_post_types)):
		foreach($active_post_types as $post_type)
			$extras = array_merge($extras, get_posts('post_parent='.$post->ID.'&post_type='.$post_type));
		?>
		<?php if(!empty($extras)) : ?>
		<ol>
			<?php foreach( (array) $extras as $extra) : ?>
				  <li><a href='post.php?post=<?php echo $extra->ID; ?>&action=edit'><?php echo $extra->post_title; ?></a></li>
			<?php endforeach; ?> 
		</ol>
		<?php else : ?>
			<p><?php _e( 'No Extras', 'taxonomy-extra-tools' ); ?></p>
		<?php endif; ?> 
	<?php else : ?>
		<p><?php _e( 'No Active Extra Post Types', 'taxonomy-extra-tools' ); ?></p>
	<?php endif; ?>
        <?php
}
function tet_extra_post_type_metabox() {
	$options = get_option('tet_options');
	$active_post_types = $options['extra_post_types'];
	//print_r($active_post_types);
	if(!empty($active_post_types))
		foreach($active_post_types as $post_type)
			add_meta_box( 'related_post', __('Post'), 'tet_post_extra_metabox', $post_type );
}
function tet_add_extra_to_post_metaboxes() {
	add_meta_box( 'post_extra', _x( 'Extras', 'post type general name', 'taxonomy-extra-tools' ), 'tet_display_post_extras', 'post' );
}

function tet_save_post_extra_data( $post_id, $post ) {
    global $allowed_tags;
    
	// verify this came from the our screen and with proper authorization.
	if ( !isset($_POST['post_extra_nonce']) || 	!wp_verify_nonce( $_POST['post_extra_nonce'], 'post_extra_metabox' ) )
            return;
	
	// verify if this is an auto save routine. If it is our form has not been submitted, so we don't want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

	// Check permissions
	if ( !current_user_can( 'edit_post', $post_id ) ) 
            return;

	if (isset($_POST["postofextra_id"]))
		update_post_meta( $post_id, '_post_of_extra', (int)$_POST["postofextra_id"] );

	if ( wp_is_post_revision( $post_id ) )
		return;
	
	$sendback = wp_get_referer();

	if ( strpos( $sendback, 'post-new.php' ) !== false )
		$sendback = admin_url( 'post.php?post='.$post_id.'&action=edit&message=6' );
	else
		$sendback = add_query_arg( 'message', '1', $sendback );
	
	wp_redirect( $sendback );
	exit;

}
function tet_post_extra_pre_update( $data , $postarr ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $postarr['ID'];

    if( isset( $postarr["parent_post"] ) && !empty( $postarr["parent_post"] ) ){
		$options = get_option('tet_options');
		$post_types = $options['extra_post_types'];
		if(get_post_type($postarr["parent_post"]) == 'post')
			$data["post_parent"] = $postarr["parent_post"];
		elseif(get_post_type($postarr["parent_post"]) == 'extra')
			$data["post_parent"] = $postarr["postofextra_id"];
    } elseif( isset( $postarr["postofextra_id"] ) && $postarr["postofextra_id"] > 0 )
        $data["post_parent"] = $postarr["postofextra_id"];
    elseif( isset( $postarr["post_parent"] ) && $postarr["post_parent"] != $data["post_parent"] )
		$data["post_parent"] = $postarr["post_parent"];

    if( $data["post_status"] == "inherit" && ( !isset( $postarr["parent_post"] ) || empty( $postarr["parent_post"] ) ) ) {
            unset( $data["post_status"] );
            $data["post_status"] = "publish";
    }
    //$data["hidden_post_status"] = $data["post_status"];
    return (array)$data;
}
?>
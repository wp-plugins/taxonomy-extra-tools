<?php
/**
 * The template for displaying Archive pages
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each specific one. For example, Twenty Thirteen
 * already has tag.php for Tag archives, category.php for Category archives,
 * and author.php for Author archives.
 *
 * @link http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @since Twenty Thirteen 1.0
 */

get_header();

global $tet_bingo;
if (!$tet_bingo)
		$bingoclass = '';
else
	$bingoclass = 'tax-index';
?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content<?php echo ' '.$bingoclass; ?>" role="main">

		<?php if ( have_posts() ) : ?>
		
			<header class="archive-header">
				<h1 class="archive-title">
				<?php if ($tet_bingo && is_tax()) :
						$term = $wp_query->get_queried_object();
						$taxobj = get_taxonomy($term->taxonomy);
						if($taxobj)
							$taxtitle = $taxobj->labels->name;
						else
							$taxtitle = $term->taxonomy;
						echo $taxtitle;
					else :
						single_term_title();
					endif; ?>
				</h1>
			</header><!-- .archive-header -->
			
			<?php if (!$tet_bingo && is_tax()) :
				$term_desc = term_description();
				if (!empty($term_desc)) {?>
					<div class="entry-content term-description">
						<?php $term = $wp_query->get_queried_object();
						//$term_id = $wp_query->get_queried_object_id();
						//$term_id = get_query_var('term_id');
						if (function_exists('z_taxonomy_image_url')){
							$defaultimgurl = '';
							//$imageurl = z_taxonomy_image_url($term->term_id, 'featured', $defaultimgurl);
							$imageurl = z_taxonomy_image_url($term->term_id, 'post-thumbnail', $defaultimgurl);
						}
						if($imageurl){
							echo '<div class="entry-thumbnail">';
							echo '<img src="'.$imageurl.'" class="taxonomy-thumbnail wp-post-image" />';
							echo '</div>';
						} ?>
						<?php echo $term_desc; ?>
						<hr />
					</div>
				<?php } ?>
			<?php endif; ?>
			
			<?php /* The loop */ ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'content', get_post_format() ); ?>
			<?php endwhile; ?>

			<?php twentythirteen_paging_nav(); ?>

		<?php else : ?>
			<?php get_template_part( 'content', 'none' ); ?>
		<?php endif; ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
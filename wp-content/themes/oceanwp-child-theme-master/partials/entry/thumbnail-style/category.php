<?php
/**
 * Category for the thumbnail style.
 *
 * @package OceanWP WordPress theme
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();
$post_type =  get_post_type( $post_id );
$taxonomy = get_category_type( $post_type );
$taxonomy_type = 'category';
?>

<?php 
if ( isset($taxonomy) )
{
?>
	<div class="blog-entry-<?php echo $taxonomy_type?> clr">
	<?php
	if ( isset( $post_id ) && !empty( $post_id ) )
	{
		echo get_the_term_list( $post_id, $taxonomy, '', ' / ', '' );
	} ?>
	</div>
<?php 
}
?>
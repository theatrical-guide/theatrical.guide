<?php
/**
 * Date for the thumbnail style.
 *
 * @package OceanWP WordPress theme
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_type =  get_post_type();
if ( $post_type == 'post' || $post_type == 'show' || $post_type == 'production' || $post_type == 'event' ) { ?>

	<div class="blog-entry-date clr">
		<?php echo get_the_date(); ?>
	</div>
	
<?php } ?>
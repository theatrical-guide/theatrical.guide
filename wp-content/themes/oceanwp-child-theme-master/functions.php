<?php

/**
 * Child theme functions
 *
 * When using a child theme (see http://codex.wordpress.org/Theme_Development
 * and http://codex.wordpress.org/Child_Themes), you can override certain
 * functions (those wrapped in a function_exists() call) by defining them first
 * in your child theme's functions.php file. The child theme's functions.php
 * file is included before the parent theme's file, so the child theme
 * functions would be used.
 *
 * Text Domain: oceanwp
 * @link http://codex.wordpress.org/Plugin_API
 *
 */

/**
 * Load the parent style.css file
 *
 * @link http://codex.wordpress.org/Child_Themes
 */
function oceanwp_child_enqueue_parent_style() {
	// Dynamically get version number of the parent stylesheet (lets browsers re-cache your stylesheet when you update your theme)
	$theme   = wp_get_theme( 'OceanWP' );
	$version = $theme->get( 'Version' );
	// Load the stylesheet
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'oceanwp-style' ), $version );
	
}
add_action( 'wp_enqueue_scripts', 'oceanwp_child_enqueue_parent_style' );

/**
 * Alter your page header background image
 *
 * Replace is_singular( 'post' ) by the function where you want to alter the layout
 * Place your image in your "img" folder
 */
function my_page_header_bg_img( $bg_img ) {

	if ( is_singular( get_main_post_types() )) {
		$bg_img = get_the_post_thumbnail_url();
	}

	// Retrun
	return $bg_img;

}
add_filter( 'ocean_page_header_background_image', 'my_page_header_bg_img' );

/**
 * Remove 'no-featured-image' class since it messes up the formatting of archive pages for custom post types
 */
function my_blog_entry_classes( $classes ) {
	foreach ($classes as $key => $element) {
		if ($element == 'no-featured-image')
		{
    		unset($classes[$key]);	
		}
	}

	return $classes;

}
add_filter( 'ocean_blog_entry_classes', 'my_blog_entry_classes' );

/**
 * Alter single posts related section to display related items based on tags and NOT categories
 */
function myprefix_alter_related_posts_query_args( $args ) {

	// Remove category arguments
	$args['category__in'] = null;

	// Get post tags
	$tags = wp_get_post_terms( get_the_ID(), 'post_tag' );

	// If post has tags, create array of tag ids and query posts inside these tags
	if ( $tags ) {
		$tag_ids = array();
		foreach( $tags as $tag ) {
			$tag_ids[] = $tag->term_id;
		}
		$args['tag__in'] = $tag_ids;
	}

	// Return arguments
	return $args;

}
add_filter( 'ocean_blog_post_related_query_args', 'myprefix_alter_related_posts_query_args' );



function get_show_post_id( $post_id )
{
	$post_type = get_post_type( $post_id );
	if ($post_type == 'show')
	{
		return $post_id;
	}
	else if ($post_type == 'production')
	{
		$production_post_id = $post_id;
		$production_pod = pods( 'production', $production_post_id );
		
		$show = $production_pod->field( 'show' );
		return $show['ID'];
	}
	else if ($post_type == 'event')
	{
		$event_pod = pods( 'event', $post_id );
		$production = $event_pod->field( 'production' );
		$production_post_id = $production['ID'];
		$production_pod = pods( 'production', $production_post_id );
		
		$show = $production_pod->field( 'show' );
		return $show['ID'];
	}
}

function is_show_related_post( $post_id ) {
	$show_post_id = get_show_post_id( $post_id );
    return isset( $show_post_id );
}

/**
 * Have all show related posts display show thumbnail
 */
function share_post_metadata( $metadata, $object_id, $meta_key, $single )
{
	remove_filter( 'get_post_metadata', 'share_post_metadata', 100 );
	$post_id = $object_id;
	$post_type = get_post_type( $object_id );        
	$show_post_id = get_show_post_id( $post_id );
	add_filter( 'get_post_metadata', 'share_post_metadata', 100, 4 );
	if ( isset( $show_post_id ) && !empty( $show_post_id ) )
	{
		if ( isset( $meta_key ) )
		{
			$thumbnail_meta_key = "_thumbnail_id";
			$is_thumbnail_metadata = ( $meta_key === $thumbnail_meta_key );
			if ( $is_thumbnail_metadata )
			{
				remove_filter( 'get_post_metadata', 'share_post_metadata', 100 );
				$metadata = get_post_meta( $show_post_id, $thumbnail_meta_key, TRUE );
				add_filter( 'get_post_metadata', 'share_post_metadata', 100, 4 );
			}
		}
	}

    return $metadata;

}
add_filter( 'get_post_metadata', 'share_post_metadata', 100, 4 );

/**
 * Have all show related posts display show tags and categories
 */
function share_post_terms( $terms, $post_id, $taxonomy ) {
	remove_filter( 'get_the_terms', 'share_post_terms', 10 );
  	$show_post_id = get_show_post_id( $post_id );
	if ( isset( $show_post_id ) && !empty( $show_post_id ) )
	{
		$terms = get_the_terms( $show_post_id, $taxonomy );
	}
	add_filter( 'get_the_terms', 'share_post_terms', 10, 3 );
	
    return $terms;
}
add_filter( 'get_the_terms', 'share_post_terms', 10, 3 );

function get_main_post_types() {
     return array( 'post', 'show', 'production', 'event', 'location' );
}

function is_main_post_type( $post_type ) {
     return in_array( $post_type, get_main_post_types() );
}

function get_reviewable_post_types() {
     return array( 'show', 'production' );
}

function is_reviewable_post_type( $post_type ) {
     return in_array( $post_type, get_reviewable_post_types() );
}

function get_show_related_post_types() {
     return array( 'show', 'production', 'event' );
}

function is_show_related_post_type( $post_type ) {
     return in_array( $post_type, get_show_related_post_types() );
}

function get_category_type( $post_type ) {
	if ( is_show_related_post_type( $post_type ) )
	{
		return 'show_category';
	}
	else if ( $post_type == 'post' )
	{
    	return 'category';
	}
}

function get_tag_type( $post_type ) {
	if ( is_show_related_post_type( $post_type ) )
	{
		return 'show_tag';
	}
	else if ( $post_type == 'post' )
	{
    	return 'post_tag';
	}
}

function debug_log($var) {
     return var_dump(highlight_string("<?\n". var_export($var, true)));  
}

function format_date_string_long($date_string) {
     return format_date_long(strtotime($date_string));         
}

function format_date_string_short($date_string) {
     return format_date_short(strtotime($date_string));       
}

function format_date_long($date) {
     return date('l, F jS, Y', $date);         
}

function format_date_short($date) {
     return date('Y-m-d', $date);         
}

function format_time_string_long($time_string) {
     return format_time_long(strtotime($time_string));      
}

function format_time_string_short($time_string) {
     return format_time_short(strtotime($time_string));    
}

function format_time_long($time) {
     return date('g:ia', $time);         
}

function format_time_short($time) {
     return date('g:ia', $time);         
}

function format_date_time_long($date_time) {
     return $date_time->format('l, F jS, Y g:ia');       
}

function format_date_time_short($date_time) {
     return $date_time->format('Y-m-d g:ia');        
}

function get_author_display_name($user_id) {
     return get_the_author_meta( 'display_name', $user_id );
}

function get_event_start_date($post_id) {
     return format_date_string_long(em_get_event($post_id, 'post_id')->event_start_date);
}

function get_event_start_time($post_id) {
     return em_get_event($post_id, 'post_id')->output('#_EVENTTIMES');
}

function get_location_address($post_id) {
     return em_get_location($post_id, 'post_id')->output('#_LOCATIONFULLLINE');
}

function get_location_map($post_id) {
     return em_get_location($post_id, 'post_id')->output('#_LOCATIONMAP');
}

function get_theatrical_run($post_id) {
	$pod = pods( 'production', $post_id );
	$year = $pod->field( 'year' );
	$events = $pod->field( 'events' );
	$tbd = "TBD";
	
	if ( !isset($events) || $events == false || count($events) == 0)
	{
		if ( isset($year) && $year > 0)
		{
			return $year;
		}
		else
		{
			return $tbd;
		}
	}
	
	$first_event = current($events);
    $first_event_start_date = em_get_event($first_event['ID'], 'post_id')->event_start_date;
	$first_event_start_date_formatted = format_date_string_long($first_event_start_date);
	
	if (count($events) == 1)
	{
		return $first_event_start_date_formatted." - ".$tbd;
	}
	
	$last_event = end($events);
    $last_event_start_date = em_get_event($last_event['ID'], 'post_id')->event_start_date;
	$last_event_start_date_formatted = format_date_string_long($last_event_start_date);
	
    return $first_event_start_date_formatted." - ".$last_event_start_date_formatted;
}

/**
 * Runs after a review has been submitted in Site Reviews.
 * Paste this in your active theme's functions.php file.
 * @param \GeminiLabs\SiteReviews\Review $review
 * @param \GeminiLabs\SiteReviews\Commands\CreateReview $request
 * @return void
 */
add_action( 'site-reviews/review/created', function( $review, $request ) {
	// do something here.
}, 10, 2 );

function user_already_reviewed( $post_id, $user_id )
{	
	$reviews = glsr_get_reviews( [ 'assigned_to' => $post_id ] );
	
	if ( isset($user_id) )
	{
		foreach ( $reviews as $review )
		{
			if ( $review['user_id'] == $user_id )
			{
				return true;
			}
		}
	}
	
	return false;
}

function email_already_reviewed( $post_id, $email )
{	
	$reviews = glsr_get_reviews( [ 'assigned_to' => $post_id ] );
	
	if ( isset($email) )
	{
		foreach ( $reviews as $review )
		{
			if ( $review['email'] == $email )
			{
				return true;
			}
		}
	}
	
	return false;
}

/**
 * Registers the [is_member][/is_member] shortcode
 * This shortcode displays content only to logged in users
 * Paste this in your active theme's functions.php file
 * @param array $atts
 * @param string $content
 * @return string
 */
add_shortcode( 'is_member', function( $atts, $content = '' ) {
    return ( is_user_logged_in() && !empty( $content ) && !is_feed() )
        ? do_shortcode( $content )
        : '';
}, 10, 2);

/**
 * Registers the [is_visitor][/is_visitor] shortcode
 * This shortcode displays content only to users who are not logged in
 * Paste this in your active theme's functions.php file
 * @param array $atts
 * @param string $content
 * @return string
 */
add_shortcode( 'is_visitor', function( $atts, $content = '' ) {
    return (( !is_user_logged_in() && !empty( $content )) || is_feed() )
        ? do_shortcode( $content )
        : '';
}, 10, 2);


/**
 * Registers the [user_has_reviewed][/user_has_reviewed] shortcode
 * This shortcode displays content only to users who already reviewed this post
 * Paste this in your active theme's functions.php file
 * @param array $atts
 * @param string $content
 * @return string
 */
add_shortcode( 'user_has_reviewed', function( $atts, $content = '' ) {
	global $post;
    $user = wp_get_current_user();
	return (is_user_logged_in() && user_already_reviewed( $post->ID, $user->ID ) )
		? do_shortcode( $content )
        : '';
}, 10, 2);

/**
 * Registers the [user_has_not_reviewed][/user_has_not_reviewed] shortcode
 * This shortcode displays content only to users who already reviewed this post
 * Paste this in your active theme's functions.php file
 * @param array $atts
 * @param string $content
 * @return string
 */
add_shortcode( 'user_has_not_reviewed', function( $atts, $content = '' ) {
	global $post;
    $user = wp_get_current_user();
	return (!is_user_logged_in() || !user_already_reviewed( $post->ID, $user->ID ) )
		? do_shortcode( $content )
        : '';
}, 10, 2);

/**
 * Registers the [your_review] shortcode
 * This shortcode displays the reviews from the current logged in user according to the specified filter
 * Paste this in your active theme's functions.php file
 * @param array $atts
 * @param string $content
 * @return string
 */
add_shortcode( 'your_review', function( $args, $content = '' ) {
	global $post;
	$user = wp_get_current_user();
	
	if ( isset( $user->ID ) )
	{
		$reviews = glsr_get_reviews(['assigned_to' => $post->ID]);
		foreach ($reviews as $key => $element) {
    		if (user_id != $user->ID) {
    	    	unset($reviews[$key]);
    		}
		}
		return $reviews->build();
	}
	return '';
}, 10, 2);

add_action( 'admin_footer-post-new.php', 'admin_footer_hook');
function admin_footer_hook(){
	global $post;
	$auto_title = get_production_auto_title( '{Show}', '{Location}', '{Year}' );
	echo '<script type="text/javascript">
        if(jQuery("#post_type").val() === "production"){	
			jQuery("#select2-pods-form-ui-pods-meta-show-container").keyup(function() {
			    jQuery("#title").val("'.$auto_title.'");
			});
			jQuery("#title").val("'.$auto_title.'");
			jQuery("#title").prop("readonly", true);
			jQuery("#title").css("opacity", "0.5");
    	}
    </script>';
}

function get_production_auto_title( $show_name, $location_name, $production_year ) {
	$title = "";

	if( isset( $show_name ) && !empty( $show_name ) ) {
		$title .= $show_name;
	}

	if( isset( $production_year ) && !empty( $production_year ) ) {
		if( isset( $title ) && !empty( $title ) ) {
			$title .= " ";
		}
		$title .= "(".$production_year.")";
	}

	if( isset( $location_name ) && !empty( $location_name ) ) {
		if( isset( $title ) && !empty( $title ) ) {
			$title .= " @ ";
		}
		$title .= $location_name;
	}

    return $title;
}

function get_event_auto_title($show_name, $location_name, $event_start_date, $event_start_time) {
	$title = "";

	if( isset( $show_name ) && !empty( $show_name ) ) {
		$title .= $show_name;
	}

	if( isset( $event_start_date ) && !empty( $event_start_date ) && isset( $event_start_time ) && !empty( $event_start_time )) {
		if( isset( $title ) && !empty( $title ) ) {
			$title .= " ";
		}
		$title .= "(".format_date_string_short($event_start_date)." ".format_time_string_short($event_start_time).")";
	}

	if( isset( $location_name ) && !empty( $location_name ) ) {
		if( isset( $title ) && !empty( $title ) ) {
			$title .= " @ ";
		}
		$title .= $location_name;
	}

    return $title;
}

add_action( 'pods_api_post_save_pod_item_production', 'adjust_production_pod_after_saving', 10, 3 ); 
function adjust_production_pod_after_saving( $pieces, $is_new_item, $id ) { 
    // Avoid recursion loops on saving. 
	pods_no_conflict_on( 'post' ); 

	$fields = array( 'post_title', 'show', 'location', 'year', 'events' );
	foreach( $fields as $field ) {
		if ( ! isset( $pieces[ 'fields_active' ][ $field ] ) ) {
			array_push ($pieces[ 'fields_active' ], $field );
		}
	}
	$show_name = $location_name = $production_year = '';
	if ( isset( $pieces[ 'fields' ][ 'show' ] ) && isset( $pieces[ 'fields'][ 'show' ][ 'value' ] ) && !empty( $pieces[ 'fields' ][ 'show' ][ 'value' ] ) ) {
		$show_post_id = $pieces[ 'fields' ][ 'show' ][ 'value' ];
		$show_name = get_the_title( $show_post_id );
		$show_thumbnail = get_the_post_thumbnail( $show_post_id );
	}
	if ( isset( $pieces[ 'fields' ][ 'location' ] ) && isset( $pieces[ 'fields'][ 'location' ][ 'value' ] ) && !empty( $pieces[ 'fields' ][ 'location' ][ 'value' ] ) ) {
		$location_post_id = $pieces[ 'fields' ][ 'location' ][ 'value' ];
		$location_name = get_the_title( $location_post_id );
	}
	if ( isset( $pieces[ 'fields' ][ 'year' ] ) && isset( $pieces[ 'fields'][ 'year' ][ 'value' ] ) && !empty( $pieces[ 'fields' ][ 'year' ][ 'value' ] ) ) {
		$production_year = strval( $pieces[ 'fields' ][ 'year' ][ 'value' ] );
	}
	if ( isset( $pieces[ 'fields' ][ 'events' ] ) && isset( $pieces[ 'fields'][ 'events' ][ 'value' ] ) && !empty( $pieces[ 'fields' ][ 'events' ][ 'value' ] ) ) {
		$event_post_ids = $pieces[ 'fields' ][ 'events' ][ 'value' ];
	}
	$auto_title = get_production_auto_title($show_name, $location_name, $production_year);
	
	$post_data = array( 
		'ID'          => $id, 
		'post_title' => $auto_title, 
		'post_name' => '', 
		'post_thumbnail'  => $show_thumbnail,
	); 

	wp_update_post( $post_data ); 

	// Avoid recursion loops on saving. 
	pods_no_conflict_off( 'post' ); 
}

add_action( 'pods_api_post_save_pod_item_event', 'adjust_event_pod_after_saving', 10, 3 ); 
function adjust_event_pod_after_saving( $pieces, $is_new_item, $id ) { 
    // Avoid recursion loops on saving. 
	pods_no_conflict_on( 'post' ); 

	$fields = array( 'post_title', 'production' );
	foreach( $fields as $field ) {
		if ( ! isset( $pieces[ 'fields_active' ][ $field ] ) ) {
			array_push ($pieces[ 'fields_active' ], $field );
		}
	}
	
	$event = em_get_event($id, 'post_id');
	
	$location_id = $event->location_id;
	$location = em_get_location( $location_id );
	$location_post_id = $location->post_id;
	$location_name = get_the_title( $location_post_id );
	
	$event_start_date =  $event->event_start_date;
	
	$event_start_time =  $event->event_start_time;
	
	$auto_title = $event_start_time;

	if ( isset( $pieces[ 'fields' ][ 'production' ] ) && isset( $pieces[ 'fields'][ 'production' ][ 'value' ] ) && !empty( $pieces[ 'fields' ][ 'production' ][ 'value' ] ) ) {
		$production_id = $pieces[ 'fields' ][ 'production' ][ 'value' ];	
		$production_pod = pods( 'production', $production_id );
		$show = $production_pod->field( 'show' );
		$show_id = $show['ID'];
		$show_name = get_the_title( $show_id );
	}

	$auto_title = get_event_auto_title($show_name, $location_name, $event_start_date, $event_start_time);
	
	$post_data = array( 
		'ID'          => $id, 
		'post_title' => $auto_title, 
		'post_name' => '', 
	); 

	wp_update_post( $post_data ); 

	// Avoid recursion loops on saving. 
	pods_no_conflict_off( 'post' ); 
}

////////////////////////////////////////////////////////////////////////
// BuddyPress Profile URL Integration //////////////////////////////////
////////////////////////////////////////////////////////////////////////
function wpdiscuz_bp_profile_url($profile_url, $user) {
    if ($user && class_exists('BuddyPress')) {
        $profile_url = bp_core_get_user_domain($user->ID);
    }
    return $profile_url;
}
add_filter('wpdiscuz_profile_url', 'wpdiscuz_bp_profile_url', 10, 2);

function restrict_users($open, $post_id) {
   if (intval($post_id) && get_post($post_id)) {
       $args = array('post_id' => $post_id, 'count' => true);
       $user = wp_get_current_user();
       if ($user && intval($user->ID)) { // for registered users
           $skip = false;
           $ignoreTheseRoles = array('administrator', 'editor'); // which user roles should be ignored
           if ($user->roles && is_array($user->roles)) {
               foreach ($user->roles as $role) {
                   if (in_array($role, $ignoreTheseRoles)) {
                       $skip = true;
                       break;
                   }
               }
           }
           if (!$skip) {
               $args['user_id'] = $user->ID;
               $open = get_comments($args) ? false : true;
           }
       } else { // for guests
           $commenter = wp_get_current_commenter();
           if ($commenter && is_array($commenter) && isset($commenter['comment_author_email'])) {
               $args['author_email'] = $commenter['comment_author_email'];
               $open = get_comments($args) ? false : true;
           }
       }
   }
   return $open;
}
add_filter('comments_open', 'restrict_users', 10, 2);

/**
 * Registers the [is_visitor][/is_visitor] shortcode
 * This shortcode displays content only to users who are not logged in
 * Paste this in your active theme's functions.php file
 * @param array $atts
 * @param string $content
 * @return string
 */
add_shortcode( 'wpd_rating_summary', function( $atts, $content = '' ) {
    return getRatingMetaHtml($atts);
}, 10, 2);


function getFormFields($post_id) {
	$post = get_post($post_id);
	$forms = get_posts([
	  'post_type' => 'wpdiscuz_form',
	  'post_status' => 'publish',
	  'numberposts' => -1
	]);
	foreach ($forms as $form)
	{
		$formOptions = get_post_meta($form->ID, 'wpdiscuz_form_general_options', true);
		$formPostTypes = $formOptions['wpdiscuz_form_post_types'];
		$formPostIDs = $formOptions['postidsArray'];
		if (in_array($post->post_type, $formPostTypes) || in_array($post->ID, $formPostIDs))
		{
			$formID = $form->ID;
			return get_post_meta($formID, 'wpdiscuz_form_fields', true);
		}
	}
}

function getRatingMetaHtml($atts = array()) {
	$html = '';
	$atts = shortcode_atts(array(
		'metakey' => 'all',
		'show-name-label' => true,
		'name-label' => '{name}: ',
		'show-count-label' => true,
		'count-label' => '{count} User Reviews',
		'show-average-label' => true,
		'average-label' => '{average} out of 5 stars',
		'itemprop' => true,
		'post_id' => null,
		'5-bar-label' => 'Excellent',
		'4-bar-label' => 'Very Good',
		'3-bar-label' => 'Average',
		'2-bar-label' => 'Poor',
		'1-bar-label' => 'Terrible',
	), $atts);
	if ($atts['post_id']) {
		$post = get_post($atts['post_id']);
		wp_enqueue_style('wpdiscuz-font-awesome');
		wp_enqueue_style('wpdiscuz-ratings');
		if (is_rtl()) {
			wp_enqueue_style('wpdiscuz-ratings-rtl');
		}
		$ratingByPostId = true;
	} else {
		global $post;
		$ratingByPostId = false;
	}
	
	$wpdiscuzRatingCountMeta = get_post_meta($post->ID, 'wpdiscuz_rating_count', true);
	$wpdiscuzRatingCount = $wpdiscuzRatingCountMeta && is_array($wpdiscuzRatingCountMeta) ? $wpdiscuzRatingCountMeta : array();
	
	$formFields = getFormFields($post->ID);
	
	if (count($wpdiscuzRatingCount) > 0 && (is_singular() || $ratingByPostId)) {
		$ratingList = array();
		foreach ($wpdiscuzRatingCount as $metaKey => $data) {
			$tempRating = 0;
			$tempRatingCount = 0;
			foreach ($data as $rating => $count) {
				$tempRating += $rating * $count;
				$tempRatingCount += $count;
				$ratingList[$metaKey][$rating] = $count;
			}
			if ($tempRatingCount <= 0) {
				$ratingList[$metaKey]['average'] = 0;
				$ratingList[$metaKey]['count'] = 0;
			} else {
				$ratingList[$metaKey]['average'] = round($tempRating / $tempRatingCount, 2);
				$ratingList[$metaKey]['count'] = $tempRatingCount;
			}
		}
		if ($ratingList) {
			$html .= '<div class="wpdiscuz-post-rating-wrap wpd-custom-field">';
			if (!isset($atts['metakey']) || $atts['metakey'] == '' || $atts['metakey'] == 'all') {
				foreach ($ratingList as $key => $value) {
					$html .= getSingleRatingHtml($post, $formFields, $key, $value, $atts);
				}
			} else {
				$html .= getSingleRatingHtml($post, $formFields, $atts['metakey'], $ratingList[$atts['metakey']], $atts);
			}
			$html .= '</div>';
		}
	}
	return $html;
}

function formatRatingText($format, $ratingName, $ratingCount, $ratingAverage)
{
	$format = str_replace( '{name}', '%1$s', $format );
	$format = str_replace( '{count}', '%2$d', $format );
	$format = str_replace( '{average}', '%3$g', $format );
	
	return sprintf($format, $ratingName, $ratingCount, $ratingAverage);;
}

function getSingleRatingLabelHtml($labelType, $formFields, $metakey, $ratingData, $args)
{
	$ratingName = $formFields[$metakey]['name'];
	$ratingCount = $ratingData['count'];
	$ratingAverage = $ratingData['average'];
		
	if (filter_var($args['show-' . $labelType . '-label'], FILTER_VALIDATE_BOOLEAN)) {
		$label .= formatRatingText($args[$labelType . '-label'], $ratingName, $ratingCount, $ratingAverage);
		return '<div class="wpdiscuz-' . $labelType . '-text"><span>' . $label . '</span></div>';
	}	
	
	return '';
}

function getSingleRatingBarHtml($rating, $ratingData, $args)
{
	$bestRating = 5;
	$worstRating = 1;
	
	$ratingPercentage = ($ratingData[$i] / $ratingData['count']) * 100;

	return '<div class="glsr-bar">
				<span class="glsr-bar-label">' . $args[$rating.'-bar-label']. '</span>
				<span class="glsr-bar-background"><span class="glsr-bar-background-percent" style="width:' . $ratingPercentage. '%"></span></span>
				<span class="glsr-bar-percent">' . $ratingPercentage. '%</span>
			</div>';
}

function getSingleRatingHtml($post, $formFields, $metakey, $ratingData, $args) {
	$bestRating = 5;
	$worstRating = 1;
	
	$html = '';
	
	if (is_array($formFields) && key_exists($metakey, $formFields)) {
		$icon = $formFields[$metakey]['icon'];
		$icon = strpos(trim($icon), ' ') ? $icon : 'fas ' . $icon;
		$html .= '<div class="wpdiscuz-post-rating-wrap-' . $metakey . '">';
		
		$html .= getSingleRatingLabelHtml('name', $formFields, $metakey, $ratingData, $args);
		
		$html .= getSingleRatingLabelHtml('count', $formFields, $metakey, $ratingData, $args);
		
		$ratingAveragePercentage = $ratingData['average'] * 100 / 5;
		
        $html .= '<div class="wpdiscuz-stars-wrapper">
                  	<div class="wpdiscuz-stars-wrapper-inner">
                    	<div class="wpdiscuz-pasiv-stars">';
		for($i = $bestRating; $i > $worstRating; $i--) {
			$html .= '			<i class="' . $icon . ' wcf-pasiv-star"></i>';
		}
		$html .= '		</div>';
		$html .= '		<div class="wpdiscuz-activ-stars" style="width:' . $ratingAveragePercentage . '%;">';
		for($i = $bestRating; $i > $worstRating; $i--) {
			$html .= '			<i class="' . $icon . ' wcf-active-star"></i>';
		}     
		$html .= '		</div>
					</div>
				</div>
				<div style="display:inline-block; position:relative;"></div>';
		
		$html .= getSingleRatingLabelHtml('average', $formFields, $metakey, $ratingData, $args);
		
        $html .= '<div class="glsr-summary-percentage">';
		for($i = $bestRating; $i > $worstRating; $i--) {
			$html .= getSingleRatingBarHtml($i, $ratingData, $args);
		}
        $html .= '</div>';
		
		$html .= '</div>';
		
		if ($args['itemprop'] && $ratingData['count']) {
			$html .= '<div style="display: none;" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating"><meta itemprop="itemReviewed" content="' . esc_attr($post->post_title) . '"><meta itemprop="bestRating" content="'. $bestRating .'"><meta itemprop="worstRating" content="' . $worstRating . '"><meta itemprop="ratingValue" content="' . $ratingData['average'] . '"><meta itemprop="ratingCount" content="' . $ratingData['count'] . '"></div>';
		}
	}
	return $html;
}

function review_comment_text( $comment_text, $comment = null, $args = array() ) {
    return $comment_text;
}
add_filter( 'get_comment_text', 'review_comment_text', 10, 3 );
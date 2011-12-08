<?php  if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Example template for displaying 500 error pages (Server Error).
 */

// Change the page title for a 500 error
if ( function_exists( 'mscr_filter_wp_title' ) ) {
	add_filter( 'wp_title', 'mscr_filter_wp_title', 10, 3 );
}

// Warning message
$mscr_error_title   = __( 'An Error Was Encountered', 'twentyten' );
$mscr_error_message = __( 'There was an error with the page you requested.', 'twentyten' );

// Is this a ban request?
if ( function_exists( 'mscr_is_ban' ) AND mscr_is_ban() ) {
	// Ban message
	$mscr_error_title   = sprintf( __( '%s Unavailable', 'twentyten' ), get_bloginfo( 'name' ) );
	$mscr_error_message = __( 'There was a problem processing your request.', 'twentyten' );
}

get_header(); ?>

	<div id="container">
		<div id="content" role="main">

			<div id="post-0" class="post error500 server-error">
				<h1 class="entry-title"><?php echo $mscr_error_title; ?></h1>
				<div class="entry-content">
					<p><?php echo $mscr_error_message; ?></p>
					<?php get_search_form(); ?>
				</div><!-- .entry-content -->
			</div><!-- #post-0 -->

		</div><!-- #content -->
	</div><!-- #container -->
	<script type="text/javascript">
		// focus on search field after it has loaded
		document.getElementById('s') && document.getElementById('s').focus();
	</script>

<?php get_footer(); ?>
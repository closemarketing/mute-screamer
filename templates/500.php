<?php  if ( ! defined('ABSPATH') ) exit;
/**
 * The template for displaying 500 error pages (Server Error).
 */

// Change the page title for a 500 error
if( is_callable( 'MSCR_Utils::filter_wp_title' ) ) {
	add_filter( 'wp_title', 'MSCR_Utils::filter_wp_title', 10, 3 );
}

get_header(); ?>

	<div id="container">
		<div id="content" role="main">

			<div id="post-0" class="post error500 server-error">
				<h1 class="entry-title"><?php _e( 'An Error Was Encountered', 'twentyten' ); ?></h1>
				<div class="entry-content">
					<p><?php _e( 'Apologies, there was an error with the page you requested. Perhaps searching will help.', 'twentyten' ); ?></p>
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
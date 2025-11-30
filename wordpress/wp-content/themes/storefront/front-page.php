<?php
/**
 * Template for displaying products list on homepage
 * 
 * This template displays WooCommerce products with pagination only
 * No breadcrumbs, no sidebar, no product count, no sorting - just products and pagination
 *
 * @package storefront
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<div id="content" class="site-content" tabindex="-1">
	<div class="col-full">
		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">
				
				<h1 class="flop-title" style="text-align: center; font-size: 48px; margin: 20px 0; color: #000; font-weight: bold; display: block !important; visibility: visible !important;">ФЛОП!</h1>
				
				<div class="woocommerce">

<?php
if ( have_posts() ) {
	woocommerce_product_loop_start();

	while ( have_posts() ) {
		the_post();
		wc_get_template_part( 'content', 'product' );
	}

	woocommerce_product_loop_end();
	
	// Добавляем только пагинацию после списка товаров
	woocommerce_pagination();
}
?>

				</div>
			</main>
		</div>
	</div>
</div>

<?php
get_footer();


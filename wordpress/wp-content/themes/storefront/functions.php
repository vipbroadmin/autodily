<?php
/**
 * Storefront engine room
 *
 * @package storefront
 */

/**
 * Assign the Storefront version to a var
 */
$theme              = wp_get_theme( 'storefront' );
$storefront_version = $theme['Version'];

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 980; /* pixels */
}

$storefront = (object) array(
	'version'    => $storefront_version,

	/**
	 * Initialize all the things.
	 */
	'main'       => require 'inc/class-storefront.php',
	'customizer' => require 'inc/customizer/class-storefront-customizer.php',
);

require 'inc/storefront-functions.php';
require 'inc/storefront-template-hooks.php';
require 'inc/storefront-template-functions.php';
require 'inc/wordpress-shims.php';

if ( class_exists( 'Jetpack' ) ) {
	$storefront->jetpack = require 'inc/jetpack/class-storefront-jetpack.php';
}

if ( storefront_is_woocommerce_activated() ) {
	$storefront->woocommerce            = require 'inc/woocommerce/class-storefront-woocommerce.php';
	$storefront->woocommerce_customizer = require 'inc/woocommerce/class-storefront-woocommerce-customizer.php';

	require 'inc/woocommerce/class-storefront-woocommerce-adjacent-products.php';

	require 'inc/woocommerce/storefront-woocommerce-template-hooks.php';
	require 'inc/woocommerce/storefront-woocommerce-template-functions.php';
	require 'inc/woocommerce/storefront-woocommerce-functions.php';
	
	/**
	 * Modify main query on homepage to show products
	 * Optimized: используем пагинацию вместо загрузки всех товаров
	 */
		add_action( 'pre_get_posts', 'storefront_homepage_products_query', 50 );
		function storefront_homepage_products_query( $query ) {
			if ( ! is_admin() && $query->is_main_query() && is_front_page() ) {
				$query->set( 'post_type', 'product' );
				// Оптимизация: используем разумное количество товаров на странице
				// Вместо -1 (все товары) используем 200 товаров на странице
				// Это предотвращает нехватку памяти при большом количестве товаров
				$query->set( 'posts_per_page', 20 );
				$query->set( 'post_status', 'publish' );
				$query->set( 'no_found_rows', false ); // Включаем пагинацию для подсчета страниц
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'DESC' );
				
				// Устанавливаем флаги для WooCommerce
				$query->is_post_type_archive = true;
				$query->is_archive = true;
				$query->is_page = false;
				$query->is_singular = false;
				$query->is_home = false;
				$query->set( 'wc_query', 'product_query' );
			}
		}
	
	/**
	 * Remove elements from homepage except products list and pagination
	 * Оставляем только: товары и пагинацию
	 */
	add_action( 'template_redirect', 'storefront_remove_homepage_elements', 1 );
	function storefront_remove_homepage_elements() {
		if ( is_front_page() ) {
			// Убираем хлебные крошки
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
			remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
			
			// Убираем заголовок "Shop"
			remove_all_actions( 'woocommerce_shop_loop_header' );
			add_filter( 'woocommerce_show_page_title', '__return_false' );
			
			// Убираем счетчик товаров
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
			
			// Убираем сортировку
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
			
			// ПАГИНАЦИЮ ОСТАВЛЯЕМ - не удаляем действия
			// remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
			// remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
			
			// Убираем сайдбар
			remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
			
			// Убираем обертки Storefront
			remove_action( 'woocommerce_before_main_content', 'storefront_before_content', 10 );
			remove_action( 'woocommerce_after_main_content', 'storefront_after_content', 10 );
		}
	}
	
	/**
	 * Убираем хлебные крошки из storefront_before_content на главной странице
	 */
	add_action( 'template_redirect', 'storefront_remove_breadcrumbs_from_homepage', 1 );
	function storefront_remove_breadcrumbs_from_homepage() {
		if ( is_front_page() ) {
			remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
		}
	}
	
	/**
	 * Убираем хлебные крошки через фильтр
	 */
	add_filter( 'woocommerce_breadcrumb', 'storefront_remove_breadcrumbs_output', 10, 2 );
	function storefront_remove_breadcrumbs_output( $crumbs, $breadcrumb ) {
		if ( is_front_page() ) {
			return array();
		}
		return $crumbs;
	}
	
	/**
	 * Отключаем обертки хлебных крошек на главной странице
	 */
	add_filter( 'woocommerce_breadcrumb_defaults', 'storefront_disable_breadcrumb_output', 999 );
	function storefront_disable_breadcrumb_output( $args ) {
		if ( is_front_page() ) {
			$args['wrap_before'] = '';
			$args['wrap_after'] = '';
			$args['before'] = '';
			$args['after'] = '';
			$args['delimiter'] = '';
		}
		return $args;
	}
	
	/**
	 * Скрываем элементы через CSS на главной странице
	 */
	add_action( 'wp_head', 'storefront_hide_homepage_elements_css' );
	function storefront_hide_homepage_elements_css() {
		if ( is_front_page() ) {
			?>
			<style type="text/css">
				.woocommerce-breadcrumb,
				.storefront-breadcrumb,
				nav.woocommerce-breadcrumb,
				.woocommerce-products-header,
				.woocommerce-products-header__title,
				h1.page-title:not(.flop-title),
				header.woocommerce-products-header {
					display: none !important;
				}
				/* Убеждаемся, что наше слово "ФЛОП!" отображается */
				h1.flop-title,
				.site-main > h1 {
					display: block !important;
					visibility: visible !important;
				}
			</style>
			<?php
		}
	}
}

if ( is_admin() ) {
	$storefront->admin = require 'inc/admin/class-storefront-admin.php';

	require 'inc/admin/class-storefront-plugin-install.php';
}

/**
 * NUX
 * Only load if wp version is 4.7.3 or above because of this issue;
 * https://core.trac.wordpress.org/ticket/39610?cversion=1&cnum_hist=2
 */
if ( version_compare( get_bloginfo( 'version' ), '4.7.3', '>=' ) && ( is_admin() || is_customize_preview() ) ) {
	require 'inc/nux/class-storefront-nux-admin.php';
	require 'inc/nux/class-storefront-nux-guided-tour.php';
	require 'inc/nux/class-storefront-nux-starter-content.php';
}

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 * https://github.com/woocommerce/theme-customisations
 */

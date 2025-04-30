<?php
/**
* Plugin Name: WPS Wholesale Products
* Description: Get Wholesale Products into account when filtering with WPS
* Author: gtsiokos
* Author URI: https://www.netpad.gr
* Plugin URI: https://www.netpad.gr
* Version: 1.0.0
*/

if( !defined( 'ABSPATH' ) ) {
	exit;
}

class Wps_Wholesale_Products {

	/**
	 * Wholesale products array
	 *
	 * @var array
	 */
	private static $wholesale_product_ids = array();

	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'wwpp_pre_get_post__in', array( __CLASS__, 'wwpp_pre_get_post__in'), 10, 2 );
		add_action( 'woocommerce_product_search_service_post_ids_for_request', array( __CLASS__, 'woocommerce_product_search_service_post_ids_for_request' ), 10, 2 );
	}

	/**
	 * Wholesale products as defined by WooCommerce Wholesale Prices extension
	 *
	 * @param array $wwpp_products
	 * @param array $query_args
	 * @return array
	 */
	public static function wwpp_pre_get_post__in( $wwpp_products, $query_args ) {
		self::$wholesale_product_ids = $wwpp_products;
		return $wwpp_products;
	}

	/**
	 * Pass the Wholesale products to WPS
	 *
	 * @param array $product_ids
	 * @param string $context
	 */
	public static function woocommerce_product_search_service_post_ids_for_request( &$product_ids, $context ) {

		if ( count( self::$wholesale_product_ids ) > 0 ) {

			// product ids for the current context
			$context_products = array_intersect( $product_ids, self::$wholesale_product_ids );

			// Wholesale products that have the postmeta wholesale_customer_have_wholesale_price
			$wholesale_context_products = array();
			foreach( $context_products as $context_product_id ) {
				if ( get_post_meta( $context_product_id, 'wholesale_customer_have_wholesale_price', true ) ) {
					$wholesale_context_products[] = $context_product_id;
				}
			}

			// For each of these products get their variation ids
			foreach( $wholesale_context_products as $product_id ) {
				$variation_ids = get_post_meta( $product_id, 'wholesale_customer_variations_with_wholesale_price', false );
				if ( count( $variation_ids ) > 0 ) {
					$wholesale_context_products = array_merge( $variation_ids, $wholesale_context_products );
				}
			}
			$product_ids = $wholesale_context_products;
		}

	}
} Wps_Wholesale_Products::init();
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
			$additional_ids = array();
			foreach ( self::$wholesale_product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					if ( $product->is_type( 'variable' ) ) {
						$variation_ids = get_post_meta( $product_id, 'wholesale_customer_variations_with_wholesale_price', false );
						
						if ( count( $variation_ids ) > 0 ) {
							$additional_ids = array_merge( $additional_ids, $variation_ids );
						}
					}
				}
			}
			$wholesale_products_variations = array_merge( self::$wholesale_product_ids, $additional_ids );
			$unfiltered_product_ids = array_intersect( $product_ids, $wholesale_products_variations );
			$filtered_product_ids = array();
			foreach ( $unfiltered_product_ids as $unfiltered_product_id ) {
				$filtered_product = wc_get_product( $unfiltered_product_id );
				if ( $filtered_product ) {
					if ( $filtered_product->is_type( 'variable' ) ) {
						$available_variations = $filtered_product->get_available_variations();
						$variation_ids = wp_list_pluck( $available_variations, 'variation_id' );
						foreach ( $variation_ids as $variation_id ) {
							if ( in_array( $variation_id, $unfiltered_product_ids ) ) {
								$filtered_product_ids[] = $variation_id;
								$filtered_product_ids[] = $unfiltered_product_id;
							}
						}
					} else {
						if ( $filtered_product->is_type( 'simple' ) ) {
							$filtered_product_ids[] = $unfiltered_product_id;
						}
					}
				}
			}
			$filtered_product_ids = array_unique( $filtered_product_ids );
			$product_ids = $filtered_product_ids;
		}
	}
} Wps_Wholesale_Products::init();
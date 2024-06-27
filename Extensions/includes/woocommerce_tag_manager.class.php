<?php
namespace EarthAsylumConsulting\Extensions;

/**
 * Extension: google_tag_manager - WooCommerce event tracking
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0613.1
 */

class woocommerce_tag_manager
{
	/**
	 * @var object parent extension object
	 */
	private $gtm;

	/**
	 * constructor method
	 *
	 * @param 	object	$gtm parent extension object
	 * @param 	array	$options ['ecommerce','cart-actions']
	 */
	public function __construct(object $gtm, array $options)
	{
		$this->gtm = $gtm;

		if (in_array('ecommerce',$options))
		{
			$this->gtm->add_filter('_add_ecommerce_event',		array($this, 'woo_ga_events'), 10, 2);
		}
		if (in_array('cart-actions',$options))
		{
			\add_filter('woocommerce_add_to_cart',				array($this, 'add_to_cart'), 10, 6);
			\add_filter('woocommerce_remove_cart_item',			array($this, 'remove_from_cart'), 10, 2);
			\add_action('woocommerce_cart_item_set_quantity', 	array($this, 'update_cart_item'), 10, 3);
			\add_action('woocommerce_applied_coupon',			array($this, 'select_promotion'));
		}
	}


	/**
	 * Called from _add_ecommerce_event filter
	 *
	 * @param bool $bool set/return true when events are added
	 * @param object $gtm calling GTM extension
	 */
	public function woo_ga_events($bool,$gtm): bool
	{
		global $wp;
		$decimals = $this->gtm->decimals;
		$currency = $this->gtm->currency;

		// purchase event
		if ( function_exists('is_order_received_page') && is_order_received_page() )
		{
			$id = isset( $wp->query_vars['order-received'] )
				? sanitize_text_field($wp->query_vars['order-received'])
				: false;
			if ( $id && ($order = wc_get_order($id)) )
			{
				$value = round($order->get_subtotal() - $order->get_discount_total(),$decimals);
				$items = [];
				foreach ($order->get_items() as $item)
				{
					$items[] = $this->get_item($item->get_id(),$item->get_quantity());
				}
				$coupons = (array)$order->get_coupon_codes();
				$discount = $order->get_total_discount();
				$this->gtm->add_ecommerce_event('purchase',[
					'transaction_id'=> $id,
					'currency'		=> $currency,
					'value'			=> $value,
					'coupon'		=> implode('/',$coupons),
					'discount'		=> round($discount,$decimals),
					'shipping'		=> round($order->get_shipping_total(),$decimals),
					'tax'			=> round($order->get_total_tax(),$decimals),
					'items'			=> $items
				]);
			}
			return true;
		}
		// begin_checkout event
		elseif ( function_exists('is_checkout') && is_checkout() )
		{
			$wcCart = WC()->cart;
			$value = round($wcCart->get_subtotal() - $wcCart->get_cart_discount_total(),$decimals);
			$items = [];
			foreach ($wcCart->get_cart_contents() as $item)
			{
				$product = $item['variation_id'] ?: $item['product_id'];
				$items[] = $this->get_item($product,$item['quantity']);
			}
			$coupons = (array)$wcCart->get_applied_coupons();
			$discount = $wcCart->get_cart_discount_total();
			$this->gtm->add_ecommerce_event('begin_checkout',[
					'currency'		=> $currency,
					'value'			=> $value,
					'coupon'		=> implode('/',$coupons),
					'discount'		=> round($discount,$decimals),
					'items'			=> $items
			]);
			return true;
		}
		// view_cart event
		elseif ( function_exists('is_cart') && is_cart() )
		{
			$wcCart = WC()->cart;
			$value = round($wcCart->get_subtotal() - $wcCart->get_cart_discount_total(),$decimals);
			$items = [];
			foreach ($wcCart->get_cart_contents() as $item)
			{
				$product = $item['variation_id'] ?: $item['product_id'];
				$items[] = $this->get_item($product,$item['quantity']);
			}
			$this->gtm->add_ecommerce_event('view_cart',[
				'currency'=>$currency,'value'=>$value,'items'=>$items
			]);
			return true;
		}
		// view_item event
		elseif ( function_exists('is_product') && is_product() )
		{
			if ($id = get_the_ID())
			{
				$item 	= $this->get_item($id);
				$value 	= round($item['price'],$decimals);
				$this->gtm->add_ecommerce_event('view_item',[
					'currency'=>$currency,'value'=>$value,'items'=>[$item]
				]);
			}
			return true;
		}
		// view_item_list (shop) event
		elseif ( function_exists('is_shop') && is_shop() )
		{
			$content = html_entity_decode( get_the_archive_title() );
			$this->gtm->add_ecommerce_event('view_item_list',['item_list_id'=>'shop','item_list_name'=>$content]);
			return true;
		}
		// view_item_list (category) event
		elseif ( function_exists('is_product_category') && is_product_category() )
		{
			$content = html_entity_decode(single_tag_title( '', false ));
			$this->gtm->add_ecommerce_event('view_item_list',['item_list_id'=>'category','item_list_name'=>$content]);
			return true;
		}
		// view_item_list (tag) event
		elseif ( function_exists('is_product_tag') && is_product_tag() )
		{
			$content = html_entity_decode(single_tag_title( '', false ));
			$this->gtm->add_ecommerce_event('view_item_list',['item_list_id'=>'tag','item_list_name'=>$content]);
			return true;
		}

		return $bool;
	}


	/**
	 * When adding to cart (woocommerce_add_to_cart)
	 *
	 * @param string $cart_item_key
	 * @param int $product_id
	 * @param int $quantity
	 * @param int $variation_id
	 * @param object $variation
	 * @param array $cart_item_data
	 *
	 */
	public function add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
	{
		$id = $variation_id ?: $product_id;
		if ( $product = wc_get_product($id) )
		{
			$item 		= $product->get_slug();
			$value 		= round($product->get_price(),$this->gtm->decimals);
			$this->gtm->add_ecommerce_event('add_to_cart',array_merge([
				'currency'	=> $this->gtm->currency,
				'value'		=> $value,
				'items' 	=> [ $this->get_item($id,$quantity) ]
			],$this->gtm->page_attributes()),true);
		}
	}


	/**
	 * Before removing from cart (woocommerce_remove_cart_item)
	 *
	 * @param string $cart_item_key
	 * @param object $wcCart
	 *
	 */
	public function remove_from_cart($cart_item_key, $wcCart)
	{
		if ($product = $wcCart->cart_contents[ $cart_item_key ][ 'data' ])
		{
			$item 		= $product->get_slug();
			$quantity 	= $wcCart->cart_contents[ $cart_item_key ][ 'quantity' ];
			$value 		= round($product->get_price(),$this->gtm->decimals);
			$this->gtm->add_ecommerce_event('remove_from_cart',array_merge([
				'currency'	=> $this->gtm->currency,
				'value'		=> $value,
				'items' 	=> [ $this->get_item($product,$quantity) ]
			],$this->gtm->page_attributes()),true);
		}
	}


	/**
	 * Updating cart quantity (woocommerce_remove_cart_item)
	 *
	 * @param string $cart_item_key
	 * @param object $wcCart
	 *
	 */
	public function update_cart_item($cart_item_key, $quantity, $wcCart)
	{
		if ($product = $wcCart->cart_contents[ $cart_item_key ][ 'data' ])
		{
			$item 		= $product->get_slug();
			$value 		= round($product->get_price(),$this->gtm->decimals);
			$this->gtm->add_ecommerce_event('update_cart_item',array_merge([
				'currency'	=> $this->gtm->currency,
				'value'		=> $value,
				'items' 	=> [ $this->get_item($product,$quantity) ]
			],$this->gtm->page_attributes()),true);
		}
	}


	/**
	 * when a coupon is applied (woocommerce_applied_coupon)
	 *
	 * @param string $coupon_code the coupon code
	 *
	 */
	public function select_promotion($coupon_code)
	{
		$coupon_id	= wc_get_coupon_id_by_code( $coupon_code );
		if ($coupon = new \WC_Coupon($coupon_id))
		{
			if ($coupon->get_discount_type() == 'percent') {
				$value = round($coupon->get_amount()/100,2);
			} else {
				$value = round($coupon->get_amount(),$this->gtm->decimals);
			}
			if ($wcCart = WC()->cart) {
				if ($amount = $wcCart->get_coupon_discount_amount( $coupon_code )) {
					$value = round($amount,$this->gtm->decimals);
				} else {
					$discount = new \WC_Discounts( $wcCart );
					if (!is_wp_error($discount->apply_coupon($coupon,true))) {
						$discounts = $discount->get_discounts_by_coupon();
						if ($amount = $discounts[$coupon_code]) {
							$value = round($amount,$this->gtm->decimals);
						}
					}
				}
			}
			$this->gtm->add_ecommerce_event('select_promotion',array_merge([
				'currency'		=> $this->gtm->currency,
				'value'			=> $value,
				'promotion_id'	=> $coupon_code,
				'promotion_name'=> $coupon->get_description()
			],$this->gtm->page_attributes()),true);
		}
	}


	/**
	 * Get item detaiils
	 *
	 * @param string $id product id
	 * @param int $quantity
	 *
	 * @return array
	 */
	private function get_item($product, int $quantity = 1)
	{
		if ( $product = wc_get_product($product) )
		{
			if ($parent = $product->get_parent_id()) {
				$name = wc_get_product($parent)->get_name();
			} else {
				$name = $product->get_name();
			}
			$value = [
				'item_id' 		=> $product->get_sku(),
				'item_name'		=> $name,//sanitize_title($product->get_name()),
				'price' 		=> round($product->get_price(), $this->gtm->decimals),
				'discount' 		=> max(0,round((float)$product->get_regular_price() - (float)$product->get_price(), $this->gtm->decimals)),
				'quantity' 		=> $quantity,
			];
			if ($parent && ($attributes = $product->get_attributes())) {
				$value['item_variant']	= implode(',',(array)$attributes);
			}
			if ($terms = get_the_terms( $product->get_id(), 'product_cat' ))
			{
				foreach ($terms as $x => $term) {
					if ($x < 5) {
						$value[rtrim('item_category'.$x+1,'1')] = $term->name;
					}
				}
			}
			return $value;
		}
		return [];
	}
}
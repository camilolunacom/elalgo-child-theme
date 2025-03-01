<?php
/**
 * Custom functions
 *
 * @package baker_edge
 */

if ( ! function_exists( 'baker_edge_child_theme_enqueue_scripts' ) ) {
	/**
	 * Scripts to enqueue
	 *
	 * @return void
	 */
	function baker_edge_child_theme_enqueue_scripts() {
		$parent_style = 'baker-edge-default-style';
		wp_enqueue_style( 'baker-edge-child-style', get_stylesheet_directory_uri() . '/style.css', array( $parent_style ), filemtime( get_stylesheet_directory() . '/style.css' ) );
		// Only for checkout pages.
		if ( is_checkout() ) {
			wp_enqueue_script( 'alzr-checkout', get_stylesheet_directory_uri() . '/assets/js/checkout.js', array( 'jquery' ), filemtime( get_stylesheet_directory() . '/assets/js/checkout.js' ), true );
			wp_enqueue_style( 'jquery-ui' );
		}
	}
	add_action( 'wp_enqueue_scripts', 'baker_edge_child_theme_enqueue_scripts' );
}

/**
 * Load translation files from child theme
 *
 * @return void
 */
function my_child_theme_locale() {
	load_child_theme_textdomain( 'alunizar', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'my_child_theme_locale' );

/**
 * Override default address fields for checkout
 *
 * @param array $address_fields Existing address fields.
 */
function custom_override_default_address_fields( $address_fields ) {
	$address_fields['last_name']['label']       = 'Apellido';
	$address_fields['address_1']['label']       = 'Dirección';
	$address_fields['address_1']['placeholder'] = 'Dirección de la empresa o residencia';
	$address_fields['address_2']['placeholder'] = 'Oficina, apartamento, casa (opcional)';
	$address_fields['country']['label']         = 'País';
	$address_fields['city']['label']            = 'Ciudad';

	return $address_fields;
}
add_filter( 'woocommerce_default_address_fields', 'custom_override_default_address_fields' );

/**
 * Create new fields for checkout
 *
 * @param array $fields Existing fields.
 */
function custom_override_checkout_fields( $fields ) {
	$fields['billing']['billing_id']            = array(
		'label'    => 'Cédula o NIT',
		'required' => true,
		'class'    => array( 'form-row-wide' ),
		'priority' => 25,
	);
	$fields['billing']['billing_delivery']      = array(
		'type'     => 'hidden',
		'required' => false,
		'priority' => 98,
	);
	$fields['billing']['billing_delivery_display']      = array(
		'type'     => 'date',
		'label'    => 'Fecha de entrega',
		'required' => true,
		'class'    => array( 'form-row-wide' ),
		'priority' => 99,
	);
	$fields['order']['order_comments']['label'] = 'Notas';

	$city_args = array(
		'Bogota'   => 'Bogotá',
		'Chia'     => 'Chía',
		'Cajica'   => 'Cajicá',
		'Mosquera' => 'Mosquera',
		'Madrid'   => 'Madrid',
	);

	$city_args = wp_parse_args( array (
		'type'        => 'select',
		'options'     => $city_args,
		'input_class' => array( 'wc-enhanced-select' ),
		'default'     => 'Bogota',
	), $fields['shipping']['shipping_city'] );

	$fields['billing']['billing_city']   = $city_args;
	$fields['shipping']['shipping_city'] = $city_args;

	$fields['billing']['billing_state']['default']   = 'CO-CUN';
	$fields['shipping']['shipping_state']['default'] = 'CO-CUN';

	unset( $fields['billing']['billing_postcode'] );
	unset( $fields['shipping']['shipping_postcode'] );

	wc_enqueue_js( "
	jQuery( ':input.wc-enhanced-select' ).filter( ':not(.enhanced)' ).each( function() {
		var select2_args = { minimumResultsForSearch: 5, width: '100%' };
		jQuery( this ).select2( select2_args ).addClass( 'enhanced' );
	});" );

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'custom_override_checkout_fields' );

function alzr_validate_checkout_fields( $fields, $errors ) {
	// Check if delivery date is 'holiday', 'invalid', or 'emtpy', then add error accordingly.
	if ( isset( $_POST['billing_delivery'] ) ) {
		$delivery_date = $_POST['billing_delivery'];

		switch ( $delivery_date ) {
			case 'holiday':
				$errors->add( 'delivery_date', 'No hacemos entregas en la fecha seleccionada. Por favor escoge otra fecha.' );
				break;
			case 'invalid':
				$errors->add( 'delivery_date', 'Lo sentimos, no alcanzamos a hacer la entrega este día. Por favor escoge otra fecha.' );
				break;
			case 'empty':
				$errors->add( 'delivery_date', 'Por favor elige una fecha de entrega.' );
				break;
			default:
				break;
		}
	} else {
		$errors->add( 'delivery_date', 'No hemos encontrado la fecha de entrega del pedido.' );
	}
}
add_action( 'woocommerce_after_checkout_validation', 'alzr_validate_checkout_fields', 10, 2 );

/**
 * Display field value on the order edit page
 *
 * @param object $order Current WC order.
 * @return void
 */
function custom_checkout_field_display_admin_after_shipping( $order ) {
	echo '<p><strong>' . esc_html__( 'Delivery date', 'alunizar' ) . ':</strong><br>' . esc_html( get_post_meta( $order->get_id(), '_billing_delivery', true ) ) . '</p>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'custom_checkout_field_display_admin_after_shipping', 10, 1 );

/**
 * Display field value on the order edit page
 *
 * @param object $order Current WC order.
 * @return void
 */
function custom_checkout_field_display_admin_after_billing( $order ) {
	echo '<p><strong>' . esc_html__( 'ID or NIT', 'alunizar' ) . ':</strong><br>' . esc_html( get_post_meta( $order->get_id(), '_billing_id', true ) ) . '</p>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_after_billing', 10, 1 );

/**
 * Add field values to email
 *
 * @param array  $fields Existing email fields.
 * @param bool   $sent_to_admin If email should be sent to admin.
 * @param object $order WC order.
 * @return array New fields.
 */
function custom_woocommerce_email_order_meta_fields( $fields, $sent_to_admin, $order ) {
	$fields['billing_id']       = array(
		'label' => __( 'Cédula o NIT' ),
		'value' => get_post_meta( $order->get_id(), '_billing_id', true ),
	);
	$fields['billing_delivery'] = array(
		'label' => __( 'Fecha de entrega' ),
		'value' => get_post_meta( $order->get_id(), '_billing_delivery', true ),
	);
	return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'custom_woocommerce_email_order_meta_fields', 10, 3 );

/**
 * Check if current cart items can be delivered today
 *
 * @return void
 */
function alzr_delivery_date() {
	$today = true;

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

		$current_product = wc_get_product( $cart_item['product_id'] );

		if ( ! has_term( 'productos-para-hoy', 'product_cat', $cart_item['product_id'] ) ) {
			$today = false;
			break;
		}
	}

	wp_add_inline_script( 'alzr-checkout', 'var canDeliverToday = ' . wp_json_encode( $today ) . ';', 'before' );
}
add_filter( 'wp_enqueue_scripts', 'alzr_delivery_Date' );

if ( function_exists( 'acf_add_options_page' ) ) {

	acf_add_options_page(
		array(
			'page_title' => 'Elalgo - Opciones',
			'menu_title' => 'Elalgo',
			'menu_slug'  => 'elalgo-settings',
			'capability' => 'edit_posts',
			'redirect'   => false,
		)
	);
}

add_filter( 'woocommerce_after_checkout_form', 'generate_dates' );

function generate_dates() {
	$no_delivery = get_field('no_delivery', 'option');
	$exceptions = get_field('exceptions', 'option');

	if ( $no_delivery ) {
		$no_delivery_dates = array();

		foreach( $no_delivery as $row ) {
			$no_delivery_dates[] = $row['date'];
		}

		wp_add_inline_script( 'alzr-checkout', 'var noDeliveryDates = ' . wp_json_encode( $no_delivery_dates ) . ';', 'before');
	}

	echo '<br>';

	if ( $exceptions ) {
		$exception_dates = array();

		foreach( $exceptions as $row ) {
			$exception_dates[] = $row['date'];
		}

		wp_add_inline_script( 'alzr-checkout', 'var exceptionDates = ' . wp_json_encode( $exception_dates ) . ';', 'before');
	}
}

add_action( 'woocommerce_before_single_product', 'woocommerce_breadcrumb', 20, 0);

/**
 * Rename "home" in breadcrumb
 */
function wcc_change_breadcrumb_home_text( $defaults ) {
	$defaults['home'] = 'Tienda';
	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'wcc_change_breadcrumb_home_text' );

/**
 * Force state to "Cundinamarca"
 */
function alzr_cart_filter_co_states( $states ) {
	$states[ 'CO' ] = array( 'CO-CUN' => __( 'Cundinamarca', 'woocommerce' ) );
	return $states;
}
add_filter( 'woocommerce_states', 'alzr_cart_filter_co_states' );

/**
 * Set default state
 */
function alzr_default_state() {
	return 'CO-CUN';
}
add_filter( 'default_checkout_billing_state', 'alzr_default_state' );
add_filter( 'default_checkout_shipping_state', 'alzr_default_state' );

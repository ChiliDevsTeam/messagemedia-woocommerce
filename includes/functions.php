<?php

/**
 * Get Settings options value
 *
 * @param  string $option
 * @param  string $section
 * @param  string $default
 *
 * @return mixed
 */
function wcmsgmedia_get_option( $option, $section, $default = '' ) {

    $options = get_option( $section );

    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }

    return $default;
}

/**
 * Get sms order shortcodes
 *
 * @since 1.7
 *
 * @return array
 */
function wcmsgmedia_sms_get_order_shortcodes() {
    return apply_filters( 'wcmsgmedia_sms_get_order_shortcodes', array(
        '[order_id]',
        '[order_status]',
        '[order_amount]',
        '[order_items]',
        '[order_items_description]',
        '[billing_firstname]',
        '[billing_lastname]',
        '[billing_email]',
        '[billing_address1]',
        '[billing_address2]',
        '[billing_country]',
        '[billing_city]',
        '[billing_state]',
        '[billing_postcode]',
        '[billing_phone]',
        '[shipping_address1]',
        '[shipping_address2]',
        '[shipping_country]',
        '[shipping_city]',
        '[shipping_state]',
        '[shipping_postcode]',
        '[payment_method]'
    ) );
}
<?php
/*
Plugin Name: Messagemedia for WooCommerce
Plugin URI: https://messagemedia.com/us/
Description: This is an WooCommerce add-on. By Using this plugin admin and customer can get notification after placing order via messagemedia SMS gateways.
Version: 1.0.1
Author: chilidevs
Author URI: http://chilidevs.com/
Text Domain: wc-messagemedia
WC requires at least: 3.0
WC tested up to: 5.1.0
Domain Path: /languages/
License: GPL2
*/

/**
 * Copyright (c) 2019 chilidevs (email: info@chilidevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

 // don't call the file directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * WC_MessageMedia class
 *
 * @class WC_MessageMedia The class that holds the entire WC_MessageMedia plugin
 */
class WC_MessageMedia {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Instance of self
     *
     * @var WC_MessageMedia
     */
    private static $instance = null;

    /**
     * Constructor for the WC_MessageMedia class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        // Define all constant
        $this->define();

        add_action( 'admin_notices', [ $this, 'installation_notice' ], 10 );
        add_action( 'woocommerce_loaded', [ $this, 'init_plugin' ] );
    }

    /**
     * Initializes the WeDevs_Dokan() class
     *
     * Checks for an existing WeDevs_WeDevs_Dokan() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Define all constant
     *
     * @since 1.0.0
     */
    public function define() {
        define( 'WC_MSGMEDIA_FILE', __FILE__ );
        define( 'WC_MSGMEDIA_DIR', dirname(__FILE__) );
        define( 'WC_MSGMEDIA_PLUGIN_LIB_PATH', dirname(__FILE__). '/libs' );
        define( 'WC_MSGMEDIA_ASSETS', plugins_url( '/assets', WC_MSGMEDIA_FILE ) );
    }

    /**
     * Installation notice
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function installation_notice() {
        if ( ! function_exists( 'WC' ) ) {
            ?>
            <div id="message" class="error notice is-dismissible">
                <p><?php echo sprintf( wp_kses_post( '<b>MessageMedia for WooCommerce</b> requires <a href="%s">WooCommerce</a> to be installed & activated! Go back your <a href="%s">Plugin page</a>', 'wc-messagemedia' ), 'https://wordpress.org/plugins/woocommerce/', esc_url( admin_url( 'plugins.php' ) ) ) ?></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'wc-messagemedia' ) ?></span></button>
            </div>
            <?php
        }
    }

    /**
     * Init plugin files after loaded WooCommerce
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_plugin() {
        // Includes necessary files
        $this->includes();

        // Init all hooks after woocommerce loaded
        $this->init_hooks();

        // Do after WC_MessageMedia main plugin loaded
        do_action( 'wc_messagemedia_loaded', $this );
    }

    /**
     * Init All hooks for loading all classes and functionalities
     *
     * @since 1.0.0
     */
    public function init_hooks() {
        add_action( 'init', [ $this, 'localization_setup' ] );
        add_action( 'init', [ $this, 'init_classes' ] );

        add_action( 'woocommerce_checkout_after_customer_details', [ $this, 'customer_notification_field' ] );
        add_action( 'woocommerce_checkout_process', [ $this, 'customer_notification_field_process' ] );
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'customer_notification_update_order_meta' ] );
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'customer_sms_status_admin_order_meta' ], 10, 1 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'trigger_after_order_place' ], 10, 3 );
    }

    /**
     * Instantiate necessary Class
     *
     * @since 1.0.0
     *
     * @return void
     */
    function init_classes() {
        if ( is_admin() ) {
            new WC_MessageMedia_Setting_Options();
        }

        new WC_MessageMedia_Gateways();
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'wc-messagemedia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Includes all files
     *
     * @since 1.7
     *
     * @return void
     */
    public function includes() {
        require_once WC_MSGMEDIA_DIR . '/includes/functions.php';
        require_once WC_MSGMEDIA_DIR . '/includes/class-admin-settings.php';
        require_once WC_MSGMEDIA_DIR . '/includes/class-gateway.php';
    }

    /**
     * Add Buyer Notification field in checkout page
     */
    function customer_notification_field() {
        if( wcmsgmedia_get_option( 'buyer_notification', 'satosms_general', 'off' ) === 'off' ) {
            return;
        }

        $required      = ( wcmsgmedia_get_option( 'force_buyer_notification', 'satosms_general', 'no' ) === 'yes' ) ? true: false;
        $checkbox_text = wcmsgmedia_get_option( 'buyer_notification_text', 'satosms_general', __( 'Notify me the order status via sms', 'wc-messagemedia' ) );

        woocommerce_form_field( 'buyer_sms_notify', [
            'type'          => 'checkbox',
            'class'         => [ 'buyer-sms-notify form-row-wide' ],
            'label'         => __( $checkbox_text, 'satosms' ),
            'required'      => $required,
        ], 0 );
    }

    /**
     * Add Buyer Notification field validation
     *
     * @since 1.0.0
     */
    function customer_notification_field_process() {
        if( wcmsgmedia_get_option( 'force_buyer_notification', 'satosms_general', 'no' ) === 'no' ) {
            return;
        }

        // Check if the field is set, if not then show an error message.
        if ( ! isset( $_POST['buyer_sms_notify'] ) ) {
            wc_add_notice( __( '<strong>Send Notification Via SMS</strong> must be required', 'wc-messagemedia' ), 'error' );
        }
    }

    /**
     * Display Customer notification in Order admin page
     *
     * @since 1.0.0
     *
     * @param  object $order
     *
     * @return void
     */
    function customer_sms_status_admin_order_meta( $order ) {
        $want_notification =  $order->get_meta('_buyer_sms_notify');
        $display_info      = !empty( $want_notification ) ? __( 'Yes', 'wc-messagemedia' ) : __( 'No', 'wc-messagemedia' );
        echo '<p><strong>'.__( 'Buyer will get SMS notification', 'wc-messagemedia' ).':</strong> ' . $display_info . '</p>';
    }

    /**
     * Update Order buyer notify meta in checkout page
     *
     * @since 1.0.0
     *
     * @param  integer $order_id
     *
     * @return void
     */
    function customer_notification_update_order_meta( $order_id ) {
        if ( ! empty( $_POST['buyer_sms_notify'] ) ) {
            update_post_meta( $order_id, '_buyer_sms_notify', sanitize_text_field( $_POST['buyer_sms_notify'] ) );
        }
    }

    /**
     * Trigger when and order is placed
     *
     * @since 1.0.0
     *
     * @param  integer $order_id
     * @param  string $old_status
     * @param  string $new_status
     *
     * @return void
     */
    public  function trigger_after_order_place( $order_id, $old_status, $new_status ) {
        $order = wc_get_order( $order_id );

        if( ! $order_id ) {
            return;
        }

        $admin_sms_data = $buyer_sms_data = array();

        $default_admin_sms_body  = __( 'You have a new Order. The [order_id] is now [order_status]', 'wc-messagemedia' );
        $default_buyer_sms_body  = __( 'Thanks for purchasing. Your [order_id] is now [order_status]. Thank you', 'wc-messagemedia' );
        $order_status_settings   = wcmsgmedia_get_option( 'order_status', 'satosms_general', [] );
        $admin_phone_number      = wcmsgmedia_get_option( 'sms_admin_phone', 'satosms_message', '' );
        $want_to_notify_buyer    = $order->get_meta( '_buyer_sms_notify' );

        $wc_country_state         = new WC_Countries();
        $countries                = $wc_country_state->countries;
        $states                   = $wc_country_state->states;
        $product_list             = $this->get_product_list( $order );
        $product_description_list = $this->get_product_description_list( $order );
        $check_if_diff_msg        = wcmsgmedia_get_option( 'enable_diff_status_mesg', 'satosms_message_diff_status', 'off' );

        $new_wc_status = 'wc-' . $new_status;

        if ( 'on' === $check_if_diff_msg ) {
            $admin_sms_body     = wcmsgmedia_get_option( 'admin-'. $new_wc_status, 'satosms_message_diff_status', $default_admin_sms_body );
            $buyer_sms_body     = wcmsgmedia_get_option( 'buyer-'. $new_wc_status, 'satosms_message_diff_status', $default_buyer_sms_body );
        } else {
            $admin_sms_body     = wcmsgmedia_get_option( 'admin_sms_body', 'satosms_message', $default_admin_sms_body );
            $buyer_sms_body     = wcmsgmedia_get_option( 'sms_body', 'satosms_message', $default_buyer_sms_body );
        }

        if( count( $order_status_settings ) < 0 ) {
            return;
        }

        if ( empty( $admin_sms_body ) ) {
            $admin_sms_body = $default_admin_sms_body;
        }

        if ( empty( $buyer_sms_body ) ) {
            $buyer_sms_body = $default_buyer_sms_body;
        }

        $parse_data = array(
            'order_status'            => $new_status,
            'order_id'                => $order_id,
            'order_amount'            => wc_price( $order->get_total() ),
            'order_item'              => $product_list,
            'order_items_description' => $product_description_list,
            'billing_first_name'      => $order->get_billing_first_name(),
            'billing_last_name'       => $order->get_billing_last_name(),
            'billing_email'           => $order->get_billing_email(),
            'billing_address1'        => $order->get_billing_address_1(),
            'billing_address2'        => $order->get_billing_address_2(),
            'billing_country'         => ! empty( $countries[$order->get_billing_country()] ) ? $countries[$order->get_billing_country()] : '',
            'billing_city'            => $order->get_billing_city(),
            'billing_state'           => ( isset( $states[$order->get_billing_country()] ) && ! empty( $states[$order->get_billing_country()] ) ) ? $states[$order->get_billing_country()][$order->get_billing_state()] : $order->get_billing_state(),
            'billing_zipcode'         => $order->get_billing_postcode(),
            'billing_phone'           => $order->get_billing_phone(),
            'shipping_address1'       => $order->get_shipping_address_1(),
            'shipping_address2'       => $order->get_shipping_address_2(),
            'shipping_country'        => isset( $countries[$order->get_shipping_country()] ) ? $countries[$order->get_shipping_country()] : '',
            'shipping_city'           => $order->get_shipping_city(),
            'shipping_state'          => ( isset( $states[$order->get_shipping_country()] ) && !empty( $states[$order->get_shipping_country()] ) ) ? $states[$order->get_shipping_country()][$order->get_shipping_state()] : $order->get_shipping_state(),
            'shipping_zipcode'        => $order->get_shipping_postcode(),
            'payment_method'          => $order->get_payment_method_title()
        );

        if ( in_array( $new_wc_status, $order_status_settings ) ) {
            if ( $want_to_notify_buyer ) {
                if ( 'on' ===  wcmsgmedia_get_option( 'admin_notification', 'satosms_general', 'on' ) ) {
                    $admin_sms_data['number']   = $admin_phone_number;
                    $admin_sms_data['sms_body'] = $this->pharse_sms_body( $admin_sms_body, $parse_data );
                    $admin_response             = WC_MessageMedia_Gateways::init()->messagemedia( $admin_sms_data );

                    if( $admin_response ) {
                        $order->add_order_note( __( 'SMS Send Successfully to admin', 'wc-messagemedia' ) );
                    } else {
                        $order->add_order_note( __( 'SMS sening faild, Try again', 'wc-messagemedia' ) );
                    }
                }

                $buyer_sms_data['number']   = $order->get_billing_phone();
                $buyer_sms_data['sms_body'] = $this->pharse_sms_body( $buyer_sms_body, $parse_data );
                $buyer_response             = WC_MessageMedia_Gateways::init()->messagemedia( $buyer_sms_data );

                if ( $buyer_response ) {
                    $order->add_order_note( __( 'SMS Send to customer Successfully', 'wc-messagemedia' ) );
                } else {
                    $order->add_order_note( __( 'SMS sening faild, Try again', 'wc-messagemedia' ) );
                }

            } else {
                if ( 'on' === wcmsgmedia_get_option( 'admin_notification', 'satosms_general', 'on' ) ) {
                    $admin_sms_data['number']   = $admin_phone_number;
                    $admin_sms_data['sms_body'] = $this->pharse_sms_body( $admin_sms_body, $parse_data );
                    $admin_response             = WC_MessageMedia_Gateways::init()->messagemedia( $admin_sms_data );

                    if ( $admin_response ) {
                        $order->add_order_note( __( 'SMS Send Successfully to admin ', 'wc-messagemedia' ) );
                    } else {
                        $order->add_order_note( __( 'SMS sening faild, Try again', 'wc-messagemedia' ) );
                    }
                }
            }
        }
    }

    /**
     * Pharse Message body with necessary variables
     *
     * @since 1.0.0
     *
     * @param  string $content
     * @param  string $order_status
     * @param  integer $order_id
     *
     * @return string
     */
    public function pharse_sms_body( $content, $data ) {
        $find = wcmsgmedia_sms_get_order_shortcodes();
        $replace = array(
            $data['order_id'],
            $data['order_status'],
            $data['order_amount'],
            $data['order_item'],
            $data['order_items_description'],
            $data['billing_first_name'],
            $data['billing_last_name'],
            $data['billing_email'],
            $data['billing_address1'],
            $data['billing_address2'],
            $data['billing_country'],
            $data['billing_city'],
            $data['billing_state'],
            $data['billing_zipcode'],
            $data['billing_phone'],
            $data['shipping_address1'],
            $data['shipping_address2'],
            $data['shipping_country'],
            $data['shipping_city'],
            $data['shipping_state'],
            $data['shipping_zipcode'],
            $data['payment_method'],
        );

        $body = str_replace( $find, $replace, $content );
        return apply_filters( 'wcmsgmedia_pharse_sms_body', $body, $data );
    }

    /**
     * Get product items list from order
     * @param  object $order
     * @return string  [list of product]
     */
    function get_product_list( $order ) {

        $product_list = '';
        $order_item = $order->get_items();

        foreach( $order_item as $product ) {
            $prodct_name[] = $product['name'];
        }

        $product_list = implode( ',', $prodct_name );

        return $product_list;
    }

    /**
     * Get product items list from order
     * @param  object $order
     * @return string  [list of product]
     */
    function get_product_description_list( $order ) {
        $product_list = '';
        $order_item = $order->get_items();

        foreach( $order_item as $product ) {
            $product_description[] = get_post( $product['product_id'] )->post_content;
        }

        $product_list = implode( ',', $product_description );

        return $product_list;
    }

} // WC_MessageMedia

/**
 * Loaded after all plugin initialize
 */
function wc_messagemedia() {
    WC_MessageMedia::init();
}

wc_messagemedia();

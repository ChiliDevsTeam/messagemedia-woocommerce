<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Check if the main settings class included or not
if ( file_exists( WC_MSGMEDIA_PLUGIN_LIB_PATH . '/class.settings-api.php' ) ) {
    require_once WC_MSGMEDIA_PLUGIN_LIB_PATH . '/class.settings-api.php';
}

/**
 * Settings API class
 *
 * @author chilidevs
 */

class WC_MessageMedia_Setting_Options {

    /**
     * Settings object
     */
    private $settings_api;

    /**
     * Hold shortcodes for sms text
     */
    public static $shortcodes;

    /**
     * Load automatically when class initiate
     *
     * @since 1.0.0
     */
    function __construct() {
        $this->settings_api = new WC_MessageMedia_Settings_API;
        self::$shortcodes = apply_filters( 'sat_sms_shortcode_insert_description', 'For order id just insert <code>[order_id]</code> and for order status insert <code>[order_status]</code>. Similarly <code>[order_items]</code>, <code>[order_items_description]</code>, <code>[order_amount]</code>, <code>[billing_firstname]</code>, <code>[billing_lastname]</code>, <code>[billing_email]</code>, <code>[billing_address1]</code>, <code>[billing_address2]</code>, <code>[billing_country]</code>, <code>[billing_city]</code>, <code>[billing_state]</code>, <code>[billing_postcode]</code>, <code>[billing_phone]</code>, <code>[shipping_address1]</code>, <code>[shipping_address2]</code>, <code>[shipping_country]</code>, <code>[shipping_city]</code>, <code>[shipping_state]</code>, <code>[shipping_postcode]</code>, <code>[payment_method]</code>' );

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'chili_settings_form_bottom_satosms_message_diff_status', array( $this, 'settings_field_message_diff_status' ) );
    }

    /**
     * Admin init hook
     * @return void
     */
    function admin_init() {
        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    /**
     * Admin Menu CB
     *
     * @return void
     */
    function admin_menu() {
        add_menu_page( __( 'MessageMedia Settings', 'wc-messagemedia' ), __( 'MessageMedia', 'wc-messagemedia' ), 'manage_woocommerce', 'wc-messagemedia-settings', array( $this, 'plugin_page' ), 'dashicons-email-alt' );
        add_submenu_page( 'wc-messagemedia-settings', __( 'MessageMedia Settings', 'wc-messagemedia' ), __( 'Settings', 'wc-messagemedia' ), 'manage_woocommerce', 'wc-messagemedia-settings', array( $this, 'plugin_page' ) );

        do_action( 'wcmsgmedia_load_menu' );
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @since 1.0.0
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style( 'admin-satosms-styles', WC_MSGMEDIA_ASSETS . '/css/admin.css', false, date( 'Ymd' ) );
        wp_enqueue_script( 'admin-satosms-scripts', WC_MSGMEDIA_ASSETS . '/js/admin.js', array( 'jquery' ), false, true );

        wp_localize_script( 'admin-satosms-scripts', 'wcmessagemedia', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' )
        ) );
    }

    /**
     * Get All settings Field
     * @return array
     */
    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'satosms_general',
                'title' => '',
                'name' => __( 'Basic', 'wc-messagemedia' ),
                'icon'  => 'dashicons-admin-generic'
            ),
            array(
                'id' => 'satosms_gateway',
                'title' => '',
                'name' => __( 'Gateway Settings', 'wc-messagemedia' ),
                'icon'  => 'dashicons-admin-tools'
            ),

            array(
                'id' => 'satosms_message',
                'title' => '',
                'name' => __( 'SMS Text', 'wc-messagemedia' ),
                'icon'  => 'dashicons-email'
            ),

            array(
                'id' => 'satosms_message_diff_status',
                'title' => '',
                'name' => __( 'Body Settings', 'wc-messagemedia' ),
                'icon'  => 'dashicons-book'
            )
        );
        return apply_filters( 'wcmsgmedia_settings_sections' , $sections );
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $customer_message = __( "Thanks for purchasing\nYour [order_id] is now [order_status]\nThank you", 'wc-messagemedia' );
        $admin_message = __( "You have a new Order\nThe [order_id] is now [order_status]\n", 'wc-messagemedia' );

        $settings_fields = array(
            'satosms_general' => apply_filters( 'wcmsgmedia_general_settings', array(
                array(
                    'name' => 'enable_notification',
                    'label' => __( 'Enable SMS Notifications', 'wc-messagemedia' ),
                    'desc' => __( 'If checked, then sms notification will be enabled', 'wc-messagemedia' ),
                    'type' => 'checkbox',
                ),

                array(
                    'name' => 'admin_notification',
                    'label' => __( 'Enable Admin Notifications', 'wc-messagemedia' ),
                    'desc' => __( 'If checked, then admin sms notification will be enabled for and order', 'wc-messagemedia' ),
                    'type' => 'checkbox',
                    'default' => 'on'
                ),

                array(
                    'name' => 'buyer_notification',
                    'label' => __( 'Enable Customer Notification', 'wc-messagemedia' ),
                    'desc' => __( 'If checked then customer will get notification checkbox options in checkout page', 'wc-messagemedia' ),
                    'type' => 'checkbox',
                ),

                array(
                    'name' => 'force_buyer_notification',
                    'label' => __( 'Force customer notification', 'wc-messagemedia' ),
                    'desc' => __( 'If select yes then customer notification option must be required in checkout page', 'wc-messagemedia' ),
                    'type' => 'select',
                    'default' => 'no',
                    'options' => array(
                        'yes' => __( 'Yes', 'wc-messagemedia' ),
                        'no'   => __( 'No', 'wc-messagemedia' )
                    )
                ),

                array(
                    'name' => 'buyer_notification_text',
                    'label' => __( 'Customer Notification Text', 'wc-messagemedia' ),
                    'desc' => __( 'Enter your text which is appeared in checkout page for the customers', 'wc-messagemedia' ),
                    'type' => 'textarea',
                    'default' => 'Send me order status notifications via SMS (N.B.: Your SMS will be sent in your billing phone. Make sure phone number must have an valid extension )'
                ),
                array(
                    'name' => 'order_status',
                    'label' => __( 'Check Order Status', 'wc-messagemedia' ),
                    'desc' => __( 'For which statues SMS notification will be sent to admin and customer', 'wc-messagemedia' ),
                    'type' => 'multicheck',
                    'options' => wc_get_order_statuses()
                )
            ) ),

            'satosms_gateway' => apply_filters( 'satosms_gateway_settings',  array(
                array(
                    'name' => 'messagemedia_api_key',
                    'label' => __( 'API Key', 'wc-messagemedia' ),
                    'desc' => sprintf( __( 'Enter your API key for messagemedia, for getting your api key please visit <a href="%s" target="_blank">API Settings</a>', 'wc-messagemedia' ), esc_url( 'https://hub.messagemedia.com/api-settings-v2' ) ),
                    'type' => 'text',
                    'default' => '',
                ),
                array(
                    'name' => 'messagemedia_api_secret',
                    'label' => __( 'API Secret', 'wc-messagemedia' ),
                    'desc' => sprintf( __( 'Enter your API Secret for messagemedia, for getting your api secret please visit <a href="%s" target="_blank">API Settings</a>', 'wc-messagemedia' ), esc_url( 'https://hub.messagemedia.com/api-settings-v2' ) ),
                    'type' => 'text',
                    'default' => '',
                ),
            ) ),

            'satosms_message' => apply_filters( 'satosms_message_settings',  array(
                array(
                    'name' => 'sms_admin_phone',
                    'label' => __( 'Enter admin Phone Number with extension', 'wc-messagemedia' ),
                    'desc' => __( '<br>Admin order sms notifications will be send in this number. Please make sure that the number must have a extension (e.g.: +8801626265565 where +880 will be extension )', 'wc-messagemedia' ),
                    'type' => 'text'
                ),
                array(
                    'name' => 'admin_sms_body',
                    'label' => __( 'Enter admin SMS body', 'wc-messagemedia' ),
                    'desc' => __( 'SMS text for admin. When an order is created then admin will get this formatted message.', 'wc-messagemedia' ) . ' ' . self::$shortcodes,
                    'type' => 'textarea',
                    'default' => $admin_message
                ),

                array(
                    'name' => 'sms_body',
                    'label' => __( 'Enter customer SMS body', 'wc-messagemedia' ),
                    'desc' => __( 'SMS text for customer. If customer notification is enabled then customer will get this formatted message when order is placed', 'wc-messagemedia' ) . ' ' . self::$shortcodes,
                    'type' => 'textarea',
                    'default' => $customer_message
                ),
            ) ),

            'satosms_message_diff_status' => apply_filters( 'wcmsgmedia_message_diff_status_settings',  array(
                array(
                    'name' => 'enable_diff_status_mesg',
                    'label' => __( 'Enable different message for different order statuses', 'wc-messagemedia' ),
                    'desc' => __( 'If checked then admin and customer will get sms text for different order statues', 'wc-messagemedia' ),
                    'type' => 'checkbox'
                ),
            ) ),
        );

        return apply_filters( 'wcmsgmedia_settings_section_content', $settings_fields );
    }

    /**
     * Loaded Plugin page
     * @return void
     */
    function plugin_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Settings', 'wc-messagemedia' ) ?></h1><br>
            <div class="chili-settings-wrap">
                <?php
                    $this->settings_api->show_navigation();
                    $this->settings_api->show_forms();
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

    function settings_field_message_diff_status() {
        $enabled_order_status = wcmsgmedia_get_option( 'order_status', 'satosms_general', array() );
        ?>
        <div class="satosms_different_message_status_wrapper satosms_hide_class">
            <hr>
            <?php if ( $enabled_order_status  ): ?>
                <h3><?php _e( 'Set sms text for customers', 'wc-messagemedia' ); ?> </h3>
                <p style="margin-top:15px; margin-bottom:0px; font-style: italic; font-size: 14px;">
                    <span><?php echo self::$shortcodes; ?></span>
                </p>
                <table class="form-table">
                    <?php foreach ( $enabled_order_status as $buyer_status_key => $buyer_status_value ): ?>
                        <?php
                            $buyer_display_order_status = str_replace( 'wc-', '', $buyer_status_key );
                            $buyer_content_value = wcmsgmedia_get_option( 'buyer-'.$buyer_status_key, 'satosms_message_diff_status', '' );
                        ?>
                        <tr valign="top">
                            <th scrope="row"><?php echo sprintf( '%s %s', ucfirst( str_replace( '-', ' ', $buyer_display_order_status ) ) , __( 'Order Status', 'wc-messagemedia' ) ); ?></th>
                            <td>
                                <textarea class="regular-text" name="satosms_message_diff_status[buyer-<?php echo $buyer_status_key; ?>]" id="satosms_message_diff_status[buyer-<?php echo $buyer_status_key; ?>]" cols="55" rows="5"><?php echo $buyer_content_value; ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </table>

                <hr>

                <h3><?php _e( 'Set SMS text for Admin', 'wc-messagemedia' ); ?></h3>
                <p style="margin-top:15px; margin-bottom:0px; font-style: italic; font-size: 14px;">
                    <span><?php echo self::$shortcodes; ?></span>
                </p>
                <table class="form-table">
                    <?php foreach ( $enabled_order_status as $admin_status_key => $admin_status_value ): ?>
                        <?php
                            $admin_display_order_status = str_replace( 'wc-', '', $admin_status_key );
                            $admin_content_value = wcmsgmedia_get_option( 'admin-'.$admin_status_key, 'satosms_message_diff_status', '' );
                        ?>
                        <tr valign="top">
                            <th scrope="row"><?php echo sprintf( '%s %s', ucfirst( str_replace( '-', ' ', $admin_display_order_status ) ) , __( 'Order Status', 'wc-messagemedia' ) ); ?></th>
                            <td>
                                <textarea class="regular-text" name="satosms_message_diff_status[admin-<?php echo $admin_status_key; ?>]" id="satosms_message_diff_status[buyer-<?php echo $admin_status_key; ?>]" cols="55" rows="5"><?php echo $admin_content_value; ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </table>

            <?php else: ?>
                <p style="margin-top:15px; margin-bottom:0px; font-size: 14px;"><?php _e( 'Sorry no order status will be selected for sending SMS in basic Settings tab. Please select some order status from Basic Settings Tab') ?></p>
            <?php endif ?>
        </div>
        <?php
    }

} // End of WC_MessageMedia_Setting_Options Class
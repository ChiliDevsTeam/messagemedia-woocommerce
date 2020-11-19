<?php
use MessageMediaMessagesLib\Models;
use MessageMediaMessagesLib\Exceptions;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SMS Gateway handler class
 *
 * @author satosms
 */
class WC_MessageMedia_Gateways {

    /**
     * Hold class instance
     *
     * @since 1.0.0
     */
    private static $_instance;

    /**
     * Directly called main instance using singletone pattern
     *
     * @since 1.0.0
     */
    public static function init() {
        if ( ! self::$_instance ) {
            self::$_instance = new WC_MessageMedia_Gateways();
        }

        return self::$_instance;
    }

    /**
     * Send sms via messagemedia
     *
     * @since 1.0.0
     */
    public function messagemedia( $sms_data ) {
        $authUserName = wcmsgmedia_get_option( 'messagemedia_api_key', 'satosms_gateway', '' );
        $authPassword = wcmsgmedia_get_option( 'messagemedia_api_secret', 'satosms_gateway', '' );

        if( empty( $authUserName ) || empty( $authPassword ) ) {
            return;
        }

        require WC_MSGMEDIA_DIR . '/vendor/autoload.php';

        /* You can change this to true when the above keys are HMAC */
        $useHmacAuthentication = false;

        $client = new MessageMediaMessagesLib\MessageMediaMessagesClient($authUserName, $authPassword, $useHmacAuthentication);

        $messagesController = $client->getMessages();

        $body = new Models\SendMessagesRequest;
        $body->messages = array();

        $body->messages[0] = new Models\Message;
        $body->messages[0]->content = $sms_data['sms_body'];
        $body->messages[0]->destinationNumber = $sms_data['number'];

        try {
            $result = $messagesController->sendMessages($body);
            return true;
        } catch (Exceptions\SendMessages400Response $e) {
            echo 'Caught SendMessages400Response: ',  $e->getMessage(), "\n";
        } catch (MessageMediaMessagesLib\APIException $e) {
            echo 'Caught APIException: ',  $e->getMessage(), "\n";
        }

    }
}

<?php 
/* @package iSMS Authenticator SMS API Integration*/
/**
 * Plugin Name:       iSMS 2 Factor Authentication
 * Plugin URI:        https://www.isms.com.my
 * Description:     2-Factor Authentication with SMS OTP Verification
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Mobiweb
 * Author URI:        https://www.mobiweb.com.my
 * License:           GPLv2 or later
 * Text Domain:       isms-authenticator
 */

defined('ABSPATH') or die( 'Access Forbidden!' );

global $wpdb;
define('ISMS_AUTHENTICATOR',$wpdb->prefix. "isms-authenticator" );

require_once(dirname(__FILE__) . '/includes/Plugin.php');
require_once(dirname(__FILE__) . '/includes/iSMSAuthProcess.php');

class wp_isms_authenticator extends wp_isms_authenticator\includes\Plugin {

    private $isms = null;
    
    public function __construct() {
        $this->name = plugin_basename(__FILE__);
        $this->pre = strtolower(__CLASS__);
        $this->version = '1.0.0.0';

         $this->actions = array(
            'plugins_loaded'        =>  false
        );
         //register the plugin and init assets
        $this->register_plugin($this->name, __FILE__, true);
    }

     public function plugins_loaded() {
        require_once(dirname(__FILE__) . '/includes/iSMSAuth.php');
        $this->isms = new \wp_isms_authenticator\includes\iSMSAuth();
    }
}

function isms_auth_activate() {
    global $wpdb; 
    $sms_sent_db = $wpdb->query('CREATE TABLE `'.ISMS_AUTHENTICATOR.'`(
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(255) NOT NULL,
      `mobile` varchar(255) NOT NULL,
      `is_expired` int(11) NOT NULL,
      `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)) 
      ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1'
    );
}

register_activation_hook(__FILE__,'isms_auth_activate');

$GLOBALS['iSMS_AUTHENTICATOR'] = new wp_isms_authenticator();

?>
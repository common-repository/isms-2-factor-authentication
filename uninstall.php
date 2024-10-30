<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	    exit();
global $wpdb;		

delete_option( 'isms_auth_account_settings' );

$isms_auth = $wpdb->prefix. "isms-authenticator" ;

$wpdb->query("DROP TABLE `".$isms_auth."`");



<?php
namespace wp_isms_authenticator\includes;
defined('ABSPATH') or die( 'Access Forbidden!' );

class iSMSAuthProcess {

    private $endpoint;
    private $options;
    private $username;
    private $password;
    private $prefix;

    function __construct() {
        $this->options = get_option( 'isms_auth_account_settings' );
       $this->endpoint = 'https://www.isms.com.my/RESTAPI.php';
		
        $this->username = $this->options['username'];
        $this->password = $this->options['password'];
    }

    function send_notification($params) {
        $data = array (
            'sendid' => $this->options['sendid'],
            'dstno' => $params['dstno'],
            'msg' => $params['msg'],
            'type' => '1',
            'agreedterm' =>  'YES',
            'method' => 'isms_send_all_id'
        );

        $payload = json_encode($data);
		$args = array(
			'body' => $payload,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode("$this->username:$this->password"),
			),
			'cookies' => array()
		);
		$response = wp_remote_post($this->endpoint, $args);
		return $response;

        
    }

    function get_data($method) {
        $data = array (
            'method' => $method
        );

		$args = array(
			'body' => json_encode($data),
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode("$this->username:$this->password"),
			),
			'cookies' => array()
		);
		$response = wp_remote_post($this->endpoint, $args);
		return $response;
    }

    function get_db_data($table) {
        global $wpdb;
        return $wpdb->get_results('SELECT * FROM '.$table.'', OBJECT );
    }

    function save_otp($data){
        global $wpdb;
        if ( $wpdb->insert(ISMS_AUTHENTICATOR,$data)){
            return true;
        }
    }
    
    function check_expired_otp(){
        global $wpdb;
        $interval = $this->options['send-interval'];

        $result = $wpdb->get_results('SELECT * FROM `'.ISMS_AUTHENTICATOR.'` WHERE is_expired = 0 AND timestamp < date_sub(now(), interval '.$interval.' minute) ');

        foreach ($result as $otp) {

           $wpdb->update(ISMS_AUTHENTICATOR,
                array('is_expired'=>'1'),
                array('id' => $otp->id),
                array(
                    '%d'
                ),
                array( '%d' )
            );
        }
        return true;
    }

    function check_otp($mobile,$otp){
        global $wpdb;

        $interval = $this->options['send-interval'];

        $check = $wpdb->get_var('SELECT * FROM `'.ISMS_AUTHENTICATOR.'` WHERE is_expired = 0 AND code = '.$otp.' AND mobile = "'.$mobile.'" AND timestamp > date_sub(now(), interval '.$interval.' minute) ');

        if($check) {
            $result = $wpdb->get_row('SELECT * FROM `'.ISMS_AUTHENTICATOR.'` WHERE is_expired = 0 AND code = '.$otp.' AND mobile = "'.$mobile.'" AND timestamp > date_sub(now(), interval '.$interval.' minute) ');

            $update =  $wpdb->update(ISMS_AUTHENTICATOR,
                array('is_expired'=>'1'),
                array('id' => $result->id),
                array(
                    '%d'    // value2
                ),
                array( '%d' )
            );

            if($update) {
                return true;
            }

        }else {
            return false;
        }
    }
}
?>
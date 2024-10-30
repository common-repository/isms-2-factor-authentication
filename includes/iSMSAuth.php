<?php
namespace wp_isms_authenticator\includes;
defined('ABSPATH') or die( 'Access Forbidden!' );
class iSMSAuth {
    private $admin_auth_options;
    public $isms_auth_process;

    function __construct() {
        add_action('admin_menu', array($this,'isms_auth_hook_to_menu') );
        add_action('admin_init', array( $this, 'isms_auth_init' ) );
        add_action("admin_enqueue_scripts", array($this,"isms_auth_scripts_and_style"));
        add_action('wp_enqueue_scripts', array($this,"isms_auth_public_scripts_and_style"));

        $this->admin_auth_options = get_option( 'isms_auth_account_settings' );
        if ( class_exists( 'WooCommerce' )) {
                add_action('woocommerce_register_form_start', array($this, 'isms_auth_wc_mobile_register_field'),40,3);
                add_action('woocommerce_register_post', array($this, 'isms_auth_validate_mobile_register_field'), 10, 3);
                add_action('woocommerce_created_customer', array($this, 'isms_auth_save_mobile_register_field'));
        }else {
            if($this->admin_auth_options['create-mobile-field'] == 'yes') {
                add_action( 'wp_footer',  array($this,'isms_auth_footer_script') );
            }else {
               add_action( 'in_admin_footer',  array($this,'isms_auth_exist_mobile_footer_script') );
               add_action( 'wp_footer',  array($this,'isms_auth_exist_public_mobile_footer_script') );
            }
        }
        
        $this->isms_auth_process = new \wp_isms_authenticator\includes\iSMSAuthProcess();
        $this->isms_auth_process->check_expired_otp();

        add_action( 'wp_ajax_generate_otp_code', array($this, 'generate_otp_code') );
        add_action( 'wp_ajax_nopriv_generate_otp_code', array($this, 'generate_otp_code') );

        add_action( 'wp_ajax_verify_otp', array($this, 'verify_otp') );
        add_action( 'wp_ajax_nopriv_verify_otp', array($this, 'verify_otp') );
    }
    
    function verify_otp() {
        $dst = sanitize_text_field(filter_var($_POST['dst'], FILTER_SANITIZE_NUMBER_INT));
        $otp = sanitize_text_field(filter_var( $_POST['otp_code'], FILTER_SANITIZE_NUMBER_INT));
        $countrycode = sanitize_text_field($_POST['countrycode']);

        $mobile = "+".$countrycode.$dst;
        $check_otp = $this->isms_auth_process->check_otp( $mobile,$otp);
        if($check_otp) {
            wp_send_json(true);
        }else {
            wp_send_json(false);
        }
    }

    function generate_otp_code() {
        $dst = sanitize_text_field(filter_var($_POST['dst'], FILTER_SANITIZE_NUMBER_INT));
        $countrycode = sanitize_text_field($_POST['countrycode']);

        $mobile = $countrycode.$dst;
        $otp = rand(100000,999999);
        $save = array(
            'code' => $otp,
            'mobile' => "+".$mobile,
            'is_expired' => 0
        );

        $params = array(
            'dstno' => $mobile,
            'msg' => $this->format_message($otp,$this->admin_auth_options['otp-template'])
        );
        
        $result = $this->isms_auth_process->send_notification($params);
		
       	$response_code = explode("=",str_replace('"', "",$result['body']));
        if ($response_code[0] == 2000) {
            $save_otp = $this->isms_auth_process->save_otp($save);
            if($save_otp){
                wp_send_json(true);
            }
        }
    }

    function isms_auth_hook_to_menu() {
        add_menu_page(
            'iSMS Authenticator API Integration',
            'iSMS OTP',
            'manage_options',
            'isms-auth-setting',
            array( $this, 'create_auth_admin_page' ),'',6
        );
    }

    function isms_auth_scripts_and_style($hook){
        if($hook == 'toplevel_page_isms-auth-setting'){
            wp_enqueue_style("isms-auth-prefix", plugins_url('../assets/prefix/css/intlTelInput.css', __FILE__));
            wp_enqueue_style("isms-auth-style", plugins_url('../assets/css/ismsauthstyle.css', __FILE__));

            wp_enqueue_script("isms-auth-prefix-js", plugins_url('../assets/prefix/js/intlTelInput.js', __FILE__));

            wp_enqueue_script("isms-auth-js", plugins_url('../assets/js/ismsauth.js', __FILE__));
            wp_localize_script('isms-auth-js', 'ajaxurl', array("scriptAuth" => admin_url('admin-ajax.php')));
            wp_localize_script('isms-auth-js', 'ismsauthScript', array(
                'pluginsUrl' => plugin_dir_url( __FILE__ ),
            ));
        }
    }

    function isms_auth_public_scripts_and_style($hook){
        wp_enqueue_style("isms-auth-prefix", plugins_url('../assets/prefix/css/intlTelInput.css', __FILE__));
        wp_enqueue_style("isms-auth-style", plugins_url('../assets/public/css/ismsauthstyle.css', __FILE__));
        wp_enqueue_script('jquery');
        wp_enqueue_script("isms-auth-prefix-js", plugins_url('../assets/prefix/js/intlTelInput.js', __FILE__));

        wp_enqueue_script("isms-auth-js", plugins_url('../assets/public/js/ismsauth.js', __FILE__));
        wp_localize_script( 'isms-auth-js', 'isms_auth_public_ajax', array( "ajaxurl" => admin_url('admin-ajax.php') ) );
        wp_localize_script('isms-auth-js', 'ismsauthScript', array(
            'pluginsUrl' =>  plugin_dir_url( __FILE__ ),
        ));
    }
    /**
     * Register and add settings
     */
    public function isms_auth_init() {
        add_option('create-mobile-field','yes');
        register_setting(
            'isms_auth_admin_settings', // Option group
            'isms_auth_account_settings', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'auth_setting_section_id', // ID
            'iSMS Account Setting', // Title
            array( $this, 'print_section_info' ), // Callback
            'my-auth-setting-admin' // Page
        );

        add_settings_field(
            'sendid', // ID
            'Sender ID', // Title
            array( $this, 'sendid_callback' ), // Callback
            'my-auth-setting-admin', // Page
            'auth_setting_section_id' // Section
        );
        add_settings_field(
            'username', // ID
            'Username', // Title
            array( $this, 'username_callback' ), // Callback
            'my-auth-setting-admin', // Page
            'auth_setting_section_id' // Section
        );

        add_settings_field(
            'phone',
            'Admin Phone',
            array( $this, 'phone_callback' ),
            'my-auth-setting-admin',
            'auth_setting_section_id'
        );
        add_settings_field(
            'password',
            'Password',
            array( $this, 'password_callback' ),
            'my-auth-setting-admin',
            'auth_setting_section_id'
        );


        add_settings_section(
            'auth_field_setting_section_id', // ID
            '2-Factor Authentication Settings', // Title
            array( $this, 'print_auth_field_section' ), // Callback
            'my-auth-setting-admin',
            'auth_setting_section_id' // Page
        );
        add_settings_field(
            'form-selector',
            'Contact Form Selector',
            array( $this, 'form_selector_callback' ),
            'my-auth-setting-admin',
            'auth_field_setting_section_id'
        );
        add_settings_field(
            'submit-btn-selector',
            'Submit Button Selector',
            array( $this, 'submit_btn_selector_callback' ),
            'my-auth-setting-admin',
            'auth_field_setting_section_id'
        );
        if ( !class_exists( 'WooCommerce' )){
            add_settings_field(
                'create-mobile-field',
                'Create Mobile Input Field',
                array( $this, 'create_mobile_field_callback' ),
                'my-auth-setting-admin',
                'auth_field_setting_section_id'
            );
            add_settings_field(
                'mobile-field-selector',
                'Mobile Input Selector',
                array( $this, 'mobile_field_selector_callback' ),
                'my-auth-setting-admin',
                'auth_field_setting_section_id'
            );

        }
        add_settings_field(
            'send-interval',
            'Minutes to resend OTP',
            array( $this, 'send_interval_callback' ),
            'my-auth-setting-admin',
            'auth_field_setting_section_id'
        );
        add_settings_field(
            'otp-template',
            'OTP Template',
            array( $this, 'otp_template_callback' ),
            'my-auth-setting-admin',
            'auth_field_setting_section_id'
        );
    }

    function print_auth_field_section() {
        print '';
    }

    function create_auth_admin_page() { ?>
        <div class="wrap">
            <h1>iSMS Authenticator Settings</h1>
            <div class="isms-divider"></div>
            <?php
			$balance = $this->isms_auth_process->get_data('isms_balance');
			$expiration = $this->isms_auth_process->get_data('isms_expiry_date');
					
            if($this->admin_auth_options){ ?>
                <div>
                    <h3>Your credit balance: <?php echo str_replace('"', "", $balance['body']); ?></h3>
                    <h4>valid until <?php echo str_replace('"', "", $expiration['body']); ?> </h4>

                </div>
            <?php } ?>

            <form method="post" action="options.php">
                <?php
				
                // This prints out all hidden setting fields
                settings_fields( 'isms_auth_admin_settings' );
                do_settings_sections( 'my-auth-setting-admin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['sendid'] ) )
            $new_input['sendid'] = sanitize_text_field( $input['sendid'] );

        if( isset( $input['username'] ) )
            $new_input['username'] = sanitize_text_field( $input['username'] );

        if( isset( $input['ismsauthphone'] ) )
            $new_input['ismsauthphone'] = sanitize_text_field( $input['ismsauthphone'] );

        if( isset( $input['password'] ) )
            $new_input['password'] = sanitize_text_field( $input['password'] );


        if( isset( $input['form-selector'] ) )
            $new_input['form-selector'] = sanitize_text_field( $input['form-selector'] );
        if( isset( $input['submit-btn-selector'] ) )
            $new_input['submit-btn-selector'] = sanitize_text_field( $input['submit-btn-selector'] );
        if( isset( $input['create-mobile-field'] ) )
            $new_input['create-mobile-field'] = sanitize_text_field( $input['create-mobile-field'] );
        if( isset( $input['mobile-field-selector'] ) )
            $new_input['mobile-field-selector'] = sanitize_text_field( $input['mobile-field-selector'] );
        if( isset( $input['send-interval'] ) )
            $new_input['send-interval'] = sanitize_text_field( $input['send-interval'] );
        if( isset( $input['otp-template'] ) )
            $new_input['otp-template'] = sanitize_text_field( $input['otp-template'] );

        return $new_input;
    }

    /**
     * Print the Section text
     */

    public function print_section_info() {
        print 'Enter your iSMS credentials';
    }

    public function sendid_callback() {
        printf(
            '<input type="text" style="width: 210px" id="sendid" autocomplete="off" name="isms_auth_account_settings[sendid]" value="%s" required="required"/>',
            isset( $this->admin_auth_options['sendid'] ) ? esc_attr( $this->admin_auth_options['sendid']) : ''
        );
    }

    public function username_callback() {
        printf(
            '<input type="text" style="width: 210px" id="username" autocomplete="off" name="isms_auth_account_settings[username]" value="%s" required="required"/>',
            isset( $this->admin_auth_options['username'] ) ? esc_attr( $this->admin_auth_options['username']) : ''
        );
    }

    public function phone_callback() {
        printf(
            '<input type="text" style="width: 210px" id="ismsauthphone" autocomplete="off" name="isms_auth_account_settings[ismsauthphone]" value="%s" required="required"/>',
            isset( $this->admin_auth_options['ismsauthphone'] ) ? esc_attr( $this->admin_auth_options['ismsauthphone']) : ''
        );
    }

    public function password_callback() {
        printf(
            '<input type="password" style="width: 210px" id="password" autocomplete="off" name="isms_auth_account_settings[password]" value="%s" required="required"/>',
            isset( $this->admin_auth_options['password'] ) ? esc_attr( $this->admin_auth_options['password']) : ''
        );
    }

    public function form_selector_callback() {
        printf(
            '<input type="text" placeholder="e.g .body-page-id #form-id" style="width: 210px" id="form-selector" autocomplete="off" name="isms_auth_account_settings[form-selector]" value="%s" required="required"/>',
            isset( $this->admin_auth_options['form-selector'] ) ? esc_attr( $this->admin_auth_options['form-selector']) : ''
        );
    }

    public function submit_btn_selector_callback() {
        printf(
            '<input type="text" placeholder="e.g #submit-btn-id" style="width: 210px" id="submit-btn-selector" autocomplete="off" name="isms_auth_account_settings[submit-btn-selector]" value="%s" required="required"/>',
            isset( $this->admin_auth_options['submit-btn-selector'] ) ? esc_attr( $this->admin_auth_options['submit-btn-selector']) : ''
        );
    }

    public function create_mobile_field_callback() {?>
        <input type="radio" id="create-mobile-field-yes" autocomplete="off" name="isms_auth_account_settings[create-mobile-field]" value="yes" checked <?php checked("yes" , $this->admin_auth_options['create-mobile-field']); ?> />Yes
        <input type="radio" id="create-mobile-field-no" autocomplete="off" name="isms_auth_account_settings[create-mobile-field]" value="no"<?php checked("no" , $this->admin_auth_options['create-mobile-field']); ?> />No

        <?php
    }

    public function mobile_field_selector_callback() {
        printf(
            '<input type="text" placeholder="e.g #mobile-id" style="width: 210px;" id="mobile-field-selector" autocomplete="off" name="isms_auth_account_settings[mobile-field-selector]" value="%s" />',
            isset( $this->admin_auth_options['mobile-field-selector'] ) ? esc_attr( $this->admin_auth_options['mobile-field-selector']) : ''
        );
    }

    public function send_interval_callback() { ?>
        <input type="number" placeholder="e.g 3" min="1" id="send-interval" autocomplete="off" name="isms_auth_account_settings[send-interval]" value="<?php if(isset( $this->admin_auth_options['send-interval'] )){ echo $this->admin_auth_options['send-interval']; }else{ echo '3'; } ?>" required="required"/>
        <?php
    }

    public function OTP_template_callback() { ?>
        <textarea id="otp-template" cols="30" rows="5" name="isms_auth_account_settings[otp-template]" ><?php if(isset( $this->admin_auth_options['otp-template'] )){ echo $this->admin_auth_options['otp-template']; }else{ echo 'Your verification code is: iSMS_OTP_CODE'; } ?></textarea>,
        <?php
    }

    public function isms_auth_wc_mobile_register_field() {
        $form_selector = $this->admin_auth_options['form-selector'];
        $btn_selector = $this->admin_auth_options['submit-btn-selector'];
        $send_interval = $this->admin_auth_options['send-interval'];
    ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ">
        <input type="hidden" id="isms-otp-validated" name="is_otp_validated" value="<?php if(isset($_POST['is_otp_validated'])){ esc_attr_e($_POST['is_otp_validated']); }else { esc_attr_e('false'); } ?>"/>
        <input type="hidden" id="isms-auth-btn-selector" value="<?php esc_attr_e($btn_selector); ?>"/><input type="hidden" id="isms-auth-send-interval" value="<?php esc_attr_e($send_interval); ?>"/>
        <input type="hidden" id="isms-auth-form-selector" value="<?php esc_attr_e($form_selector); ?>"/>
        <input type="hidden" id="isms-auth-country-code" name="isms_auth_country_code" value="<?php if(isset($_POST['isms_auth_country_code'])){ esc_attr_e($_POST['isms_auth_country_code']); }else { esc_attr_e('60'); } ?>"/>
        <label for="text-phone"><?php _e( 'Phone', 'woocommerce' ); ?><span class="required">*</span></label>
        <input type="hidden" class="input-text" name="isms_reg_mobile_phone" id="isms_reg_mobile_phone" value="<?php esc_attr_e( $_POST['isms_reg_mobile_phone'] ); ?>" />
        <input type="tel" class="input-text" id="isms_reg_billing_phone" value="<?php esc_attr_e( $_POST['billing_phone'] ); ?>" />
        <div id="isms-otp-tr-holder" style="display: none">
            <input type="text" class="input-text" name="isms_reg_otp" id="isms_reg_otp" placeholder="Enter verification code sent"/>
            <br/>
            <input type="button" value="Resend OTP" id="isms-resend-otp">
            <input type="button" value="Verify OTP" id="isms-verify-otp">
           
        </div>
         <div class="isms-auth-response-holder" style="display:none"></div>
        <?php
    }

        /**
         * register fields Validating.
         */

    function isms_auth_validate_mobile_register_field( $username, $email, $validation_errors ) {
        if ( isset( $_POST['billing_phone'] ) && empty( $_POST['billing_phone'] ) ) {
            $validation_errors->add( 'billing_phone_error', __( '<strong>Error</strong>: Phone is required!', 'woocommerce' ) );
        }
        return $validation_errors;
    }

        /**
         * Below code save extra fields.
         */
    function isms_auth_save_mobile_register_field( $customer_id ) {
        if ( isset( $_POST['billing_phone'] ) ) {
                // Phone input filed which is used in WooCommerce
            update_user_meta( $customer_id, 'billing_phone', sanitize_text_field( $_POST['billing_phone'] ) );
        }
    }

        /*Non WooCommerce site*/
        /**
         * Front end registration
         */
    function isms_auth_footer_script (){
        $form_selector = $this->admin_auth_options['form-selector'];
        $btn_selector = $this->admin_auth_options['submit-btn-selector'];
        $send_interval = $this->admin_auth_options['send-interval'];
    ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#mobile-field-selector').closest('tr').fadeOut('slow');
                
                $('<table class="isms-table-otp"><tr><td>Mobile Number: </td><td><input type="hidden" id="isms-otp-validated" name="is_otp_validated" value="<?php if(isset($_POST['is_otp_validated'])){ esc_attr_e($_POST['is_otp_validated']); }else { esc_attr_e('false'); } ?>"/><input type="hidden" id="isms-auth-btn-selector" value="<?php esc_attr_e($btn_selector); ?>"/><input type="hidden" id="isms-auth-send-interval" value="<?php esc_attr_e($send_interval); ?>"/><input type="hidden" id="isms-auth-form-selector" value="<?php esc_attr_e($form_selector); ?>"/><input type="hidden" id="isms-auth-country-code" name="isms_auth_country_code" value="<?php if(isset($_POST['isms_auth_country_code'])){ esc_attr_e($_POST['isms_auth_country_code']); }else { esc_attr_e('60'); } ?>"/><input type="text" class="input-text" name="isms_reg_mobile_phone" id="isms_reg_mobile_phone" required="required" autocomplete="off" value="<?php if(isset($_POST['isms_hidden_reg_mobile_phone'])){ esc_attr_e($_POST['isms_hidden_reg_mobile_phone']); } ?>"/></td></tr><tr id="isms-otp-tr-holder" style="display:none"><td>Enter Verification Code sent</td><td><input type="text" class="input-text" name="isms_reg_otp" id="isms_reg_otp"/></td></tr><tr id="isms-otp-button-holder" style="display:none"><td colspan="2"><input type="button" value="Resend OTP" id="isms-resend-otp"><input type="button" value="Verify OTP" id="isms-verify-otp"></td></tr><tr><td colspan="2"><div class="isms-auth-response-holder" style="display:none"></div></td></tr></table>').insertBefore('<?php esc_attr_e($form_selector.' '.$btn_selector); ?>');
                    
                    $(document).on('click', 'ul#country-listbox li', function() {
                        $('#isms-auth-country-code').val($(this).attr('data-dial-code'));
                    });
            });
        </script>

    <?php }

    function isms_auth_exist_mobile_footer_script () {
        $mobile_field = $this->admin_auth_options['mobile-field-selector'];
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#mobile-field-selector').closest('tr').fadeIn('slow');
                $('#mobile-field-selector').val('<?php esc_attr_e($mobile_field); ?>');
            });
        </script>
    <?php }

    function isms_auth_exist_public_mobile_footer_script () {
        $form_selector = $this->admin_auth_options['form-selector'];
        $btn_selector = $this->admin_auth_options['submit-btn-selector'];
        $send_interval = $this->admin_auth_options['send-interval'];

        $mobile_field = $this->admin_auth_options['mobile-field-selector'];
        $hiddenfield = str_replace('#','',$mobile_field);

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('<table class="isms-table-otp"><tr><td><input type="hidden" id="create-mobile-selector" value="<?php esc_attr_e($mobile_field);?>"/><input type="hidden" id="create-mobile" value="<?php esc_attr_e($this->admin_auth_options['create-mobile-field']);?>"/></td><td><input type="hidden" id="isms-otp-validated" name="is_otp_validated" value="<?php if(isset($_POST['is_otp_validated'])){ esc_attr_e($_POST['is_otp_validated']); }else { esc_attr_e('false'); } ?>"/><input type="hidden" id="isms-auth-btn-selector" value="<?php  esc_attr_e($btn_selector); ?>"/><input type="hidden" id="isms-auth-send-interval" value="<?php  esc_attr_e($send_interval); ?>"/><input type="hidden" id="isms-auth-form-selector" value="<?php  esc_attr_e( $form_selector); ?>"/><input type="hidden" id="isms-auth-country-code" name="isms_auth_country_code" value="<?php if(isset($_POST['isms_auth_country_code'])){  esc_attr_e($_POST['isms_auth_country_code']); }else {  esc_attr_e('60'); } ?>"/></td></tr><tr id="isms-otp-tr-holder" style="display:none"><td>Enter Verification Code sent</td><td><input type="text" class="input-text" name="isms_reg_otp" id="isms_reg_otp"/></td></tr><tr id="isms-otp-button-holder" style="display:none"><td colspan="2"><input type="button" value="Resend OTP" id="isms-resend-otp"><input type="button" value="Verify OTP" id="isms-verify-otp"></td></tr><tr><td colspan="2"><div class="isms-auth-response-holder" style="display:none"></div></td></tr></table>').insertBefore('<?php  esc_attr_e($form_selector.' '.$btn_selector); ?>');

                $(document).on('click', 'ul#country-listbox li', function() {
                    $('#isms-auth-country-code').val($(this).attr('data-dial-code'));
                });

                $('#mobile-field-selector').closest('tr').fadeIn('slow');
                $('#mobile-field-selector').val('<?php esc_attr_e($mobile_field); ?>');
            });
        </script>
    <?php }
   
    private function format_message($otp,$message) {
        return str_replace('iSMS_OTP_CODE',$otp,$message);
    }
}

?>
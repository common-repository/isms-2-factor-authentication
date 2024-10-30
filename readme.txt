=== iSMS 2 Factor Authentication ===

Contributors: Mobiweb
Tags: Tags: sms,otp sms,form authenticator,sms verification
Requires at least: 5.2
Requires PHP: 5.6.20
Tested up to: 5.7.1
Stable tag: 1.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SMS Authenticator (SMS Verification) integration for your WordPress contact forms.

== Description ==

iSMS Authenticator plugin enables you to verify mobile number by adding a layer of SMS authentication into any contact form or registration form  before the form is submitted.

The iSMS Authenticator plugin will send one time OTP SMS to the user who entered mobile number before submitting the contact forms or registration forms. Once the mobile number is confirmed to be a true mobile number user, the form can be allowed to continue form submission.


iSMS Authenticator Wordpress plugin is free and we cover secure gateways for the SMS plugin. Sign up an [iSMS account](https://www.isms.com.my/register.php), top up some [SMS credits](https://www.isms.com.my/buy_reload.php) and you can start enjoy our SMS services in any of your Wordpress forms.

= PLUGIN FEATURES =
User contact form verification via 2FA (Two-Factor Authentication) verification SMS

iSMS Authenticator plugin has been tested with WordPress default theme Twenty Twenty along with several plugins ( versions at point of release ):

1. Contact Form 7
2. WPForms
3. WooCommerce ( My Account Registration Form )

iSMS Authenticator plugin is compatible with WooCommerce WordPress in verifying user when they register their mobile phone number in WooCommerce under "My Account"

iSMS Authenticator is suitable for any forms that requires SMS vertification from visitors.


== Installation ==

The easiest way to install iSMS Authenticator plugin is via WordPress Dashboard. Go to the "Plugins" screen, click "Add New", and search for "iSMS" in the WordPress Plugin Directory. Then, click "Install Now". Lastly, click "Activate" and you can start using the plugin!

== Manual installation for iSMS Authenticator Plugin ==
1. Upload 'isms-authenticator' directory to the "/wp-content/plugins/" directory e.g via FTP
2. Activate the plugin through the "Plugins" menu in WordPress
3. Enter username and password of valid [iSMS account](https://www.isms.com.my) in iSMS Settings page

== Plugin Configurations ==
1. Go to iSMS OTP Settings and fill in your iSMS account credentials.
2. Check for your contact form's Form ID selector and Submit button ID selector. We recommend using Google Chrome's inspect element. You can also insert the ID or class that you have assigned for your contact form.
3. Fill in the ID selector's name into Contact Form Selector and Submit Button Selector.
4. The OTP field will be added to your Wordpress website's contact form instantly.
5. When user tries to submit the contact form, user will receive OTP verification code SMS.

== Frequently Asked Questions ==

= Where do you get the username and password? =

You can create an iSMS account at [iSMS website](https://www.isms.com.my/register.php).

= How do you purchase credit? =

Click the Reload Credit link next to the balance amount or visit our [iSMS website](https://www.isms.com.my/buy_reload.php).

== Screenshots ==

1. iSMS OTP Settings
2. Contact Form ID
3. 2-Factor Authentication Settings
4. How to integrated iSMS into contact forms
5. How to integrated iSMS into contact forms
6. iSMS integrated into contact forms
7. OTP Verification SMS
8. OTP Verification SMS
9. OTP Verification SMS
10. iSMS integrated into WooCommerce Registration Form

== Changelog ==

Version 1.0
* Intial release.

== License ==

This plugin is Free Software, released and licensed under the GPL, version 2 (https://www.gnu.org/licenses/gpl-2.0.html).
You may use it free of charge for any purpose.
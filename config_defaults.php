<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

$defaults = array(
	'config' => array(
		'system_online' => array(
			'name' => 'system_online',
			'value' => 'all',
			'description' => 'system status',
			'type' => 'select|all,no submissions,admin only',
			'required' => '',
			'allowed' => ''
		),
		'offline_text' => array(
			'name' => 'offline_text',
			'value' => 'The [company_name] Submission Manager is currently offline.',
			'description' => 'text displayed when system is offline <span class="small">(HTML allowed)</span>',
			'type' => 'textarea',
			'required' => '',
			'allowed' => 'html'
		),
		'instruction_text' => array(
			'name' => 'instruction_text',
			'value' => '<p class="header">How to use the [company_name] Submission Manager:</p>' . "\n" . '<p><b>If you already have an account, you do not need to use this form.<br><img src="arrow_left_2.png" alt="arrow left" width="16" height="13" style="vertical-align: middle;"> Please login using the form on the left.</b></p>' . "\n" . '<ol>' . "\n" . '<li>Fill in all of your contact information. If you are submitting work for someone else, fill in <u>your</u> contact info and fill in the name of the person your are submitting for in the &ldquo;writer name&rdquo; field.</li>' . "\n" . '<li>Fill in the title(s) of the work(s) you are submitting. If you are submitting multiple works, separate titles with commas.</li>' . "\n" . '<li>Use the browse button to find the file on your computer that you would like to submit. Select the file and click on the <b>open</b> button. Your file will then appear in the &ldquo;file&rdquo; field.</li>' . "\n" . '<li>If you wish you can fill in the comments field with any additional information you&rsquo;d like to send, then click <b>submit</b>.</li>' . "\n" . '<li>You will then have the option to review your information and confirm that it is correct. Hit <b>continue</b> and you&rsquo;re done.</li>' . "\n" . '</ol>',
			'description' => 'text to be displayed above submission form <span class="small">(HTML allowed)</span>',
			'type' => 'textarea',
			'required' => '',
			'allowed' => 'html'
		),
		'submission_text' => array(
			'name' => 'submission_text',
			'value' => 'Thank you for your submission. You can check the status of your submission at any time by visiting [app_url] and logging into your account.',
			'description' => 'text to be displayed and emailed to user after submission <span class="small">(HTML allowed)</span>',
			'type' => 'textarea',
			'required' => '',
			'allowed' => 'html'
		),
		'redirect_url' => array(
			'name' => 'redirect_url',
			'value' => '',
			'description' => 'redirect URL after submission (leave blank for default confirmation)',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'test_mode' => array(
			'name' => 'test_mode',
			'value' => '',
			'description' => 'no emails are sent',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'timezone' => array(
			'name' => 'timezone',
			'value' => 0,
			'description' => 'local timezone',
			'type' => 'select|timezones',
			'required' => '',
			'allowed' => ''
		),
		'dst' => array(
			'name' => 'dst',
			'value' => 'Y',
			'description' => 'adjust for daylight saving time',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'company_name' => array(
			'name' => 'company_name',
			'value' => '',
			'description' => 'your company&rsquo;s name',
			'type' => 'text',
			'required' => 'Y',
			'allowed' => ''
		),
		'show_company_name' => array(
			'name' => 'show_company_name',
			'value' => 'Y',
			'description' => 'display company name in header',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'logo_path' => array(
			'name' => 'logo_path',
			'value' => '',
			'description' => 'path to your company&rsquo;s logo (relative to document root)',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'app_url' => array(
			'name' => 'app_url',
			'value' => '',
			'description' => 'absolute URL to Submission Manager',
			'type' => 'text',
			'required' => 'Y',
			'allowed' => ''
		),
		'fonts' => array(
			'name' => 'fonts',
			'value' => 'Arial, Times New Roman',
			'description' => 'comma separated list of fonts',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'font_size' => array(
			'name' => 'font_size',
			'value' => 10,
			'description' => 'base font size',
			'type' => 'text',
			'required' => '',
			'allowed' => 'zero'
		),
		'color_background' => array(
			'name' => 'color_background',
			'value' => 'white',
			'description' => 'color name or HEX value',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'color_foreground' => array(
			'name' => 'color_foreground',
			'value' => '#EFEFEF',
			'description' => 'color name or HEX value',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'color_form' => array(
			'name' => 'color_form',
			'value' => '#DFDFDF',
			'description' => 'color name or HEX value',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'color_text' => array(
			'name' => 'color_text',
			'value' => 'black',
			'description' => 'color name or HEX value',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'color_link' => array(
			'name' => 'color_link',
			'value' => '#325287',
			'description' => 'color name or HEX value',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'color_link_hover' => array(
			'name' => 'color_link_hover',
			'value' => 'red',
			'description' => 'color name or HEX value',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'border_radius' => array(
			'name' => 'border_radius',
			'value' => 5,
			'description' => 'rounding of borders (in pixels)',
			'type' => 'text',
			'required' => '',
			'allowed' => 'zero'
		),
		'submission_limit' => array(
			'name' => 'submission_limit',
			'value' => 0,
			'description' => 'limit of simutaneous submissions (0 for no limit)',
			'type' => 'text',
			'required' => '',
			'allowed' => 'zero'
		),
		'pagination_limit' => array(
			'name' => 'pagination_limit',
			'value' => 100,
			'description' => 'limit of results per page in admin area (0 for no limit)',
			'type' => 'text',
			'required' => '',
			'allowed' => 'zero'
		),
		'default_sort_order' => array(
			'name' => 'default_sort_order',
			'value' => 'ascending',
			'description' => 'default sort order',
			'type' => 'select|ascending,descending',
			'required' => '',
			'allowed' => ''
		),
		'upload_path' => array(
			'name' => 'upload_path',
			'value' => '',
			'description' => 'absolute server path for storing file uploads. use forward slashes (&ldquo;/&rdquo;) with trailing slash',
			'type' => 'text',
			'required' => 'Y',
			'allowed' => ''
		),
		'general_dnr_email' => array(
			'name' => 'general_dnr_email',
			'value' => '',
			'description' => 'general DO NOT REPLY email address for notifications',
			'type' => 'text',
			'required' => 'Y',
			'allowed' => ''
		),
		'admin_email' => array(
			'name' => 'admin_email',
			'value' => '',
			'description' => 'main administrator email address for help',
			'type' => 'text',
			'required' => 'Y',
			'allowed' => ''
		),
		'mail_method' => array(
			'name' => 'mail_method',
			'value' => 'mail',
			'description' => 'outgoing mail method',
			'type' => 'select|mail,sendmail,smtp',
			'required' => '',
			'allowed' => ''
		),
		'smtp_secure' => array(
			'name' => 'smtp_secure',
			'value' => '',
			'description' => 'SMTP security protocol',
			'type' => 'select|NULL,ssl,tls',
			'required' => '',
			'allowed' => ''
		),
		'smtp_port' => array(
			'name' => 'smtp_port',
			'value' => '',
			'description' => 'SMTP port number',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'smtp_auth' => array(
			'name' => 'smtp_auth',
			'value' => '',
			'description' => 'SMTP authorization',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'smtp_host' => array(
			'name' => 'smtp_host',
			'value' => '',
			'description' => 'SMTP host name',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'smtp_username' => array(
			'name' => 'smtp_username',
			'value' => '',
			'description' => 'SMTP user name',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'smtp_password' => array(
			'name' => 'smtp_password',
			'value' => '',
			'description' => 'SMTP password',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'submission_price' => array(
			'name' => 'submission_price',
			'value' => '0.00',
			'description' => 'submission price',
			'type' => 'text',
			'required' => '',
			'allowed' => 'zero'
		),
		'currency_symbol' => array(
			'name' => 'currency_symbol',
			'value' => '$',
			'description' => 'currency symbol',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'success_result_code' => array(
			'name' => 'success_result_code',
			'value' => 0,
			'description' => 'successful payment result code',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'payment_redirect_method' => array(
			'name' => 'payment_redirect_method',
			'value' => 'GET',
			'description' => 'payment variables method',
			'type' => 'select|GET,POST,cURL',
			'required' => '',
			'allowed' => ''
		),
		'cc_exp_date_format' => array(
			'name' => 'cc_exp_date_format',
			'value' => 'MMYYYY',
			'description' => 'credit card expiration date format',
			'type' => 'select|MMYYYY,MM-YYYY,YYYYMM,YYYY-MM',
			'required' => '',
			'allowed' => ''
		),
		'captcha_version' => array(
			'name' => 'captcha_version',
			'value' => 2,
			'description' => 'Google reCAPTCHA version',
			'type' => 'select|2,3',
			'required' => '',
			'allowed' => ''
		),
		'captcha_site_key' => array(
			'name' => 'captcha_site_key',
			'value' => '',
			'description' => 'Google reCAPTCHA site key',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'captcha_secret_key' => array(
			'name' => 'captcha_secret_key',
			'value' => '',
			'description' => 'Google reCAPTCHA secret key',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'mysqldump_path' => array(
			'name' => 'mysqldump_path',
			'value' => 'mysqldump',
			'description' => 'path to mysqldump',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'csp' => array(
			'name' => 'csp',
			'value' => 'default-src \'self\'; script-src \'self\' \'nonce-[nonce]\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';',
			'description' => 'Content Security Policy',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'exclude_countries' => array(
			'name' => 'exclude_countries',
			'value' => '',
			'description' => 'comma separated list of excluded country codes (use "USA_only" to exclude all but USA)',
			'type' => 'text',
			'required' => '',
			'allowed' => ''
		),
		'send_mail_staff' => array(
			'name' => 'send_mail_staff',
			'value' => 'Y',
			'description' => 'send staff email notifications',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'send_mail_contact' => array(
			'name' => 'send_mail_contact',
			'value' => 'Y',
			'description' => 'send submitter email notifications',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'allow_withdraw' => array(
			'name' => 'allow_withdraw',
			'value' => '',
			'description' => 'allow submitters to withdraw their own submissions',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'show_date_paid' => array(
			'name' => 'show_date_paid',
			'value' => '',
			'description' => 'show date paid on submissions page',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'show_payment_fields' => array(
			'name' => 'show_payment_fields',
			'value' => '',
			'description' => 'show credit card fields on submission forms',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		),
		'check_updates' => array(
			'name' => 'check_updates',
			'value' => 'Y',
			'description' => 'check for new versions of Submission Manager',
			'type' => 'checkbox',
			'required' => '',
			'allowed' => ''
		)
	),

	'action_types' => array(
		1 => array(
			'action_type_id' => 1,
			'name' => 'accept',
			'description' => '',
			'status' => 'accepted',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => 'Congratulations from [company_name]!',
			'body' => 'Dear [writer]:' . "\n\n" . 'Congratulations! Your submission "[title]" has been selected for publication in [company_name].' . "\n\n" . '[message]' . "\n\n" . 'Sincerely,' . "\n\n" . 'The Editors of [company_name]'
		),
		2 => array(
			'action_type_id' => 2,
			'name' => 'withdraw',
			'description' => '',
			'status' => 'withdrawn',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => 'Your submission to [company_name]',
			'body' => 'Dear [writer]:' . "\n\n" . 'Your submission "[title]" has been withdrawn from consideration by [company_name].' . "\n\n" . '[message]' . "\n\n" . 'Sincerely,' . "\n\n" . 'The Editors of [company_name]'
		),
		3 => array(
			'action_type_id' => 3,
			'name' => 'forward 1',
			'description' => '',
			'status' => 'received',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => '[company_name] submission #[submission_id] forwarded by [reader]',
			'body' => '[reader] has forwarded you the submission "[title]" by [writer]. Please visit [app_url] to log in and check your forwarded submissions.' . "\n\n" . '[message]'
		),
		4 => array(
			'action_type_id' => 4,
			'name' => 'forward 2',
			'description' => '',
			'status' => 'received',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => '[company_name] submission #[submission_id] forwarded by [reader]',
			'body' => '[reader] has forwarded you the submission "[title]" by [writer]. Please visit [app_url] to log in and check your forwarded submissions.' . "\n\n" . '[message]'
		),
		5 => array(
			'action_type_id' => 5,
			'name' => 'forward 3',
			'description' => '',
			'status' => 'received',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => '[company_name] submission #[submission_id] forwarded by [reader]',
			'body' => '[reader] has forwarded you the submission "[title]" by [writer]. Please visit [app_url] to log in and check your forwarded submissions.' . "\n\n" . '[message]'
		),
		6 => array(
			'action_type_id' => 6,
			'name' => 'forward 4',
			'description' => '',
			'status' => 'received',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => '[company_name] submission #[submission_id] forwarded by [reader]',
			'body' => '[reader] has forwarded you the submission "[title]" by [writer]. Please visit [app_url] to log in and check your forwarded submissions.' . "\n\n" . '[message]'
		),
		7 => array(
			'action_type_id' => 7,
			'name' => 'reject 1',
			'description' => '',
			'status' => 'declined',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => 'Your submission to [company_name]',
			'body' => 'Dear Writer:' . "\n\n" . 'We appreciate the opportunity to read your work, but unfortunately this submission was not a right fit for [company_name].' . "\n\n" . 'Thank you for trying us.' . "\n\n" . 'Sincerely,' . "\n\n" . 'The Editors of [company_name]'
		),
		8 => array(
			'action_type_id' => 8,
			'name' => 'reject 2',
			'description' => '',
			'status' => 'declined',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => 'Your submission to [company_name]',
			'body' => 'Dear [writer]:' . "\n\n" . 'Thank you for sending us "[title]". We really enjoyed this piece, but we didn\'t feel it was right for [company_name].' . "\n\n" . 'We hope that you will continue to send us your work.' . "\n\n" . 'Sincerely,' . "\n\n" . 'The Editors of [company_name]'
		),
		9 => array(
			'action_type_id' => 9,
			'name' => 'reject 3',
			'description' => '',
			'status' => 'declined',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => 'Your submission to [company_name]',
			'body' => 'Dear [writer]:' . "\n\n" . 'Thank you for sending us "[title]".' . "\n\n" . '[message]' . "\n\n" . 'Unfortunately this particular piece was not a right fit for [company_name], but we were very impressed by your writing. We hope that you will feel encouraged by this short note and send us something else.' . "\n\n" . 'We look forward to reading more.' . "\n\n" . 'Sincerely,' . "\n\n" . 'The Editors of [company_name]'
		),
		10 => array(
			'action_type_id' => 10,
			'name' => 'reject 4',
			'description' => '',
			'status' => 'declined',
			'active' => 'Y',
			'access_groups' => '1,2,3,4,5',
			'from_reader' => '',
			'subject' => 'Your submission to [company_name]',
			'body' => '[message]'
		)
	),

	'file_types' => array(
		'doc' => array('ext' => 'doc'),
		'docx' => array('ext' => 'docx'),
		'pdf' => array('ext' => 'pdf'),
		'rtf' => array('ext' => 'rtf'),
		'txt' => array('ext' => 'txt')
	),

	'fields' => array(
		'first_name' => array(
			'field' => 'first_name',
			'name' => 'first name',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'last_name' => array(
			'field' => 'last_name',
			'name' => 'last name',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'email' => array(
			'field' => 'email',
			'name' => 'email',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'company' => array(
			'field' => 'company',
			'name' => 'company',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => ''
		),
		'address1' => array(
			'field' => 'address1',
			'name' => 'address 1',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'address2' => array(
			'field' => 'address2',
			'name' => 'address 2',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => ''
		),
		'city' => array(
			'field' => 'city',
			'name' => 'city',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'state' => array(
			'field' => 'state',
			'name' => 'state',
			'type' => 'select',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 2,
			'enabled' => 'Y',
			'required' => ''
		),
		'zip' => array(
			'field' => 'zip',
			'name' => 'zip',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => ''
		),
		'country' => array(
			'field' => 'country',
			'name' => 'country',
			'type' => 'select',
			'section' => 'contact',
			'value' => 'USA',
			'maxlength' => 3,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'phone' => array(
			'field' => 'phone',
			'name' => 'phone',
			'type' => 'text',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => ''
		),
		'password' => array(
			'field' => 'password',
			'name' => 'password',
			'type' => 'password',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 72,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'password2' => array(
			'field' => 'password2',
			'name' => 'confirm password',
			'type' => 'password',
			'section' => 'contact',
			'value' => '',
			'maxlength' => 72,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'mailing_list' => array(
			'field' => 'mailing_list',
			'name' => 'join our mailing list',
			'type' => 'checkbox',
			'section' => 'contact',
			'value' => 'Y',
			'maxlength' => 1,
			'enabled' => 'Y',
			'required' => ''
		),
		'writer' => array(
			'field' => 'writer',
			'name' => 'writer name',
			'type' => 'text',
			'section' => 'submission',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => ''
		),
		'title' => array(
			'field' => 'title',
			'name' => 'submission title',
			'type' => 'text',
			'section' => 'submission',
			'value' => '',
			'maxlength' => 255,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'genre_id' => array(
			'field' => 'genre_id',
			'name' => 'genre',
			'type' => 'select',
			'section' => 'submission',
			'value' => '',
			'maxlength' => 100,
			'enabled' => 'Y',
			'required' => ''
		),
		'file' => array(
			'field' => 'file',
			'name' => 'file',
			'type' => 'file',
			'section' => 'submission',
			'value' => '',
			'maxlength' => 1048576,
			'enabled' => 'Y',
			'required' => 'Y'
		),
		'comments' => array(
			'field' => 'comments',
			'name' => 'comments',
			'type' => 'textarea',
			'section' => 'submission',
			'value' => '',
			'maxlength' => 3000,
			'enabled' => 'Y',
			'required' => ''
		),
		'cc_number' => array(
			'field' => 'cc_number',
			'name' => 'credit card number',
			'type' => 'text',
			'section' => 'payment',
			'value' => '',
			'maxlength' => 50,
			'enabled' => 'Y',
			'required' => ''
		),
		'cc_exp_month' => array(
			'field' => 'cc_exp_month',
			'name' => 'expiration month',
			'type' => 'select',
			'section' => 'payment',
			'value' => '',
			'maxlength' => 2,
			'enabled' => 'Y',
			'required' => ''
		),
		'cc_exp_year' => array(
			'field' => 'cc_exp_year',
			'name' => 'expiration year',
			'type' => 'select',
			'section' => 'payment',
			'value' => '',
			'maxlength' => 4,
			'enabled' => 'Y',
			'required' => ''
		),
		'cc_csc' => array(
			'field' => 'cc_csc',
			'name' => 'card security code',
			'type' => 'text',
			'section' => 'payment',
			'value' => '',
			'maxlength' => 4,
			'enabled' => 'Y',
			'required' => ''
		)
	),

	'genres' => array(
		1 => array(
			'genre_id' => 1,
			'name' => 'poetry',
			'submission_limit' => 0,
			'redirect_url' => '',
			'price' => '0.00',
			'active' => 'Y',
			'blind' => ''
		),
		2 => array(
			'genre_id' => 2,
			'name' => 'fiction',
			'submission_limit' => 0,
			'redirect_url' => '',
			'price' => '0.00',
			'active' => 'Y',
			'blind' => ''
		),
		3 => array(
			'genre_id' => 3,
			'name' => 'nonfiction',
			'submission_limit' => 0,
			'redirect_url' => '',
			'price' => '0.00',
			'active' => 'Y',
			'blind' => ''
		)
	),

	'groups' => array(
		'admin' => array(
			'name' => 'admin',
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		),
		'editor' => array(
			'name' => 'editor',
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		),
		1 => array(
			'name' => 1,
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		),
		2 => array(
			'name' => 2,
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		),
		3 => array(
			'name' => 3,
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		),
		4 => array(
			'name' => 4,
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		),
		5 => array(
			'name' => 5,
			'allowed_forwards' => 'admin,editor,1,2,3,4,5',
			'blind' => ''
		)
	)
);

foreach ($defaults['config'] as $key => $value) {$config_defaults[$key] = $value['value'];}
?>
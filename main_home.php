<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}
$form_type = 'submit';

if ($config['system_online'] == 'no submissions')
{
	if ($config['offline_text']) {echo '<p>' . replace_placeholders($config['offline_text']) . '</p>';}
	echo '<div class="small" style="display: inline-block; padding: 10px; background-color: ' . $config['color_foreground'] . ';">' . $no_submissions_text . '</div>';
	exit_error();
}

if (!$submit)
{
	echo replace_placeholders($config['instruction_text']);
	echo '<div style="margin-left: 100px;">';
	form_main();
	echo '</div>';
}
else
{
	form_hash('validate');
}

if ($submit == 'submit')
{
	if (!isset($_COOKIE['submgr_cookie_test'])) {$error_output = $no_cookies_text; exit_error();}
	$_SESSION['post'] = cleanup($_POST, 'strip_tags', 'stripslashes');
	$_SESSION['post_display'] = array_map('htmlspecialchars', $_SESSION['post']);
	if (isset($_FILES['file']) && $_FILES['file']['name']) {upload();} // run upload() if first time submit or re-submit with new file
	extract($_SESSION['post_display']);
	form_check();
	get_price();
	echo '<p>You entered:</p>' . display('html');
	form_confirmation();
	echo '<div style="margin-left: 100px;">';
	form_main();
	echo '</div>';
}

if ($submit == 'continue')
{
	if ($use_captcha) {process_captcha();}
	extract($_SESSION['post']);
	db_update('insert contact', 'insert submission');
	$_SESSION['post']['contact_id'] = $contact_id; // contact_id needs to be inserted for get_local_variables()
	if ($config['send_mail_staff']) {send_mail('staff', 'submissions');}
	if ($config['send_mail_contact']) {send_mail('contact', 'submission');}
	if ($config['redirect_url'] || (isset($genre_id) && $genres['all'][$genre_id]['redirect_url']))
	{
		get_price();
		if ($config['payment_redirect_method'] == 'POST' && (float) $price) {form_post();} else {redirect();}
	}
	echo '<b>[ submission successfully received ]</b>';
	if ($config['submission_text']) {echo '<br><br>Dear ' . $_SESSION['post_display']['first_name'] . ',<br><br>' . replace_placeholders($config['submission_text']);}
	echo '<br><br>Your account is now automatically setup.<br><img src="arrow_left_2.png" alt="arrow left" width="16" height="13" style="vertical-align: middle;"> You can use the form on the left to log in to your account using your email address and password.';
	if ($config['submission_limit'] != 1)
	{
		echo '<br><br><a href="' . $_SERVER['PHP_SELF'] . '?page=login&module=submit&first_submission=1"><b>submit another?</b></a>';
	}
	else
	{
		$keep = array('csrf_token');
		flush_session($keep); // cannot kill_session() because form_login() has already been rendered with form_hash()
	}
}
?>
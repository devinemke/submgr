<?php
// this breaks download() since files (TXT, CSV, SQL) are run through Tidy before dowload
// if (extension_loaded('tidy')) {ob_start('ob_tidyhandler');} else {ob_start();}
ob_start();

$header_called = false;
include('inc_common.php');
if ($continue && $page == 'login') {include('inc_login.php');}
include('header.php');

if (INSTALLED)
{
	if (!$configuration_status)
	{
		$config['system_online'] = 'admin only';
		$copy = 'Required general configuration settings missing or invalid!';
		if ($page == 'login') {$copy = '<div><a href="' . $_SERVER['PHP_SELF'] . '?page=login&module=configuration&submodule=general" class="notice">' . $copy . '</a></div>';} else {$copy = '<div class="notice">' . $copy . '</div>';}
		echo $copy . '<br>';
	}

	if ($config['system_online'] == 'admin only')
	{
		if (isset($_SESSION['contact']['access']) && $_SESSION['contact']['access'] == 'admin')
		{
			echo '';
		}
		else
		{
			if ($config['offline_text']) {echo '<p>' . replace_placeholders($config['offline_text']) . '</p>';}
			echo '<div class="small" style="display: inline-block; padding: 10px; background-color: ' . $config['color_foreground'] . ';">' . $admin_only_text . '</div>';
			exit_error();
		}
	}

	if (isset($_GET['result_code']))
	{
		echo '<div class="header">';
		if (strtolower($_GET['result_code']) == strtolower($config['success_result_code'])) {echo 'Your payment was successfully processed. Thank you for submitting to ' . htmlspecialchars($config['company_name']) . '.';} else {echo '<span class="notice">Your submission was received, but your payment was not processed successfully.<br>Please contact ' . mail_to($config['admin_email']) . ' for assistance.</span>';}
		echo '</div><br>';
	}
}

if ($output) {echo $output;}
if (file_exists('main_' . $page . '.php')) {include('main_' . $page . '.php');} else {exit_error('page not found');}
include('footer.php');

output_tidy();
?>
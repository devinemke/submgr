<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}
$header_called = true;

echo '<!DOCTYPE html>
<html lang="en">
<head>
<title>'; if ($config && $config['company_name']) {echo htmlspecialchars($config['company_name']) . ' ';} echo 'Submission Manager'; if (isset($page_title)) {echo ' [ ' . htmlspecialchars($page_title) . ' ]';} echo '</title>
<meta charset="UTF-8">
';

if (file_exists('favicon.ico')) {echo '<link rel="icon" href="favicon.ico">';}
if ($config) {include('css.php');}
if ($GLOBALS['db_connect'] || $page == 'install') {include('javascript.php');}
if (isset($use_captcha) && $use_captcha && $submit == 'submit' && ($page == 'home' || ($page == 'login' && $module == 'submit'))) {echo '<script src="https://www.google.com/recaptcha/api.js" nonce="' . $GLOBALS['nonce'] . '"></script>';}
if (isset($header_extra)) {echo $header_extra;}

echo '
</head>
<body>
';

if ($page == 'login' && ($module == 'account' || $module == 'submissions' || $module == 'contacts' || $module == 'reports'))
{
	echo '
	<div id="tooltip_div"></div>
	<script src="tooltip.js" nonce="' . $GLOBALS['nonce'] . '"></script>
	';
}

echo '
<table style="border-collapse: collapse; width: 100%;">
	<tr>
		<td style="width: 200px;'; if (defined('TEST_MAIL') && TEST_MAIL) {echo ' border: 2px solid red;';} echo '">
		';

			if ($config)
			{
				if ($config['test_mode']) {echo '<div class="small notice">[ TEST MODE ]</div>';}
				if ($config['logo_path'])
				{
					$logo = '';
					if (filter_var($config['logo_path'], FILTER_VALIDATE_URL)) {$get_headers = @get_headers($config['logo_path']);}
					if (isset($get_headers) && $get_headers && is_array($get_headers) && strpos($get_headers[0], '200') !== false) {$logo = 'url';}
					if ($logo != 'url' && file_exists($config['logo_path'])) {$logo = 'local';}
					if ($logo)
					{
						$image_size = @getimagesize($config['logo_path']);
						if ($image_size && is_array($image_size)) {$image_size = $image_size[3];} else {$image_size = '';}
						echo '<a href="' . $_SERVER['PHP_SELF'] . '?kill_session=1"><img src="' . $config['logo_path'] . '" alt="' . htmlspecialchars($config['company_name']) . ' logo" ' . $image_size . '></a><br>';
					}
				}
				if ($config['company_name'] && $config['show_company_name']) {echo '<div style="font-size: ' . $font_size_plus10 . 'pt; font-weight: bold;"><a href="' . $_SERVER['PHP_SELF'] . '?kill_session=1">' . htmlspecialchars($config['company_name']) . '</a></div>';}
			}

			echo '
			<div class="header">Submission Manager</div>

		</td>
		<td>
		';
			if ($page == 'login' && isset($_SESSION['login']) && $_SESSION['login'])
			{
				if (!isset($_SESSION['contact_display']['first_name'])) {$_SESSION['contact_display']['first_name'] = '???';}
				if (!isset($_SESSION['contact_display']['last_name'])) {$_SESSION['contact_display']['last_name'] = '???';}
				if (!isset($_SESSION['contact_display']['email'])) {$_SESSION['contact_display']['email'] = '???';}

				echo '
				you are logged in as:<br>
				<span class="header">' . $_SESSION['contact_display']['first_name'] . ' ' . $_SESSION['contact_display']['last_name'] . '</span> <b>&lt;' . $_SESSION['contact_display']['email'] . '&gt;</b><br>
				';

				if ($_SESSION['contact']['access']) {echo '<div class="small">access status: <b>' . $_SESSION['contact_display']['access'] . '</b></div>';}

				echo '
				<b>
				[ <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=logout">logout</a>';
				if (isset($access_grouping) && in_array($_SESSION['contact']['access'], $access_grouping['staff'])) {echo '<span style="margin: 0px 5px 0px 5px;">|</span><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&submodule=forwards">my forwards</a><span style="margin: 0px 5px 0px 5px;">|</span><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=contacts&single_contact=1&contact_id=' . $_SESSION['contact']['contact_id'] . '">my account</a>';}
				echo ' ]
				</b>
				';

				if ($_SESSION['contact']['access'] == 'admin' && $submit == 'login')
				{
					if ($config['check_updates'])
					{
						check_version('SubMgr', true);
						if ($version_remote != '???' && $version_local < $version_remote) {echo '<div class="small notice"><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=maintenance&submodule=versions" style="color: red;">new Submission Manager version available!</a></div>';}
					}

					check_version('structure');
					if ($version_structure && $version_structure < $version_local) {echo '<div class="small notice"><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=maintenance&submodule=update_structure" style="color: red;">Data Structure update required!</a></div>';}
				}
			}

		echo '
		</td>
		<td>
		';
			if ($page == 'login' && isset($_SESSION['login']) && $_SESSION['login'] && isset($access_grouping) && in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
			{
				if ($_SESSION['contact']['access'] == 'editor')
				{
					// so editors cannot go to configuration nor maintenance
					unset($modules_admin[array_search('configuration', $modules_admin)]);
					unset($modules_admin[array_search('maintenance', $modules_admin)]);
				}

				echo '
				<form action="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '" method="post" name="form_nav" id="form_nav">
				<label for="module" id="label_module">go to:</label>
				<select id="module" name="module" class="form_input" style="width: auto;">
				';

				foreach ($modules_admin as $value)
				{
					echo '<option value="' . $value . '"';
					if ($value == $module) {echo ' selected';}
					echo '>' . $value . '</option>' . "\n";
				}

				echo '
				</select>
				<input type="submit" value="Go" class="form_button" style="width: 35px;">
				<input type="hidden" name="submit_hidden" value="Go">
				</form>
				';
			}

		echo '
		</td>
		<td style="text-align: right;">
		';
			if ($page == 'login' && isset($_SESSION['login']) && $_SESSION['login'] && isset($access_grouping) && in_array($_SESSION['contact']['access'], $access_grouping['staff']))
			{
				$result = @mysqli_query($GLOBALS['db_connect'], 'SHOW TABLE STATUS') or exit_error('query failure: SHOW TABLE STATUS');
				echo '<table style="border-collapse: collapse; display: inline-block;">';
				while ($row = mysqli_fetch_assoc($result))
				{
					extract($row);
					$db_totals[$Name] = $Rows;
					if ($Name == 'actions' || $Name == 'contacts' || $Name == 'submissions') {echo '<tr style="text-align: right;"><td style="font-weight: bold; padding-right: 5px;">' . $Name . ':</td><td>' . $Rows . '</td></tr>' . "\n";}
				}
				echo '</table>';
			}

		echo '
		</td>
	</tr>
</table>

<hr>

<table style="border-collapse: collapse; width: 100%;">
<tr>
';

if ($display_login)
{
	echo '
	<td class="foreground" style="width: 200px; padding: 5px;">

		<div class="small" style="text-align: center;">Already have an account?<br>Please login.</div><br>';

		if ($page == 'home' && $submit == 'continue' && isset($_SESSION['post']['email'])) {$email = $_SESSION['post']['email'];} // for login form
		form_login();

	echo '
	</td>
	<td style="width: 20px;">
		&nbsp;
	</td>
	';
}

echo '
<td>
';
?>
<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

header('X-Frame-Options: SAMEORIGIN');
if (!isset($_COOKIE['submgr_cookie_test'])) {setcookie('submgr_cookie_test', 1);}
$session_name = 'submgr';
$getcwd = getcwd();
if ($getcwd) {$session_name .= '_' . sha1($getcwd);}
session_name($session_name);
$session_start = session_start();
$nonce = get_token();
form_hash('session'); // needed here so csrf_token exists in javascript
$_SERVER['PHP_SELF'] = htmlentities($_SERVER['PHP_SELF']);

$pages = array('home', 'login', 'install', 'help', 'error');
if (isset($_GET['page'])) {$page = htmlentities($_GET['page']);} else {$page = 'home';}
if (!in_array($page, $pages)) {$page = 'error';}
if ($page != 'home') {$page_title = $page;}
if (isset($_GET['kill_session'])) {kill_session('regenerate');} // session needed for form_hash()

if (isset($_REQUEST['module'])) {$module = htmlentities($_REQUEST['module']);} else {$module = '';}
if (isset($_REQUEST['submodule'])) {$submodule = htmlentities($_REQUEST['submodule']);} else {$submodule = '';}

if (isset($_POST['submit'])) {$submit = htmlentities($_POST['submit']);} else {$submit = '';}
if (isset($_POST['submit_hidden'])) {$submit = htmlentities($_POST['submit_hidden']);}
$submit_js = $submit; // needed for javascript because this changes downstream

$gm_timestamp = time();
$gm_date_time = gmdate('Y-m-d H:i:s', $gm_timestamp);
$gm_date = gmdate('Y-m-d', $gm_timestamp);
$gm_year = gmdate('Y', $gm_timestamp);
$output = '';
$notice = '';
$db_connect = false;
$configuration_status = true;
$config = array();
$display_login = true;
$continue = true;
$form_check = true;
$errors = array();
$password_length_min = 8; $password_length_max = 72; // needed here globally for install, will be overwritten by $fields
$no_submissions_text = 'Submission Manager is currently in <b>&ldquo;no submissions&rdquo;</b> mode.<br>Submitters and staff may log into their accounts however no new submissions are being accepted at this time.';
$admin_only_text = 'Submission Manager is currently in <b>admin only</b> mode.<br>Only the system administrators have access at this time.';
$no_cookies_text = '<b>Submission Manager</b> requires that cookies be enabled in your web browser. Please enable cookies and try again. You may also need to empty your browser cache and restart your browser.';

if (!$session_start) {$display_login = false; exit_error('session_start failed');}
if (file_exists('config_defaults.php')) {include('config_defaults.php'); $config = $config_defaults;} else {$display_login = false; exit_error('missing config_defaults.php');}
if (file_exists('config_db.php')) {include('config_db.php');} elseif (file_exists('config_db_default.php')) {include('config_db_default.php');} else {$display_login = false; exit_error('missing config_db.php');}
if (file_exists('db_schema.php')) {include('db_schema.php');} else {$display_login = false; exit_error('missing db_schema.php');}

$modules = array(
'account' => 'account summary',
'update' => 'update your account',
'submit' => 'submit your work',
'pay_submission' => 'pay for submission',
'logout' => 'logout'
);

$modules_admin = array(
'submissions',
'contacts',
'reports',
'configuration',
'maintenance'
);

$login_required_fields = array(
'submissions' => array('submitter_id', 'title'),
'actions' => array('reader_id', 'action_type_id')
);

$local_variables = array(
'contacts' => array(
	'contact_id',
	'first_name',
	'last_name',
	'name',
	'email',
	'company',
	'address1',
	'address2',
	'city',
	'state',
	'zip',
	'country',
	'phone'
	),
'submissions' => array(
	'submission_id',
	'genre_id'
	),
'payment' => array(
	'price',
	'cc_number',
	'cc_exp_month',
	'cc_exp_year',
	'cc_exp_date',
	'cc_csc',
	'hash',
	'timestamp',
	'result_code',
	'error'
)
);

function check_version($software, $get_remote = false)
{
	if ($software == 'PHP')
	{
		$version = PHP_VERSION;
		$min_version = '5.5.0';
	}

	if ($software == 'mySQL')
	{
		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT VERSION() AS version') or exit_error('query failure: SELECT VERSION');
		$row = mysqli_fetch_assoc($result);
		$version = $row['version'];
		$GLOBALS['version_mysql'] = $version;
		$min_version = '5.0.0';
	}

	if ($software == 'SubMgr')
	{
		$version = @file_get_contents('version.txt');
		if ($version) {$version_local = trim($version);} else {$version_local = '???';}
		$GLOBALS['version_local'] = $version_local;

		if ($get_remote)
		{
			$options = array('http' => array('user_agent' => $_SERVER['HTTP_USER_AGENT'], 'timeout' => 10.0, 'ignore_errors' => true));
			$context = stream_context_create($options);
			$version = @file_get_contents('https://www.submissionmanager.net/version.txt', false, $context);
			if ($version) {$version_remote = trim($version);} else {$version_remote = '???';}
			$GLOBALS['version_remote'] = $version_remote;
		}
	}

	if ($software == 'structure')
	{
		$result = @mysqli_query($GLOBALS['db_connect'], "SHOW TABLE STATUS LIKE 'config'") or exit_error('query failure: SHOW TABLE STATUS<br><br>' . mysqli_error($GLOBALS['db_connect']));
		$row = mysqli_fetch_assoc($result);
		$GLOBALS['version_structure'] = $row['Comment'];
	}

	if (isset($min_version) && version_compare($version, $min_version) < 0)
	{
		$GLOBALS['error_output'] = '<b>Submission Manager</b> requires ' . $software . ' version ' . $min_version . ' or higher. Please notify your system administrator.';
		$GLOBALS['display_login'] = false;
		exit_error();
	}
}

check_version('PHP');

if (!extension_loaded('mysqli'))
{
	$error_output = '<b>Submission Manager</b> requires that the mySQLi extension be enabled in your PHP configuration. Please notify your system administrator.';
	$display_login = false;
	exit_error();
}

function db_connect($db_host, $db_username, $db_password, $db_name = '', $db_port = '')
{
	$GLOBALS['mysqli_sql_exception'] = '';
	if (!$db_port) {$db_port = ini_get('mysqli.default_port');}

	try {$db_connect = @mysqli_connect($db_host, $db_username, $db_password, '', $db_port);}
	catch (mysqli_sql_exception $exception) {$GLOBALS['mysqli_sql_exception'] = $exception->getMessage();}

	try {if ($db_connect && $db_name) {$db_select = @mysqli_select_db($db_connect, $db_name);} else {$db_select = true;}}
	catch (mysqli_sql_exception $exception) {$GLOBALS['mysqli_sql_exception'] = $exception->getMessage();}

	if ($db_connect && $db_select) {$GLOBALS['db_connect'] = $db_connect;} else {$GLOBALS['db_connect'] = false;}
	if ($GLOBALS['db_connect'])
	{
		@mysqli_query($GLOBALS['db_connect'], 'SET NAMES "utf8"') or exit_error('query failure: SET NAMES utf8');
		@mysqli_set_charset($GLOBALS['db_connect'], 'utf8');
	}

	return $GLOBALS['db_connect'];
}

function insert_from_array($table, $array)
{
	foreach ($array as $value)
	{
		$sql_array = array();
		foreach ($value as $field_name => $field_value)
		{
			if ($field_value != '') {$field_value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $field_value) . "'";} else {$field_value = 'NULL';}
			$sql_array[] = $field_name . ' = ' . $field_value;
		}
		$sql = "INSERT INTO `$table` SET " . implode(', ', $sql_array);
		@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT INTO ' . $table);
	}
}

function reset_defaults($table, $name, $array = '')
{
	@mysqli_query($GLOBALS['db_connect'], "TRUNCATE `$table`") or exit_error('query failure: TRUNCATE ' . $table);
	if ($array) {insert_from_array($table, $array);}
	$GLOBALS['notice'] = $name . ' settings have been reset to the default values';
}

if (INSTALLED)
{
	foreach ($config_db as $key => $value)
	{
		if ($key != 'password' && $key != 'port' && $value == '')
		{
			$display_login = false;
			exit_error('missing database info from configuration file');
			break;
		}
	}

	if (!isset($config_db['port'])) {$config_db['port'] = '';}
	@db_connect($config_db['host'], $config_db['username'], $config_db['password'], $config_db['name'], $config_db['port']);

	if (!$GLOBALS['db_connect'])
	{
		$display_login = false;
		exit_error('Database Connection: ' . $GLOBALS['mysqli_sql_exception']);
	}
}
else
{
	$page = 'install';
	$display_login = false;
}

if ($GLOBALS['db_connect'])
{
	check_version('mySQL');

	function get_tables()
	{
		$GLOBALS['show_tables'] = array();
		$result = @mysqli_query($GLOBALS['db_connect'], 'SHOW TABLES') or exit_error('query failure: SHOW TABLES');
		while ($row = mysqli_fetch_row($result))
		{
			if (isset($GLOBALS['schema'][$row[0]])) {$GLOBALS['show_tables'][] = $row[0];}
		}
	}
	get_tables();

	// check for required tables
	$required_tables = array('config','contacts','submissions');
	foreach ($required_tables as $value)
	{
		if (!in_array($value, $show_tables))
		{
			$display_login = false;
			exit_error('required tables unavailable');
			break;
		}
	}

	function check_config($array)
	{
		extract($GLOBALS);

		$config_invalid = array();

		foreach ($array as $key => $value)
		{
			if ($value == '' && $defaults['config'][$key]['required'])
			{
				$missing[] = $key;
				$config_invalid[] = $key;
				$form_check = false;
			}

			if ($defaults['config'][$key]['allowed'] == 'zero' && !is_numeric($value))
			{
				$errors[] = $key . ' must be a numeric value';
				$config_invalid[] = $key;
				$form_check = false;
			}
		}

		if ($array['upload_path'])
		{
			$array['upload_path'] = str_replace('\\', '/', $array['upload_path']);
			if (substr($array['upload_path'], -1) != '/') {$array['upload_path'] .= '/';}

			if (!file_exists($array['upload_path']))
			{
				$errors[] = 'upload_path is an invalid path';
				$config_invalid[] = 'upload_path';
				$form_check = false;
			}
		}

		$emails = array();
		if ($array['general_dnr_email']) {$emails['general_dnr_email'] = $array['general_dnr_email'];}
		if ($array['admin_email']) {$emails['admin_email'] = $array['admin_email'];}

		if ($emails)
		{
			foreach ($emails as $key => $value)
			{
				if (!email_check($value))
				{
					if (is_numeric($key)) {$key = 'mail_admin_list';}
					$bad_mails[] = $key;
					$config_invalid[] = $key;
					$form_check = false;
				}
			}
		}

		if (isset($array['payment_redirect_method']) && $array['payment_redirect_method'] == 'cURL' && !extension_loaded('curl'))
		{
			$errors[] = 'cURL extension is not loaded';
			$form_check = false;
		}

		if (isset($array['captcha_site_key']) && isset($array['captcha_secret_key']))
		{
			if (($array['captcha_site_key'] && !$array['captcha_secret_key']) || (!$array['captcha_site_key'] && $array['captcha_secret_key']))
			{
				$errors[] = 'both captcha_site_key and captcha_secret_key must be set to use CAPTCHA';
				$form_check = false;
			}

			if ($array['captcha_site_key'] && $array['captcha_secret_key'] && !extension_loaded('curl'))
			{
				$errors[] = 'cURL extension must be loaded to use CAPTCHA';
				$form_check = false;
			}
		}

		if (!$form_check)
		{
			if (isset($bad_mails) && $bad_mails) {$errors[] = 'invalid email(s): ' . implode(', ', $bad_mails);}
			if (isset($missing) && $missing) {$errors[] = 'required field(s) missing: ' . implode(', ', $missing);}
			$notice = 'ERROR! The following errors were detected:<ul>';
			foreach ($errors as $value) {$notice .= '<li>' . $value . '</li>';}
			$notice .= '</ul>';

			if ($page == 'login' && $module == 'configuration' && $submodule == 'general') {$GLOBALS['notice'] = $notice;}
			$GLOBALS['configuration_status'] = false;
		}
		else
		{
			$GLOBALS['notice'] = '';
			$GLOBALS['configuration_status'] = true;
		}

		$GLOBALS['config_invalid'] = $config_invalid;
		return $array;
	}

	if ($page == 'login' && $module == 'configuration' && $submodule == 'general' && isset($_SESSION['contact']['access']) && $_SESSION['contact']['access'] == 'admin')
	{
		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			foreach ($_SESSION['config'] as $key => $value)
			{
				if (isset($_POST['config'][$key]))
				{
					$clean = trim($_POST['config'][$key]);
					$clean = stripslashes($clean);

					// allow HTML
					if ($defaults['config'][$key]['allowed'] != 'html') {$clean = strip_tags($clean);}

					// make "0" instead of NULL
					if ($defaults['config'][$key]['allowed'] == 'zero')
					{
						if ($key == 'submission_price') {$clean = preg_replace('/[^0-9.]/i', '', $clean);} else {$clean = preg_replace('/[^0-9]/i', '', $clean);}
						if ($clean == '') {$clean = 0;}
						if ($key == 'submission_price' && is_numeric($clean)) {$clean = number_format($clean, 2, '.', '');}
					}

					$post_config[$key] = $clean;
				}
				else
				{
					$post_config[$key] = '';
				}
			}

			// update CSP for captcha
			if (isset($post_config['captcha_site_key']) && isset($post_config['captcha_secret_key']) && $post_config['captcha_site_key'] && $post_config['captcha_secret_key'] && isset($post_config['csp']))
			{
				if (strpos($post_config['csp'], 'https://www.google.com/recaptcha/') === false)
				{
					$post_config['csp'] = str_replace('script-src \'self\'', 'script-src \'self\' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/', $post_config['csp']);
					$post_config['csp'] .= ' frame-src \'self\' https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/;';
				}
			}

			$post_config = check_config($post_config);

			// update db unconditionally so valid fields get written (even if form check fails)
			foreach ($post_config as $key => $value)
			{
				if (!in_array($key, $config_invalid))
				{
					if ($value === '') {$value = 'NULL';} else {$value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . "'";}
					$sql = 'UPDATE config SET value = ' . $value . " WHERE name = '" . $key . "'";
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE config');
				}
			}

			if ($configuration_status)
			{
				$notice = 'General configuration settings updated successfully';
			}
			else
			{
				$form_check = false;
			}
		}

		if ($submit == 'reset defaults')
		{
			foreach ($config_defaults as $key => $value) {$config_defaults_reset[$key] = array('name' => $key, 'value' => $value);}
			reset_defaults('config', 'General Configuration', $config_defaults_reset);
		}
	}

	function get_config()
	{
		$GLOBALS['config'] = array(); // flush out config

		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM config') or exit_error('query failure: SELECT config');
		if (mysqli_num_rows($result))
		{
			while ($row = mysqli_fetch_assoc($result)) {$GLOBALS['config'][$row['name']] = $row['value'];}
		}
		else
		{
			exit_error('empty configuration table');
		}
	}

	function compare_configs()
	{
		global $config_defaults, $config;

		$keys_config_defaults = array_keys($config_defaults);
		$keys_config = array_keys($config);
		$missing_configs = array_diff($keys_config_defaults, $keys_config);
		$extra_configs = array_diff($keys_config, $keys_config_defaults);

		$GLOBALS['missing_configs'] = $missing_configs;
		$GLOBALS['extra_configs'] = $extra_configs;
	}

	get_config();

	// check for missing/extra config rows to suppress errors before data structure update
	compare_configs();
	if ($missing_configs)
	{
		foreach ($missing_configs as $value) {$config[$value] = $config_defaults[$value];}
	}
	if ($extra_configs)
	{
		foreach ($extra_configs as $value) {unset($config[$value]);}
	}

	// extract needed for app_url and company_name global vars
	extract($config);

	$app_url_slash = $app_url;
	if (substr((string) $app_url_slash, -1) != '/') {$app_url_slash .= '/';}

	$upload_path_year = $config['upload_path'] . $gm_year . '/';

	// required config settings must be set
	if (!isset($post_config)) {$config = check_config($config);}

	if (isset($config['captcha_site_key']) && isset($config['captcha_secret_key']) && $config['captcha_site_key'] && $config['captcha_secret_key'] && extension_loaded('curl')) {$use_captcha = true;} else {$use_captcha = false;}
	if ($use_captcha && isset($config['captcha_version'])) {$captcha_version = $config['captcha_version'];} else {$captcha_version = 2;}

	if (isset($config['csp']) && $config['csp'])
	{
		$csp = str_replace('[nonce]', $GLOBALS['nonce'], $config['csp']);
		header('Content-Security-Policy: ' . $csp);
	}
}

if ($page == 'login' && isset($_SESSION['contact']['access']) && $_SESSION['contact']['access'] == 'admin')
{
	if ($module == 'configuration' && $submodule == 'action_types')
	{
		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			foreach ($_POST['action_types'] as $key => $value)
			{
				$value = cleanup($value, 'strip_tags', 'stripslashes');

				foreach ($value as $field_name => $field_value)
				{
					if ($field_name != 'description' && $field_name != 'status' && $field_value == '')
					{
						$form_check = false;
						$errors[$key] = $field_name;
					}
				}

				if (!isset($value['active'])) {$value['active'] = '';}
				if (!isset($value['from_reader'])) {$value['from_reader'] = '';}
				if (isset($value['access_groups'])) {$value['access_groups'] = implode(',', $value['access_groups']);} else {$value['access_groups'] = '';}

				$post_action_types_insert[$key] = $value;
			}

			if (!$form_check)
			{
				foreach ($_SESSION['action_types'] as $key => $value)
				{
					if (isset($post_action_types_insert[$key])) {$post_action_types[$key] = $post_action_types_insert[$key];} else {$post_action_types[$key] = $value;}
				}

				$notice = 'ERROR! The following fields were empty:<ul>';
				foreach ($errors as $key => $value) {$notice .= '<li>' . $_SESSION['action_types'][$key]['name'] . ' => ' . $value . '</li>';}
				$notice .= '</ul>';
			}
			else
			{
				foreach ($post_action_types_insert as $key => $value)
				{
					$sql_array = array();
					foreach ($value as $field_name => $field_value)
					{
						if ($field_value != '') {$field_value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $field_value) . "'";} else {$field_value = 'NULL';}
						$sql_array[] = $field_name . ' = ' . $field_value;
					}
					$sql = 'UPDATE action_types SET ' . implode(', ', $sql_array) . ' WHERE action_type_id = ' . $key;
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE action_types');
				}

				$notice = 'Action type settings updated successfully';
			}
		}

		if ($submit == 'reset defaults')
		{
			reset_defaults('action_types', 'Action Types', $defaults['action_types']);
		}
	}

	if ($module == 'configuration' && $submodule == 'file_types')
	{
		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			@mysqli_query($GLOBALS['db_connect'], 'TRUNCATE file_types') or exit_error('query failure: TRUNCATE file_types');
			asort($_POST['file_types']);
			$_POST['file_types'] = array_unique($_POST['file_types']);
			$_POST['file_types'] = cleanup($_POST['file_types'], 'strip_tags', 'stripslashes');

			foreach ($_POST['file_types'] as $value)
			{
				if ($value != '')
				{
					$value = str_replace(' ', '', $value);
					$value = preg_replace('/[^A-Za-z0-9]/i', '', $value);
					$value = substr($value, 0, 10);
					$value = strtolower($value);
					$sql = "INSERT INTO file_types SET ext = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . "'";
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT file_types');
				}
			}

			$notice = 'File Type settings updated successfully';
		}

		if ($submit == 'reset defaults')
		{
			reset_defaults('file_types', 'File Types', $defaults['file_types']);
		}

		if (isset($_GET['ext']) && $_GET['ext'] != '' && isset($_GET['delete']))
		{
			$ext = trim($_GET['ext']);
			@mysqli_query($GLOBALS['db_connect'], "DELETE FROM file_types WHERE ext = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $ext) . "'") or exit_error('query failure: DELETE file_types');
			$notice = 'File Type ' . $ext . ' deleted';
		}
	}

	if ($module == 'configuration' && $submodule == 'fields')
	{
		$fields_editable = array(
		'name' => 'text',
		'value' => 'text',
		'maxlength' => 'text',
		'enabled' => 'checkbox',
		'required' => 'checkbox'
		);

		$fields_checkbox_disabled = array('email', 'password', 'password2', 'cc_number', 'cc_exp_month', 'cc_exp_year', 'cc_csc');

		include_once('inc_lists.php');

		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			foreach ($_POST['fields'] as $key => $value)
			{
				$value = cleanup($value, 'strip_tags', 'stripslashes');
				if ($value['maxlength']) {$value['maxlength'] = preg_replace('/[^0-9]/i', '', $value['maxlength']);}
				if (isset($value['enabled'])) {$value['enabled'] = 'Y';} else {$value['enabled'] = '';}
				if (isset($value['required'])) {$value['required'] = 'Y';} else {$value['required'] = '';}
				if (!$value['enabled']) {$value['required'] = '';}
				if ($defaults['fields'][$key]['type'] == 'checkbox' && $value['value']) {$value['value'] = 'Y';}
				if ($key == 'country') {$value['value'] = preg_replace('/[^A-Z]/', '', $value['value']);}

				if (in_array($key, $fields_checkbox_disabled))
				{
					$value['enabled'] = $defaults['fields'][$key]['enabled'];
					$value['required'] = $defaults['fields'][$key]['required'];
				}

				if (!$value['name'])
				{
					$form_check = false;
					$errors[$key][] = 'name';
					$notices[] = 'ERROR: Field Names cannot be blank';
				}

				if (!is_numeric($value['maxlength']) || $value['maxlength'] == 0)
				{
					$form_check = false;
					$errors[$key][] = 'maxlength';
					$notices[] = 'ERROR: Field Max Lengths must be numeric and greater than 0';
				}

				if ($key == 'country' && $value['value'] && !isset($lists['countries'][$value['value']]))
				{
					$form_check = false;
					$errors[$key][] = 'value';
					$notices[] = 'ERROR: Unrecognized country code';
				}

				if (strpos($key, 'password') !== false && ($value['maxlength'] < 8 || $value['maxlength'] > 72))
				{
					$form_check = false;
					$errors[$key][] = 'maxlength';
					$notices[] = 'ERROR: Password Max Length must be between 8 and 72';
				}

				if ($key == 'file' && $value['maxlength'] > 4294967295)
				{
					$form_check = false;
					$errors[$key][] = 'maxlength';
					$notices[] = 'ERROR: File maximum size is 4294967295 (4 GB)';
				}

				$post_fields[$key] = $value;
			}

			if ($form_check)
			{
				foreach ($post_fields as $key => $value)
				{
					$sql_array = array();
					foreach ($value as $sub_key => $sub_value)
					{
						if ($sub_value != '') {$sub_value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $sub_value) . "'";} else {$sub_value = 'NULL';}
						$sql_array[] = $sub_key . ' = ' . $sub_value;
					}
					$sql = 'UPDATE fields SET ' . implode(', ', $sql_array) . ' WHERE field = "' . $key . '"';
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE fields');
				}

				$notice = 'Fields settings updated successfully';
			}
			else
			{
				$notices = array_unique($notices);
				$notice = implode('<br>', $notices);
			}
		}

		if ($submit == 'reset defaults')
		{
			reset_defaults('fields', 'Fields', $defaults['fields']);
		}
	}

	if ($module == 'configuration' && $submodule == 'groups')
	{
		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			foreach ($defaults['groups'] as $key => $value)
			{
				$sql = 'UPDATE `groups` SET ';

				if (isset($_POST['groups'][$key]))
				{
					if (isset($_POST['groups'][$key]['allowed_forwards'])) {$sql .= "allowed_forwards = '" . implode(',', $_POST['groups'][$key]['allowed_forwards']) . "', ";} else {$sql .= 'allowed_forwards = NULL, ';}
					if (isset($_POST['groups'][$key]['blind'])) {$sql .= "blind = 'Y'";} else {$sql .= 'blind = NULL';}
				}
				else
				{
					$sql .= 'allowed_forwards = NULL, blind = NULL';
				}

				$sql .= " WHERE name = '$key'";

				@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE groups');
				$notice = 'Group settings updated successfully';
			}
		}

		if ($submit == 'reset defaults')
		{
			reset_defaults('groups', 'Groups', $defaults['groups']);
		}
	}

	if ($module == 'configuration' && $submodule == 'genres')
	{
		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			if (!$_POST['genres']['new']['name']) {unset($_POST['genres']['new']);}

			foreach ($_POST['genres'] as $key => $value)
			{
				$value = cleanup($value, 'strip_tags', 'stripslashes');
				if ($value['submission_limit']) {$value['submission_limit'] = preg_replace('/[^0-9]/i', '', $value['submission_limit']);}
				if ($value['price']) {$value['price'] = preg_replace('/[^0-9.]/i', '', $value['price']);}

				if ($value['name'] != '')
				{
					$genre_names[] = strtolower($value['name']);
				}
				else
				{
					$form_check = false;
					$errors[$key][] = 'name';
					$notices[] = 'ERROR: Genre Names cannot be blank';
				}

				if ($value['submission_limit'] && !is_numeric($value['submission_limit']))
				{
					$form_check = false;
					$errors[$key][] = 'submission_limit';
					$notices[] = 'ERROR: Submission Limits must be numeric';
				}

				if ($value['price'] && !is_numeric($value['price']))
				{
					$form_check = false;
					$errors[$key][] = 'price';
					$notices[] = 'ERROR: Prices must be numeric';
				}

				if ($value['submission_limit'] && (int) $value['submission_limit'] > 255)
				{
					$form_check = false;
					$errors[$key][] = 'submission_limit';
					$notices[] = 'ERROR: Maximum submission limit is 255';
				}

				if ($value['price'] && (float) $value['price'] > 9999.99)
				{
					$form_check = false;
					$errors[$key][] = 'price';
					$notices[] = 'ERROR: Maximum price is $9999.99';
				}

				if ($value['submission_limit'] == '') {$value['submission_limit'] = '0';}
				if ($value['price'] == '') {$value['price'] = '0.00';} else {$value['price'] = number_format($value['price'], 2, '.', '');}
				if (!isset($value['active'])) {$value['active'] = '';}
				if (!isset($value['blind'])) {$value['blind'] = '';}

				$post_genres[$key] = $value;
			}

			$counts = array_count_values($genre_names);
			foreach ($counts as $key => $value)
			{
				if ($value > 1)
				{
					$form_check = false;
					$notices[] = 'ERROR: All genre names must be unique';
					break;
				}
			}

			if ($form_check)
			{
				foreach ($post_genres as $key => $value)
				{
					$sql_array = array();
					foreach ($value as $field_name => $field_value)
					{
						if ($field_value != '') {$field_value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $field_value) . "'";} else {$field_value = 'NULL';}
						$sql_array[] = $field_name . ' = ' . $field_value;
					}

					if ($key != 'new')
					{
						$sql = 'UPDATE genres SET ' . implode(', ', $sql_array) . ' WHERE genre_id = ' . $key;
						@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE genres');
					}
					else
					{
						$sql = 'INSERT INTO genres SET ' . implode(', ', $sql_array);
						@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT genres');
					}
				}

				$notice = 'Genre settings updated successfully';
				unset($post_genres['new']);
			}
			else
			{
				$notices = array_unique($notices);
				$notice = implode('<br>', $notices);
			}
		}

		if ($submit == 'reset defaults')
		{
			reset_defaults('genres', 'Genres', $defaults['genres']);

			// need to change submissions with orphaned genres
			$sql = 'UPDATE submissions SET genre_id = NULL WHERE genre_id NOT IN(' . implode(',', array_keys($defaults['genres'])) . ')';
			@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE submissions NULL genre_id');
		}

		if (isset($_GET['genre_id']) && $_GET['genre_id'] && is_numeric($_GET['genre_id']) && isset($_GET['delete']))
		{
			$genre_id = (int) trim($_GET['genre_id']);

			$sql = 'DELETE FROM genres WHERE genre_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $genre_id);
			@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: DELETE genre_id');

			// need to change submissions with orphaned genres
			$sql = 'UPDATE submissions SET genre_id = NULL WHERE genre_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $genre_id);
			@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE submissions NULL genre_id');

			$notice = 'Genre #' . $genre_id . ' deleted';
		}
	}

	if ($module == 'configuration' && $submodule == 'payment_vars')
	{
		if ($submit) {form_hash('validate');}

		if ($submit == 'update')
		{
			$out_in = array('out' => 0, 'in' => 0);
			$counts = array('$submission_id' => $out_in, '$result_code' => $out_in);

			foreach ($_POST['payment_vars'] as $key => $value)
			{
				$value = cleanup($value, 'strip_tags', 'stripslashes');

				if (is_numeric($key) && ($value['name'] == '' || $value['value'] == ''))
				{
					$form_check = false;
					$notice = 'ERROR: both name and value must not be blank';
				}

				if ($key == 'new')
				{
					if ($value['name'] == '' && $value['value'] == '')
					{
						echo '';
					}
					elseif ($value['name'] != '' && $value['value'] != '')
					{
						echo '';
					}
					else
					{
						$form_check = false;
						$notice = 'ERROR: both name and value must not be blank';
					}
				}

				if (isset($counts[$value['value']])) {$counts[$value['value']][$value['direction']]++;}

				$post_payment_vars[$key] = $value;
			}

			// check counts for $submission_id and $result_code
			if ($counts['$submission_id']['in'] > 1)
			{
				$form_check = false;
				$notice = 'ERROR: only one incoming submission_id payment variable can be used';
			}
			if ($counts['$result_code']['out'] > 0)
			{
				$form_check = false;
				$notice = 'ERROR: result_code must only be an incoming payment variable (not outgoing)';
			}
			if ($counts['$result_code']['in'] > 1)
			{
				$form_check = false;
				$notice = 'ERROR: only one incoming result_code payment variable can be used';
			}

			if ($form_check)
			{
				foreach ($post_payment_vars as $payment_key => $payment_value)
				{
					foreach ($payment_value as $payment_value_key => $payment_value_value) {$payment_value[$payment_value_key] = mysqli_real_escape_string($GLOBALS['db_connect'], $payment_value_value);}
					extract($payment_value);

					if ($payment_key == 'new')
					{
						if ($name == '' && $value == '') {break;}
						$sql_start = 'INSERT INTO';
						$sql_end = ", direction = '$direction'";
					}
					else
					{
						$sql_start = 'UPDATE';
						$sql_end = " WHERE payment_var_id = " . mysqli_real_escape_string($GLOBALS['db_connect'], $payment_key);
					}
					$sql = $sql_start . " payment_vars set name = '$name', value = '$value'" . $sql_end;
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT payment_vars');
				}

				$notice = 'Payment variables settings updated successfully';
			}
		}

		if ($submit == 'reset defaults')
		{
			reset_defaults('payment_vars', 'Payment Variables');
			@mysqli_query($GLOBALS['db_connect'], 'ALTER TABLE `payment_vars` COMMENT = ""') or exit('query failure: ALTER TABLE payment_vars COMMENT');

			$payment_vars_config = array(
			'payment_redirect_method',
			'success_result_code',
			'cc_exp_date_format',
			'show_date_paid',
			'show_payment_fields'
			);

			foreach ($payment_vars_config as $value)
			{
				if ($config_defaults[$value] == '') {$value_sql = 'NULL';} else {$value_sql = "'" . $config_defaults[$value] . "'";}
				$sql = "UPDATE config SET value = $value_sql WHERE name = '$value'";
				$result = mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT config FROM payment_vars_preset');
			}
		}

		if (isset($_GET['payment_var_id']) && $_GET['payment_var_id'] && is_numeric($_GET['payment_var_id']) && isset($_GET['delete']))
		{
			$payment_var_id = (int) trim($_GET['payment_var_id']);
			@mysqli_query($GLOBALS['db_connect'], 'DELETE FROM payment_vars WHERE payment_var_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $payment_var_id)) or exit_error('query failure: DELETE payment variable');
			$notice = 'Payment Variable #' . $payment_var_id . ' deleted';
		}
	}
}

if (INSTALLED && $GLOBALS['db_connect'])
{
	get_genres();
	get_file_types();
	get_fields();
	get_action_types();

	// needed to suppress errors before version 3.41 update
	if (isset($fields['password']['maxlength'])) {$password_length_max = $fields['password']['maxlength'];}
	if (isset($fields['file']['maxlength'])) {$max_file_size_formatted = get_bytes_formatted($fields['file']['maxlength']);}

	$timezone = $config['timezone'];
	$timezone_safe = (float) $timezone;
	$is_DST = 0;
	if ($config['dst'] && date('I', $gm_timestamp) == 1) {$timezone += 1; $is_DST = 1;}
	if ($timezone >= 0) {$timezone = '+' . $timezone;}
	if (strpos($timezone, '.5') !== false) {$timezone = str_replace('.5', ':30', $timezone);} else {$timezone .= ':00';}
	$local_date_time = timezone_adjust($gm_date_time) . ' (GMT ' . $timezone . ')';
	$local_date = substr($local_date_time, 0, 10);

	@mysqli_query($GLOBALS['db_connect'], 'SET time_zone = "' . $timezone . '"') or exit_error('query failure: SET time_zone');
	@$timezone_name = timezone_name_from_abbr('', $timezone_safe * 3600, $is_DST);
	@date_default_timezone_set($timezone_name);
}

function get_genres()
{
	global $show_tables;

	$GLOBALS['genres'] = array();
	if (in_array('genres', $show_tables))
	{
		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM genres ORDER BY genre_id') or exit_error('query failure: SELECT genres');
		if ($result && mysqli_num_rows($result))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$GLOBALS['genres']['all'][$row['genre_id']] = $row;
				if ($row['active']) {$GLOBALS['genres']['active'][$row['genre_id']] = $row['genre_id'];}
				if ((float) $row['price']) {$GLOBALS['genres']['price'][$row['genre_id']] = $row['genre_id'];} // string "0.00" returns TRUE
			}
			$_SESSION['genres'] = $GLOBALS['genres'];
		}
	}
}

function get_file_types()
{
	global $show_tables;

	$GLOBALS['file_types'] = array();
	if (in_array('file_types', $show_tables))
	{
		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM file_types ORDER BY ext') or exit_error('query failure: SELECT file_types');
		if ($result && mysqli_num_rows($result))
		{
			while ($row = mysqli_fetch_assoc($result)) {$GLOBALS['file_types'][] = $row['ext'];}
			$_SESSION['file_types'] = $GLOBALS['file_types'];
		}
	}
}

function get_fields()
{
	global $show_tables;

	$GLOBALS['fields'] = array();
	if (in_array('fields', $show_tables))
	{
		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM `fields`') or exit_error('query failure: SELECT fields');
		if ($result && mysqli_num_rows($result))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$row['error'] = '';
				if ($row['field'] == 'state') {$row['list'] = 'states';}
				if ($row['field'] == 'country') {$row['list'] = 'countries';}
				if ($row['field'] == 'genre_id') {$row['list'] = 'genres';}
				if ($row['field'] == 'cc_exp_month') {$row['list'] = 'months';}
				if ($row['field'] == 'cc_exp_year') {$row['list'] = 'years';}
				$GLOBALS['fields'][$row['field']] = $row;
			}
			$_SESSION['fields'] = $GLOBALS['fields'];
		}
	}
}

function get_groups()
{
	global $show_tables;

	$GLOBALS['groups'] = array();
	if (in_array('groups', $show_tables))
	{
		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM `groups`') or exit_error('query failure: SELECT groups');
		if ($result && mysqli_num_rows($result))
		{
			while ($row = mysqli_fetch_assoc($result)) {$GLOBALS['groups'][$row['name']] = $row;}
			$_SESSION['groups'] = $GLOBALS['groups'];
		}
	}
}

function get_action_types()
{
	$GLOBALS['action_types'] = array();
	$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM action_types ORDER BY action_type_id');
	if ($result && mysqli_num_rows($result))
	{
		while ($row = mysqli_fetch_assoc($result))
		{
			$GLOBALS['action_types']['all'][$row['action_type_id']] = $row;
			if ($row['active']) {$GLOBALS['action_types']['active'][] = $row['action_type_id'];}
			$GLOBALS['action_types']['keynames'][$row['name']] = $row['action_type_id'];
			if (strpos($row['name'], 'forward') !== false) {$GLOBALS['action_types']['forwards'][] = $row['action_type_id'];}
			if (strpos($row['name'], 'reject') !== false) {$GLOBALS['action_types']['rejects'][] = $row['action_type_id'];}
		}
	}
}

function get_bytes_formatted($bytes)
{
	$bytes_formatted = '';

	$size_array = array(1 => 'B', 1024 => 'KB', 1048576 => 'MB', 1073741824 => 'GB', 1099511627776 => 'TB');
	foreach ($size_array as $key => $value)
	{
		if ($bytes >= $key) {$bytes_formatted = number_format(round($bytes / $key, 2), 2) . ' ' . $value;}
	}

	return $bytes_formatted;
}

function timezone_adjust($date_time)
{
	global $config;

	$timestamp = strtotime($date_time);
	$timestamp += $config['timezone'] * 3600;
	if ($config['dst'] && date('I', $timestamp) == 1) {$timestamp += 3600;}
	$date_time = date('Y-m-d H:i:s', $timestamp);

	return $date_time;
}

function exit_error($error = '')
{
	extract($GLOBALS);

	$continue = false;
	include_once('header.php');
	if ($error)
	{
		echo 'We&rsquo;re Sorry,<br><br>We are experiencing temporary difficulties completing your request at this time. Please try again later.';
		if (isset($config['admin_email']) && $config['admin_email']) {echo '<br>If your problem persists, please contact ' . mail_to($config['admin_email']) . '.';}
		echo '<br><br>error: <b>' . $error . '</b>';
	}
	if (isset($error_output)) {echo $error_output;}
	include_once('footer.php');
	exit();
}

function kill_session($arg = '')
{
	global $session_name;

	$_SESSION = array();

	if (ini_get('session.use_cookies') && $arg != 'regenerate')
	{
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}

	session_destroy();

	if ($arg == 'regenerate')
	{
		session_name($session_name);
		session_start();
		form_hash('session');
	}
}

function flush_session($keep)
{
	foreach ($_SESSION as $key => $value) {if (!in_array($key, $keep)) {unset($_SESSION[$key]);}}
}

function get_payment_vars()
{
	$payment_vars['out'] = array();
	$payment_vars['in'] = array();
	$payment_vars_count = 0;

	$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM payment_vars ORDER BY payment_var_id') or exit_error('query failure: SELECT payment_vars');
	if ($result && mysqli_num_rows($result))
	{
		while ($row = mysqli_fetch_assoc($result))
		{
			$payment_vars[$row['direction']][$row['payment_var_id']] = $row;
			$payment_vars_count++;
		}
	}

	$GLOBALS['payment_vars'] = $payment_vars;
	$GLOBALS['payment_vars_count'] = $payment_vars_count;
}

function get_local_variables($arg)
{
	extract($GLOBALS);
	$local_variables_merged = array_merge($local_variables['contacts'], $local_variables['submissions'], $local_variables['payment']);

	foreach ($local_variables_merged as $value)
	{
		if (isset($arg[$value]) && $arg[$value] != '') {$local_variables_flat[$value] = $arg[$value];}
	}

	if ($config['show_payment_fields'])
	{
		$local_variables_flat['cc_number'] = $cc_number;
		$local_variables_flat['cc_exp_month'] = $cc_exp_month;
		$local_variables_flat['cc_exp_year'] = $cc_exp_year;
		if ($config['cc_exp_date_format'] == 'MMYYYY') {$local_variables_flat['cc_exp_date'] = $cc_exp_month . $cc_exp_year;}
		if ($config['cc_exp_date_format'] == 'MM-YYYY') {$local_variables_flat['cc_exp_date'] = $cc_exp_month . '-' . $cc_exp_year;}
		if ($config['cc_exp_date_format'] == 'YYYYMM') {$local_variables_flat['cc_exp_date'] = $cc_exp_year . $cc_exp_month;}
		if ($config['cc_exp_date_format'] == 'YYYY-MM') {$local_variables_flat['cc_exp_date'] = $cc_exp_year . '-' . $cc_exp_month;}
		$local_variables_flat['cc_csc'] = $cc_csc;
	}

	$local_variables_flat['name'] = $arg['first_name'] . ' ' . $arg['last_name'];
	$local_variables_flat['submission_id'] = $submission_id;
	$local_variables_flat['price'] = $price;
	$local_variables_flat['timestamp'] = $gm_timestamp;
	$local_variables_flat['result_code'] = 0;

	$GLOBALS['local_variables_flat'] = $local_variables_flat;
}

function form_main()
{
	if (!ini_get('file_uploads')) {exit_error('Your web server is not configured to accept file uploads.');}
	include_once('inc_lists.php');
	extract($GLOBALS);

	function display_form_row($key, $value)
	{
		global $config, $fields, $genres, $submit, $form_type;
		$output = '';
		$extra_tr = '';
		$extra_before = '';
		$extra_after = '';
		$class = '';
		if (isset($_GET['address_check']) && isset($_SESSION['address_check_fields']) && in_array($key, $_SESSION['address_check_fields'])) {$class = 'error';}

		if ($key == 'writer') {$extra_after = ' <span class="small">(if different from above)</span>';}

		if ($key == 'file')
		{
			if ($fields['file']['maxlength']) {$extra_before = '<input type="hidden" name="MAX_FILE_SIZE" value="' . $fields['file']['maxlength'] . '">'; $extra_after .= ' <span class="small">(' . $GLOBALS['max_file_size_formatted'] . ' max)</span>';}
			if (isset($_SESSION['file_upload']['filename'])) {$extra_after .= '<span class="small" style="margin-left: 5px;">file selected: <b>' . $_SESSION['file_upload']['filename'] . '</b></span>';}
		}

		if ($key == 'genre_id' && isset($genres['active']))
		{
			if (isset($_GET['genre_id']) && $_GET['genre_id'] && isset($genres['all'][$_GET['genre_id']]) && !$submit) {$GLOBALS['genre_id'] = (int) $_GET['genre_id'];}
			foreach ($genres['active'] as $sub_value) {$genres_form[$sub_value] = $genres['all'][$sub_value]['name'];}
			$GLOBALS['genres'] = $genres_form;
		}

		if ($key == 'comments' && $value['maxlength']) {$extra_after = ' <span class="small">(' . $value['maxlength'] . ' characters max)</span>';}

		if (strpos($key, 'cc_') !== false) {$extra_tr = ' style="display: none;" id="row_' . $key . '"';}
		if ($key == 'cc_number') {$extra_after = ' price: ' . $config['currency_symbol'] . '<span style="font-weight: bold;" id="price_display">0.00</span>';}

		if ($form_type == 'submit' && !$submit && $value['value']) {$GLOBALS[$key] = htmlspecialchars($value['value']);}

		$output .= '<tr' . $extra_tr . '><td class="row_left"><label for="' . $key . '" id="label_' . $key . '" class="' . $class . '">' . $value['name'] . ':</label></td><td>' . $extra_before;
		if ($value['type'] == 'text' || $value['type'] == 'password' || $value['type'] == 'file') {$output .= '<input type="' . $value['type'] . '" id="' . $key . '" name="' . $key . '"'; if ($value['type'] != 'file') {$output .= ' value="'; if (isset($GLOBALS[$key])) {$output .= $GLOBALS[$key];} $output .= '" maxlength="' . $value['maxlength'] . '"';} $output .=' class="' . $class . '">';}
		if ($value['type'] == 'select') {$output .= '<select id="' . $key . '" name="' . $key . '" class="' . $class . '">'; if ($key != 'genre_id') {$output .= '<option value="">&nbsp;</option>';} foreach ($GLOBALS[$value['list']] as $sub_key => $sub_value) {$output .= '<option value="' . $sub_key . '"'; if (isset($GLOBALS[$key]) && $GLOBALS[$key] == $sub_key) {$output .= ' selected';} $output .= '>' . $sub_value . '</option>' . "\n";} $output .= '</select>';}
		if ($value['type'] == 'checkbox') {$output .= '<input type="' . $value['type'] . '" id="' . $key . '" name="' . $key . '" value="Y"'; if (isset($GLOBALS[$key]) && $GLOBALS[$key]) {$output .= ' checked';} $output .= '>';}
		if ($value['type'] == 'textarea') {$output .= '<textarea id="' . $key . '" name="' . $key . '" maxlength="' . $value['maxlength'] . '">'; if (isset($GLOBALS[$key])) {$output .= $GLOBALS[$key];} $output .= '</textarea>';}
		$output .= $extra_after . '</td></tr>' . "\n";

		return $output;
	}

	function display_form_block($section)
	{
		$extra_tr = '';
		if ($section == 'payment') {$extra_tr = ' style="display: none;"';}
		$output = '<tr' . $extra_tr . ' id="header_' . $section . '"><td>&nbsp;</td><td class="header" style="padding-top: 20px;">' . ucfirst($section) . ':</td></tr>' . $GLOBALS['form_sections'][$section];
		return $output;
	}

	foreach ($fields as $key => $value)
	{
		if (!$value['enabled']) {unset($fields[$key]);}
	}

	if (isset($config['exclude_countries']) && $config['exclude_countries'])
	{
		if ($config['exclude_countries'] == 'USA_only')
		{
			$GLOBALS['countries'] = array('USA' => 'United States');
			$GLOBALS['country'] = 'USA';
		}
		else
		{
			$exclude_countries = explode(',', $config['exclude_countries']);
			foreach ($GLOBALS['countries'] as $key => $value)
			{
				if (in_array($key, $exclude_countries)) {unset($GLOBALS['countries'][$key]);}
			}
		}
	}

	if (isset($_SESSION['post']['password']) && $_SESSION['post']['password']) {$GLOBALS['password'] = $_SESSION['post']['password'];} else {$GLOBALS['password'] = '';}
	if (!isset($genres['active'])) {unset($fields['genre_id']);}

	$GLOBALS['form_sections'] = array('contact' => '', 'submission' => '', 'payment' => '');
	foreach ($fields as $key => $value) {$GLOBALS['form_sections'][$value['section']] .= display_form_row($key, $value);}

	$action = $_SERVER['PHP_SELF'] . '?page=' . $page;
	if ($page == 'login') {$action .= '&module=' . $module;}
	if ($form_type == 'submit' || $form_type == 'login_submit') {$enctype = ' enctype="multipart/form-data"';} else {$enctype = '';}

	echo '
	<form action="' . $action . '" method="post" name="form_main" id="form_main" autocomplete="off"' . $enctype . '>
	<table class="padding_lr_5">
	';

	if ($form_type == 'submit' || $form_type == 'update')
	{
		echo display_form_block('contact');
	}

	if ($form_type == 'submit' || $form_type == 'login_submit')
	{
		echo display_form_block('submission');
		echo display_form_block('payment');
	}

	if ($form_type == 'pay_submission')
	{
		echo display_form_block('payment');
	}

	echo '
	<tr>
	<td>&nbsp;</td>
	<td><input type="submit" id="form_main_submit" name="submit" value="submit" class="form_button" style="margin-top: 10px;">
	';

	if ($page == 'login') {echo ' <input type="submit" id="form_main_cancel" name="submit" value="cancel" class="form_button" style="margin-top: 10px;">';}

	echo '
	</td>
	</tr>
	</table>
	</form>
	';
}

function form_confirmation()
{
	extract($GLOBALS);

	$submit_value = 'continue';
	if ($page == 'login' && $module == 'update') {$submit_value = 'save changes';}

	$action = $_SERVER['PHP_SELF'];
	if ($page == 'login') {$action .= '?page=' . $page . '&module=' . $module;}

	$button = '<button type="submit" id="form_confirmation_submit" name="submit" value="continue" class="form_button" style="margin-left: 5px;">' . $submit_value . '</button>';

	echo '
	<br>
	<form action="' . $action . '" method="post" name="form_confirmation" id="form_confirmation">
	';

	if ($submit_value == 'continue' && $use_captcha)
	{
		if ($captcha_version == 2)
		{
			echo '
			<p>Now, please take a moment to verify that you are not a robot. This step is necessary to process your submission.</p>
			<div class="g-recaptcha" id="g-recaptcha" data-sitekey="' . $config['captcha_site_key'] . '"></div>
			';
		}

		if ($captcha_version == 3)
		{
			$button = '<button id="form_confirmation_submit" class="form_button g-recaptcha" data-sitekey="' . $config['captcha_site_key'] . '" data-callback="onSubmit" data-action="submit" style="margin: 10px 0px 0px 0px;">' . $submit_value . '</button>';
		}
	}

	// fixed submit_hidden/form_hash needed here for captcha_version 3 which does not go through disable_submit()
	echo '
	<p>If the above information is correct, click ' . $button . '</p>
	<p>If you wish to make changes, <a href="#" id="form_main_show"><b>click here</b></a>, update the form below, and hit <b>submit</b>.</p>
	<input type="hidden" id="form_confirmation_submit_hidden_fixed" name="submit_hidden" value="continue">
	<input type="hidden" id="form_confirmation_hash_fixed" name="form_hash" value="' . $_SESSION['csrf_token'] . '">
	</form>
	';

	if ($submit_value == 'continue' && $use_captcha && $captcha_version == 3) {echo '<script nonce="' . $GLOBALS['nonce'] . '">function onSubmit(token) {document.getElementById("form_confirmation").submit();}</script>';}
}

function form_login()
{
	extract($GLOBALS);

	if ($notice) {echo '<div class="notice">' . $notice . '</div>';}
	if (!isset($email)) {$email = '';}
	if (isset($_REQUEST['email']) && $_REQUEST['email']) {$email = htmlspecialchars(trim($_REQUEST['email']));}

	echo '
	<form action="' . $_SERVER['PHP_SELF'] . '?page=login" method="post" name="form_login" id="form_login">
	<table class="foreground small" style="width: 190px; border-spacing: 5px; border: 1px solid ' . $config['color_text'] . ';">
	<tr>
	<td>
	<label for="login_email" id="label_login_email">email:</label><br><input type="text" id="login_email" name="login_email" value="' . $email . '" maxlength="50" style="width: 180px;"><br>
	<label for="login_password" id="label_login_password">password:</label><br><input type="password" id="login_password" name="login_password" maxlength="' . $password_length_max . '" style="width: 180px;">
	<br>
	<div style="text-align: center;">
	<input type="submit" id="form_login_submit" name="submit" value="login" class="form_button" style="width: 50px; margin-top: 5px;">
	';

	if (isset($_SESSION['goto_config'])) {echo '<input type="hidden" name="goto_config" value="Y">';}

	echo '
	<br>
	<br>
	<a href="' . $_SERVER['PHP_SELF'] . '?page=help">need help?</a>
	</div>
	</td>
	</tr>
	</table>
	</form>
	';
}

function form_post()
{
	extract($GLOBALS);

	$action = '';
	$hidden = '';
	if (isset($genre_id) && $genres['all'][$genre_id]['redirect_url']) {$action = $genres['all'][$genre_id]['redirect_url'];} else {$action = $config['redirect_url'];}
	if (!$action) {exit_error('redirect_url not set');}
	$prep = prep_payment_vars('post');
	foreach ($prep as $key => $value) {$hidden .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\n";}

	echo '
	Submission Manager is processing your transaction. Please wait.
	<form action="' . $action . '" method="post" name="form_post" id="form_post">
	' . $hidden . '
	<noscript>
	<p>JavaScript is currently disabled or is not supported by your browser. Please click <b>continue</b> to process your transaction.</p>
	<input type="submit" value="continue" class="form_button">
	</noscript>
	</form>
	';

	kill_session();
	exit_error();
}

function form_hash($arg)
{
	if ($arg == 'session')
	{
		if (!isset($_SESSION['csrf_token'])) {$_SESSION['csrf_token'] = $GLOBALS['nonce'];}
	}

	if ($arg == 'validate')
	{
		if (!isset($_POST['form_hash']) || !isset($_SESSION['csrf_token']) || (isset($_POST['form_hash']) && isset($_SESSION['csrf_token']) && $_POST['form_hash'] != $_SESSION['csrf_token'])) {kill_session('regenerate'); exit_error('csrf_token');}
	}
}

function cleanup()
{
	global $fields;

	$args = func_get_args();
	$array = array_shift($args);

	foreach ($array as $key => $value)
	{
		if (!is_array($value))
		{
			$value = (string) $value;
			if (strpos($key, 'password') === false && $key != 'form_hash')
			{
				$value = trim($value);
				$value = str_replace("\r", '', $value);
				$value = preg_replace("~[ ]{2,}~", ' ', $value);
				foreach ($args as $function) {$value = $function($value);}
			}
			if ($key == 'email') {$value = strtolower($value);}
			if ($key == 'phone' || $key == 'cc_number' || $key == 'cc_exp_month' || $key == 'cc_exp_year' || $key == 'cc_csc') {$value = preg_replace('/[^0-9]/i', '', $value);}
			if ($key == 'phone' && strlen($value) < 7) {$value = '';}
			if ($key == 'state' && isset($array['country']) && $array['country'] != 'USA') {$value = '';}
			if ($key == 'zip' && isset($array['country']) && $array['country'] == 'USA') {$value = preg_replace('/[^0-9]/i', '', $value);}
			if ($key == 'zip') {$value = strtoupper($value);}
			if ($key == 'comments')
			{
				if ($fields['comments']['maxlength']) {$value = substr($value, 0, $fields['comments']['maxlength']);}
				$value = preg_replace("~[\n]{2,}~", "\n\n", $value);
			}
			$array[$key] = $value;
		}
	}

	return $array;
}

function email_check($email)
{
	if (preg_match('#^[a-z0-9.!\#$%&\'*+-/=?^_`{|}~]+@([0-9.]+|([^\s]+\.+[a-z]{2,6}))$#si', $email)) {return true;} else {return false;}
}

function password_check($password)
{
	global $password_length_min, $password_length_max;

	$length = strlen($password);
	if ($length >= $password_length_min && $length <= $password_length_max && strpos($password, ' ') === false) {return true;} else {return false;}
}

function form_check()
{
	extract($GLOBALS);
	if ($file_types) {$file_types_list = implode(', ', $file_types);} else {$file_types_list = array();}

	$checks = array(
	'blank' => array('status' => true, 'warning' => 'Required field(s) missing'),
	'email' => array('status' => true, 'warning' => 'Invalid email address'),
	'zip' => array('status' => true, 'warning' => 'Incomplete zip code'),
	'password' => array('status' => true, 'warning' => 'Passwords must be ' . $password_length_min . '-' . $password_length_max . ' characters and cannot contain spaces'),
	'password_match' => array('status' => true, 'warning' => 'Passwords do not match'),
	'file' => array('status' => true, 'warning' => 'No upload file selected'),
	'filesize_big' => array('status' => true, 'warning' => 'Uploaded file exceeds the maximum file size limit of ' . $max_file_size_formatted),
	'filesize_small' => array('status' => true, 'warning' => 'Uploaded file is empty (0 bytes)'),
	'file_ext' => array('status' => true, 'warning' => 'Invalid file extension. Allowed file extensions: ' . $file_types_list),
	'cc_expired' => array('status' => true, 'warning' => 'Expiration Date entered indicates that your credit card has expired')
	);

	if ((isset($_SESSION['post']['genre_id']) && (float) $genres['all'][$_SESSION['post']['genre_id']]['price'] && $config['show_payment_fields']) || $form_type == 'pay_submission')
	{
		$fields['cc_number']['required'] = 'Y';
		$fields['cc_exp_month']['required'] = 'Y';
		$fields['cc_exp_year']['required'] = 'Y';
		$fields['cc_csc']['required'] = 'Y';
	}

	foreach ($_SESSION['post'] as $key => $value)
	{
		if ($value == '' && isset($fields[$key]) && $fields[$key]['required'])
		{
			$key_display = str_replace('_', ' ', $key);
			$checks['blank']['status'] = false;
			$checks['blank']['warning'] .= '<br><span style="margin-left: 10px;">&bull; ' . $key_display . '</span>';
		}
	}

	if (isset($_SESSION['post']['email']))
	{
		if (!email_check($email)) {$checks['email']['status'] = false;}
	}

	if (isset($_SESSION['post']['country']) && $_SESSION['post']['country'] == 'USA')
	{
		if (isset($_SESSION['post']['state']) && !$_SESSION['post']['state']) {$checks['blank']['status'] = false; $checks['blank']['warning'] .= '<br><span style="margin-left: 10px;">&bull; state</span>';}
		if (isset($_SESSION['post']['zip']) && strlen($_SESSION['post']['zip']) < 5) {$checks['zip']['status'] = false;}
	}

	if (isset($_SESSION['post']['password']))
	{
		if ($form_type == 'update')
		{
			if ($_SESSION['post']['password'] && !password_check($password)) {$checks['password']['status'] = false;}
		}
		else
		{
			if (!password_check($password)) {$checks['password']['status'] = false;}
		}

		if (isset($password2) && $password != $password2) {$checks['password_match']['status'] = false;}
	}

	if (($form_type == 'submit' || $form_type == 'login_submit') && isset($_SESSION['file_upload']))
	{
		$_FILES['file'] = $_SESSION['file_upload'];

		if ($_FILES['file']['error'] == 3 || $_FILES['file']['error'] == 4 || !$_SESSION['file_upload']['is_uploaded_file'] || !$_SESSION['file_upload']['move_uploaded_file']) {$checks['file']['status'] = false;}
		if ($checks['file']['status'] && $_FILES['file']['size'] == 0) {$checks['filesize_small']['status'] = false;}
		if ($_FILES['file']['error'] == 1 || $_FILES['file']['error'] == 2 || ($fields['file']['maxlength'] && $_FILES['file']['size'] > $fields['file']['maxlength'])) {$checks['filesize_big']['status'] = false;}

		$pathinfo = pathinfo($_FILES['file']['name']);
		if (!isset($pathinfo['extension']) || (isset($pathinfo['extension']) && $pathinfo['extension'] == '')) {$checks['file_ext']['status'] = false;}
		if (isset($pathinfo['extension']) && !in_array(strtolower($pathinfo['extension']), $file_types)) {$checks['file_ext']['status'] = false;}

		// if file is too big then the file will not be uploaded thus other checks will fail
		if (!$checks['filesize_big']['status']) {$checks['file']['status'] = true;}
	}

	if (isset($_SESSION['post']['cc_exp_month']) && isset($_SESSION['post']['cc_exp_year']) && $_SESSION['post']['cc_exp_month'] && $_SESSION['post']['cc_exp_year'])
	{
		$cc_timestamp = strtotime($_SESSION['post']['cc_exp_year'] . '-' . $_SESSION['post']['cc_exp_month'] . '-01');
		$last_day = date('t', $cc_timestamp);
		$cc_timestamp = strtotime($_SESSION['post']['cc_exp_year'] . '-' . $_SESSION['post']['cc_exp_month'] . '-' . $last_day);
		if ($cc_timestamp < $gm_timestamp) {$checks['cc_expired']['status'] = false;}
	}

	$notice_string = '<ul>';
	foreach ($checks as $key => $value)
	{
		if (!$value['status'])
		{
			$notice_string .= '<li>' . $value['warning'] . '</li>' . "\n";
			$form_check = false;
		}
	}
	$notice_string .= '</ul>';

	if (!$form_check)
	{
		echo '<div class="notice">The following errors were detected:' . $notice_string . 'Please correct these errors and press submit again.</div><br>';
		if ($submodule == 'insert_submission') {form_insert_submission();} else {form_main();}
		exit_error();
	}

	else
	{
		if (isset($_SESSION['post']['email']))
		{
			$result = @mysqli_query($GLOBALS['db_connect'], "SELECT contact_id, email FROM contacts WHERE email = '$email'");
			if (mysqli_num_rows($result))
			{
				if ($page != 'login')
				{
					echo '<p>The email address that you entered, <b>' . $email . '</b>, is already in our database.<br><img src="arrow_left_2.png" alt="arrow left" width="16" height="13" style="vertical-align: middle;"> If you have already have an account, please login using the form to the left.</p>';
					$GLOBALS['email'] = $_POST['email'];
					exit_error();
				}

				$row = mysqli_fetch_assoc($result);
				if ($page == 'login' && $module == 'update' && ($row['contact_id'] != $_SESSION['contact']['contact_id']))
				{
					echo '<p>The email address that you entered, <b>' . $email . '</b>, is already in our database.<br>You must choose a unique email address.</p>';
					$GLOBALS['email'] = $_SESSION['contact']['email'];
					form_main();
					exit_error();
				}
			}
		}
	}
}

function process_captcha()
{
	global $config;

	$captcha = array(
	'secret' => $config['captcha_secret_key'],
	'response' => $_POST['g-recaptcha-response'],
	'remoteip' => $_SERVER['REMOTE_ADDR']
	);
	$curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
	curl_setopt_array($curl, array(
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_HTTPHEADER => array('Accept: application/json'),
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => $captcha));
	$response = curl_exec($curl);
	curl_close($curl);
	$response = json_decode($response);
	if (!$response->success) {exit_error('CAPTCHA fail');}
}

function upload()
{
	extract($GLOBALS);

	if (!file_exists($config['upload_path'])) {exit_error('invalid path for upload directory');}
	if (!file_exists($upload_path_year)) {@mkdir($upload_path_year) or exit_error('unable to create upload directory');}

	// cleanup old temp file
	if (isset($_SESSION['file_upload']['filename_temp'])) {@unlink($upload_path_year . $_SESSION['file_upload']['filename_temp']);}

	$_SESSION['file_upload'] = $_FILES['file'];
	$filename = $_FILES['file']['name'];
	$filename = urldecode($filename);
	$filename = stripslashes($filename);
	$filename_temp = 'temp_' . $gm_timestamp . '_' . $filename;
	$_SESSION['file_upload']['filename'] = $filename;
	$_SESSION['file_upload']['filename_temp'] = $filename_temp;
	$_SESSION['file_upload']['is_uploaded_file'] = @is_uploaded_file($_FILES['file']['tmp_name']);
	$_SESSION['file_upload']['move_uploaded_file'] = @move_uploaded_file($_FILES['file']['tmp_name'], $upload_path_year . $filename_temp);
}

function display($arg)
{
	include_once('inc_lists.php');
	extract($GLOBALS);
	$display_source = $GLOBALS; // need to work with a copy
	$output = '';
	$display_template = '[first_name] [last_name]' . "\n" . '[email]' . "\n" . '[company]' . "\n" . '[address1]' . "\n" . '[address2]' . "\n" . '[city], [state] [zip]' . "\n" . '[country]' . "\n" . '[phone]';
	$odd_boxes['comma'] = array('[city],', ', [state]'); // orphaned boxes with commas
	$odd_boxes['email'] = array('[at]' => '|at|', '[dot]' => '|dot|'); // needed when email is run through mail_to()

	foreach ($fields as $key => $value)
	{
		if ($value['section'] == 'contact' && $key != 'password2')
		{
			if ($key == 'country' && isset($display_source[$key]) && $display_source[$key] && isset($countries[$display_source[$key]])) {$display_source[$key] = $countries[$display_source[$key]] . ' (' . $display_source[$key] . ')';}
			if (isset($display_source[$key]) && $display_source[$key]) {$search_replace['[' . $key . ']'] = $display_source[$key];}
		}

		if ($value['section'] == 'submission' || $value['section'] == 'payment') {$display_template_array[$value['section']][$key] = '[' . $key . ']';}
	}

	$display = str_replace(array_keys($search_replace), $search_replace, $display_template);
	$display = str_replace($odd_boxes['comma'], '', $display);
	$display = str_replace(array_keys($odd_boxes['email']), $odd_boxes['email'], $display);
	$display = preg_replace('~\[.*?\]~', '', $display);
	$display = preg_replace("~[ ]{2,}~", '', $display);
	$display = preg_replace("~[\n]{2,}~", "\n", $display);
	$display = str_replace($odd_boxes['email'], array_keys($odd_boxes['email']), $display);
	$output .= trim($display);
	if ($page == 'login' && $module == 'update' && $_SESSION['post']['password'] && password_wrapper('hash', $_SESSION['post']['password']) != $_SESSION['contact']['password']) {$output .= '<div class="notice"><i>* new password detected</i></div>';}

	if (isset($title) && $title)
	{
		if ($arg == 'text')
		{
			$output .= "\n\n";
			if (isset($writer) && $writer) {$output .= '[writer]: ' . $writer . "\n";} else {$output .= '[writer]: ' . $first_name . ' ' . $last_name . "\n";}
			$output .= '[title]: ' . $title;
			if (isset($genre_id) && isset($genres['all'][$genre_id])) {$output .= "\n" . '[genre_id]: ' . $genres['all'][$genre_id]['name'];}
		}

		if ($arg == 'html')
		{
			$output .= '<hr><table style="border-collapse: collapse;">';
			if (isset($writer) && $writer) {$output .= '<tr><td class="row_left">[writer]:</td><td><b>' . $writer . '</b></td></tr>';}
			if ($title) {$output .= '<tr><td class="row_left">[title]:</td><td><b>' . $title . '</b></td></tr>';}
			if (isset($genre_id) && $genre_id && isset($genres['all'][$genre_id])) {$output .= '<tr><td class="row_left">[genre_id]:</td><td><b>' . $genres['all'][$genre_id]['name'] . '</b></td></tr>';}
			if (isset($_SESSION['file_upload']['filename']) && $_SESSION['file_upload']['filename']) {$output .= '<tr><td class="row_left">[file]:</td><td><b>' . $_SESSION['file_upload']['filename'] . '</b></td></tr>';}
			if (isset($comments) && $comments) {$output .= '<tr><td class="row_left">[comments]:</td><td><b>' . $comments . '</b></td></tr>';}
			$output .= '</table>';
		}
	}

	if (isset($price) && (float) $price && $arg == 'html')
	{
		$output .= '<hr><table style="border-collapse: collapse;"><tr><td class="row_left">price:</td><td><b>' . $config['currency_symbol'] . $price . '</b></td></tr>';
		if (isset($cc_number) && $cc_number)
		{
			$cc_number_display = str_repeat('xxxx ', 3) . substr($cc_number, -4);
			if ($config['cc_exp_date_format'] == 'MMYYYY') {$cc_exp_date = $cc_exp_month . $cc_exp_year;}
			if ($config['cc_exp_date_format'] == 'MM-YYYY') {$cc_exp_date = $cc_exp_month . '-' . $cc_exp_year;}
			if ($config['cc_exp_date_format'] == 'YYYYMM') {$cc_exp_date = $cc_exp_year . $cc_exp_month;}
			if ($config['cc_exp_date_format'] == 'YYYY-MM') {$cc_exp_date = $cc_exp_year . '-' . $cc_exp_month;}
			$output .= '<tr><td class="row_left">[cc_number]:</td><td><b>' . $cc_number_display . '</b></td></tr><tr><td class="row_left">expiration date:</td><td><b>' . $cc_exp_date . '</b></td></tr><tr><td class="row_left">[cc_csc]:</td><td><b>' . $cc_csc . '</b></td></tr>';
		}
		$output .= '</table>';
	}

	if ($arg == 'html')
	{
		$output = '<div class="foreground" style="padding: 5px; display: inline-block;">' . nl2br($output) . '</div>';
	}

	foreach ($display_template_array as $value)
	{
		foreach ($value as $sub_key => $sub_value) {$output = str_replace($sub_value, $fields[$sub_key]['name'], $output);}
	}

	return $output;
}

function db_update()
{
	extract($GLOBALS);
	foreach ($_SESSION['post'] as $key => $value) {$_SESSION['post_escaped'][$key] = mysqli_real_escape_string($GLOBALS['db_connect'], $value);}
	extract($_SESSION['post_escaped']);
	// if (!$first_name && !$last_name) {exit_error('blank db entry');}
	$args = func_get_args();

	if (in_array('insert contact', $args))
	{
		foreach ($fields as $key => $value)
		{
			if ($value['section'] == 'contact' && $key != 'password2')
			{
				if ($key == 'password') {$_SESSION['post_escaped'][$key] = password_wrapper('hash', $_SESSION['post_escaped'][$key]);}
				if (isset($_SESSION['post_escaped'][$key]) && $_SESSION['post_escaped'][$key]) {$sql_array[$key] = $key . ' = "' . $_SESSION['post_escaped'][$key] . '"';} else {$sql_array[$key] = $key . ' = NULL';}
			}
		}

		$sql = 'INSERT INTO contacts SET date_time = "' . $gm_date_time . '",' . implode(', ', $sql_array);
		@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT contact');
		$contact_id = mysqli_insert_id($GLOBALS['db_connect']);
		$GLOBALS['contact_id'] = $contact_id;
	}

	if (in_array('insert submission', $args))
	{
		if (isset($_SESSION['file_upload'])) {$pathinfo = pathinfo($_SESSION['file_upload']['filename_temp']);}
		if (isset($pathinfo['extension']) && $pathinfo['extension']) {$ext = strtolower($pathinfo['extension']);} else {$ext = '';}

		if (isset($writer) && $writer)
		{
			// so unescaped name and writer are compared
			if (isset($_SESSION['current_contact_array'])) {$name_array = $_SESSION['current_contact_array'];} // staff submission
			elseif (isset($_SESSION['contact'])) {$name_array = $_SESSION['contact'];} // submitter login
			else {$name_array = $_SESSION['post'];} // first time submitter

			$writer_compare = strtolower($_SESSION['post']['writer']);
			$name_compare = strtolower($name_array['first_name']) . ' ' . strtolower($name_array['last_name']);
			if ($writer_compare == $name_compare) {$writer = '';}
		}
		else
		{
			$writer = '';
		}

		$sql = "INSERT INTO submissions SET date_time = '$gm_date_time', submitter_id = $contact_id";
		if ($writer) {$sql .= ", writer = '$writer'";}
		if (isset($title) && $title) {$sql .= ", title = '$title'";}
		if (isset($genre_id) && $genre_id && is_numeric($genre_id)) {$sql .= ", genre_id = $genre_id";}
		if ($ext) {$sql .= ", ext = '$ext'";}
		if (isset($comments) && $comments) {$sql .= ", comments = '$comments'";}
		@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT submission');
		$submission_id = mysqli_insert_id($GLOBALS['db_connect']);
		$GLOBALS['submission_id'] = $submission_id;

		// when using sample data, target file may already exist causing rename() to fail
		if (file_exists($upload_path_year . $submission_id . '.' . $ext)) {@unlink($upload_path_year . $submission_id . '.' . $ext) or exit_error('unlink existing file');}
		if (isset($_SESSION['file_upload'])) {@rename($upload_path_year . $_SESSION['file_upload']['filename_temp'], $upload_path_year . $submission_id . '.' . $ext) or exit_error('rename file');}
	}

	unset($_SESSION['post_escaped']);
}

function make_email($name, $email)
{
	$output = $name . ' ' . ' <' . $email . '>';
	return $output;
}

function make_tooltip($arg)
{
	if ($arg == '&nbsp;') {$arg = '';}
	$arg = trim($arg);
	$arg = str_replace("\r", '', $arg);
	$arg = str_replace("\n", '<br>', $arg);
	$arg = json_encode($arg);
	return $arg;
}

function get_row_string($table, $field_name, $field_value)
{
	global $page, $submodule;
	include_once('inc_lists.php');

	$row = '';
	$result = @mysqli_query($GLOBALS['db_connect'], "SELECT * FROM `$table` WHERE $field_name = $field_value") or exit_error('query failure: get_row_string');
	$array = mysqli_fetch_assoc($result);
	unset($array['password']);

	$foreign_keys = array(
	'reader_id',
	'receiver_id',
	'action_type_id',
	'genre_id'
	);

	foreach ($array as $key => $value)
	{
		// show friendly names of readers/receivers/actions/genres
		if (in_array($key, $foreign_keys) && $value)
		{
			if (($key == 'reader_id' || $key == 'receiver_id') && isset($_SESSION['readers']['raw'][$value])) {$value .= ' (' . $_SESSION['readers']['raw'][$value]['first_name'] . ' ' . $_SESSION['readers']['raw'][$value]['last_name'] . ')';}
			if ($key == 'action_type_id')
			{
				$description = '';
				if ($_SESSION['action_types']['all'][$value]['description']) {$description = ' - ' . $_SESSION['action_types']['all'][$value]['description'];}
				$value .= ' (' . $_SESSION['action_types']['all'][$value]['name'] . $description . ')';
			}
			if ($key == 'genre_id' && isset($_SESSION['genres']['all'][$value])) {$value .= ' (' . $_SESSION['genres']['all'][$value]['name'] . ')';}
		}

		if ($key == 'date_time' && $value) {$value = timezone_adjust($value);}
		if ($key == 'country' && isset($lists['countries'][$value])) {$value = $lists['countries'][$value] . ' (' . $value . ')';}

		$row .= $key . ': ' . $value . "\n";
	}

	return $row;
}

function test_mail($function, $headers, $to, $subject, $body)
{
	global $header_called;

	$headers = str_replace('<', '&lt;', $headers);
	$headers = str_replace('>', '&gt;', $headers);
	$to = str_replace('<', '&lt;', $to);
	$to = str_replace('>', '&gt;', $to);

	$output = '
	<table class="table_list" style="width: auto; text-align: left; margin-bottom: 10px;">
	<tr><td class="row_left">function:</td><td>' . $function . '</td></tr>
	<tr><td class="row_left">headers:</td><td>' . $headers . '</td></tr>
	<tr><td class="row_left">to:</td><td>' . $to . '</td></tr>
	<tr><td class="row_left">subject:</td><td>' . $subject . '</td></tr>
	<tr><td class="row_left">message:</td><td>' . nl2br($body) . '</td></tr>
	</table>
	';

	if ($header_called) {echo $output;} else {$GLOBALS['output'] .= $output;}
}

function mail_setup()
{
	global $config;

	require_once('PHPMailer.php');
	require_once('SMTP.php');
	require_once('Exception.php');
	$mail = new PHPMailer\PHPMailer\PHPMailer;
	$mail->CharSet = 'UTF-8';
	if ($config['mail_method'] == 'mail') {$mail->IsMail();}
	if ($config['mail_method'] == 'sendmail') {$mail->IsSendmail();}
	if ($config['mail_method'] == 'smtp') {$mail->IsSMTP(); $mail->SMTPKeepAlive = true;}
	if ($config['smtp_secure']) {$mail->SMTPSecure = $config['smtp_secure'];}
	if ($config['smtp_port']) {$mail->Port = $config['smtp_port'];}
	if ($config['smtp_auth']) {$mail->SMTPAuth = true;}
	if ($config['smtp_host']) {$mail->Host = $config['smtp_host'];}
	if ($config['smtp_username']) {$mail->Username = $config['smtp_username'];}
	if ($config['smtp_password']) {$mail->Password = $config['smtp_password'];}

	return $mail;
}

function send_mail($arg1, $arg2)
{
	extract($GLOBALS);

	if ($arg1 == 'staff')
	{
		$result = mysqli_query($GLOBALS['db_connect'], 'SELECT email FROM contacts WHERE email IS NOT NULL AND email_notification LIKE "%' . $arg2 . '%" ORDER BY email') or exit_error('query failure: SELECT emails for staff mail');
		if (mysqli_num_rows($result))
		{
			while ($row = mysqli_fetch_assoc($result)) {$list[] = $row['email'];}

			// mail always comes from $config['general_dnr_email']
			$headers = 'From: ' . make_email($config['company_name'], $config['general_dnr_email']);
			$from_name = $config['company_name'];
			$from_email = $config['general_dnr_email'];

			$to = implode(',', $list);
			$subject = 'Submission Manager: ' . ucfirst(substr($arg2, 0, -1));
			if ($config['company_name']) {$subject = $config['company_name'] . ' ' . $subject;}
			$body = '';

			if ($arg2 == 'submissions')
			{
				$subject .= ' ' . $contact_id . '-' . $submission_id;
				$body = get_row_string('contacts', 'contact_id', $contact_id) . "\n" . get_row_string('submissions', 'submission_id', $submission_id);
			}

			if ($arg2 == 'actions')
			{
				$subject .= ' ' . $submission_id . '-' . $action_id;
				$body = get_row_string('actions', 'action_id', $action_id);
			}

			if ($arg2 == 'updates')
			{
				if ($submodule == 'update')
				{
					$contact_id = $_POST['contact_id'];
				}
				else
				{
					// from admin/editor login
					if (isset($_SESSION['current_contact_id'])) {$contact_id = $_SESSION['current_contact_id'];} else {$contact_id = $_SESSION['contact']['contact_id'];}
				}

				$subject .= ' ' . $contact_id;
				$body = get_row_string('contacts', 'contact_id', $contact_id);
			}
		}
		else
		{
			return;
		}
	}

	if ($arg1 == 'contact')
	{
		$headers = 'From: ' . make_email($config['company_name'], $config['general_dnr_email']);
		$from_name = $config['company_name'];
		$from_email = $config['general_dnr_email'];
		$to = $email;
		$body = "Dear $first_name $last_name,\n\n";

		if ($arg2 == 'submission')
		{
			$subject = 'Thank you for your submission to ' . $config['company_name'];
			$body .= 'Your submission was received successfully.' . "\n\n" . display('text');
			if ($config['submission_text']) {$body .= "\n\n" . strip_tags(replace_placeholders($config['submission_text']));}
		}

		if ($arg2 == 'action')
		{
			extract($preview);
			$to = $_SESSION['to_email']; // otherwise $to will have full name
			$headers = 'From: ' . $from;
			if (isset($_SESSION['file_upload']['is_uploaded_file']) && $_SESSION['file_upload']['is_uploaded_file']) {$attachment = $path;}
		}

		if ($arg2 == 'reset')
		{
			$app_url_reset = $app_url_slash . 'index.php?page=login&token=' . $GLOBALS['token'];
			$subject = $config['company_name'] . ' Submission Manager password reset information';
			$body .= 'You have reset the password for your ' . $config['company_name'] . ' Submission Manager account. To login to your account please follow the link below:' . "\n\n" . '<a href="' . $app_url_reset . '"><b>Reset Account Password</b></a>' . "\n\n" . 'This link will expire in one hour. If you need any further help accessing your account please contact <a href="mailto:' . $config['admin_email'] . '">' . $config['admin_email'] . '</a>';
			$html_mail = true;
		}

		$body .= "\n";
	}

	$body .= "\n" . $local_date_time;

	if (isset($html_mail)) {$body = '<!DOCTYPE html><html lang="en"><head><title>' . htmlspecialchars($config['company_name']) . '</title><meta charset="UTF-8"></head><body>' . nl2br($body) . '</body></html>';}

	if ($config['test_mode'] && $to && defined('TEST_MAIL') && TEST_MAIL)
	{
		test_mail($arg1, $headers, $to, $subject, $body);
	}
	if (!$config['test_mode'] && $to)
	{
		if (!isset($GLOBALS['mail'])) {$mail = mail_setup(); $GLOBALS['mail'] = $mail;} // so $mail object is only created once for loops
		$mail->SetFrom($from_email, $from_name);
		if (isset($list)) {foreach ($list as $value) {$mail->AddAddress($value);}} else {$mail->AddAddress($to);}
		$mail->Subject = $subject;
		if (isset($html_mail)) {$mail->MsgHTML($body);} else {$mail->Body = $body;}
		if (isset($attachment)) {$mail->AddAttachment($attachment);}
		if (!$mail->Send()) {exit_error('mail failure');}
		$mail->ClearAddresses();
	}
}

$placeholders = array(
'app_url' => 'the URL of the Submission Manager',
'company_name' => 'your company&rsquo;s name',
'reader' => 'the name of the staff reader sending the action',
'receiver' => 'the name of the staff reader receiving the action',
'submission_id' => 'the submission&rsquo;s unique ID number',
'title' => 'the title(s) of the submission',
'writer' => 'the writer&rsquo;s name',
'first_name' => 'the writer&rsquo;s first name',
'last_name' => 'the writer&rsquo;s last name',
'genre' => 'the submission&rsquo;s genre',
'message' => 'a personalized message'
);

function replace_placeholders($arg)
{
	$placeholders = array_keys($GLOBALS['placeholders']);

	foreach ($placeholders as $key => $value)
	{
		if ($value == 'first_name' || $value == 'last_name')
		{
			if (isset($_SESSION['submission']['contact'][$value])) {$GLOBALS[$value] = $_SESSION['submission']['contact'][$value];}
		}

		if (isset($GLOBALS[$value])) {$replacements[$value] = $GLOBALS[$value];} else {$replacements[$value] = '';}
		$placeholders[$key] = '[' . $value . ']';
	}

	$arg = str_replace($placeholders, $replacements, $arg);
	$arg = trim($arg);
	$arg = str_replace("\r", "\n", $arg);
	$arg = preg_replace("~[ ]{2,}~", ' ', $arg);
	$arg = preg_replace("~[\n]{2,}~", "\n\n", $arg);

	return $arg;
}

function get_price()
{
	global $config, $genres, $genre_id;

	$price = 0;
	if (isset($genre_id) && (float) $genres['all'][$genre_id]['price']) {$price = $genres['all'][$genre_id]['price'];} else {$price = $config['submission_price'];}
	$GLOBALS['price'] = $price;
}

function get_hash($submission_id)
{
	$sha1_file = sha1_file('config_db.php');
	$hash = sha1($sha1_file . $submission_id);
	return $hash;
}

function prep_payment_vars($arg)
{
	extract($GLOBALS);

	if ($page == 'home') {$contact_array = $_SESSION['post'];}
	if ($page == 'login') {$contact_array = $_SESSION['contact'];}

	get_payment_vars();
	get_local_variables($contact_array);

	// must get these directly from GLOBALS
	$payment_vars = $GLOBALS['payment_vars'];
	$local_variables_flat = $GLOBALS['local_variables_flat'];

	foreach ($payment_vars['out'] as $payment_value)
	{
		extract($payment_value);
		if ($value == '$genre_id' && isset($genre_id)) {$value = $genres['all'][$genre_id]['name'];}
		if ($value == '$hash' && isset($submission_id)) {$value = get_hash($submission_id);}
		$value_no_dollar = substr($value, 1);
		if ($value[0] == '$' && isset($local_variables_flat[$value_no_dollar])) {$prep_value = (string) $local_variables_flat[$value_no_dollar];} else {$prep_value = (string) $value;}
		if ($prep_value[0] != '$')
		{
			if ($arg == 'get') {$prep_array[$name] = $name . '=' . urlencode($prep_value);}
			if ($arg == 'post') {$prep_array[$name] = htmlspecialchars($prep_value);}
		}
	}

	return $prep_array;
}

function redirect()
{
	extract($GLOBALS);

	if (isset($genre_id) && $genres['all'][$genre_id]['redirect_url']) {$url = $genres['all'][$genre_id]['redirect_url'];} else {$url = $config['redirect_url'];}

	if ((float) $price)
	{
		$url_array = prep_payment_vars('get');
		$nvp = implode('&', $url_array);
		$url_nvp = $url . '?' . $nvp;
	}

	if ($config['payment_redirect_method'] == 'GET')
	{
		if (isset($url_nvp)) {$url_get = $url_nvp;} else {$url_get = $url;}
		kill_session();
		ob_end_clean();
		header('location: ' . $url_get);
		exit();
	}

	if ($config['payment_redirect_method'] == 'cURL')
	{
		function PPHttpPost($method, $nvp, $url)
		{
			extract($GLOBALS);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			if ($GLOBALS['IsAuthorizeNet']) {curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $nvp);
			$httpResponse = curl_exec($ch);
			curl_close($ch);
			if (!$httpResponse) {$GLOBALS['error_output'] = str_replace('[error]', $method . ' failed: ' . curl_error($ch) . ' (' . curl_errno($ch) . ')', $GLOBALS['error_output']); exit_error();}
			$httpParsedResponseAr = array();

			// invalid EXPDATE from PayPal Payments Pro NVP (single string, not NVP)
			if (strpos($httpResponse, 'is not a valid uint32') !== false)
			{
				$httpParsedResponseAr['ACK'] = 'Failure';
				$httpParsedResponseAr['L_ERRORCODE0'] = 'ERROR';
				$httpParsedResponseAr['L_SHORTMESSAGE0'] = 'Invalid EXPDATE';
				$httpParsedResponseAr['L_LONGMESSAGE0'] = $httpResponse;
			}

			if ($GLOBALS['IsAuthorizeNet'])
			{
				$httpResponse = trim($httpResponse, "\xEF\xBB\xBF");
				$httpResponse = json_decode($httpResponse, true);

				if (isset($httpResponse['refId'])) {$httpParsedResponseAr['refId'] = $httpResponse['refId'];}

				if (isset($httpResponse['transactionResponse']))
				{
					if (isset($httpResponse['transactionResponse']['responseCode'])) {$httpParsedResponseAr['responseCode'] = $httpResponse['transactionResponse']['responseCode'];}
					if (isset($httpResponse['transactionResponse']['errors'][0]['errorCode'])) {$httpParsedResponseAr['errorCode'] = $httpResponse['transactionResponse']['errors'][0]['errorCode'];}
					if (isset($httpResponse['transactionResponse']['errors'][0]['errorText'])) {$httpParsedResponseAr['errorText'] = $httpResponse['transactionResponse']['errors'][0]['errorText'];}
				}
				else
				{
					if (isset($httpResponse['messages']['resultCode']) && $httpResponse['messages']['resultCode'] == 'Error') {$httpParsedResponseAr['responseCode'] = 'Error';}
					if (isset($httpResponse['messages']['message'][0]['code'])) {$httpParsedResponseAr['errorCode'] = $httpResponse['messages']['message'][0]['code'];}
					if (isset($httpResponse['messages']['message'][0]['text'])) {$httpParsedResponseAr['errorText'] = $httpResponse['messages']['message'][0]['text'];}
				}
			}
			else
			{
				$httpResponseAr = explode('&', $httpResponse);
				foreach ($httpResponseAr as $value)
				{
					$tmpAr = explode('=', $value);
					if (count($tmpAr) > 1) {$httpParsedResponseAr[$tmpAr[0]] = urldecode($tmpAr[1]);}
				}
			}

			if (count($httpParsedResponseAr) == 0 || !isset($httpParsedResponseAr[$GLOBALS['result_field']])) {$GLOBALS['error_output'] = str_replace('[error]', 'Invalid HTTP Response for POST request to ' . $url, $GLOBALS['error_output']); exit_error();}
			return $httpParsedResponseAr;
		}

		extract($GLOBALS);
		$GLOBALS['method'] = 'cURL';
		$GLOBALS['result_field'] = '';
		$cc_fail_text = '';
		if ($page == 'home' || ($page == 'login' && $module == 'submit')) {$cc_fail_text = 'Your submission was received successfully, but your credit card payment has failed.';}
		if ($module == 'pay_submission') {$cc_fail_text = 'Your credit card payment has failed.';}
		$GLOBALS['error_output'] = '<div>We are sorry. ' . $cc_fail_text . ' Details of the error are below:</div><div class="notice" style="margin: 10px 0px 10px 0px;">[error]</div><div>Please log back into your account and click <b>&ldquo;pay now&rdquo;</b> next to your existing unpaid submission to try your payment again.</div><div>If you need further help please contact ' . mail_to($config['admin_email']) . '.</div>';
		if ($page == 'login') {$GLOBALS['error_output'] .= $back_to_account;}
		$payment_status = false;

		foreach ($payment_vars['out'] as $value)
		{
			if ($value['name'] == 'METHOD') {$GLOBALS['method'] = $value['value'];}
			if ($value['name'] == 'PayPal_REST_clientID' || $value['name'] == 'PayPal_REST_secret') {$PayPal_REST_temp[$value['name']] = $value['value'];}
			if ($value['name'] == 'name' || $value['name'] == 'transactionKey' || $value['name'] == 'refId') {$AuthorizeNet_temp[] = $value['name'];}
		}

		foreach ($payment_vars['in'] as $value)
		{
			if ($value['value'] == '$result_code') {$GLOBALS['result_field'] = $value['name'];}
			if ($value['value'] == '$error') {$GLOBALS['errors'][$value['name']] = '';}
		}

		$GLOBALS['IsPayPal_REST'] = false;
		if (isset($PayPal_REST_temp) && count($PayPal_REST_temp) == 2) {$GLOBALS['IsPayPal_REST'] = true;}

		if ($GLOBALS['IsPayPal_REST'])
		{
			function curl_paypal($endpoint, $http_header_array, $post_fields)
			{
				extract($GLOBALS['PayPal_REST_temp']);

				$curl = curl_init($endpoint);
				curl_setopt($curl, CURLOPT_USERPWD, $PayPal_REST_clientID . ':' . $PayPal_REST_secret);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $http_header_array);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
				$response = curl_exec($curl);
				curl_close($curl);
				if (!$response) {$GLOBALS['error_output'] = str_replace('[error]', 'cURL failed: ' . curl_error($curl) . ' (' . curl_errno($curl) . ')', $GLOBALS['error_output']); exit_error();}
				$response = json_decode($response, true);
				return $response;
			}

			$GLOBALS['PayPal_REST_temp'] = $PayPal_REST_temp;
			$PayPal_REST_requestID = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(get_token(), 4));
			include_once('inc_lists.php');

			$endpoint = $url . '/v1/oauth2/token';
			$http_header_array = array('Content-Type: application/x-www-form-urlencoded');
			$post_fields = 'grant_type=client_credentials';
			$response = curl_paypal($endpoint, $http_header_array, $post_fields);
			if (isset($response['error']) && isset($response['error_description'])) {$GLOBALS['error_output'] = str_replace('[error]', $response['error'] . ' : ' . $response['error_description'], $GLOBALS['error_output']); exit_error();}
			if (isset($response['access_token'])) {$PayPal_REST_access_token = $response['access_token'];} else {$GLOBALS['error_output'] = str_replace('[error]', 'Invalid access_token', $GLOBALS['error_output']); exit_error();}

			$PayPal_REST_json = '
			{
				"intent": "CAPTURE",
				"purchase_units":
				[
					{
						"custom_id": "[custom_id]",
						"description": "[description]",
						"amount":
						{
							"currency_code": "[currency_code]",
							"value": "[value]"
						}
					}
				],
				"payer":
				{
					"name":
					{
						"given_name": "[given_name]",
						"surname": "[surname]"
					},
					"email_address": "[email_address]",
					"address":
					{
						"address_line_1": "[address_line_1]",
						"address_line_2": "[address_line_2]",
						"admin_area_2": "[admin_area_2]",
						"admin_area_1": "[admin_area_1]",
						"postal_code": "[postal_code]",
						"country_code": "[country_code]"
					}
				},
				"payment_source":
				{
					"card":
					{
						"number": "[number]",
						"expiry": "[expiry]",
						"security_code": "[security_code]"
					}
				}
			}
			';

			$prep_array = prep_payment_vars('post');
			foreach ($prep_array as $key => $value)
			{
				if ($key == 'country_code') {$value = convert_country_codes($value);}
				$PayPal_REST_json = str_replace("[$key]", $value, $PayPal_REST_json);
			}

			$endpoint = $url . '/v2/checkout/orders';
			$http_header_array = array('Authorization: Bearer ' . $PayPal_REST_access_token, 'PayPal-Request-Id: ' . $PayPal_REST_requestID, 'Content-Type: application/json');
			$post_fields = $PayPal_REST_json;
			$response = curl_paypal($endpoint, $http_header_array, $post_fields);

			$httpParsedResponseAr['status'] = '';
			$httpParsedResponseAr['error'] = '';
			if (isset($response['id']) && isset($response['status'])) {$httpParsedResponseAr['status'] = $response['status'];}
			if ($httpParsedResponseAr['status'] != $config['success_result_code'])
			{
				if (isset($response['name']) && isset($response['details']))
				{
					if ($response['name'] == 'UNPROCESSABLE_ENTITY' && $response['details'][0]['issue'] == 'UNPROCESSABLE_ENTITY' && $response['details'][0]['description'] == 'UNPROCESSABLE_ENTITY' && !isset($response['details'][0]['field']) && !isset($response['details'][0]['location']) && strlen($cc_csc) > 3)
					{
						$response['details'][0]['issue'] = 'CSC/CVV LENGTH';
						$response['details'][0]['description'] = 'Invalid card security code';
					}

					$httpParsedResponseAr['error'] .= $response['name'];
					foreach ($response['details'] as $value)
					{
						unset($value['value']);
						$httpParsedResponseAr['error'] .= '<pre>' . print_r($value, true) . '</pre>';
					}
					$httpParsedResponseAr['error'] = str_replace('Array', '', $httpParsedResponseAr['error']);
				}
			}
		}

		$GLOBALS['IsAuthorizeNet'] = false;
		if (isset($AuthorizeNet_temp) && count($AuthorizeNet_temp) == 3) {$GLOBALS['IsAuthorizeNet'] = true;}

		if ($GLOBALS['IsAuthorizeNet'])
		{
			$AuthorizeNet_json = '
			{
				"createTransactionRequest":
				{
					"merchantAuthentication":
					{
						"name": "[name]",
						"transactionKey": "[transactionKey]"
					},
					"refId": "[refId]",
					"transactionRequest":
					{
						"transactionType": "authCaptureTransaction",
						"amount": "[amount]",
						"payment":
						{
							"creditCard":
							{
								"cardNumber": "[cardNumber]",
								"expirationDate": "[expirationDate]",
								"cardCode": "[cardCode]"
							}
						},
						"customer":
						{
							"id": "[customer_id]",
							"email": "[email]"
						},
						"billTo":
						{
							"firstName": "[firstName]",
							"lastName": "[lastName]",
							"company": "[company]",
							"address": "[address]",
							"city": "[city]",
							"state": "[state]",
							"zip": "[zip]",
							"country": "[country]"
						},
						"transactionSettings":
						{
							"setting":
							{
								"settingName": "duplicateWindow",
								"settingValue": "10"
							}
						}
					}
				}
			}
			';

			$prep_array = prep_payment_vars('post');
			foreach ($prep_array as $key => $value) {$AuthorizeNet_json = str_replace("[$key]", $value, $AuthorizeNet_json);}
			$nvp = $AuthorizeNet_json;
		}

		if (!isset($httpParsedResponseAr)) {$httpParsedResponseAr = PPHttpPost($GLOBALS['method'], $nvp, $url);}

		if (strpos($config['success_result_code'], '|') !== false)
		{
			$success_result_code_array = explode('|', $config['success_result_code']);
			if (in_array($httpParsedResponseAr[$GLOBALS['result_field']], $success_result_code_array)) {$payment_status = true;}
		}
		else
		{
			if ($httpParsedResponseAr[$GLOBALS['result_field']] == $config['success_result_code']) {$payment_status = true;}
		}

		$keep = array('login', 'contact', 'csrf_token');
		flush_session($keep);

		if ($payment_status)
		{
			$sql = "UPDATE submissions SET date_paid = '$gm_date' WHERE submission_id = " . mysqli_real_escape_string($GLOBALS['db_connect'], $submission_id);
			$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE submissions payment');
		}
		else
		{
			$sql = 'UPDATE submissions SET date_paid = NULL WHERE submission_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $submission_id);
			$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE submissions payment');

			foreach ($GLOBALS['errors'] as $key => $value)
			{
				if (isset($httpParsedResponseAr[$key])) {$GLOBALS['errors'][$key] = $httpParsedResponseAr[$key];}
			}

			$GLOBALS['error_output'] = str_replace('[error]', implode(' : ', $GLOBALS['errors']), $GLOBALS['error_output']);
			exit_error();
		}
	}
}

function download($path, $file, $type)
{
	global $csv, $backup;

	ob_end_clean(); // needed for Tidy?

	header('Content-Description: File Transfer');
	header('Content-Type: ' . $type);
	header('Content-Disposition: attachment; filename="' . $file . '"');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	if ($type == 'application/octet-stream')
	{
		header('Content-Length: ' . filesize($path));
		readfile($path);
	}

	if ($type == 'text/csv' && isset($csv))
	{
		header('Content-Length: ' . strlen($csv));
		echo $csv;
	}

	if ($type == 'text/sql' && isset($backup))
	{
		header('Content-Length: ' . strlen($backup));
		echo $backup;
	}

	exit();
}

function get_token()
{
	if (version_compare(PHP_VERSION, '7.0.0', '>='))
	{
		$token = bin2hex(random_bytes(20));
	}
	elseif (extension_loaded('openssl'))
	{
		$token = bin2hex(openssl_random_pseudo_bytes(20));
	}
	else
	{
		$token = sha1(uniqid(mt_rand(), true) . session_id());
	}

	return $token;
}

function password_wrapper($function, $password, $hash = '')
{
	$output = '';

	if ($function == 'hash')
	{
		$output = password_hash($password, PASSWORD_DEFAULT);
	}

	if ($function == 'verify')
	{
		if ($password == $hash && isset($_SESSION['module']) && $_SESSION['module'] == 'update2account')
		{
			$output = true;
		}
		elseif (strlen((string) $hash) == 40)
		{
			if ($hash == sha1($password)) {$output = true;} else {$output = false;}
			if ($output) {$GLOBALS['password_needs_rehash'] = true;}
		}
		else
		{
			$output = password_verify($password, (string) $hash);
			if ($output && password_needs_rehash($hash, PASSWORD_DEFAULT)) {$GLOBALS['password_needs_rehash'] = true;}
		}
	}

	return $output;
}

function mail_to($arg)
{
	$explode = explode('@', $arg);
	$explode2 = explode('.', $explode[1]);
	$user = $explode[0];
	$suffix = array_pop($explode2);
	$domain = implode('.', $explode2);
	$email = $user . ' [at] ' . $domain . ' [dot] ' . $suffix;
	return '<span class="mail_to_link">' . $email . '</span>';
}

function output_tidy()
{
	$output_tidy = true;
	if (defined('TIDY') && !TIDY) {$output_tidy = false;}

	if (extension_loaded('tidy') && $output_tidy)
	{
		$tidy_config = array('indent' => true, 'wrap' => 0);
		$buffer = ob_get_clean();
		$tidy = new tidy;
		$tidy->parseString($buffer, $tidy_config, 'utf8');
		$tidy->cleanRepair();
		$tidy = str_replace(' type="text/css"', '', $tidy);
		$tidy = str_replace(' type="text/javascript"', '', $tidy);
		echo $tidy;
	}
	else
	{
		ob_end_flush();
	}

	exit();
}

$custom = 'custom.php';
if (@file_exists($custom)) {include($custom);}
?>
<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}
if (INSTALLED) {exit('app already installed');}
if (isset($_POST['step'])) {$step = $_POST['step'];} else {$step = 1;}

$copy = array(
1 => 'Welcome to the <b>Submission Manager</b> installation. Basic information is needed for your database connection. Please fill out the form below. If you do not know this information, please speak to your system administrator.',
2 => 'Now please eneter the name of the database that you wish to use for the <b>Submission Manager</b>',
3 => 'The <b>Submission Manager</b> installation will now attempt to create the necessary tables in your database.<br><br><span class="notice"><i>WARNING!</i> all data in existing tables will be overwritten. If necessary, please backup your database tables before going forward.</span>',
4 => 'Please enter your name, email address and a password of your choice. This info will be used to create a adminstrator level account in the <b>Submission Manager</b>. Passwords must be ' . $password_length_min . '-' . $password_length_max . ' characters (no spaces).',
5 => 'The final step in the <b>Submission Manager</b> installation is to configure some of the program&rsquo;s basic settings. Please verify your account login info below and you will be taken to the main configuration page.'
);

$config_db_string = file_get_contents('config_db_default.php');

function form_install($step)
{
	global $config_db, $admin, $password_length_min, $password_length_max;

	$form_steps = range(1, 4);
	if (in_array($step, $form_steps)) {echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" name="form_install" id="form_install">';}

	if ($step == 1)
	{
		echo '
		<p style="font-weight: bold;">mySQL database connection:</p>
		<table>
		<tr><td class="row_left"><label for="config_db_host" id="label_config_db_host">host:</label></td><td><input type="text" id="config_db_host" name="config_db[host]" value="'; if (isset($config_db['host'])) {echo $config_db['host'];} echo '"></td></tr>
		<tr><td class="row_left"><label for="config_db_username" id="label_config_db_username">username:</label></td><td><input type="text" id="config_db_username" name="config_db[username]" value="'; if (isset($config_db['username'])) {echo $config_db['username'];} echo '"></td></tr>
		<tr><td class="row_left"><label for="config_db_password" id="label_config_db_password">password:</label></td><td><input type="password" id="config_db_password" name="config_db[password]" value="'; if (isset($config_db['password'])) {echo $config_db['password'];} echo '"></td></tr>
		<tr><td class="row_left"><label for="config_db_port" id="label_config_db_port">port:</label></td><td><input type="text" id="config_db_port" name="config_db[port]" value="'; if (isset($config_db['port'])) {echo $config_db['port'];} echo '"> <span class="small">(leave blank for default)</span></td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" id="form_install_submit" name="submit" value="go to step 2" class="form_button"></td></tr>
		</table>
		';
	}

	if ($step == 2)
	{
		echo '
		<p style="font-weight: bold;">mySQL database name:</p>
		<table>
		<tr><td class="row_left"><label for="config_db_name" id="label_config_db_name">database name:</label></td><td><input type="text" id="config_db_name" name="config_db[name]" value="'; if (isset($config_db['name'])) {echo $config_db['name'];} echo '"></td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" id="form_install_submit" name="submit" value="go to step 3" class="form_button"></td></tr>
		</table>
		';
	}

	if ($step == 3)
	{
		echo '
		<input type="submit" id="form_install_submit" name="submit" value="create tables" class="form_button">
		';
	}

	if ($step == 4)
	{
		echo '
		<p style="font-weight: bold;">admin account:</p>
		<table>
		<tr><td class="row_left"><label for="admin_first_name" id="label_admin_first_name">first name:</label></td><td><input type="text" id="admin_first_name" name="admin[first_name]" value="'; if (isset($admin['first_name'])) {echo $admin['first_name'];} echo '"></td></tr>
		<tr><td class="row_left"><label for="admin_last_name" id="label_admin_last_name">last name:</label></td><td><input type="text" id="admin_last_name" name="admin[last_name]" value="'; if (isset($admin['last_name'])) {echo $admin['last_name'];} echo '"></td></tr>
		<tr><td class="row_left"><label for="admin_email" id="label_admin_email">email:</label></td><td><input type="text" id="admin_email" name="admin[email]" value="'; if (isset($admin['email'])) {echo $admin['email'];} echo '"></td></tr>
		<tr><td class="row_left"><label for="admin_password" id="label_admin_password">password:</label></td><td><input type="password" id="admin_password" name="admin[password]" value="'; if (isset($admin['password'])) {echo $admin['password'];} echo '" maxlength="' . $password_length_max . '"></td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" id="form_install_submit" name="submit" value="go to step 5" class="form_button"></td></tr>
		</table>
		';
	}

	$step_plus = $step + 1;
	if (in_array($step, $form_steps)) {echo '<input type="hidden" id="step" name="step" value="' . $step_plus . '"></form>';}

	if ($step == 5)
	{
		form_login();
	}
}

function display_step($step)
{
	global $submit, $form_check, $errors, $copy;

	if ($step)
	{
		if ($submit) {form_hash('validate');}
		echo '<p><b>STEP ' . $step . ':</b> ' . $copy[$step] . '</p>';
		if (!$form_check)
		{
			echo '<blockquote class="notice">';
			foreach ($errors as $value) {echo 'ERROR: ' . $value . '<br>';}
			echo '</blockquote>';
		}
		form_install($step);
	}
}


if ($step == 1)
{
	$config_db = array();
}

if ($step == 2)
{
	$config_db = array();
	foreach ($_POST['config_db'] as $key => $value)
	{
		$value = trim($value);
		$value = strip_tags($value);
		$value = stripslashes($value);
		if ($key != 'password' && $key != 'port' && $value == '')
		{
			$form_check = false;
			$errors[] = 'Missing required form field(s)';
			break;
		}
		$config_db[$key] = $value;
	}

	if (!$errors)
	{
		@db_connect($config_db['host'], $config_db['username'], $config_db['password'], '', $config_db['port']);

		if ($GLOBALS['db_connect'])
		{
			check_version('mySQL');
			$_SESSION['config_db'] = $config_db;
			$step = 2;
		}
		else
		{
			$form_check = false;
			$errors[] = 'Unable to connect to database.<br>Please check that your database connection data is correct.<br>If you continue to have problems, please contact your system administrator.<br><br>Database Connection: ' . $GLOBALS['mysqli_sql_exception'];
		}
	}

	if (!$form_check) {$step = 1;}
}

if ($step == 3)
{
	$name = $_POST['config_db']['name'];
	$name = trim($name);
	$name = strip_tags($name);
	$name = stripslashes($name);
	$config_db['name'] = $name;

	if ($name == '')
	{
		$form_check = false;
		$errors[] = 'Missing required form field(s)';
	}

	if (!$errors)
	{
		$_SESSION['config_db']['name'] = $name;
		@db_connect($_SESSION['config_db']['host'], $_SESSION['config_db']['username'], $_SESSION['config_db']['password'], $_SESSION['config_db']['name'], $_SESSION['config_db']['port']);

		if ($GLOBALS['db_connect'])
		{
			foreach ($_SESSION['config_db'] as $key => $value) {$config_db_keys[] = '[' . $key . ']'; $config_db_escaped[$key] = addcslashes($value, "'");}
			$config_db_string = str_replace($config_db_keys, $config_db_escaped, $config_db_string);
			$_SESSION['config_db_string'] = $config_db_string;
			@file_put_contents('config_db.php', $config_db_string) or exit_error('cannot open config_db.php');
			$step = 3;
		}
		else
		{
			$form_check = false;
			$errors[] = 'Unable to connect to specified database name.<br>Please check that your database name is correct.<br>If you continue to have problems, please contact your system administrator.<br><br>Database Connection: ' . $GLOBALS['mysqli_sql_exception'];
		}
	}

	if (!$form_check) {$step = 2;}
}

if ($step == 4)
{
	@db_connect($_SESSION['config_db']['host'], $_SESSION['config_db']['username'], $_SESSION['config_db']['password'], $_SESSION['config_db']['name'], $_SESSION['config_db']['port']) or exit_error('unable to connect to database');
	check_version('SubMgr');

	$sql = 'ALTER DATABASE `' . $_SESSION['config_db']['name'] . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';
	@mysqli_query($GLOBALS['db_connect'], $sql);

	$sql = '';
	foreach ($schema as $key => $value)
	{
		$sql_fields = '';
		$sql_indexes = '';
		$extra = '';
		if ($key == 'config' && isset($version_local)) {$extra = " COMMENT = '$version_local'";}

		$sql .= "DROP TABLE IF EXISTS `$key`;" . "\r\n" . "CREATE TABLE `$key` (" . "\r\n";
		foreach ($value['fields'] as $sub_key => $sub_value) {$sql_fields .= "`$sub_key` $sub_value[type] $sub_value[extra]," . "\r\n";}
		foreach ($value['indexes'] as $sub_key => $sub_value) {$sql_indexes .= "$sub_value[type] `$sub_key` ($sub_value[fields])," . "\r\n";}
		$sql_indexes = substr(trim($sql_indexes), 0, -1);
		$sql .= $sql_fields . $sql_indexes . ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci' . $extra . ';';
	}

	$explode = explode(';', $sql);

	foreach ($explode as $value)
	{
		$value = trim($value);
		if ($value)
		{
			$result = @mysqli_query($GLOBALS['db_connect'], $value);
			if (!$result)
			{
				echo '<p>There was an error creating tables. Please speak to your system administrator. Below is a description of the error:</p><p class="notice">' . mysqli_error($GLOBALS['db_connect']) . '</p>';
				$step = 0;
				break;
				exit_error();
			}
		}
	}

	foreach ($defaults as $key => $value)
	{
		if ($key == 'config')
		{
			foreach ($value as $sub_key => $sub_value)
			{
				// unset extra config fields not stored in db
				unset($sub_value['description']);
				unset($sub_value['type']);
				unset($sub_value['required']);
				unset($sub_value['allowed']);
				$value[$sub_key] = $sub_value;
			}
		}

		insert_from_array($key, $value);
	}

	$step = 4;
}

if ($step == 5)
{
	@db_connect($_SESSION['config_db']['host'], $_SESSION['config_db']['username'], $_SESSION['config_db']['password'], $_SESSION['config_db']['name'], $_SESSION['config_db']['port']) or exit_error('unable to connect to database');

	$admin = array();
	foreach ($_POST['admin'] as $key => $value)
	{
		$value = trim($value);
		$value = strip_tags($value);
		$value = stripslashes($value);

		if ($value == '')
		{
			$form_check = false;
			$errors[] = 'Missing required form field(s)';
			break;
		}

		if ($key == 'email' && $value && !email_check($value))
		{
			$form_check = false;
			$errors[] = 'Invalid email address';
		}

		if ($key == 'password' && $value && !password_check($value))
		{
			$form_check = false;
			$errors[] = 'Passwords must be ' . $password_length_min . '-' . $password_length_max . ' characters (no spaces)';
		}

		$admin[$key] = $value;
	}

	if (!$errors)
	{
		foreach ($admin as $key => $value) {$admin_escaped[$key] = mysqli_real_escape_string($GLOBALS['db_connect'], $value);}
		extract($admin_escaped);
		$sql = "INSERT INTO contacts SET
		date_time = '$gm_date_time',
		first_name = '$first_name',
		last_name = '$last_name',
		email = '$email',
		country = 'USA',
		password = '" . password_wrapper('hash', $password) . "',
		access = 'admin'";
		@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT INTO contacts');

		$config_db_string = str_replace("define('INSTALLED', false);", "define('INSTALLED', true);", $_SESSION['config_db_string']);
		@file_put_contents('config_db.php', $config_db_string) or exit_error('cannot open config_db.php');

		$_SESSION['goto_config'] = true;
	}

	if (!$form_check) {$step = 4;}
}

display_step($step);
?>
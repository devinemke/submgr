<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

if (!isset($_COOKIE['submgr_cookie_test']) && !isset($_GET['token']))
{
	$error_output = $no_cookies_text;
	exit_error();
}

function get_describe($full_columns = false)
{
	global $show_tables;

	$describe = array();
	foreach ($show_tables as $value)
	{
		if ($full_columns)
		{
			$result = @mysqli_query($GLOBALS['db_connect'], "SHOW FULL COLUMNS FROM `$value`") or exit_error('query failure: SHOW FULL COLUMNS');
			while ($row = mysqli_fetch_assoc($result)) {$describe[$value][$row['Field']] = $row;}
		}
		else
		{
			$result = @mysqli_query($GLOBALS['db_connect'], "DESCRIBE `$value`") or exit_error('query failure: DESCRIBE');
			while ($row = mysqli_fetch_assoc($result)) {$describe[$value][$row['Field']] = $row['Type'];}
		}
	}

	$GLOBALS['describe'] = $describe;
	$_SESSION['describe'] = $describe;
}

get_describe();

function sync_last_action($submission_id)
{
	$last_action_id = 'NULL';
	$last_reader_id = 'NULL';
	$last_action_type_id = 'NULL';
	$last_receiver_id = 'NULL';

	// subquery
	$sql = 'SELECT action_id, reader_id, action_type_id, receiver_id FROM actions WHERE action_id = (SELECT MAX(action_id) FROM actions WHERE submission_id = ' . $submission_id . ')';
	$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT last action FOR sync_last_action');
	if (mysqli_num_rows($result))
	{
		$row = mysqli_fetch_assoc($result);
		if ($row['action_id']) {$last_action_id = $row['action_id'];}
		if ($row['reader_id']) {$last_reader_id = $row['reader_id'];}
		if ($row['action_type_id']) {$last_action_type_id = $row['action_type_id'];}
		if ($row['receiver_id']) {$last_receiver_id = $row['receiver_id'];}
	}

	$sql = 'UPDATE submissions SET last_action_id = ' . $last_action_id . ', last_reader_id = ' . $last_reader_id . ', last_action_type_id = ' . $last_action_type_id . ', last_receiver_id = ' . $last_receiver_id . ' WHERE submission_id = ' . $submission_id;
	@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE submissions FOR sync_last_action');

	return true;
}

if (isset($_GET['token']) && $_GET['token'])
{
	$token = trim($_GET['token']);

	if (strlen($token) != 40 || !ctype_alnum($token)) {kill_session('regenerate'); exit_error('invalid reset token');} // session needed for form_hash()

	$sql = "SELECT * FROM resets WHERE token = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $token) . "' ORDER BY date_time DESC LIMIT 1";
	$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('SELECT reset');
	if (mysqli_num_rows($result))
	{
		$row = mysqli_fetch_assoc($result);
		if ($gm_timestamp - strtotime($row['date_time'] . ' GMT') > 3600)
		{
			$error_output = 'This account password reset has expired. For security, password resets expire after one hour. You may reset your password again <a href="' . $app_url_slash . 'index.php?page=help">here</a>. If you need additional help please contact ' . mail_to($config['admin_email']) . '.';
			kill_session('regenerate'); // session needed for form_hash()
			exit_error();
		}
		else
		{
			$result_contact = @mysqli_query($GLOBALS['db_connect'], "SELECT * FROM contacts WHERE contact_id = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $row['contact_id']) . "'") or exit_error('query failure: SELECT FROM contacts');
			$_SESSION['contact_reset'] = mysqli_fetch_assoc($result_contact);
			$_SESSION['reset'] = $row;
		}
	}
	else
	{
		kill_session('regenerate'); // session needed for form_hash()
		exit_error('reset token not found');
	}
}

if (isset($_SESSION['login']) && $_SESSION['login'] && $module == 'update' && $submit == 'continue')
{
	form_hash('validate');

	if (isset($_SESSION['post']['password']) && $_SESSION['post']['password']) {$_SESSION['post']['password'] = password_wrapper('hash', $_SESSION['post']['password']);} else {unset($fields['password']);}
	foreach ($fields as $key => $value)
	{
		if ($value['section'] == 'contact' && $key != 'password2')
		{
			if (isset($_SESSION['post'][$key]) && $_SESSION['post'][$key]) {$sql_array[$key] = $key . ' = "' . mysqli_real_escape_string($GLOBALS['db_connect'], $_SESSION['post'][$key]) . '"';} else {$sql_array[$key] = $key . ' = NULL';}
		}
	}

	$sql = 'UPDATE contacts SET ' . implode(', ', $sql_array) . ' WHERE contact_id = ' . $_SESSION['contact']['contact_id'];
	@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE contacts');
	$notice = 'Your information has been successfully updated.';
	extract($_SESSION['post']);
	if ($config['send_mail_staff']) {send_mail('staff', 'updates');}
	$login_email = $email; // for login routine below
	if ($password) {$login_password = $password;} else {$login_password = $_SESSION['contact']['password'];} // for login routine below
	unset($title); // to hide the last submission from display()
	$submit = 'login';
	$module = 'account';
	$_SESSION['module'] = 'update2account';
}

if (isset($_GET['first_submission']) && isset($_SESSION['post']['email']) && isset($_SESSION['post']['password']))
{
	$_POST['login_email'] = $_SESSION['post']['email'];
	$_POST['login_password'] = $_SESSION['post']['password'];
	$_POST['form_hash'] = $_SESSION['csrf_token']; // otherwise form_hash('validate') below will fail
	$submit = 'login';
}

if ($submit == 'login')
{
	form_hash('validate');
	if (isset($_SESSION['goto_config'])) {$goto_config = 'Y';} // for install
	$keep = array('csrf_token', 'goto_config', 'module');
	flush_session($keep);
	$_SESSION['login'] = false;

	$_POST = cleanup($_POST, 'strip_tags', 'stripslashes');
	extract($_POST);
	$email = htmlspecialchars($login_email); // to pre-populate login form

	if (!$login_email)
	{
		$form_check = false;
		$errors[] = 'missing email';
	}
	else
	{
		if (!email_check($login_email))
		{
			$form_check = false;
			$errors[] = 'invalid email address';
		}
	}

	if (!$login_password)
	{
		$form_check = false;
		$errors[] = 'missing password';
	}
	else
	{
		if (!password_check($login_password))
		{
			$form_check = false;
			$errors[] = 'passwords must be ' . $password_length_min . '-' . $password_length_max . ' characters (no spaces)';
		}
	}

	if (!$form_check)
	{
		$notice = '<br><div>ERROR!<ul>';
		foreach ($errors as $value) {$notice .= '<li>' . $value . '</li>';}
		$notice .= '</ul></div>';
		exit_error();
	}

	$error_output_generic = '<p><span class="notice">Invalid login:</span> If you have forgotten your password, please visit our <a href="' . $_SERVER['PHP_SELF'] . '?page=help">help page</a>.</p>';

	$result = @mysqli_query($GLOBALS['db_connect'], "SELECT * FROM contacts WHERE email = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $login_email) . "'") or exit_error('query failure: SELECT FROM contacts');
	if (!mysqli_num_rows($result))
	{
		$error_output = $error_output_generic;
		exit_error();
	}
	else
	{
		$_SESSION['contact'] = mysqli_fetch_assoc($result);

		if (!password_wrapper('verify', $login_password, $_SESSION['contact']['password']))
		{
			$error_output = $error_output_generic;
			exit_error();
		}

		$_SESSION['login'] = true;
		unset($_SESSION['module']);

		// contacts.password must be VARCHAR(255) for password_needs_rehash to work
		if (isset($GLOBALS['password_needs_rehash']) && $describe['contacts']['password'] == 'varchar(255)')
		{
			$hash = password_wrapper('hash', $login_password);
			$sql = "UPDATE contacts SET password = '$hash' WHERE contact_id = " . mysqli_real_escape_string($GLOBALS['db_connect'], $_SESSION['contact']['contact_id']);
			@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('UPDATE contact password');
			$_SESSION['contact']['password'] = $hash; // so new password hash is used
		}

		if (in_array('resets', $show_tables))
		{
			$sql = 'DELETE FROM resets WHERE contact_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $_SESSION['contact']['contact_id']);
			@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('DELETE reset');
		}

		if (!$_SESSION['contact']['access'] || $_SESSION['contact']['access'] == 'blocked') {$module = 'account';} else {$module = 'submissions';}
		if (isset($goto_config)) {$module = 'configuration'; header('location: ' . $_SERVER['PHP_SELF'] . '?page=login&module=configuration&submodule=general'); exit();} // for install
	}
}

if (isset($_GET['first_submission']) && isset($_SESSION['login']) && $_SESSION['login'])
{
	$submit = '';
	$module = 'submit';
}

if (isset($_SESSION['login']) && $_SESSION['login'])
{
	$page_title = $module;
	if (isset($modules[$module]) && !$_SESSION['contact']['access']) {$page_title = $modules[$module];}

	$access_grouping = array(
	'admin_editor' => array('admin', 'editor'),
	'active' => array('active 1', 'active 2', 'active 3', 'active 4', 'active 5'),
	'no_access' => array('', 'inactive', 'blocked'),
	);
	$access_grouping['staff'] = array_merge($access_grouping['admin_editor'], $access_grouping['active']);

	if ($_SESSION['contact']['access'] && in_array($_SESSION['contact']['access'], $access_grouping['active'])) {$access_number = substr($_SESSION['contact']['access'], -1);}
}
elseif (isset($_SESSION['contact_reset']))
{
	function form_new_password()
	{
		extract($GLOBALS);
		$output = '';

		$output .= '
		Please enter a new password.<br><br>
		<form action="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '" method="post" name="form_new_password" id="form_new_password">
		<table>
		<tr><td class="row_left"><label for="password" id="label_password">password:</label></td><td><input type="password" id="password" name="password" value="" maxlength="' . $password_length_max . '"> <span class="small">(' . $password_length_min . '-' . $password_length_max . ' characters)</span></td></tr>
		<tr><td class="row_left"><label for="password2" id="label_password2">confirm password:</label></td><td><input type="password" id="password2" name="password2" value="" maxlength="' . $password_length_max . '"></td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" id="form_new_password_submit" name="submit" value="submit" class="form_button" style="margin-top: 10px;"></tr>
		</table>
		</form>
		';

		return $output;
	}

	$display_login = false;

	if (!$submit)
	{
		$error_output = form_new_password();
		exit_error();
	}

	if ($submit)
	{
		form_hash('validate');
		$_POST = cleanup($_POST, 'strip_tags', 'stripslashes');
		extract($_POST);

		if (!$password || !$password2)
		{
			$form_check = false;
			$errors[] = 'missing password';
		}

		if (!password_check($password) || !password_check($password2))
		{
			$form_check = false;
			$errors[] = 'passwords must be ' . $password_length_min . '-' . $password_length_max . ' characters (no spaces)';
		}

		if ($password != $password2)
		{
			$form_check = false;
			$errors[] = 'passwords do not match';
		}

		if (!$form_check)
		{
			$notice = 'ERROR: ' . implode('<br>', $errors);
			$error_output = '<div class="notice">' . $notice . '</div><br>' . form_new_password();
			exit_error();
		}

		$sql = "UPDATE contacts SET password = '" . password_wrapper('hash', $password) . "' WHERE contact_id = " . mysqli_real_escape_string($GLOBALS['db_connect'], $_SESSION['contact_reset']['contact_id']);
		@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('UPDATE password reset');

		$sql = 'DELETE FROM resets WHERE contact_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $_SESSION['contact_reset']['contact_id']);
		@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('DELETE reset');

		$email = htmlspecialchars($_SESSION['contact_reset']['email']); // to pre-populate login form
		$display_login = true;
		kill_session('regenerate'); // session needed for form_hash()
		$error_output = '<img src="arrow_left_2.png" alt="arrow left" width="16" height="13" style="vertical-align: middle;"> Your password has been updated successfully. Please log in to access your account.';
		exit_error();
	}
}
else
{
	$error_output = '<img src="arrow_left_2.png" alt="arrow left" width="16" height="13" style="vertical-align: middle;"> To access this page you must first login using the form on the left.';
	exit_error();
}

if ($module == 'pay_submission')
{
	// this all needs to happen before javascript for price
	if (!$submit)
	{
		if (isset($_GET['submission_id']) && $_GET['submission_id'] && is_numeric($_GET['submission_id'])) {$submission_id = (int) $_GET['submission_id'];} else {exit_error('invalid submission ID');}
		if (!isset($_SESSION['submissions'][$submission_id])) {exit_error('<p>You are not authorized to access this submission.</p>');}
		$_SESSION['pay_submission_id'] = $submission_id;
	}

	extract($_SESSION['submissions'][$_SESSION['pay_submission_id']]);
	get_price();

	$url = '';
	if (isset($genre_id) && $genres['all'][$genre_id]['redirect_url']) {$url = $genres['all'][$genre_id]['redirect_url'];} else {$url = $config['redirect_url'];}
	if (!$url) {exit_error('redirect URL not configured');}
}

if ($submodule && $submodule != 'forwards') {$_SESSION['submodule'] = $submodule;}
if (!isset($_REQUEST['submodule']) && isset($_SESSION['submodule'])) {$submodule = $_SESSION['submodule'];}

if ($_SESSION['contact']['access'])
{
	function get_readers()
	{
		$readers = array();

		$result = @mysqli_query($GLOBALS['db_connect'], "SELECT contact_id, first_name, last_name, email, access FROM contacts WHERE access IS NOT NULL AND access != 'blocked' ORDER BY last_name, first_name") or exit_error('query failure: SELECT readers');
		while ($row = mysqli_fetch_assoc($result))
		{
			$row = array_map('strval', $row);
			$row_display = array_map('htmlspecialchars', $row);
			$readers['all'][$row['contact_id']] = $row_display;
			$readers['raw'][$row['contact_id']] = $row;
			if ($row['access'] != 'inactive') {$readers['active'][] = $row['contact_id'];} else {$readers['inactive'][] = $row['contact_id'];}
		}

		$GLOBALS['readers'] = $readers;
		$_SESSION['readers'] = $readers;
	}

	function get_min_max($table, $id_field)
	{
		$result = @mysqli_query($GLOBALS['db_connect'], "SELECT MIN($id_field) AS min_id, DATE(MIN(date_time)) AS min_date_time, DATE(MIN(timestamp)) AS min_timestamp, MAX($id_field) AS max_id, DATE(MAX(date_time)) AS max_date_time, DATE(MAX(timestamp)) AS max_timestamp FROM `$table`") or exit_error('query failure: SELECT MIN MAX ' . $table);
		$array = mysqli_fetch_assoc($result);
		$GLOBALS['min_max'][$table] = $array;
	}

	function cascading_deletes($where)
	{
		global $config;

		$deletes['contacts'] = 0;
		$deletes['submissions'] = 0;
		$deletes['actions'] = 0;
		$deletes['files'] = 0;

		$result_select_contacts = @mysqli_query($GLOBALS['db_connect'], 'SELECT contact_id FROM contacts WHERE ' . $where) or exit_error('query failure: SELECT contacts for delete');
		if (mysqli_num_rows($result_select_contacts))
		{
			while ($row = mysqli_fetch_assoc($result_select_contacts)) {$delete_contacts[] = $row['contact_id'];}
			$delete_contacts_string = implode(',', $delete_contacts);

			if (isset($_POST['delete_related']))
			{
				$result_select_submissions = @mysqli_query($GLOBALS['db_connect'], 'SELECT submission_id, YEAR(date_time) AS year, ext FROM submissions WHERE submitter_id IN(' . $delete_contacts_string . ')') or exit_error('query failure: SELECT submissions for DELETE');
				if (mysqli_num_rows($result_select_submissions))
				{
					while ($row = mysqli_fetch_assoc($result_select_submissions))
					{
						$delete_submissions[] = $row['submission_id'];
						$unlink = @unlink($config['upload_path'] . $row['year'] . '/' . $row['submission_id'] . '.' . $row['ext']);
						if ($unlink) {$deletes['files']++;}
					}

					$delete_submissions_string = implode(',', $delete_submissions);

					@mysqli_query($GLOBALS['db_connect'], 'DELETE FROM submissions WHERE submission_id IN(' . $delete_submissions_string . ')') or exit_error('query failure: DELETE submissions');
					$deletes['submissions'] = mysqli_affected_rows($GLOBALS['db_connect']);

					$result = mysqli_query($GLOBALS['db_connect'], 'SELECT action_id, YEAR(date_time) AS year, ext FROM actions WHERE submission_id IN(' . $delete_submissions_string . ')') or exit_error('query failure: SELECT actions for DELETE');
					while ($row = mysqli_fetch_assoc($result))
					{
						$unlink = @unlink($config['upload_path'] . $row['year'] . '/action_' . $row['action_id'] . '.' . $row['ext']);
						if ($unlink) {$deletes['files']++;}
					}

					@mysqli_query($GLOBALS['db_connect'], 'DELETE FROM actions WHERE submission_id IN(' . $delete_submissions_string . ')') or exit_error('query failure: DELETE actions');
					$deletes['actions'] = mysqli_affected_rows($GLOBALS['db_connect']);
				}
			}

			@mysqli_query($GLOBALS['db_connect'], 'DELETE FROM contacts WHERE contact_id IN(' . $delete_contacts_string . ')') or exit_error('query failure: DELETE contacts');
			$deletes['contacts'] = mysqli_affected_rows($GLOBALS['db_connect']);
		}

		$GLOBALS['deletes'] = $deletes;
	}

	get_groups();

	if ($module == 'submissions')
	{
		if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
		{
			if ($submodule == 'delete')
			{
				if (isset($_REQUEST['submission_id']) && $_REQUEST['submission_id'] && $_REQUEST['submission_id'] != $_SESSION['submission']['submission_id']) {$submodule = 'fin';}
				if ($submodule == 'delete') {$_REQUEST['submission_id'] = $_SESSION['submission']['submission_id'];}

				if (isset($_REQUEST['submission_id']))
				{
					$_SESSION['table'] = 'submissions';
					$_SESSION['id_name'] = 'submission_id';
					$_SESSION['id_value'] = $_REQUEST['submission_id'];
					$submit = 'confirm';
				}

				if (isset($_REQUEST['action_id']))
				{
					$_SESSION['table'] = 'actions';
					$_SESSION['id_name'] = 'action_id';
					$_SESSION['id_value'] = $_REQUEST['action_id'];
					$submit = 'confirm';
				}

				if ($submit == 'confirm')
				{
					$deletes['submissions'] = 0;
					$deletes['actions'] = 0;
					$deletes['files'] = 0;

					$sql = 'DELETE FROM ' . $_SESSION['table'] . ' WHERE ' . $_SESSION['id_name'] . ' = ' . $_SESSION['id_value'];
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: DELETE');
					$deletes[$_SESSION['table']] = mysqli_affected_rows($GLOBALS['db_connect']);

					if ($_SESSION['table'] == 'submissions')
					{
						$unlink = @unlink($config['upload_path'] . gmdate('Y', strtotime($_SESSION['submission']['date_time'])) . '/' . $_SESSION['submission']['submission_id'] . '.' . $_SESSION['submission']['ext']);
						if ($unlink) {$deletes['files']++;}
					}

					if ($_SESSION['table'] == 'actions')
					{
						$path = $config['upload_path'] . gmdate('Y', strtotime($_SESSION['submission']['actions'][$_SESSION['id_value']]['date_time'])) . '/action_' . $_SESSION['id_value'] . '.' . $_SESSION['submission']['actions'][$_SESSION['id_value']]['ext'];

						if (file_exists($path))
						{
							$unlink = @unlink($path);
							if ($unlink) {$deletes['files']++;}
						}

						sync_last_action($_SESSION['submission']['submission_id']);
					}

					if ($_SESSION['table'] == 'submissions' && $_SESSION['submission']['actions'])
					{
						$sql = 'DELETE FROM actions WHERE action_id IN(' . implode(',', array_keys($_SESSION['submission']['actions'])) . ')';
						@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: DELETE related actions');
						$deletes['actions'] = mysqli_affected_rows($GLOBALS['db_connect']);

						foreach ($_SESSION['submission']['actions'] as $key => $value)
						{
							$path = $config['upload_path'] . gmdate('Y', strtotime($value['date_time'])) . '/action_' . $key . '.' . $value['ext'];
							$unlink = @unlink($path);
							if ($unlink) {$deletes['files']++;}
						}
					}

					foreach ($deletes as $key => $value)
					{
						if ($value) {$notice .= 'deleted ' . $key . ': ' . $value . '<br>';}
					}

					$submodule = 'fin';
					if ($_SESSION['table'] == 'submissions') {unset($_REQUEST['submission_id']);}
				}

				if ($submit == 'cancel')
				{
					$submodule = 'fin';
				}
			}

			if ($submit == 'apply to tagged')
			{
				$submodule = 'tag';
				$submit = 'search submissions';
				if (isset($_SESSION['offset'])) {$_GET['offset'] = $_SESSION['offset'];}

				if (!isset($_POST['tag']))
				{
					$form_check = false;
					$errors[] = 'no tagged submissions';
				}

				if (!$_POST['tag_action_type_id'])
				{
					$form_check = false;
					$errors[] = 'no action selected';
				}

				if (in_array($_POST['tag_action_type_id'], $action_types['forwards']) && !$_POST['tag_receiver_id'])
				{
					$form_check = false;
					$errors[] = 'no receiver selected';
				}

				if (!$form_check)
				{
					$notice = 'ERROR:<ul>';
					foreach ($errors as $value) {$notice .= '<li>' . $value . '</li>';}
					$notice .= '</ul>';
				}
				else
				{
					$tag_confirm = true;
				}
			}

			if ($submit == 'confirm' && isset($_POST['tag']))
			{
				form_hash('validate');
				extract($_SESSION['contact']);
				$submodule = 'insert_action';

				$reader = $_SESSION['contact']['first_name'] . ' ' . $_SESSION['contact']['last_name'];
				$action = $action_types['all'][$_SESSION['tag_action_type_id']]['name'];

				if (isset($_SESSION['tag_receiver_id']) && $_SESSION['tag_receiver_id']) {$receiver = $_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['first_name'] . ' ' . $_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['last_name'];} else {$receiver = '';}

				// needed to determine if receiver is in blind group
				if (isset($_SESSION['tag_receiver_id'])) {$access_group = str_replace('active ', '', $_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['access']);}

				if ($action_types['all'][$_SESSION['tag_action_type_id']]['from_reader'])
				{
					$from_name = $reader;
					$from_email = $_SESSION['contact']['email'];
					$from = make_email($reader, $_SESSION['contact']['email']);
				}
				else
				{
					$from_name = $config['company_name'];
					$from_email = $config['general_dnr_email'];
					$from = make_email($config['company_name'], $config['general_dnr_email']);
				}

				$preview_all = array(
				'reader' => $reader,
				'action' => $action,
				'receiver' => $receiver,
				'notes' => '',
				'from_name' => $from_name,
				'from_email' => $from_email,
				'from' => $from,
				'to' => '',
				'subject' => $action_types['all'][$_SESSION['tag_action_type_id']]['subject'],
				'body' => trim(str_replace('[message]', '', $action_types['all'][$_SESSION['tag_action_type_id']]['body']))
				);

				$tag = array();
				$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT submission_id, submitter_id, title, writer, genre_id FROM submissions WHERE submission_id IN(' . implode(',', $_POST['tag']) . ') ORDER BY date_time, submission_id') or exit_error('query failure: SELECT tag submissions');
				while ($row = mysqli_fetch_assoc($result))
				{
					$tag[$row['submission_id']] = $row;
					$writers_get[$row['submission_id']] = $row['submitter_id'];
				}

				$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT contact_id, first_name, last_name, email FROM contacts WHERE contact_id IN(' . implode(',', $writers_get) . ')') or exit_error('query failure: SELECT tag contacts');
				while ($row = mysqli_fetch_assoc($result)) {$writers_got[$row['contact_id']] = $row;}

				foreach ($tag as $key => $value)
				{
					$tag[$key]['contact'] = $writers_got[$value['submitter_id']];
					if (!$value['writer']) {$tag[$key]['writer'] = $tag[$key]['contact']['first_name'] . ' ' . $tag[$key]['contact']['last_name'];}
					if ($value['genre_id']) {$tag[$key]['genre'] = $_SESSION['genres']['all'][$value['genre_id']]['name'];}
				}

				$preview_digest = $preview_all;
				$preview_digest['subject'] = $config['company_name'] . ' submissions forwarded by ' . $reader;
				$preview_digest['body'] = $reader . ' has forwarded you the following submissions:' . "\n\n" . '[digest_list]' . "\n" . 'Please visit ' . $app_url . ' to log in and check your forwarded submissions.';
				$digest_list = '';

				foreach ($tag as $key => $value)
				{
					extract($value);
					extract($value['contact']);

					$sql = "INSERT INTO actions SET
					date_time = '$gm_date_time',
					submission_id = $key,
					reader_id = " . $_SESSION['contact']['contact_id'] . ",
					action_type_id = " . $_SESSION['tag_action_type_id'];
					if ($receiver) {$sql .= ', receiver_id = ' . $_SESSION['tag_receiver_id'];}

					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT actions');
					$action_id = mysqli_insert_id($GLOBALS['db_connect']);
					sync_last_action($key);

					if ($config['send_mail_staff'])
					{
						extract($_SESSION['contact']);
						send_mail('staff', 'actions');
					}

					if ($config['send_mail_contact'] && isset($_POST['send_action_mail']))
					{
						extract($value);
						extract($value['contact']);
						$preview = $preview_all;

						if (in_array($_SESSION['tag_action_type_id'], $action_types['forwards']))
						{
							$to = make_email($_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['first_name'] . ' ' . $_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['last_name'], $_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['email']);
							$_SESSION['to_email'] = $_SESSION['readers']['all'][$_SESSION['tag_receiver_id']]['email'];

							// replace writer with [blind] if receiver is in blind group or genre is blind
							if ($_SESSION['groups'][$access_group]['blind'] || (isset($_SESSION['genres']['all'][$genre_id]) && $_SESSION['genres']['all'][$genre_id]['blind'])) {$writer = '[blind]';}

							$digest_list .= '- "' . $title . '" by ' . $writer . "\n";
						}
						else
						{
							$to = make_email($value['contact']['first_name'] . ' ' . $value['contact']['last_name'], $value['contact']['email']);
							$_SESSION['to_email'] = $value['contact']['email'];
						}

						// only send if action is not forward (otherwise send digest)
						if (!in_array($_SESSION['tag_action_type_id'], $action_types['forwards']))
						{
							$preview['to'] = $to;
							$preview['subject'] = replace_placeholders($preview['subject']);
							$preview['body'] = replace_placeholders($preview['body']);
							send_mail('contact', 'action');
						}
					}
				}

				if ($config['send_mail_contact'] && isset($_POST['send_action_mail']) && in_array($_SESSION['tag_action_type_id'], $action_types['forwards']))
				{
					$preview_digest['to'] = $to;
					$preview_digest['body'] = str_replace('[digest_list]', $digest_list, $preview_digest['body']);
					$preview = $preview_digest;
					send_mail('contact', 'action');
				}

				$notice = count($_POST['tag']) . ' new action(s) inserted';
				$submit = 'search submissions';
				if (isset($_SESSION['offset'])) {$_GET['offset'] = $_SESSION['offset'];}
				unset($_POST['tag']);
				unset($submission_id);
				unset($title); // to avoid conflict with contact tooltip
			}
		}

		if ($submit == 'send')
		{
			form_hash('validate');
			$submodule = 'insert_action';

			if ((!in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) && strpos($action_types['all'][$_SESSION['insert_action']['action_type_id']]['access_groups'], $access_number) === false) {exit_error('unauthorized action type');}

			if (isset($_SESSION['file_upload']) && $_SESSION['file_upload']['is_uploaded_file'])
			{
				$pathinfo = pathinfo($_SESSION['file_upload']['filename_temp']);
				if (isset($pathinfo['extension']) && $pathinfo['extension']) {$ext = strtolower($pathinfo['extension']);} else {$ext = '';}
				$_SESSION['insert_action']['ext'] = $ext;
			}

			foreach ($_SESSION['insert_action'] as $key => $value)
			{
				if ($value) {$value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . "'";} else {$value = 'NULL';}
				$sql_array[] = $key . ' = ' . $value;
			}

			$sql = "INSERT INTO actions SET date_time = '$gm_date_time', " . implode(', ', $sql_array);
			@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT actions');
			$action_id = mysqli_insert_id($GLOBALS['db_connect']);
			sync_last_action($_SESSION['insert_action']['submission_id']);
			$notice = 'action_id ' . $action_id . ' inserted';

			if (isset($_SESSION['file_upload']) && $_SESSION['file_upload']['is_uploaded_file'])
			{
				$filename = 'action_' . $action_id . '.' . $ext;
				$path = $upload_path_year . $filename;
				@rename($upload_path_year . $_SESSION['file_upload']['filename_temp'], $path) or exit_error('rename file');
			}

			extract($_SESSION['insert_action']);
			extract($_SESSION['contact']);

			if ($config['send_mail_staff']) {send_mail('staff', 'actions');}
			if ($config['send_mail_contact'] && isset($_POST['send_action_mail']))
			{
				$preview = $_SESSION['preview'];

				if (($_SESSION['insert_action']['receiver_id'] && !$_SESSION['groups'][$_SESSION['safe']['access_group']]['blind']) || !in_array($_SESSION['insert_action']['action_type_id'], $_SESSION['action_types']['forwards']))
				{
					$preview['subject'] = $_SESSION['safe']['subject'];
					$preview['body'] = $_SESSION['safe']['body'];
				}

				if ($_SESSION['submission']['genre_id'] && $_SESSION['genres']['all'][$_SESSION['submission']['genre_id']]['blind'] && in_array($_SESSION['insert_action']['action_type_id'], $_SESSION['action_types']['forwards'])) {$preview = $_SESSION['preview'];}

				send_mail('contact', 'action');
			}

			unset($_SESSION['file_upload']);
		}

		if ($submit == 'cancel')
		{
			if (isset($_POST['tag'])) {$submit = 'search submissions';}
			if (isset($_SESSION['file_upload']['filename_temp'])) {@unlink($upload_path_year . $_SESSION['file_upload']['filename_temp']);}
			unset($_SESSION['file_upload']);
		}

		if (isset($_POST['submit']) && $_POST['submit'] == 'search submissions') {unset($_POST['tag']);}
	}

	if ($module == 'contacts' && in_array($_SESSION['contact']['access'], $access_grouping['staff']))
	{
		if (isset($_GET['single_contact'])) {$submodule = '';} // so contact_id won't be flushed out when coming from other modules

		$non_searchable = array('date_time', 'timestamp', 'password', 'access');

		foreach ($describe['contacts'] as $key => $value)
		{
			$fields_keys[$key] = ''; // fields as keys with blank values for INSERT
			if (!in_array($key, $non_searchable)) {$fields_searchable[] = $key;} // fields for search dropdown
		}

		if (!isset($_SESSION['current_contact_id'])) {$_SESSION['current_contact_id'] = '';}

		$submit_submodule = array(
		'update' => 'update',
		'delete' => 'delete',
		'confirm' => 'delete'
		);

		if (isset($submit_submodule[$submit])) {$submodule = $submit_submodule[$submit];}

		if ($submodule == 'update' || $submit == 'insert')
		{
			form_hash('validate');

			// active staff cannot update their own access nor insert
			if (in_array($_SESSION['contact']['access'], $access_grouping['active']))
			{
				unset($_POST['access']);
				if ($submit == 'insert') {exit_error('<p>You are not authorized to access this area.</p>');}
			}

			$_POST = cleanup($_POST, 'strip_tags', 'stripslashes');

			foreach ($_POST as $key => $value)
			{
				if (!in_array($key, array_keys($describe['contacts']))) {unset($_POST[$key]);}
			}

			// booleans
			if (!isset($_POST['mailing_list'])) {$_POST['mailing_list'] = '';}
			if (isset($_POST['email_notification'])) {$_POST['email_notification'] = implode(',', $_POST['email_notification']);} else {$_POST['email_notification'] = '';}

			if ($submodule == 'update') {$_POST = array('contact_id' => $_SESSION['current_contact_id']) + $_POST;}

			extract($_POST);

			if (!$first_name && !$last_name)
			{
				$form_check = false;
				$errors[] = 'You must enter a first or last name';
			}

			if ($email)
			{
				if (!email_check($email))
				{
					$form_check = false;
					$errors[] = 'Invalid email address';
				}

				$sql = "SELECT COUNT(*) AS count FROM contacts WHERE email = '$email'";
				if (isset($contact_id)) {$sql .= ' AND contact_id != ' . $contact_id;}
				$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT COUNT contacts');
				$row = mysqli_fetch_assoc($result);
				if ($row['count'])
				{
					$form_check = false;
					$errors[] = 'email address <b>' . $email . '</b> is already the database';
				}
			}

			if ($country && $country == 'USA')
			{
				if ($address1 && !$state)
				{
					$form_check = false;
					$errors[] = 'State required for USA address';
				}

				if ($zip && strlen($zip) < 5)
				{
					$form_check = false;
					$errors[] = 'Incomplete zip code';
				}
			}

			if (isset($access) && $access && $access != 'inactive' && $access != 'blocked')
			{
				if (!$email)
				{
					$form_check = false;
					$errors[] = 'Missing email (all staff members must have an email address)';
				}
			}

			if (isset($password) && $password && !password_check($password))
			{
				$form_check = false;
				$errors[] = 'passwords must be ' . $password_length_min . '-' . $password_length_max . ' characters (no spaces)';
			}

			if ($_SESSION['contact']['access'] != 'admin' && isset($access) && $access == 'admin')
			{
				// only admins can insert/update admins
				$form_check = false;
				$errors[] = 'You are not authorized to insert/update <b>admin</b> access status contacts.';
			}

			if (!$form_check)
			{
				$notice = 'The following errors were detected:<ul>';
				foreach ($errors as $value) {$notice .= '<li>' . $value . '</li>';}
				$notice .= '</ul>';
			}
			else
			{
				if (isset($_POST['password']) && $_POST['password']) {$_POST['password'] = password_wrapper('hash', $_POST['password']);} else {unset($_POST['password']);}

				foreach ($_POST as $key => $value)
				{
					if ($value) {$value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . "'";} else {$value = 'NULL';}
					$sql_array[] = $key . ' = ' . $value;
				}

				if ($submodule == 'update') {$sql = 'UPDATE'; $notice_ending = 'updated';}
				if ($submodule == 'insert') {$sql = 'INSERT INTO'; $notice_ending = 'inserted'; $sql_array[] = "date_time = '$gm_date_time'";}
				$sql .= ' contacts SET ' . implode(', ', $sql_array);
				if ($submodule == 'update') {$sql .= ' WHERE contact_id = ' . $_SESSION['current_contact_id'];}
				@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: ' . strtoupper($submodule) . ' contacts');
				if ($submodule == 'insert') {$_SESSION['current_contact_id'] = mysqli_insert_id($GLOBALS['db_connect']);}
				$notice = 'contact_id ' . $_SESSION['current_contact_id'] . ' successfully ' . $notice_ending;
				if ($config['send_mail_staff']) {send_mail('staff', 'updates');}
				$submodule = 'fin';
			}
		}

		if ($submodule == 'delete')
		{
			// active staff cannot delete contacts
			if (in_array($_SESSION['contact']['access'], $access_grouping['active'])) {exit_error('<p>You are not authorized to access this area.</p>');}

			if (isset($_REQUEST['contact_id']) && $_REQUEST['contact_id'] != $_SESSION['current_contact_id'])
			{
				// if user moves to another record (no DELETE nor CANCEL)
				$_SESSION['current_contact_id'] = $_REQUEST['contact_id'];
				$submit = 'cancel';
			}

			if ($submit == 'delete')
			{
				$submit = 'confirm';
				$_POST['delete_related'] = true;
			}

			if ($submit == 'confirm')
			{
				// only admins can delete other admins
				if ($_SESSION['contact']['access'] != 'admin' && $_SESSION['current_contact_array']['access'] == 'admin') {exit_error('<p>You are not authorized to delete this contact.</p>');}

				form_hash('validate');
				cascading_deletes('contact_id = ' . $_SESSION['current_contact_id']);

				if (isset($_SESSION['next_contact_id'])) {$_SESSION['current_contact_id'] = $_SESSION['next_contact_id'];}
				elseif (isset($_SESSION['prev_contact_id'])) {$_SESSION['current_contact_id'] = $_SESSION['prev_contact_id'];}
				else {$_SESSION['current_contact_id'] = '';}

				foreach ($deletes as $key => $value) {$notice .= 'deleted ' . $key . ': ' . $value . '<br>';}

				unset($_SESSION['sql']); // so old search query is flushed after delete
				$submodule = 'fin';
			}
		}

		if ($submodule == 'insert_submission' && $submit == 'continue')
		{
			form_hash('validate');
			extract($_SESSION['current_contact_array']); // to avoid blank_db error
			db_update('insert submission');
			if ($config['send_mail_staff']) {send_mail('staff', 'submissions');}
			if ($config['send_mail_contact'] && isset($_POST['send_action_mail']))
			{
				extract($_SESSION['post']);
				send_mail('contact', 'submission');
			}

			unset($_SESSION['file_upload']);
			$notice = 'submission successfully inserted';
			$submodule = 'fin';
		}

		if ($submodule || $submit == 'cancel') {$_REQUEST['contact_id'] = $_SESSION['current_contact_id'];} // back to previous record
		if ($submodule == 'insert' && isset($_GET['contact_id']) && $_GET['contact_id']) {$submodule = 'fin'; $_REQUEST['contact_id'] = $_GET['contact_id'];} // if user clicks on another name instead of confirmimg insert
		if ($submit == 'Go' || $submit == 'search contacts' || $submit == 'cancel') {$submodule = 'fin';}
		if ($submit == 'Go') {unset($_REQUEST['contact_id']);}
		if ($submit != 'search contacts') {unset($_REQUEST['search_field']); unset($_REQUEST['search_value']);}
	}

	if ($module == 'maintenance' && $_SESSION['contact']['access'] == 'admin')
	{
		if ($submodule == 'cleanup')
		{
			$deletes['temp_files'] = 0;
			$deletes['resets'] = 0;
			$deletes['truncate_resets'] = false;

			if ($submit == 'delete temp files')
			{
				form_hash('validate');

				if (isset($_SESSION['unrecorded files']) && $_SESSION['unrecorded files'])
				{
					foreach ($_SESSION['unrecorded files'] as $key => $value)
					{
						foreach ($value as $file)
						{
							$unlink = @unlink($config['upload_path'] . $key . '/' . $file);
							if ($unlink) {$deletes['temp_files']++;}
						}
					}
				}

				$sql = 'DELETE FROM resets WHERE date_time < (NOW() - INTERVAL 1 HOUR)';
				@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: DELETE resets');
				$deletes['resets'] = mysqli_affected_rows($GLOBALS['db_connect']);

				$sql = 'SELECT COUNT(*) AS count FROM resets';
				$result = mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: COUNT resets');
				$row = mysqli_fetch_assoc($result);
				if ($row['count'] == 0)
				{
					@mysqli_query($GLOBALS['db_connect'], 'TRUNCATE resets') or exit_error('query failure: TRUNCATE resets');
					$deletes['truncate_resets'] = true;
				}
			}

			if (isset($_SESSION['missing files']) && $_SESSION['missing files'] && isset($_GET['fill_missing']) && $_GET['fill_missing'])
			{
				$copy = '';
				foreach ($_SESSION['missing files'] as $key => $value)
				{
					foreach ($value as $sub_value)
					{
						$path = $config['upload_path'] . $key . '/' . $sub_value;
						$contents = file_put_contents($path, 'text') or exit_error('cannot write to ' . $path);
						$copy .= $contents . ' bytes written to ' . $path . '<br>' . "\n";
					}
				}
			}
		}

		if ($submodule == 'sample')
		{
			$copy = '
			<p>This function will insert or delete 100 sample contacts and subissions for testing purposes. Please note that all example contacts will have an email address with the domain <b>@example.com</b>.</p>
			<input type="submit" id="submit_insert_sample_data" name="submit" value="insert sample data" class="form_button" style="width: 150px;"> <input type="submit" id="submit_delete_sample_data" name="submit" value="delete sample data" class="form_button" style="width: 150px;">
			';

			if ($submit) {form_hash('validate');}

			if ($submit == 'insert sample data')
			{
				$names = array('Joe','John','Robert','Richard','Tom','William','Charles','Christopher','Kenneth','Jason','Noah','Aaron','Baker','Allen','George','Andrew','Bert','Earnest','James','Stephen','David','Taylor','Fredrick','Brian','Roger','Ann','Sally','Jane','Helen','Jennifer','Rachel','Mary','Hillary','Barbara','Ginger','Judy','Rebecca','Laura','Lauren','Betty','Joan','Margaret','Katherine','Christine','Phoebe','Melissa','May','Dina','Cindy','Lisa');
				$titles = array('River','Dream','Summer','Winter','Spring','Fall','Autumn','Fine','Joy','Madness','Anger','Clown','Year','Day','Morning','Evening','Night','Future','Past','Ocean','Lake','First','Second','Mother','Father','Brother','Sister','Earth','Sky','Water','Wind','Fire','Why','Depression','Battle','War','Peace','Breakfast','Lunch','Dinner','Up','Down','Left','Right','Black','White','Back','Front','Side','Top');

				include_once('inc_lists.php');
				$timestamp_minus3 = $gm_timestamp - (60 * 60 * 24 * 365 * 3);
				$genres_sample = $genres; // not conflict with global $genres

				for ($i = 1; $i <= 100; $i++)
				{
					shuffle($names);
					shuffle($titles);
					shuffle($genres_sample['all']);

					$rand_timestamp = rand($timestamp_minus3, $gm_timestamp);
					$rand_date_time = gmdate('Y-m-d H:i:s', $rand_timestamp);
					$rand_year = gmdate('Y', $rand_timestamp);

					$sql = "INSERT INTO contacts SET
					date_time = '$rand_date_time',
					first_name = '" . $names[0] . "',
					last_name = '" . $names[1] . "',
					address1 = '123 " . $names[3] . " St.',
					city = '" . $names[4] . "',
					state = '" . $states[array_rand($states)] . "',
					zip = '12345',
					country = 'USA'";

					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT sample contacts');
					$contact_id = mysqli_insert_id($GLOBALS['db_connect']);

					$email = strtolower($names[0]) . $contact_id . '@example.com';
					$password = password_wrapper('hash', 'password' . $contact_id);
					$sql = "UPDATE contacts SET email = '$email', password = '$password' WHERE contact_id = $contact_id";
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE sample contacts');

					$comments = implode(' ', array_slice($titles, 0, 20));
					$text = $titles[0] . ' ' . $titles[1] . "\r\n" . 'by ' . $names[0] . ' ' . $names[1] . "\r\n\r\n" . implode(' ', $titles);

					$sql = "INSERT INTO submissions SET
					date_time = '$rand_date_time',
					submitter_id = $contact_id,
					title = '" . $titles[0] . " " . $titles[1] . "',
					genre_id = '" . $genres_sample['all'][0]['genre_id'] . "',
					ext = 'txt',
					comments = '$comments'";

					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT sample submissions');
					$submission_id = mysqli_insert_id($GLOBALS['db_connect']);

					$rand_path = $config['upload_path'] . $rand_year . '/';
					if (!file_exists($rand_path)) {@mkdir($rand_path);}
					@file_put_contents($rand_path . $submission_id . '.txt', $text) or exit_error('cannot write to ' . $rand_path);
				}

				$notice = 'sample data inserted successfully';
			}

			if ($submit == 'delete sample data')
			{
				$_POST['delete_related'] = true;
				cascading_deletes("email LIKE '%@example.com'");

				if (!$deletes['contacts'])
				{
					$notice = 'no sample data found';
				}
				else
				{
					foreach ($deletes as $key => $value) {$notice .= 'deleted ' . $key . ': ' . $value . '<br>';}
				}
			}
		}

		if ($submodule == 'export')
		{
			get_min_max('contacts', 'contact_id');
			get_min_max('submissions', 'submission_id');

			function display_range($table, $id_field)
			{
				global $min_max;

				$output = '
				<b>export range:</b><br>
				<table class="padding_lr_5">
				<tr><td><input type="radio" id="export[' . $table . '][field][' . $id_field . ']" name="export[' . $table . '][field]" value="' . $id_field . '" checked> <label for="export[' . $table . '][field][' . $id_field . ']" id="label_export[' . $table . '][field][' . $id_field . ']">primary keys</label></td><td class="small"><label for="export[' . $table . '][range][' . $id_field . '][min]" id="label_export[' . $table . '][range][' . $id_field . '][min]">min:</label> <input type="text" id="export[' . $table . '][range][' . $id_field . '][min]" name="export[' . $table . '][range][' . $id_field . '][min]" value="' . $min_max[$table]['min_id'] . '" style="width: 100px;"> <label for="export[' . $table . '][range][' . $id_field . '][max]" id="label_export[' . $table . '][range][' . $id_field . '][max]">max:</label> <input type="text" id="export[' . $table . '][range][' . $id_field . '][max]" name="export[' . $table . '][range][' . $id_field . '][max]" value="' . $min_max[$table]['max_id'] . '" style="width: 100px;"></td></tr>
				<tr><td><input type="radio" id="export[' . $table . '][field][date_time]" name="export[' . $table . '][field]" value="date_time"> <label for="export[' . $table . '][field][date_time]" id="label_export[' . $table . '][field][date_time]">date created</label></td><td class="small"><label for="export[' . $table . '][range][date_time][min]" id="label_export[' . $table . '][range][date_time][min]">min:</label> <input type="text" id="export[' . $table . '][range][date_time][min]" name="export[' . $table . '][range][date_time][min]" value="' . $min_max[$table]['min_date_time'] . '" style="width: 100px;"> <label for="export[' . $table . '][range][date_time][max]" id="label_export[' . $table . '][range][date_time][max]">max:</label> <input type="text" id="export[' . $table . '][range][date_time][max]" name="export[' . $table . '][range][date_time][max]" value="' . $min_max[$table]['max_date_time'] . '" style="width: 100px;"></td></tr>
				<tr><td><input type="radio" id="export[' . $table . '][field][timestamp]" name="export[' . $table . '][field]" value="timestamp"> <label for="export[' . $table . '][field][timestamp]" id="label_export[' . $table . '][field][date_updated]">date updated</label></td><td class="small"><label for="export[' . $table . '][range][timestamp][min]" id="label_export[' . $table . '][range][timestamp][min]">min:</label> <input type="text" id="export[' . $table . '][range][timestamp][min]" name="export[' . $table . '][range][timestamp][min]" value="' . $min_max[$table]['min_timestamp'] . '" style="width: 100px;"> <label for="export[' . $table . '][range][timestamp][max]" id="label_export[' . $table . '][range][timestamp][max]">max:</label> <input type="text" id="export[' . $table . '][range][timestamp][max]" name="export[' . $table . '][range][timestamp][max]" value="' . $min_max[$table]['max_timestamp'] . '" style="width: 100px;"></td></tr>
				</table>
				';

				return $output;
			}

			if (!$submit)
			{
				$copy = '
				<p>These functions will export Submission Manager data into CSV files.</p>

				<p class="header">Export Contacts</p>
				<input type="checkbox" id="export[contacts][primary_key]" name="export[contacts][primary_key]" value="Y" checked> <label for="export[contacts][primary_key]" id="label_export[contacts][primary_key]">include primary key field</label><br>
				<input type="checkbox" id="export[contacts][field_names]" name="export[contacts][field_names]" value="Y" checked> <label for="export[contacts][field_names]" id="label_export[contacts][field_names]">include field names as first row</label><br>
				<input type="checkbox" id="export[contacts][staff]" name="export[contacts][staff]" value="Y"> <label for="export[contacts][staff]" id="label_export[contacts][staff]">include staff contacts</label><br>
				<input type="checkbox" id="export[contacts][clmp]" name="export[contacts][clmp]" value="Y"> <label for="export[contacts][clmp]" id="label_export[contacts][clmp]">use CLMP data structure</label><br><br>
				';

				$copy .= display_range('contacts', 'contact_id');

				$copy .= '
				<br>
				<input type="submit" name="submit" value="export contacts" class="form_button" style="width: 150px; margin-bottom: 20px;">

				<p class="header">Export Submissions</p>
				<input type="checkbox" id="export[submissions][primary_key]" name="export[submissions][primary_key]" value="Y" checked> <label for="export[submissions][primary_key]" id="label_export[submissions][primary_key]">include primary key field</label><br>
				<input type="checkbox" id="export[submissions][field_names]" name="export[submissions][field_names]" value="Y" checked> <label for="export[submissions][field_names]" id="label_export[submissions][field_names]">include field names as first row</label><br>
				<input type="checkbox" id="export[submissions][contacts]" name="export[submissions][contacts]" value="Y"> <label for="export[submissions][contacts]" id="label_export[submissions][contacts]">include contacts</label><br>
				<input type="checkbox" id="export[submissions][actions]" name="export[submissions][actions]" value="Y"><label for="export[submissions][actions]" id="label_export[submissions][actions]"> include actions</label><br><br>
				';

				$copy .= display_range('submissions', 'submission_id');

				$copy .= '
				<br>
				<input type="submit" name="submit" value="export submissions" class="form_button" style="width: 150px;">
				';
			}

			if (strpos($submit, 'export') !== false)
			{
				if ($submit == 'export contacts') {$table = 'contacts'; $id_field = 'contact_id';}
				if ($submit == 'export submissions') {$table = 'submissions'; $id_field = 'submission_id';}

				$fields['contacts'] = array(
				'contact_id' => 'AccountNo',
				'date_time' => 'date_time',
				'timestamp' => 'timestamp',
				'first_name' => 'First',
				'last_name' => 'Last',
				'email' => 'Email',
				'company' => 'Company',
				'address1' => 'Address1',
				'address2' => 'Address2',
				'city' => 'City',
				'state' => 'State',
				'zip' => 'Zip',
				'country' => 'Country',
				'phone' => 'Telephone',
				'mailing_list' => 'mailing_list',
				'access' => 'access',
				'email_notification' => 'email_notification',
				'notes' => 'notes'
				);

				$fields['submissions'] = array(
				'submission_id' => 'submissions.submission_id',
				'date_time' => 'submissions.date_time',
				'timestamp' => 'submissions.timestamp',
				'date_paid' => 'submissions.date_paid',
				'submitter_id' => 'submissions.submitter_id',
				'writer' => 'submissions.writer',
				'title' => 'submissions.title',
				'genre_id' => 'submissions.genre_id',
				'ext' => 'submissions.ext',
				'comments' => 'submissions.comments',
				'notes' => 'submissions.notes'
				);

				foreach ($_POST['export'][$table]['range'][$_POST['export'][$table]['field']] as $key => $value)
				{
					$value = trim($value);
					$value = strip_tags($value);
					$value = stripslashes($value);
					if (strpos($_POST['export'][$table]['field'], 'id') !== false) {$var = 'id';} else {$var = $_POST['export'][$table]['field'];}
					$var_name = $key . '_' . $var;
					if ($value == '') {$value = $min_max[$table][$var_name];}
					$$key = $value;
				}

				$fields_array = array_keys($fields[$table]);
				$field = $_POST['export'][$table]['field'];
				$extra = '';

				if ($table == 'submissions' && isset($_POST['export'][$table]['contacts']))
				{
					$fields['submissions']['first_name'] = 'contacts.first_name';
					$fields['submissions']['last_name'] = 'contacts.last_name';
					$fields['submissions']['email'] = 'contacts.email';
					$fields['submissions']['company'] = 'contacts.company';
					$fields['submissions']['address1'] = 'contacts.address1';
					$fields['submissions']['address2'] = 'contacts.address2';
					$fields['submissions']['city'] = 'contacts.city';
					$fields['submissions']['state'] = 'contacts.state';
					$fields['submissions']['zip'] = 'contacts.zip';
					$fields['submissions']['country'] = 'contacts.country';
					$fields['submissions']['phone'] = 'contacts.phone';
					$fields['submissions']['mailing_list'] = 'contacts.mailing_list';

					$fields_array = array_values($fields[$table]);
					$field = 'submissions.' . $field;
					$extra .= ', contacts';
				}

				$sql = 'SELECT ' . implode(', ', $fields_array) . ' FROM ' . $table . $extra . ' WHERE ' . $field . " BETWEEN '" . mysqli_real_escape_string($GLOBALS['db_connect'], $min) . "' AND '" . mysqli_real_escape_string($GLOBALS['db_connect'], $max) . "'";
				if ($table == 'contacts' && !isset($_POST['export'][$table]['staff'])) {$sql .= " AND (access IS NULL OR access = 'blocked')";}
				if ($table == 'submissions' && isset($_POST['export'][$table]['contacts'])) {$sql .= ' AND submitter_id = contact_id';}
				$sql .= ' ORDER BY ' . $id_field;

				$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT FROM ' . $table . ' for export');
				if (mysqli_num_rows($result))
				{
					$csv = '';

					if (isset($_POST['export'][$table]['field_names']))
					{
						if (!isset($_POST['export'][$table]['primary_key'])) {unset($fields[$table][$id_field]);}
						if (isset($_POST['export'][$table]['clmp'])) {$fields[$table]['submgr_id'] = 'submgr_id';} else {$fields[$table] = array_keys($fields[$table]);}
						foreach ($fields[$table] as $key => $value) {$fields[$table][$key] = '"' . $value . '"';}
						$csv = implode(',', $fields[$table]) . "\r\n";
					}

					if ($table == 'submissions' && isset($_POST['export'][$table]['actions']))
					{
						while ($row = mysqli_fetch_assoc($result)) {$submission_ids[] = $row['submission_id'];}
						$sql = 'SELECT action_id, submission_id, reader_id, action_type_id, receiver_id, first_name, last_name FROM actions, contacts WHERE reader_id = contact_id AND submission_id IN(' . implode(',', $submission_ids) . ')';
						$result_actions = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT FROM actions for export');
						if (mysqli_num_rows($result_actions))
						{
							get_readers();

							while ($row = mysqli_fetch_assoc($result_actions))
							{
								$action_display = $action_types['all'][$row['action_type_id']]['name'];
								if ($action_types['all'][$row['action_type_id']]['description']) {$action_display .= ' - ' . $action_types['all'][$row['action_type_id']]['description'];}
								$action_display .= ' (' . $row['first_name'] . ' ' . $row['last_name'];
								if ($row['receiver_id'] && isset($readers['all'][$row['receiver_id']])) {$action_display .= ' -> ' . $readers['all'][$row['receiver_id']]['first_name'] . ' ' . $readers['all'][$row['receiver_id']]['last_name'];}
								$action_display .= ')';
								$export_actions[$row['submission_id']][$row['action_id']] = $action_display;
							}
						}
						mysqli_data_seek($result, 0);
					}

					while ($row = mysqli_fetch_assoc($result))
					{
						$id = $row[$id_field];
						if (!isset($_POST['export'][$table]['primary_key'])) {unset($row[$id_field]);}
						if ($table == 'submissions' && isset($_POST['export'][$table]['actions']) && isset($export_actions) && isset($export_actions[$id])) {$row = $row + $export_actions[$id];}

						foreach ($row as $key => $value)
						{
							if ($key == 'genre_id' && $value) {$value = $genres['all'][$value]['name'];}
							$value = trim($value);
							$value = str_replace('"', '""', $value);
							$value = '"' . $value . '"';
							$row[$key] = $value;
							if (isset($_POST['export'][$table]['clmp'])) {$row['submgr_id'] = '"' . $id . '"';}
						}
						$csv .= implode(',', $row) . "\r\n";
					}

					$csv = trim($csv);
					$path = '';
					$file = 'submgr_' . $table . '_' . gmdate('YmdHis', $gm_timestamp) . '.csv';
					download($path, $file, 'text/csv');
				}
				else
				{
					$notice = 'no records found matching your search criteria';
				}
			}
		}

		if ($submodule == 'backup')
		{
			$copy = '
			<p>This function will backup your Submission Manager mySQL database using mysqldump.</p>
			<input type="submit" name="submit" value="backup" class="form_button" style="width: 100px;">
			';

			if ($submit == 'backup')
			{
				ini_set('max_execution_time', '9999');
				ini_set('max_input_time', '-1');
				ini_set('memory_limit', '-1');
				ini_set('default_socket_timeout', '-1');

				if (isset($config['mysqldump_path']) && $config['mysqldump_path']) {$mysqldump_path = trim($config['mysqldump_path']);} else {$mysqldump_path = 'mysqldump';}
				if (strpos($mysqldump_path, ' ') !== false && substr($mysqldump_path, 0, 1) != '"' && substr($mysqldump_path, -1) != '"') {$mysqldump_path = '"' . $mysqldump_path . '"';}
				if (strpos($mysqldump_path, 'mysqldump') === false) {$mysqldump_path = '';}
				foreach ($config_db as $key => $value) {if ($key == 'name') {$config_db_escaped[$key] = addslashes($value);} else {$config_db_escaped[$key] = str_replace("'", "'\''", $value);}}
				$backup = '';
				$command = $mysqldump_path . " --host='" . $config_db_escaped['host'] . "' --user='" . $config_db_escaped['username'] . "' --password='" . $config_db_escaped['password'] . "' " . $config_db_escaped['name'];
				$backup = shell_exec($command);

				if ($backup == NULL)
				{
					$copy .= '<p class="notice">ERROR: invalid mysqldump path</p>';
				}
				elseif ($backup == '')
				{
					$copy .= '<p class="notice">ERROR: backup is zero bytes</p>';
				}
				else
				{
					$backup = trim($backup);
					$path = '';
					$file = 'submgr_backup_' . gmdate('YmdHis', $gm_timestamp) . '.sql';
					download($path, $file, 'text/sql');
				}
			}
		}

		if ($submodule == 'purge')
		{
			get_min_max('submissions', 'submission_id');

			$copy = '
			<p>This function will purge submissions and their related actions and files.</p>
			<p class="notice"><i>WARNING:</i> This will permanently delete data from your database! Please backup and archive your database before proceeding.</p>
			<b>purge range:</b><br>
			<table class="padding_lr_5">
			<tr><td>date created</td><td class="small"><label for="purge_range[min]" id="label_purge_range[min]">min:</label> <input type="text" id="purge_range[min]" name="purge_range[min]" value="' . $min_max['submissions']['min_date_time'] . '" style="width: 100px;"> <label for="purge_range[max]" id="label_purge_range[max]">max:</label> <input type="text" id="purge_range[max]" name="purge_range[max]" value="' . $min_max['submissions']['max_date_time'] . '" style="width: 100px;"></td></tr>
			</table>
			<br>
			<input type="submit" id="submit_purge" name="submit" value="purge" class="form_button" style="width: 100px;">
			';

			if ($submit == 'purge')
			{
				form_hash('validate');

				if (!$_POST['purge_range']['min'] || !$_POST['purge_range']['max'])
				{
					$notice = 'ERROR: missing mix or max date';
				}
				else
				{
					$timestamp['min'] = strtotime($_POST['purge_range']['min']);
					$timestamp['max'] = strtotime($_POST['purge_range']['max']);
					$date['min'] = date('Y-m-d', $timestamp['min']) . ' 00:00:00';
					$date['max'] = date('Y-m-d', $timestamp['max']) . ' 23:59:59';

					$to_purge['submissions'] = array();
					$to_purge['actions'] = array();
					$purged['submissions'] = 0;
					$purged['actions'] = 0;
					$purged['files'] = 0;

					$sql = "SELECT submission_id, YEAR(date_time) AS year, ext FROM submissions WHERE date_time BETWEEN '" . mysqli_real_escape_string($GLOBALS['db_connect'], $date['min']) . "' AND '" . mysqli_real_escape_string($GLOBALS['db_connect'], $date['max']) . "'";
					$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT submissions for purge');
					while ($row = mysqli_fetch_assoc($result)) {$to_purge['submissions'][$row['submission_id']] = $row;}

					if ($to_purge['submissions'])
					{
						$sql = 'SELECT action_id, YEAR(date_time) AS year, ext FROM actions WHERE submission_id IN(' . implode(',', array_keys($to_purge['submissions'])) . ')';
						$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT actions for purge');
						while ($row = mysqli_fetch_assoc($result)) {$to_purge['actions'][$row['action_id']] = $row;}

						$result = mysqli_query($GLOBALS['db_connect'], 'DELETE FROM submissions WHERE submission_id IN(' . implode(',', array_keys($to_purge['submissions'])) . ')') or exit_error('query failure: DELETE submissions for purge');
						$purged['submissions'] = mysqli_affected_rows($GLOBALS['db_connect']);

						foreach ($to_purge['submissions'] as $key => $value)
						{
							$unlink = @unlink($config['upload_path'] . $value['year'] . '/' . $key . '.' . $value['ext']);
							if ($unlink) {$purged['files']++;}
						}
					}

					if ($to_purge['actions'])
					{
						$result = mysqli_query($GLOBALS['db_connect'], 'DELETE FROM actions WHERE action_id IN(' . implode(',', array_keys($to_purge['actions'])) . ')') or exit_error('query failure: DELETE actions for purge');
						$purged['actions'] = mysqli_affected_rows($GLOBALS['db_connect']);

						foreach ($to_purge['actions'] as $key => $value)
						{
							$unlink = @unlink($config['upload_path'] . $value['year'] . '/action_' . $key . '.' . $value['ext']);
							if ($unlink) {$purged['files']++;}
						}
					}

					foreach ($purged as $key => $value) {$notice .= 'deleted ' . $key . ': ' . $value . '<br>';}
				}
			}
		}
	}

	if ($submodule == 'fin') // kill submodule
	{
		unset($_REQUEST['submodule']);
		unset($_SESSION['submodule']);
		$submodule = '';
	}
}

if (isset($_SESSION['contact']))
{
	if ($config['system_online'] == 'admin only' && $_SESSION['contact']['access'] != 'admin')
	{
		$_SESSION = array();
	}
	else
	{
		$_SESSION['contact'] = array_map('strval', $_SESSION['contact']);
		$_SESSION['contact_display'] = array_map('htmlspecialchars', $_SESSION['contact']);
		extract($_SESSION['contact_display']);
	}
}

if (isset($_SESSION['contact']) && $_SESSION['contact']['access'] == 'admin')
{
	if ($module == 'configuration' && $submit == 'Go') {$submodule = 'general';} // needed here before javascript to set var submodule
	if ($module == 'maintenance' && $submit == 'Go') {$submodule = ''; $copy = '';} // needed here to reset submodule
}

$display_login = false;

if ($module == 'logout')
{
	foreach ($_SESSION['contact'] as $key => $value) {unset($$key);} // so forms are blank
	if (isset($_SESSION['contact_display']['email'])) {$_REQUEST['email'] = $_SESSION['contact_display']['email'];} // to pre-populate form_login() but not form_main()
	kill_session('regenerate'); // session needed for form_hash()
	$page = 'home';
	$display_login = true;
	$output = '<p class="header">You have successfully logged out. Thank you for using the ' . htmlspecialchars((string) $config['company_name']) . ' Submission Manager.</p>';
}
?>
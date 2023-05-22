<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

function form_reset()
{
	extract($GLOBALS);

	if (!isset($reset_email)) {$reset_email = '';}
	if (isset($_REQUEST['reset_email']) && $_REQUEST['reset_email']) {$reset_email = htmlspecialchars(trim($_REQUEST['reset_email']));}
	$output = '';

	$output .= '
	<form action="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '" method="post" name="form_reset" id="form_reset">
	<table class="foreground" style="border-spacing: 5px; border: 1px solid ' . $config['color_text'] . ';">
	<tr>
	<td>
	<label for="reset_email" id="label_reset_email">email:</label><br>
	<input type="text" id="reset_email" name="reset_email" value="' . $reset_email . '" maxlength="50" style="width: 200px;"><br>
	<div style="text-align: center;"><input type="submit" id="form_reset_submit" name="submit" value="reset password" class="form_button" style="width: 100px; margin-top: 5px;"></div>
	</td>
	</tr>
	</table>
	</form>
	';

	return $output;
}

if ($submit == 'reset password')
{
	form_hash('validate');
	$_POST = cleanup($_POST, 'strip_tags', 'stripslashes');
	extract($_POST);

	if (!$reset_email)
	{
		$form_check = false;
		$notice = 'missing email';
	}
	else
	{
		if (!email_check($reset_email))
		{
			$form_check = false;
			$notice = 'invalid email address';
		}
	}

	if (!$form_check)
	{
		$notice = 'ERROR! ' . $notice;
	}
	else
	{
		$notice_generic = 'Password reset link will be sent to <b>' . htmlspecialchars($reset_email) . '</b> if it exists.<br>The message containing your password reset link will come from <b>' . htmlspecialchars($config['general_dnr_email']) . '</b>.<br>Please make sure to check your bulk mail folders in case this message is marked as spam.';

		$result = @mysqli_query($GLOBALS['db_connect'], "SELECT contact_id, first_name, last_name, email FROM contacts WHERE email = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $reset_email) . "'") or exit_error('query failure: SELECT FROM contacts');
		if (!mysqli_num_rows($result))
		{
			$notice = $notice_generic;
		}
		else
		{
			$row = mysqli_fetch_assoc($result);
			extract($row);

			// check for existing reset
			$sql = 'SELECT * FROM resets WHERE contact_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $row['contact_id']) . ' ORDER BY date_time DESC LIMIT 1';
			$result_reset = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('SELECT reset');
			if (mysqli_num_rows($result_reset))
			{
				$row_reset = mysqli_fetch_assoc($result_reset);
				if ($gm_timestamp - strtotime($row_reset['date_time'] . ' GMT') <= 3600) {$errors[] = 'This account password was reset within the last hour. For security, passwords can only be reset once per hour. Please try again later.<br>If you have recently reset your password, and have not yet received your password reset link, please check your spam folder.';}
			}

			if ($errors)
			{
				$notice = 'ERROR: ' . implode('', $errors);
			}
			else
			{
				$token = $GLOBALS['nonce'];
				send_mail('contact', 'reset');

				$sql_array = array(
				'date_time' => $gm_date_time,
				'contact_id' => $row['contact_id'],
				'token' => $token
				);

				foreach ($sql_array as $key => $value) {$sql_array[$key] = "$key = '$value'";}
				$sql = 'INSERT INTO resets SET ' . implode(',', $sql_array);
				@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('INSERT reset');

				$notice = $notice_generic;
			}
		}
	}
}

if ($notice) {echo '<p class="notice">' . $notice . '</p>';}

echo '
<ul style="margin: 0px 0px 0px 15px;">
<li style="margin-bottom: 20px;"><b>Why log in?</b><br>Logging in allows you to manage your account. Once logged in you can easily update your personal information or submit more work.</li>
<li style="margin-bottom: 20px;"><b>Do I need to set up an account?</b><br>If you have already submitted work using our automated system you already have an account. If not, doing so will create an account.</li>
<li style="margin-bottom: 20px;"><b>How do I access my account?</b><br>To access your account, you will need the email address and password you originally used to submit your work.</li>
<li style="margin-bottom: 20px;"><b>What if I forget my password?</b><br>You can reset your password using the form below. Enter the email address you used to create your account and a password reset link will be sent to that address.<br><br>' . form_reset() . '</li>
<li><b>What if I still need help?</b><br>Please email ' . mail_to($config['admin_email']) . '.</li>
</ul>
';
?>
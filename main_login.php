<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

$back_to_account = '<div style="font-weight: bold; margin-top: 10px;">[ <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=account"> back to my account</a> ]</div>';
$GLOBALS['js_object'] = '';

function calc_submission_status($arg)
{
	global $action_types;
	$status = 'received';
	$type = '';
	$last_action_id = '';
	$last_action_message = '';

	if (is_numeric($arg))
	{
		$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT MAX(action_id) AS max_action_id FROM actions WHERE submission_id = ' . $arg) or exit_error('query failure: SELECT MAX(action)');
		$row = mysqli_fetch_assoc($result);
		$last_action_id = $row['max_action_id'];
		if ($last_action_id)
		{
			$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT action_type_id, message FROM actions WHERE action_id = ' . $last_action_id) or exit_error('query failure: SELECT action type');
			if (mysqli_num_rows($result))
			{
				$array = mysqli_fetch_assoc($result);
				$type = $action_types['all'][$array['action_type_id']]['name'];
				if (strpos($type, 'forward') === false && $array['message']) {$last_action_message = $array['message'];}
			}
		}
	}
	else
	{
		$type = $arg;
	}

	if ($type)
	{
		if (isset($action_types['all'][$action_types['keynames'][$type]]['status']) && $action_types['all'][$action_types['keynames'][$type]]['status'])
		{
			$status = $action_types['all'][$action_types['keynames'][$type]]['status'];
		}
		else
		{
			if ($type == 'accept') {$status = 'accepted';}
			if ($type == 'withdraw') {$status = 'withdrawn';}
			if (strpos($type, 'reject') !== false) {$status = 'declined';}
		}
	}

	$GLOBALS['last_action_id'] = $last_action_id;
	$GLOBALS['last_action_type'] = $type;
	$GLOBALS['last_action_message'] = $last_action_message;
	return $status;
}

function address_check()
{
	extract($GLOBALS);

	$address_check_fields = array();

	foreach ($fields as $key => $value)
	{
		if ($value['section'] == 'contact' && $key != 'password2')
		{
			if ($value['required'] && !$_SESSION['contact'][$key]) {$address_check_fields[$key] = $key;}
		}
	}

	foreach ($_SESSION['contact'] as $key => $value)
	{
		if (!$value && isset($fields[$key]) && $fields[$key]['required']) {$address_check_fields[$key] = $key;}
	}

	// if ((!$first_name || !$last_name || !$address1 || !$city) || (!$country && (!$state || !$zip)))

	if ($address_check_fields)
	{
		$_SESSION['address_check_fields'] = $address_check_fields;
		header('location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=update&address_check=1');
		exit();
	}
}

if (!$_SESSION['contact']['access'] || $_SESSION['contact']['access'] == 'blocked') // if submitter login
{
	if (!isset($modules[$module])) {exit_error('page not found');}

	if ($config['allow_withdraw'] && isset($_GET['action']) && $_GET['action'] == 'withdraw' && isset($_GET['submission_id']) && $_GET['submission_id'] && is_numeric($_GET['submission_id']))
	{
		$submission_id = (int) $_GET['submission_id'];

		if (!isset($_SESSION['submissions'][$submission_id]))
		{
			$notice = 'You are not authorized to withdraw submission #' . $submission_id . '.';
		}
		else
		{
			if ($config['send_mail_staff'])
			{
				$submodule = 'insert_action';

				// needed for get_row_string() in send_mail()
				$_SESSION['readers']['all'][$_SESSION['contact']['contact_id']] = $_SESSION['contact'];
				$_SESSION['readers']['raw'][$_SESSION['contact']['contact_id']] = $_SESSION['contact'];

				$_SESSION['action_types'] = $action_types;
				extract($_SESSION['contact']);
			}

			if ($config['send_mail_contact'])
			{
				$preview['from_name'] = $config['company_name'];
				$preview['from_email'] = $config['general_dnr_email'];
				$preview['from'] = make_email($config['company_name'], $config['general_dnr_email']);
				$_SESSION['to_email'] = $_SESSION['contact']['email'];
				$writer = $_SESSION['contact']['first_name'] . ' ' . $_SESSION['contact']['last_name'];
			}

			$sql = "INSERT INTO actions SET
			date_time = '$gm_date_time',
			submission_id = $submission_id,
			reader_id = " . $_SESSION['contact']['contact_id'] . ",
			action_type_id = 2";

			$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT withdraw actions');
			$action_id = mysqli_insert_id($GLOBALS['db_connect']);
			sync_last_action($submission_id);

			if ($config['send_mail_staff']) {send_mail('staff', 'actions');}
			if ($config['send_mail_contact'])
			{
				$title = $_SESSION['submissions'][$submission_id]['title'];
				$preview['subject'] = replace_placeholders($action_types['all'][2]['subject']);
				$preview['body'] = replace_placeholders($action_types['all'][2]['body']);
				send_mail('contact', 'action');
			}

			$notice = 'Submission #' . $submission_id . ' has been withdrawn.';

			// flush globals vars for display()
			foreach ($placeholders as $key => $value)
			{
				// display() needs first_name && last_name to remain GLOBAL
				if ($key != 'first_name' && $key != 'last_name') {$GLOBALS[$key] = '';}
			}
		}
	}

	echo '
	<table style="border-collapse: collapse; width: 100%;">
	<tr>
	<td class="foreground" style="width: 200px; padding: 5px;">

		<table class="foreground" style="width: 190px; font-weight: bold; border-spacing: 5px; border: 1px solid ' . $config['color_text'] . ';">
		<tr>
		<td style="white-space: nowrap;">
		choose an action:
		<ul class="nav_list">
		';

		unset($modules['pay_submission']);
		foreach ($modules as $key => $value)
		{
			if ($key == $module) {$value = '<span style="color: ' . $config['color_link_hover'] . ';">' . $value . '</span>';}
			echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $key . '">' . $value . '</a></li>' . "\n";
		}

		echo '
		</ul>
		</td>
		</tr>
		</table>

	</td>
	<td style="padding-left: 20px;">
	';

	if ($notice)
	{
		echo '<p class="notice">' . $notice . '</p>';
		$notice = ''; // kill notice in login form
	}

	// gather submissions regardless of module to get submission pending count
	$submissions = array();

	$genre_limits = false;
	foreach ($genres['all'] as $key => $value)
	{
		if ($value['submission_limit']) {$genre_limits = true;}
		$submissions_pending_count[$key] = 0;
	}
	$submissions_pending_count['all'] = 0;

	$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM submissions WHERE submitter_id = ' . $contact_id . ' ORDER BY date_time') or exit_error('query failure: SELECT submissions');
	if (mysqli_num_rows($result))
	{
		while ($row = mysqli_fetch_assoc($result))
		{
			// moved to display level
			// if ($row['date_time']) {$row['date_time'] = timezone_adjust($row['date_time']);}
			$row['status'] = calc_submission_status($row['submission_id']);
			$row['last_action_id'] = $last_action_id;
			$row['last_action_type'] = $last_action_type;
			$row['last_action_message'] = $last_action_message;
			$submissions[$row['submission_id']] = $row;
			$submission_ids[] = $row['submission_id'];
		}
	}

	// see how many are pending
	foreach ($submissions as $value)
	{
		if (!$value['last_action_type'] || strpos($value['last_action_type'], 'forward') !== false)
		{
			if (isset($value['genre_id'])) {$submissions_pending_count[$value['genre_id']]++;}
			$submissions_pending_count['all']++;
		}
	}

	$_SESSION['submissions'] = $submissions;
	$_SESSION['submissions_pending_count'] = $submissions_pending_count;

	if ($module == 'account')
	{
		echo '<p style="font-weight: bold;">Your account:</p>' . display('html') . '<p style="font-weight: bold;">Submissions: (' . count($submissions) . ')</p>';

		if ($submissions)
		{
			$GLOBALS['js_object'] .= 'var submissions = new Object();' . "\n";

			echo '
			<table class="table_list">
			<tr>
			<th>date / time (GMT ' . $timezone . ')</th>
			';
			if ($config['show_date_paid']) {echo '<th>date paid</th>';}
			echo '
			<th>writer</th>
			<th>title(s)</th>
			<th>genre</th>
			<th>file</th>
			<th>comments<br>(by submitter)</th>
			<th>status</th>
			<th>comments<br>(by staff)</th>
			';
			if ($config['allow_withdraw'] && $submissions_pending_count['all']) {echo '<th>withdraw?</th>';}
			echo '</tr>';

			foreach ($submissions as $value)
			{
				// for js_object
				$file_object = '';
				$withdraw_object = '';

				$value = array_map('strval', $value);
				$value = array_map('htmlspecialchars', $value);
				extract($value);
				$class = 'submission';
				$date_time = timezone_adjust($date_time);
				if ($config['show_date_paid'])
				{
					if (!$date_paid)
					{
						if ((float) $config['submission_price'] || (isset($genres['all'][$genre_id]) && (float) $genres['all'][$genre_id]['price']))
						{
							$class .= ' notice_row';
							$date_paid = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=pay_submission&submission_id=' . $submission_id . '">pay now</a>';
						}
						else
						{
							$date_paid = '&nbsp;';
						}
					}
				}
				if (!$writer) {$writer = $first_name . ' ' . $last_name;}
				if ($genre_id && isset($genres['all'][$genre_id])) {$genre = $genres['all'][$genre_id]['name'];} else {$genre = '&nbsp;';}
				if (file_exists($config['upload_path'] . date('Y', strtotime($value['date_time'])) . '/' . $submission_id . '.' . $ext)) {$file = '<a href="download.php?submission_id=' . $submission_id . '">' . $submission_id . '.' . $ext . '</a>';} else {$file = '<span id="file_' . $submission_id . '" style="color: red;">' . $submission_id . '.' . $ext . '</span>'; $file_object = $submission_id . '.' . $ext;}
				if ($comments) {$comments = '<a href="#" id="comments_' . $submission_id . '">view</a>';} else {$comments = '&nbsp;';}
				if ($last_action_message) {$last_action_message = '<a href="#" id="last_action_message_' . $submission_id . '">view</a>';} else {$last_action_message = '&nbsp;';}

				echo '
				<tr id="' . $submission_id . '" class="' . $class . '">
				<td>' . $date_time . '</td>
				';
				if ($config['show_date_paid']) {echo '<td>' . $date_paid . '</td>';}
				echo '
				<td>' . $writer . '</td>
				<td>' . $title . '</td>
				<td>' . $genre . '</td>
				<td>' . $file . '</td>
				<td>' . $comments . '</td>
				<td>' . $status . '</td>
				<td>' . $last_action_message . '</td>
				';
				if ($config['allow_withdraw'] && $submissions_pending_count['all'])
				{
					echo '<td>';
					if (!$value['last_action_type'] || strpos($value['last_action_type'], 'forward') !== false) {echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=account&action=withdraw&submission_id=' . $submission_id . '" id="withdraw_' . $submission_id . '">withdraw</a>'; $withdraw_object = $submission_id;} else {echo '&nbsp;';}
					echo '</td>';
				}
				echo '</tr>';

				$GLOBALS['js_object'] .= 'submissions[' . $submission_id . '] = {file: ' . make_tooltip($file_object) . ', comments: ' . make_tooltip($value['comments']) . ', last_action_message: ' . make_tooltip($value['last_action_message']) . ', withdraw: ' . make_tooltip($withdraw_object) . '};' . "\n";
			}

			echo '</table>';
		}
	}

	if ($module == 'update')
	{
		$form_type = 'update';
		if (isset($_GET['address_check'])) {echo '<p><b>We do not have your complete contact information. In order for you to continue you must update your account.</b></p>';}

		if (!$submit)
		{
			echo '<p>Please update the information below and press the <b>submit</b> button when you are done.</p>';
			form_main();
		}

		if ($submit == 'submit')
		{
			form_hash('validate');
			$_SESSION['post'] = cleanup($_POST, 'strip_tags', 'stripslashes');
			$_SESSION['post_display'] = array_map('htmlspecialchars', $_SESSION['post']);
			extract($_SESSION['post_display']);
			if (!isset($_SESSION['post_display']['mailing_list'])) {unset($mailing_list);} // only checkbox in form
			form_check();
			echo '<p>You entered:</p>' . display('html');
			form_confirmation();
			form_main();
		}
	}

	if ($module == 'submit')
	{
		function submission_limit_error($arg)
		{
			global $config, $genres, $back_to_account;

			if (is_numeric($arg))
			{
				$limit = $genres['all'][$arg]['submission_limit'];
				$genre = ' ' . $genres['all'][$arg]['name'];
			}
			else
			{
				$limit = $config['submission_limit'];
				$genre = '';
			}

			$submission_word = 'submission';
			if ($limit > 1) {$submission_word .= 's';}
			echo 'According to our records, you currently have ' . $limit . ' or more' . $genre . ' submissions that are under consideration by our editorial staff. Because of the large number of submissions we receive, we have instituted a limit of ' . $limit . $genre . ' ' . $submission_word . ' at a time by any one submitter. Once our staff has decided on one of your existing submissions, you can submit another. You can always check this page to see the status of your submissions. Thank you for your continued interest in being a contributor.' . $back_to_account;
			exit_error();
		}

		if ($config['system_online'] == 'no submissions')
		{
			if ($config['offline_text']) {echo '<p>' . replace_placeholders($config['offline_text']) . '</p>';}
			echo '<div class="small" style="padding: 10px; width: 400px; background-color: ' . $config['color_foreground'] . ';">' . $no_submissions_text . '</div>' . $back_to_account;
			exit_error();
		}

		if ($_SESSION['contact']['access'] == 'blocked')
		{
			echo 'Submissions have been blocked for this account.' . $back_to_account;
			exit_error();
		}

		$form_type = 'login_submit';

		if (!$submit)
		{
			address_check();

			// submission limit check
			if (!$fields['genre_id']['enabled']) {$genre_limits = false;}
			if (!$genre_limits && $config['submission_limit'] && $_SESSION['submissions_pending_count']['all'] >= $config['submission_limit']) {submission_limit_error('config');}

			echo display('html') . '<p>To submit your work please fill out the form below and then hit <b>submit</b>.</p>';
			form_main();
		}
		else
		{
			form_hash('validate');
		}

		if ($submit == 'submit')
		{
			$_SESSION['post'] = cleanup($_POST, 'strip_tags', 'stripslashes');
			$_SESSION['post_display'] = array_map('htmlspecialchars', $_SESSION['post']);
			if ($fields['genre_id']['enabled'] && $genre_limits && $genres['all'][$_SESSION['post']['genre_id']]['submission_limit'] && $_SESSION['submissions_pending_count'][$_SESSION['post']['genre_id']] >= $genres['all'][$_SESSION['post']['genre_id']]['submission_limit']) {submission_limit_error($_SESSION['post']['genre_id']);} // submission limit check for this genre
			if (isset($_FILES['file']) && $_FILES['file']['name']) {upload();} // run upload() if first time submit or re-submit with new file
			extract($_SESSION['post_display']);
			form_check();
			get_price();
			echo '<p>You entered:</p>' . display('html');
			form_confirmation();
			form_main();
		}

		if ($submit == 'continue')
		{
			if ($use_captcha) {process_captcha();}
			extract($_SESSION['post']);
			db_update('insert submission');
			if ($config['send_mail_staff']) {send_mail('staff', 'submissions');}
			if ($config['send_mail_contact']) {send_mail('contact', 'submission');}
			unset($_SESSION['file_upload']);
			extract($_SESSION['post_display']);
			if ($config['redirect_url'] || (isset($genre_id) && $genres['all'][$genre_id]['redirect_url']))
			{
				get_price();
				if ($config['payment_redirect_method'] == 'POST' && (float) $price) {form_post();} else {redirect();}
			}
			echo '<b>[ submission successfully received ]</b>';
			if ($config['submission_text']) {echo '<br><br>Dear ' . $first_name . ',<br><br>' . replace_placeholders($config['submission_text']);}
			echo $back_to_account;
		}
	}

	if ($module == 'pay_submission')
	{
		if (!$config['show_payment_fields'])
		{
			if ($config['payment_redirect_method'] == 'POST' && (float) $price) {form_post();} else {redirect();}
		}

		$form_type = 'pay_submission';

		if (!$submit)
		{
			address_check();
			echo display('html') . '<p>To pay for your submission please fill out the form below and then hit <b>submit</b>.</p>';
			form_main();
		}
		else
		{
			form_hash('validate');
		}

		if ($submit == 'submit')
		{
			$_SESSION['post'] = cleanup($_POST, 'strip_tags', 'stripslashes');
			$_SESSION['post_display'] = array_map('htmlspecialchars', $_SESSION['post']);
			extract($_SESSION['post_display']);
			form_check();
			echo '<p>You entered:</p>' . display('html');
			form_confirmation();
			form_main();
		}

		if ($submit == 'continue')
		{
			extract($_SESSION['post']);
			if ($config['payment_redirect_method'] == 'POST' && (float) $price) {form_post();} else {redirect();}
			echo '<b>[ submission successfully paid ]</b>' . $back_to_account;
		}
	}

	echo '
	</td>
	</tr>
	</table>
	';
}

else // if staff login
{
	if ($module && !in_array($module, $modules_admin)) {exit_error('page not found');}

	if ($submit == 'Go')
	{
		$keep = array('login', 'contact', 'csrf_token');
		flush_session($keep);
		$submodule = '';
	}

	if ($_SESSION['contact']['access'] == 'inactive')
	{
		echo 'Your account access status has been set to <b>&ldquo;inactive&rdquo;</b> by the system administrator. You no longer have access priveledges to Submission Manager.';
		exit_error();
	}

	$display_results = false;
	$pagination = false;
	$sql = '';
	$result_count = 0;
	$offset = 0;

	if (isset($_GET['offset']) && $_GET['offset'] != '' && is_numeric($_GET['offset']))
	{
		$offset = (int) $_GET['offset'];
		$_SESSION['offset'] = $offset;
	}
	if (isset($_SESSION['offset'])) {$offset = $_SESSION['offset'];}

	function field2array($type, $string)
	{
		eval(str_replace($type, '$array = array', $string) . ';');
		return $array;
	}

	// needed for submissions and reports
	function make_action_types_form()
	{
		global $action_types;

		$action_types_form = array(
		'all' => 'all',
		'no action' => 'no action',
		'all forwards' => 'all forwards',
		'all rejects' => 'all rejects'
		);

		if (isset($action_types['active']))
		{
			foreach ($action_types['active'] as $value) {$action_types_form[$value] = $action_types['all'][$value]['name'];}
		}

		$GLOBALS['action_types_form'] = $action_types_form;
	}

	function paginate()
	{
		extract($GLOBALS);

		$offset_plus1 = $offset + 1;
		$offset_end_range = $offset + $config['pagination_limit'];
		if ($offset_end_range > $result_count) {$offset_end_range = $result_count;}

		$url = $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module;
		if ($submodule) {$url .= '&submodule=' . $submodule;} // submoldule needed for "forwards" for actives

		$output = '
		<table style="border-collapse: collapse; width: auto; display: inline-block;">
		<tr><td colspan="2" style="text-align: center; white-space: nowrap;">showing <b>' . $count . '</b> record(s)<br><b>' . $offset_plus1 . '</b> - <b>' . $offset_end_range . '</b> (total <b>' . $result_count . ')</b></td></tr>
		<tr><td style="width: 50%; text-align: right; padding-right: 5px;">
		';

		if ($offset)
		{
			$offset_first = 0;
			if ($offset < $config['pagination_limit']) {$offset_previous = 0;} else {$offset_previous = $offset - $config['pagination_limit'];}
			$output .= '<a href="' . $url . '&offset=' . $offset_first . '"><img src="arrow_left_2.png" width="16" height="13" alt="first"></a> <a href="' . $url . '&offset=' . $offset_previous . '"><img src="arrow_left_1.png" width="8" height="13" alt="previous"></a>';
		}

		$output .= '</td><td style="width: 50%; text-align: left; padding-left: 5px;">';

		if ($offset_end_range != $result_count)
		{
			$offset_next = $offset + $config['pagination_limit'];
			$offset_last = $result_count - $config['pagination_limit'];
			$output .= '<a href="' . $url . '&offset=' . $offset_next . '"><img src="arrow_right_1.png" width="8" height="13" alt="next"></a> <a href="' . $url . '&offset=' . $offset_last . '"><img src="arrow_right_2.png" width="16" height="13" alt="last"></a>';
		}

		$output .= '
		</td>
		</tr>
		</table>
		';

		return $output;
	}

	if ($action_types) {$_SESSION['action_types'] = $action_types;}
	$_REQUEST = cleanup($_REQUEST, 'strip_tags', 'stripslashes');
	if ($module == 'submissions' || $submodule == 'insert_submission' || $submodule == 'test_upload') {$enctype = ' enctype="multipart/form-data"';} else {$enctype = '';}
	echo '<form action="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '" method="post" name="form_' . $module . '" id="form_' . $module . '" autocomplete="off"' . $enctype . '>';

	if ($module == 'submissions')
	{
		if (!in_array('action_types', $show_tables)) {exit_error('action_types table unavailable');}

		$submissions = array();
		$single_display = false;

		unset($_SESSION['sql']); // conflict with contacts area

		// coming from reports
		if (isset($_GET['from_reports']) && ($_GET['from_reports'] == 'monthly' || $_GET['from_reports'] == 'status' || $_GET['from_reports'] == 'forwards'))
		{
			if ($_REQUEST['search_genre_id'] == 'all') {$_REQUEST['search_genre_id'] = 'all submissions';}
			if ($_REQUEST['search_genre_id'] == 'no_genre') {$_REQUEST['search_genre_id'] = 'all no genre';}
			$submit = 'search submissions';
		}

		$search_fields = array(
		'search_keyword',
		'search_action_type_id',
		'search_receiver_id',
		'search_date_order',
		'search_genre_id',
		'search_payment'
		);
		foreach ($search_fields as $value)
		{
			if (isset($_REQUEST[$value]))
			{
				$_REQUEST[$value] = str_replace('_', ' ', $_REQUEST[$value]);
				$$value = $_REQUEST[$value];
			}
			else
			{
				$$value = '';
			}
		}

		get_readers();

		function get_submissions()
		{
			extract($GLOBALS);

			$sql1 = 'SELECT submissions.* FROM submissions ';
			$sql_order = ' ORDER BY submissions.date_time ASC, submissions.submission_id ASC';
			$sql_limit = '';

			if ($submit == 'search submissions')
			{
				if ($search_action_type_id == 'all')
				{
					$sql2 = 'WHERE 1 = 1';
				}
				elseif ($search_action_type_id == 'no action')
				{
					// sync_last_actions
					$sql2 = 'WHERE last_action_id IS NULL';
				}
				else
				{
					// sync_last_actions
					$sql2 = ' WHERE last_action_type_id ';
					if ($search_action_type_id == 'all forwards') {$sql2 .= ' IN(' . implode(',', $action_types['forwards']) . ')';}
					elseif ($search_action_type_id == 'all rejects') {$sql2 .= ' IN(' . implode(',', $action_types['rejects']) . ')';}
					else {$sql2 .= ' = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $search_action_type_id);}
					if ($search_receiver_id && $search_receiver_id != 'anyone' && ($search_action_type_id == 'all forwards' || in_array($search_action_type_id, $action_types['forwards']))) {$sql2 .= ' AND last_receiver_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $search_receiver_id);}
				}

				if ($search_genre_id == 'all no genre') {$sql2 .= ' AND submissions.genre_id IS NULL';}
				if (is_numeric($search_genre_id)) {$sql2 .= ' AND submissions.genre_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $search_genre_id);}
				if ($search_payment == 'paid') {$sql2 .= ' AND submissions.date_paid IS NOT NULL';}
				if ($search_payment == 'unpaid') {$sql2 .= ' AND submissions.date_paid IS NULL';}

				// look for search_keyword only if it does not contain sid: or cid:
				if ($search_keyword && strpos($search_keyword, 'sid:') === false && strpos($search_keyword, 'cid:') === false)
				{
					$search_keyword_array = preg_split('/[ ]+/', $search_keyword);
					$search_keyword_array = array_unique($search_keyword_array);
					$keyword_exact = false;

					if (count($search_keyword_array) > 1 && substr($search_keyword, 0, 1) == '"' && substr($search_keyword, -1) == '"')
					{
						$temp = substr($search_keyword, 1);
						$temp = substr($temp, 0, -1);
						$search_keyword_array = array($temp);
						$keyword_exact = true;
					}
					unset($temp);

					$keyword_fields = array(
					'submissions.submission_id',
					'submissions.submitter_id',
					'submissions.writer',
					'submissions.title',
					'submissions.comments',
					'submissions.notes',
					'contacts.first_name',
					'contacts.last_name',
					'contacts.email',
					'actions.notes',
					'actions.message'
					);

					// FULLTEXT search
					// mimimum search length = 4 set by ft_min_word_len
					// maximum search length = 84 set by ft_max_word_len
					// watch for STOPWORDS set by ft_stopword_file
					foreach ($keyword_fields as $value)
					{
						$explode = explode('.', $value);
						// separate FULLTEXT indexed fields from INT fields (non-FULLTEXT fields have to be searched by LIKE)
						if (strpos($value, '_id') !== false) {$keyword_fields_like[] = $explode[0] . '.' . $explode[1];} else {$keyword_fields_match[$explode[0]][] = $explode[0] . '.' . $explode[1];}
					}

					// prep LIKE fields
					// using preceeding % wildcard will not use index
					foreach ($keyword_fields_like as $value) {$sql_array['like'][] = $value . ' LIKE "' . mysqli_real_escape_string($GLOBALS['db_connect'], implode(' ', $search_keyword_array)) . '%"';}
					$sql_array['like'] = implode(' OR ', $sql_array['like']);

					// prep MATCH/AGAINST fields
					if (!$keyword_exact) {foreach ($search_keyword_array as $key => $value) {$search_keyword_array[$key] = '+' . $value . '*';}}
					foreach ($keyword_fields_match as $key => $value)
					{
						$implode = mysqli_real_escape_string($GLOBALS['db_connect'], implode(' ', $search_keyword_array));
						if (!$keyword_exact) {$against = "'" . $implode . "'";} else {$against = '\'"' . $implode . '"\'';} // exact macth must have double quotes
						$against .= ' IN BOOLEAN MODE';
						$sql_array[$key] = 'MATCH(' . implode(',', $value) . ') AGAINST(' . $against . ')';
					}

					$keyword_clause = array();
					$keyword_clause['like'] = $sql_array['like'];
					$keyword_clause['submissions'] = $sql_array['submissions'];
					$keyword_clause['contacts'] = $sql_array['contacts'];
					$keyword_clause['actions'] = $sql_array['actions'];
					// subquery
					// $keyword_clause['actions'] = '(submissions.submission_id IN(SELECT actions.submission_id FROM actions WHERE ' . $sql_array['actions'] . '))';
					// using UNION instead of OR
					// $keyword_clause = implode(' OR ', $keyword_clause);

					// build UNION
					foreach ($keyword_clause as $key => $value)
					{
						$sql_union = $sql1;
						if ($key == 'contacts' || $key == 'actions') {$sql_union .= ',' . $key . ' ';}
						$sql_union .= $sql2 . ' AND ' . $value;
						if ($key == 'contacts') {$sql_union .= ' AND submissions.submitter_id = contacts.contact_id';}
						if ($key == 'actions') {$sql_union .= ' AND submissions.submission_id = actions.submission_id';}
						$keyword_clause[$key] = '(' . $sql_union . ')';
					}
					$keyword_clause = implode(' UNION ', $keyword_clause);
					$sql2 = $keyword_clause;

					/*
					// LIKE search replaced with FULLTEXT search
					foreach ($search_keyword_array as $value)
					{
						foreach ($keyword_fields as $sub_value)
						{
							$like = $sub_value . ' LIKE "%' . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . '%"';
							if (strpos($sub_value, 'actions') === false) {$temp['submissions_contacts'][] = $like;} else {$temp['actions'][] = $like;}
						}

						$sql_array['submissions_contacts'][] = '(' . implode(' OR ', $temp['submissions_contacts']) . ')';
						$sql_array['actions'][] = '(' . implode(' OR ', $temp['actions']) . ')';

						unset($temp); // this needs to be reset on each loop iteration
					}

					$keyword_clause = implode(' AND ', $sql_array['submissions_contacts']);
					// subquery
					$keyword_clause .= ' OR (submissions.submission_id IN(SELECT actions.submission_id FROM actions WHERE ' . implode(' AND ', $sql_array['actions']) . '))';

					$sql1 = str_replace('FROM submissions', 'FROM contacts, submissions', $sql1);
					$sql2 .= ' AND (' . $keyword_clause . ') AND submissions.submitter_id = contacts.contact_id';
					*/
				}

				// remove unecessary WHERE clauses
				if (strpos($sql2, 'WHERE 1 = 1 AND') !== false) {$sql2 = str_replace('1 = 1 AND', '', $sql2);}
				if (strpos($sql2, 'WHERE 1 = 1') !== false) {$sql2 = str_replace('WHERE 1 = 1', '', $sql2);}

				// run the COUNT() query only if search_keyword does not contain sid: or cid:
				if (strpos($search_keyword, 'sid:') === false && strpos($search_keyword, 'cid:') === false)
				{
					// $sql2_safe needed to form inner UNION query
					if (isset($keyword_clause)) {$sql_count = 'SELECT COUNT(*) AS count FROM (' . $sql2 . ') AS count'; $sql2_safe = $sql2;} else {$sql_count = str_replace('submissions.*', 'COUNT(*) AS count', $sql1) . $sql2;}
					$sql_count = preg_replace("~[ ]{2,}~", ' ', $sql_count);
					$result = @mysqli_query($GLOBALS['db_connect'], $sql_count) or exit_error('query failure: COUNT submissions');
					$row = mysqli_fetch_assoc($result);
					$GLOBALS['result_count'] = $row['count'];
				}

				// build ORDER
				if ($search_date_order == 'descending') {$sql_order = str_replace('ASC', 'DESC', $sql_order);}
				$sql2 .= $sql_order;

				// build LIMIT
				if (isset($_POST['submit']) && $_POST['submit'] == 'search submissions') {$GLOBALS['offset'] = 0;}
				if ($config['pagination_limit'] && $GLOBALS['result_count'] > $config['pagination_limit'])
				{
					$sql_limit = ' LIMIT ' . $GLOBALS['offset'] . ', ' . $config['pagination_limit'];
					$sql2 .= $sql_limit;
				}

				// build SQL
				if (isset($keyword_clause)) {$sql = 'SELECT * FROM (' . $sql2_safe . ') AS submissions ' . $sql_order . $sql_limit;} else {$sql = $sql1 . $sql2;}

				if ($search_action_type_id == 'all' || $search_action_type_id == 'no action') {$search_receiver_id = '';}
				if ($search_action_type_id == 'all forwards' || in_array($search_action_type_id, $action_types['forwards'])) {$search_receiver_id = $search_receiver_id;} else {$search_receiver_id = '';}

				foreach ($search_fields as $value)
				{
					$_SESSION['criteria'][$value] = $$value;
				}
			}

			if ($search_keyword && strpos($search_keyword, 'cid:') !== false) {$_REQUEST['contact_id'] = trim(str_replace('cid:', '', $search_keyword));}
			if (isset($_REQUEST['contact_id']) && $_REQUEST['contact_id'])
			{
				if (is_numeric($_REQUEST['contact_id']))
				{
					$_REQUEST['contact_id'] = (int) $_REQUEST['contact_id'];
					$sql = $sql1 . 'WHERE submissions.submitter_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $_REQUEST['contact_id']) . $sql_order;
				}
				else
				{
					$sql = '';
					$GLOBALS['notice'] = 'invalid search value';
				}
			}

			if (isset($search_submission_id) && $search_submission_id) {$submission_id = $search_submission_id;}
			if (isset($_REQUEST['submission_id']) && $_REQUEST['submission_id']) {$submission_id = $_REQUEST['submission_id'];}
			// need PHP5 for case insensitive id:
			if ($search_keyword && strpos($search_keyword, 'sid:') !== false) {$submission_id = trim(str_replace('sid:', '', $search_keyword));}

			if (isset($submission_id))
			{
				if (is_numeric($submission_id))
				{
					$submission_id = (int) $submission_id;
					$sql = $sql1 . 'WHERE submissions.submission_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $submission_id);
				}
				else
				{
					$sql = '';
					$GLOBALS['notice'] = 'invalid search value';
				}

				$GLOBALS['submission_id'] = $submission_id;
				$GLOBALS['single_display'] = true;

				// to reset search form
				if (isset($_POST['search_submission_id'])) {unset($_SESSION['criteria']);}
			}

			if ($submodule == 'tag' && isset($_POST['tag']) && $_POST['tag'] && $form_check)
			{
				$sql = $sql1 . 'WHERE submissions.submission_id IN(' . implode(',', $_POST['tag']) . ')' . $sql_order;
			}

			if (isset($_SESSION['criteria']))
			{
				extract($_SESSION['criteria']);
			}

			if ($sql)
			{
				$sql = preg_replace("~[ ]{2,}~", ' ', $sql);
				$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT submissions');
				$num_rows = mysqli_num_rows($result);
				if (!$GLOBALS['result_count']) {$GLOBALS['result_count'] = $num_rows;}
				if ($submodule == 'forwards') {$_SESSION['forwards'] = array();}

				if ($num_rows)
				{
					while ($row = mysqli_fetch_assoc($result))
					{
						// moved to display level
						// if ($row['date_time']) {$row['date_time'] = timezone_adjust($row['date_time']);}
						$row['contact'] = array();
						$row['actions'] = array();
						$submissions[$row['submission_id']] = $row;
						$submission_ids[] = $row['submission_id'];
						$submitters[$row['submitter_id']] = $row['submitter_id'];
						$submission_id = $row['submission_id']; // to get single submission array below

						// fill forwards array
						if ($submodule == 'forwards' && in_array($row['last_action_type_id'], $action_types['forwards']) && $row['last_receiver_id'] == $_SESSION['contact']['contact_id']) {$_SESSION['forwards'][$row['submission_id']] = $row['submission_id'];}
					}

					// get full contact info
					$result_contacts = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM contacts WHERE contact_id IN(' . implode(',', $submitters) . ')') or exit_error('query failure: SELECT contacts');
					$submitters = array();
					while ($row = mysqli_fetch_assoc($result_contacts)) {$submitters[$row['contact_id']] = $row;}
					foreach ($submissions as $key => $value)
					{
						if (isset($submitters[$value['submitter_id']])) {$value['contact'] = $submitters[$value['submitter_id']];}
						$submissions[$key] = $value;
					}

					// get full actions
					$result_actions = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM actions WHERE submission_id IN(' . implode(',', $submission_ids) . ') ORDER BY date_time, action_id') or exit_error('query failure: SELECT actions');
					while ($row = mysqli_fetch_assoc($result_actions))
					{
						// moved to display level
						// if ($row['date_time']) {$row['date_time'] = timezone_adjust($row['date_time']);}
						$submissions[$row['submission_id']]['actions'][$row['action_id']] = $row;
					}

					$GLOBALS['submissions'] = $submissions;
					$GLOBALS['display_results'] = true;

					if ($num_rows == 1 && !isset($_POST['tag']))
					{
						$GLOBALS['single_display'] = true; // needed to prevent tag form
						$_SESSION['submission'] = $submissions[$submission_id];
					}
				}
				else
				{
					if (isset($_POST['submit']) && $_POST['submit'] == 'confirm') {$GLOBALS['notice'] = $GLOBALS['notice'];} else {$GLOBALS['notice'] = '<b>0</b> records matching your search criteria';}
				}
			}
		}

		function display_submissions()
		{
			extract($GLOBALS);

			$submissions_count = count($submissions);

			if ($submissions_count == 1 && !isset($_POST['tag']))
			{
				$single_display = true;
				$submission_id = key($submissions);
				if ($submodule == 'forwards') {$submodule = '';} // so actions will be displayed
			}
			else
			{
				$submission_id = '';
			}

			$GLOBALS['single_display'] = $single_display;
			$header = '';

			if (!$submission_id)
			{
				$header1 = '';
				$header2 = '';
				$header3 = '';

				$header1 = '<table class="padding_lr_5">';

				if ($search_keyword)
				{
					$header1 .= '<tr class="foreground"><td class="row_left">keywords:</td><td><b>' . $search_keyword . '</b></td></tr>';
				}

				if (isset($search_genre_id) && $search_genre_id)
				{
					$header1 .= '<tr class="foreground"><td class="row_left">genre:</td><td><b>';
					if (isset($genres['all'][$search_genre_id])) {$header1 .= $genres['all'][$search_genre_id]['name'];} else {$header1 .= $search_genre_id;}
					$header1 .= '</b></td></tr>';
				}

				if (isset($search_action_type_id) && $search_action_type_id)
				{
					$header1 .= '<tr class="foreground"><td class="row_left">last action:</td><td><b>';
					if (isset($action_types['all'][$search_action_type_id]))
					{
						$header1 .= $action_types['all'][$search_action_type_id]['name'];
						if ($action_types['all'][$search_action_type_id]['description']) {$header1 .= ' - ' . $action_types['all'][$search_action_type_id]['description'];}
					}
					else
					{
						$header1 .= $search_action_type_id;
					}
					$header1 .= '</b></td></tr>';
				}

				if (isset($search_receiver_id) && $search_receiver_id)
				{
					$header1 .= '<tr class="foreground"><td class="row_left">to:</td><td><b>';
					if (isset($readers['all'][$search_receiver_id])) {$header1 .= $readers['all'][$search_receiver_id]['first_name'] . ' ' . $readers['all'][$search_receiver_id]['last_name'];} else {$header1 .= $search_receiver_id;}
					$header1 .= '</b></td></tr>';
				}

				$header1 .= '<tr class="foreground"><td class="row_left">total:</td><td><b>' . $result_count . '</b></td></tr></table>';

				if (isset($_REQUEST['contact_id']) && $_REQUEST['contact_id'])
				{
					$first_submission = reset($submissions);
					if ($first_submission['contact'])
					{
						foreach ($first_submission['contact'] as $key => $value)
						{
							$value = htmlspecialchars((string) $value);
							if ($key == 'email' && $value) {$value = mail_to($value);}
							$GLOBALS[$key] = $value;
						}

						$header1 = display('html');
						if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) {$header1 .= '<br><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=contacts&single_contact=1&contact_id=' . $_REQUEST['contact_id'] . '"><img src="button_update.png" width="12" height="13" alt="update" style="vertical-align: middle;"> <b>update this contact</b></a><br>';}
						$header1 .= '<br>total: <b>' . $result_count . '</b>';
					}
					else
					{
						$header1 = '<span style="notice">missing contact data!</span>';
					}
				}

				if ($submodule == 'tag' && $form_check)
				{
					$result_count = $submissions_count;
					$header1 = '<b>tagged</b> (' . $result_count . ')';
				}

				if ($config['pagination_limit'] && $result_count > $config['pagination_limit'])
				{
					$pagination = true;
					$GLOBALS['count'] = $submissions_count;
					$header2 = paginate();
				}

				if ((in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) && $submit == 'search submissions' && !$single_display) {$header3 = '<a href="#tag" class="tag_all">tag all</a><br><a href="#untag" class="tag_all">untag all</a>';}

				$header = '
				<table style="border-collapse: collapse; width: 100%;">
				<tr>
				<td style="width: 33%; vertical-align: middle; text-align: left; white-space: nowrap;">' . $header1 . '</td>
				<td style="width: 34%; vertical-align: middle; text-align: center;">' . $header2 . '</td>
				<td style="width: 33%; vertical-align: middle; text-align: right;" class="small">' . $header3 . '</td>
				</tr>
				</table>
				';
			}

			$headings = array(
			'ID',
			'date / time',
			'date_paid' => 'date paid',
			'writer',
			'title(s)',
			'genre',
			'file',
			'comments<br>(by submitter)',
			'notes<br>(by staff)',
			'status',
			'actions'
			);

			if (!$config['show_date_paid']) {unset($headings['date_paid']);}

			if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) && $submit == 'search submissions' && !$single_display) {$headings['tag'] = 'tag (<span id="checked_count">0</span>)';}

			if ($header) {echo $header;}

			echo '
			<table class="table_list"'; if ($single_display) {echo ' style="margin-bottom: 20px;"';} echo '>
			<tr>
			';

			foreach ($headings as $value)
			{
				echo '<th>' . $value;
				if ($value == 'date / time' && !$single_display && isset($_SESSION['criteria']['search_date_order']))
				{
					if ($_SESSION['criteria']['search_date_order'] == 'ascending') {echo ' <img src="arrow_up_1.png" width="13" height="8" alt="arrow up">';}
					if ($_SESSION['criteria']['search_date_order'] == 'descending') {echo ' <img src="arrow_down_1.png" width="13" height="8" alt="arrow down">';}
				}
				echo '</th>';
			}
			echo '</tr>';

			$contact_access = str_replace('active ', '', $_SESSION['contact']['access']);
			$_SESSION['contact_access'] = $contact_access;
			$GLOBALS['js_object'] .= 'var submissions = new Object();' . "\n" . 'var actions = new Object();' . "\n";
			$i = 1;

			foreach ($submissions as $value)
			{
				// for js_object
				$file_object = '';
				$tag_object = '';
				$action_count_object = '';

				$value = cleanup($value, 'htmlspecialchars');

				if ($value['contact'])
				{
					foreach ($value['contact'] as $contact_key => $contact_value)
					{
						$GLOBALS[$contact_key] = ''; // flush out GLOBAL vars
						if ($contact_value)
						{
							$contact_value = htmlspecialchars($contact_value);
							$value['contact'][$contact_key] = $contact_value;
							$GLOBALS[$contact_key] = $contact_value;
						}
					}
					$contact_tooltip = trim(display('text'));
				}
				else
				{
					$value['contact']['first_name'] = '???';
					$value['contact']['last_name'] = '???';
					$contact_tooltip = 'contact missing!';
				}

				extract($value);
				$class = 'submission';

				$submission_id_display = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submission_id=' . $submission_id . '">' . $submission_id . '</a>';
				if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) && $single_display) {$submission_id_display .= ' <a href="#" id="update_submission_' . $submission_id . '"><img src="button_update.png" alt="update" width="12" height="13"></a> <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&submodule=delete&submission_id=' . $submission_id . '" id="delete_submission_' . $submission_id . '"><img src="button_delete.png" alt="delete" width="11" height="13"></a>';}
				$date_time = timezone_adjust($date_time);
				if ($config['show_date_paid'])
				{
					if (isset($genres['all'][$genre_id]))
					{
						if (((float) $config['submission_price'] || (float) $genres['all'][$genre_id]['price']) && !$date_paid) {$class .= ' notice_row';}
					}
					if (!$date_paid) {$date_paid = '&nbsp;';}
				}
				if (($genre_id && isset($genres['all'][$genre_id]) && $genres['all'][$genre_id]['blind']) || $groups[$contact_access]['blind'])
				{
					$writer = '<span id="writer_' . $submission_id . '">[blind]</span>';
					$contact_tooltip = '';
				}
				else
				{
					if ($writer) {$writer = '<span style="color: red;">' . $writer . '</span>';} else {$writer = $value['contact']['first_name'] . ' ' . $value['contact']['last_name'];}
					$writer = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $submitter_id . '" id="writer_' . $submission_id . '">' . $submitter_id . ' - ' . $writer . '</a>';
					if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) {$writer = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=contacts&single_contact=1&contact_id=' . $submitter_id . '"><img src="button_update.png" alt="update" width="12" height="13"></a> ' . $writer;}
				}
				if ($genre_id && isset($genres['all'][$genre_id])) {$genre = $genres['all'][$genre_id]['name'];} else {$genre = '&nbsp;';}
				if (file_exists($config['upload_path'] . date('Y', strtotime($value['date_time'])) . '/' . $submission_id . '.' . $ext))
				{
					if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) || (in_array($_SESSION['contact']['access'], $access_grouping['active']) && in_array($submission_id, $_SESSION['forwards']))) {$file = '<a href="download.php?submission_id=' . $submission_id . '">' . $submission_id . '.' . $ext . '</a>';} else {$file = $submission_id . '.' . $ext;}
				}
				else
				{
					$file = '<span id="file_' . $submission_id . '" style="color: red;">' . $submission_id . '.' . $ext . '</span>';
					$file_object = $submission_id . '.' . $ext;
				}
				if ($comments) {$comments = '<a href="#" id="comments_' . $submission_id . '">view</a>';} else {$comments = '&nbsp;';}
				if (($genre_id && isset($genres['all'][$genre_id]) && $genres['all'][$genre_id]['blind']) || $groups[$contact_access]['blind']) {$comments = '&nbsp;'; $value['comments'] = '';}
				if ($notes) {$notes = '<a href="#" id="notes_' . $submission_id . '">view</a>';} else {$notes = '&nbsp;';}
				$status = 'received';
				if ($value['actions'])
				{
					$last_action = end($value['actions']);
					$status = calc_submission_status($action_types['all'][$last_action['action_type_id']]['name']);
					$action_count_object = 'last action: ' . $action_types['all'][$value['last_action_type_id']]['name'];
					if (isset($value['last_reader_id']) && $value['last_reader_id'])
					{
						if (in_array($value['last_action_type_id'], $action_types['forwards'])) {$preposition = 'from';} else {$preposition = 'by';}
						if (isset($readers['all'][$value['last_reader_id']])) {$action_count_object .= ' ' . $preposition . ' ' . $readers['all'][$value['last_reader_id']]['first_name'] . ' ' . $readers['all'][$value['last_reader_id']]['last_name'];} else {$action_count_object .= ' ' . $preposition . ' ???';}
						if ($value['last_action_type_id'] == 2 && $value['last_reader_id'] == $value['contact']['contact_id'])
						{
							if (($genre_id && isset($genres['all'][$genre_id]) && $genres['all'][$genre_id]['blind']) || $groups[$contact_access]['blind']) {$action_count_object = 'last action: ' . $action_types['all'][$value['last_action_type_id']]['name'] . ' by [blind]';} else {$action_count_object = 'last action: ' . $action_types['all'][$value['last_action_type_id']]['name'] . ' by ' . $value['contact']['first_name'] . ' ' . $value['contact']['last_name'];}
						}
					}
					if (isset($value['last_receiver_id']) && $value['last_receiver_id'])
					{
						if (isset($readers['all'][$value['last_receiver_id']])) {$action_count_object .= ' to ' . $readers['all'][$value['last_receiver_id']]['first_name'] . ' ' . $readers['all'][$value['last_receiver_id']]['last_name'];} else {$action_count_object .= ' to ???';}
					}
				}
				$action_count = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submission_id=' . $submission_id . '" id="action_count_' . $submission_id . '">' . count($value['actions']) . '</a>';

				echo '
				<tr id="tr_' . $i . '" class="' . $class . '">
				<td id="' . $submission_id . '" style="white-space: nowrap;">' . $submission_id_display . '</td>
				<td>' . $date_time . '</td>
				';
				if ($config['show_date_paid']) {echo '<td>' . $date_paid . '</td>';}
				echo '
				<td style="text-align: left;">' . $writer . '</td>
				<td style="text-align: left;">' . $title . '</td>
				<td style="text-align: left;">' . $genre . '</td>
				<td style="text-align: left;">' . $file . '</td>
				<td>' . $comments . '</td>
				<td>' . $notes . '</td>
				<td>' . $status . '</td>
				<td>' . $action_count . '</td>
				';

				if (isset($headings['tag'])) {echo '<td><input type="checkbox" id="check_' . $i . '" name="tag[]" value="' . $submission_id . '"'; if (isset($_POST['tag']) && in_array($submission_id, $_POST['tag'])) {echo ' checked';} echo '></td>'; $tag_object = $i;}

				echo '
				</tr>
				';

				$i++;

				$GLOBALS['js_object'] .= 'submissions[' . $submission_id . '] = {writer: ' . make_tooltip($contact_tooltip) . ', file: ' . make_tooltip($file_object) . ', comments: ' . make_tooltip($value['comments']) . ', notes: ' . make_tooltip($value['notes']) . ', action_count: ' . make_tooltip($action_count_object) . ', tag: ' . make_tooltip($tag_object) . '};' . "\n";
			}

			echo '</table>';

			unset($notes); // to not conflict with action notes
			if ($header && $pagination && $submissions_count > 5) {echo $header;}

			// display actions
			if ($single_display)
			{
				if ($submodule != 'insert_action') {$submodule = '';} // if linked from non-submission module

				if (!$submodule || $submodule == 'delete')
				{
					$header = '<b>actions</b> (' . count($submissions[$submission_id]['actions']) . ')<span style="margin-left: 10px;">[ status: <b>' . $status . '</b> ]</span>';

					if (!$submissions[$submission_id]['actions'])
					{
						echo $header . '<br>';
					}
					else
					{
						$headings = array(
						'ID',
						'date / time',
						'reader',
						'type',
						'receiver',
						'file',
						'notes<br>(by staff)',
						'message<br>(sent to receiver)'
						);

						echo $header . '<br>
						<table class="table_list" style="margin-top: 5px;">
						<tr>';
						foreach ($headings as $value) {echo '<th>' . $value . '</th>';}
						echo '</tr>';

						foreach ($submissions[$submission_id]['actions'] as $value)
						{
							// for js_object
							$file_object = '';
							$reader_tooltip = '';
							$receiver_tooltip = '';

							$value = cleanup($value, 'htmlspecialchars');
							extract($value);

							$action_id_display = $action_id;
							if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) {$action_id_display .= ' <a href="#" id="update_action_' . $action_id . '"><img src="button_update.png" alt="update" width="12" height="13"></a> <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&submodule=delete&action_id=' . $action_id . '" id="delete_action_' . $action_id . '"><img src="button_delete.png" alt="delete" width="11" height="13"></a>';}
							$date_time = timezone_adjust($date_time);
							if (isset($reader_id) && $reader_id)
							{
								$display_array = array();
								if (isset($readers['all'][$reader_id])) {$display_array = $readers['all'][$reader_id];}
								if ($reader_id == $submitter_id) {$display_array = $submissions[$submission_id]['contact']; $display_array = array_map('strval', $display_array); $display_array = array_map('htmlspecialchars', $display_array);}
								if ($display_array)
								{
									$reader_tooltip = $display_array['first_name'] . ' ' . $display_array['last_name'] . '<br>' . $display_array['email'];
									if ($display_array['access']) {$reader_tooltip .= '<br>' . $display_array['access'];}
									$display_name = $display_array['first_name'] . ' ' . $display_array['last_name'];
									if ($reader_id == $submitter_id)
									{
										if (($genre_id && isset($genres['all'][$genre_id]) && $genres['all'][$genre_id]['blind']) || $groups[$contact_access]['blind']) {$display_name = '[blind]'; $reader_tooltip = '';}
									}
									$reader_display = '<span id="reader_' . $action_id . '">' . $display_name . '</span>';
								}
								else
								{
									$reader_display = '???';
								}
							}
							else
							{
								$reader_display = '&nbsp;';
							}
							if (isset($receiver_id) && $receiver_id)
							{
								if (isset($readers['all'][$receiver_id]))
								{
									$receiver_tooltip = $readers['all'][$receiver_id]['first_name'] . ' ' . $readers['all'][$receiver_id]['last_name'] . '<br>' . $readers['all'][$receiver_id]['email'] . '<br>' . $readers['all'][$receiver_id]['access'];
									$receiver_display = '<span id="receiver_' . $action_id . '">' . $readers['all'][$receiver_id]['first_name'] . ' ' . $readers['all'][$receiver_id]['last_name'] . '</span>';
								}
								else
								{
									$receiver_display = '???';
								}
							}
							else
							{
								$receiver_display = '&nbsp;';
							}
							$action = $action_types['all'][$action_type_id]['name'];
							if ($action_types['all'][$action_type_id]['description']) {$action .= ' - ' . $action_types['all'][$action_type_id]['description'];}
							if ($ext)
							{
								if (file_exists($config['upload_path'] . date('Y', strtotime($value['date_time'])) . '/action_' . $action_id . '.' . $ext)) {$file = '<a href="download.php?submission_id=' . $submission_id . '&action_id=' . $action_id . '">' . $action_id . '.' . $ext . '</a>';} else {$file = '<span id="file_' . $action_id . '" style="color: red;">' . $action_id . '.' . $ext . '</span>'; $file_object = $action_id . '.' . $ext;}
							}
							else
							{
								$file = '&nbsp;';
							}
							if ($notes) {$notes = nl2br($notes);} else {$notes = '&nbsp;';}
							if ($message) {$message = nl2br($message);} else {$message = '&nbsp;';}

							echo '
							<tr>
							<td style="white-space: nowrap;">' . $action_id_display . '</td>
							<td>' . $date_time . '</td>
							<td>' . $reader_display . '</td>
							<td>' . $action . '</td>
							<td>' . $receiver_display . '</td>
							<td>' . $file . '</td>
							<td style="text-align: left;">' . $notes . '</td>
							<td style="text-align: left;">' . $message . '</td>
							</tr>
							';

							$GLOBALS['js_object'] .= 'actions[' . $action_id . '] = {reader: ' . make_tooltip($reader_tooltip) . ', receiver: ' . make_tooltip($receiver_tooltip) . ', file: ' . make_tooltip($file_object) . '};' . "\n";
						}

						echo '</table>';
					}

					if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) || (in_array($_SESSION['contact']['access'], $access_grouping['active']) && in_array($submission_id, $_SESSION['forwards']))) {echo '<input type="submit" name="submit" value="insert new action" class="form_button" style="width: 150px; margin-top: 5px;">';}
				}

				if ($submodule == 'insert_action')
				{
					if ($submit == 'remove')
					{
						if (isset($_SESSION['file_upload']['filename_temp'])) {@unlink($upload_path_year . $_SESSION['file_upload']['filename_temp']);}
						unset($_SESSION['file_upload']);
						$_POST['submit'] = 'preview'; // needed for subsequent extract()
						$submit = 'preview';
					}

					if ($submit == 'preview')
					{
						$_SESSION['post'] = cleanup($_POST, 'strip_tags', 'stripslashes');
						$_SESSION['post'] = str_replace("\r", '', $_SESSION['post']);
						extract($_SESSION['post']);
						if ($_FILES['file']['name']) {upload();} // run upload() if first time submit or re-submit with new file
						if (!in_array($new_action_type_id, $action_types['forwards'])) {$new_receiver_id = '';}

						if (!$new_action_type_id)
						{
							$form_check = false;
							$error = 'missing action type';
						}

						if (in_array($new_action_type_id, $action_types['forwards']) && !$new_receiver_id)
						{
							$form_check = false;
							$error = 'missing receiver';
						}

						if (!in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) && strpos($action_types['all'][$new_action_type_id]['access_groups'], $access_number) === false) {exit_error('unauthorized action type');}

						if (isset($_SESSION['file_upload']))
						{
							$_FILES['file'] = $_SESSION['file_upload'];

							if ($_FILES['file']['error'] == 3 || !$_SESSION['file_upload']['is_uploaded_file'] || !$_SESSION['file_upload']['move_uploaded_file']) {$form_check = false; $error = 'file upload failed';}
							if ($_FILES['file']['size'] == 0) {$form_check = false; $error = 'Uploaded file is empty (0 bytes)';}
							if ($_FILES['file']['error'] == 1 || $_FILES['file']['error'] == 2 || ($fields['file']['maxlength'] && $_FILES['file']['size'] > $fields['file']['maxlength'])) {$form_check = false; $error = 'Uploaded file exceeds the maximum file size limit of ' . $max_file_size_formatted;}
						}

						if (!$form_check)
						{
							$notice = 'ERROR! ' . $error;
						}
						else
						{
							$reader = $_SESSION['contact']['first_name'] . ' ' . $_SESSION['contact']['last_name'];

							if ($new_receiver_id) {$receiver = $readers['raw'][$new_receiver_id]['first_name'] . ' ' . $readers['raw'][$new_receiver_id]['last_name'];} else {$receiver = '';}

							$action = $action_types['all'][$new_action_type_id]['name'];
							if ($action_types['all'][$new_action_type_id]['description']) {$action .= ' - ' . $action_types['all'][$new_action_type_id]['description'];}

							$submission_id = $_SESSION['submission']['submission_id'];

							$title = $_SESSION['submission']['title'];

							if ($_SESSION['submission']['writer']) {$writer = $_SESSION['submission']['writer'];} else {$writer = $_SESSION['submission']['contact']['first_name'] . ' ' . $_SESSION['submission']['contact']['last_name'];}

							if ($action_types['all'][$new_action_type_id]['from_reader'])
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

							if (in_array($new_action_type_id, $action_types['forwards']))
							{
								$to = make_email($readers['raw'][$new_receiver_id]['first_name'] . ' ' . $readers['raw'][$new_receiver_id]['last_name'], $readers['raw'][$new_receiver_id]['email']);
								$_SESSION['to_email'] = $readers['all'][$new_receiver_id]['email'];
							}
							else
							{
								$to = make_email($_SESSION['submission']['contact']['first_name'] . ' ' . $_SESSION['submission']['contact']['last_name'], $_SESSION['submission']['contact']['email']);
								$_SESSION['to_email'] = $_SESSION['submission']['contact']['email'];
							}

							$subject = $action_types['all'][$new_action_type_id]['subject'];
							$body = $action_types['all'][$new_action_type_id]['body'];

							if (!isset($message)) {$message = '';}
							if (strpos($body, '[message]') === false && $message) {$message = '';}

							foreach ($placeholders as $key => $value) {$GLOBALS[$key] = $$key;}

							// so we can unblind as needed
							$_SESSION['safe']['subject'] = replace_placeholders($subject);
							$_SESSION['safe']['body'] = replace_placeholders($body);

							// replace writer with [blind] if sender is in blind group
							$access_group = str_replace('active ', '', $_SESSION['contact']['access']);
							if ($_SESSION['groups'][$access_group]['blind'])
							{
								$GLOBALS['writer'] = '[blind]';
								if (!in_array($new_action_type_id, $_SESSION['action_types']['forwards'])) {$to = '[blind]';}
							}

							// replace writer with [blind] if receiver is in blind group
							if ($new_receiver_id)
							{
								$access_group = str_replace('active ', '', $_SESSION['readers']['all'][$new_receiver_id]['access']);
								if ($_SESSION['groups'][$access_group]['blind']) {$GLOBALS['writer'] = '[blind]';}
								$_SESSION['safe']['access_group'] = $access_group;
							}

							// replace writer with [blind] if genre is blind
							if ($_SESSION['submission']['genre_id'] && $_SESSION['genres']['all'][$_SESSION['submission']['genre_id']]['blind'])
							{
								$GLOBALS['writer'] = '[blind]';
								if (!in_array($new_action_type_id, $_SESSION['action_types']['forwards'])) {$to = '[blind]';}
							}

							$subject = replace_placeholders($subject);
							$body = replace_placeholders($body);
							if ($message) {$message_display = nl2br(htmlspecialchars($message));}

							if (isset($_SESSION['file_upload']['filename'])) {$filename = $_SESSION['file_upload']['filename'];} else {$filename = '';}

							$preview = array(
							'reader' => $reader,
							'action' => $action,
							'receiver' => $receiver,
							'notes' => $notes,
							'from_name' => $from_name,
							'from_email' => $from_email,
							'from' => $from,
							'to' => $to,
							'file' => $filename,
							'subject' => $subject,
							'body' => $body
							);

							$_SESSION['preview'] = $preview;

							foreach ($preview as $key => $value)
							{
								$value = htmlspecialchars($value);
								$value = nl2br($value);
								if ($key == 'reader' || $key == 'action' || $key == 'receiver' || $key == 'notes' || $key == 'file') {$value = '<span style="color: #800000;">' . $value . '</span>';}
								if ($key == 'body' && $message) {$value = str_replace($message_display, '<span style="color: #800000;">' . $message_display . '</span>', $value);}
								$preview_display[$key] = $value;
							}

							// remove from_name and from_email from display
							unset($preview_display['from_name']);
							unset($preview_display['from_email']);

							$_SESSION['insert_action'] = array(
							 'submission_id' => $submission_id,
							 'reader_id' => $_SESSION['contact']['contact_id'],
							 'action_type_id' => $new_action_type_id,
							 'receiver_id' => $new_receiver_id,
							 'notes' => $notes,
							 'message' => $message
							);
						}
					}

					echo '
					<table style="border-collapse: collapse;">
						<tr>
							<td style="padding-right: 10px;">

								<label for="new_action_type_id" id="label_new_action_type_id">create action:</label><br>
								<select id="new_action_type_id" name="new_action_type_id" style="width: 150px;">
								<option value="">&nbsp;</option>
								';

								foreach ($action_types['active'] as $value)
								{
									$value = (string) $value;
									if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) || strpos($action_types['all'][$value]['access_groups'], $access_number) !== false)
									{
										echo '<option value="' . $value . '"';
										if (isset($new_action_type_id) && $new_action_type_id == $value) {echo ' selected';}
										echo '>' . $action_types['all'][$value]['name'];
										if ($action_types['all'][$value]['description']) {echo ' - ' . $action_types['all'][$value]['description'];}
										echo '</option>' . "\n";
									}
								}

								echo '
								</select><br>
								<label for="new_receiver_id" id="label_new_receiver_id">to:</label> <span class="small">[ <i>only for forwards</i> ]</span><br>
								<select id="new_receiver_id" name="new_receiver_id" style="width: 150px;">
								<option value="">&nbsp;</option>
								';

								$allowed_forwards_array = explode(',', $groups[$contact_access]['allowed_forwards']);
								foreach ($readers['active'] as $value)
								{
									$access = str_replace('active ', '', $readers['all'][$value]['access']);
									if (in_array($access, $allowed_forwards_array))
									{
										echo '<option value="' . $value . '"';
										if (isset($new_receiver_id) && $new_receiver_id == $value) {echo ' selected';}
										echo '>' . $readers['all'][$value]['last_name'] . ', ' . $readers['all'][$value]['first_name'] . '</option>' . "\n";
									}
								}

								echo '
								</select>

							</td>
							<td style="padding-right: 10px;">

								<label for="notes" id="label_notes">notes:</label> <span class="small">[ <i>only for internal records</i> ]</span><br>
								<textarea id="notes" name="notes">'; if (isset($notes)) {echo htmlspecialchars($notes);} echo '</textarea><br>
								<label for="message" id="label_message">message:</label> <span class="small">[ <i>sent to receiver (if enabled)</i> ]</span><br>
								<textarea id="message" name="message">'; if (isset($message)) {echo htmlspecialchars($message);} echo '</textarea><br>
								<label for="file" id="label_file">attach file:</label> <span class="small">[ <i>' . $max_file_size_formatted . ' max</i> ]</span><br>';
								if ($fields['file']['maxlength']) {echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . $fields['file']['maxlength'] . '">';}
								echo '<input type="file" id="file" name="file">';
								if (isset($_SESSION['file_upload']['filename']) && $_SESSION['file_upload']['filename']) {echo '<br>file selected: <b>' . $_SESSION['file_upload']['filename'] . '</b> [<input type="submit" name="submit" value="remove" style="width: 50px; border: 0px; color: ' . $config['color_link'] . '; background-color: ' . $config['color_background'] . ';"> ]';}
								echo '
								<br>
								<br>
								<div style="text-align: center;">
								<input type="submit" id="submit_preview" name="submit" value="preview" class="form_button"> <input type="submit" name="submit" value="cancel" class="form_button">
								</div>

							</td>
							';

						if ($submit == 'preview')
						{
							echo '
							<td>
							';
								if ($notice)
								{
									echo '<div class="notice">' . $notice . '</div>';
								}

								if ($form_check)
								{
									echo '<table class="padding_lr_5">';
									foreach ($preview_display as $key => $value)
									{
										echo '
										<tr class="foreground">
										<td class="row_left">' . $key . ':</td>
										<td><b>' . $value . '</b></td>
										</tr>
										';
									}
									echo '
									<tr>
									<td>&nbsp;</td>
									<td style="padding-top: 10px;">
										<input type="checkbox" id="send_action_mail" name="send_action_mail" value="Y" checked><label for="send_action_mail" id="label_send_action_mail">send mail?</label>
										<input type="submit" id="submit_send" name="submit" value="send" class="form_button" style="margin-left: 10px;">
										<input type="submit" name="submit" value="cancel" class="form_button">
									</td>
									</table>
									';
								}

								echo '<td>';
						}

						echo '
						</tr>
					</table>
					';
				}
			}
		}

		function get_forwards()
		{
			global $action_types;

			$forwards = array();

			// sync_last_actions
			$sql = 'SELECT submission_id FROM submissions WHERE last_action_type_id IN(' . implode(',', $action_types['forwards']) . ') AND last_receiver_id = ' . $_SESSION['contact']['contact_id'] . ' ORDER BY date_time';

			$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT forwards');
			if (mysqli_num_rows($result)) {while ($row = mysqli_fetch_assoc($result)) {$forwards[$row['submission_id']] = $row['submission_id'];}}
			$_SESSION['forwards'] = $forwards;
		}

		// admin/editor
		if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
		{
			if ($submit == 'update')
			{
				form_hash('validate');

				$login_required_fields = $login_required_fields[$_SESSION['table']];

				foreach ($_REQUEST['row'] as $key => $value)
				{
					$value = trim($value);
					$value = stripslashes($value);
					$value = strip_tags($value);

					if (in_array($key, $login_required_fields) && !$value)
					{
						$form_check = false;
						$errors[] = 'required field(s) missing';
						break;
					}

					if (strpos($key, '_id') !== false && $value && !is_numeric($value))
					{
						$form_check = false;
						$errors[] = 'invalid input (IDs must be numeric)';
						break;
					}

					// no longer can edit date_time's
					if ($key == 'date_time' && $value)
					{
						$value_timestamp = @strtotime($value . ' GMT');
						$getdate = @getdate($value_timestamp);
						if (!checkdate($getdate['mon'], $getdate['mday'], $getdate['year']))
						{
							$form_check = false;
							$errors[] = 'invalid date/time';
							break;
						}
						else
						{
							// convert back to GMT
							$timezone_diff_seconds = $gm_timestamp - strtotime($local_date_time);
							$value = gmdate('Y-m-d H:i:s', $value_timestamp + $timezone_diff_seconds);
						}
					}

					if ($key == 'date_paid' && $value)
					{
						$value_timestamp = @strtotime($value);
						$getdate = @getdate($value_timestamp);
						if (!checkdate($getdate['mon'], $getdate['mday'], $getdate['year']))
						{
							$form_check = false;
							$errors[] = 'invalid date/time for date paid';
							break;
						}
						else
						{
							$value = date('Y-m-d', $value_timestamp);
						}
					}

					if ($key == 'writer' && strlen($value) > 50)
					{
						$form_check = false;
						$errors[] = 'maximum length for writer is 50';
						break;
					}

					if ($key == 'title' && strlen($value) > 255)
					{
						$form_check = false;
						$errors[] = 'maximum length for title is 255';
						break;
					}

					if ($key == 'ext' && strlen($value) > 10)
					{
						$form_check = false;
						$errors[] = 'maximum ext for title is 10';
						break;
					}

					if ($value) {$value = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . "'";} else {$value = 'NULL';}
					$sql_array[$key] = $key . ' = ' . $value;
				}

				if (isset($_REQUEST['row']['action_type_id']))
				{
					if (in_array($_REQUEST['row']['action_type_id'], $action_types['forwards']) && !$_REQUEST['row']['receiver_id'])
					{
						$form_check = false;
						$errors[] = 'missing receiver (action type "forward" requires receiver)';
					}

					if (!in_array($_REQUEST['row']['action_type_id'], $action_types['forwards']))
					{
						$sql_array['receiver_id'] = 'receiver_id = NULL';
					}
				}

				if ($form_check)
				{
					$sql = 'UPDATE ' . $_SESSION['table'] . ' SET ' . implode(', ', $sql_array) . ' WHERE ' . $_SESSION['id_name'] . ' = ' . $_SESSION['id_value'];
					@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE');
					$notice = $_SESSION['id_name'] . ' ' . $_SESSION['id_value'] . ' successfully updated';
					$sql = '';

					if ($_SESSION['table'] == 'actions') {sync_last_action($_SESSION['submission']['submission_id']);}
				}
				else
				{
					$notice = 'The following errors were detected:<ul>';
					foreach ($errors as $value) {$notice .= '<li>' . $value . '</li>';}
					$notice .= '</ul>update aborted';
				}
			}

			if ($submit == 'insert new action' || $submit == 'preview' || $submit == 'cancel' || $submit == 'send' || $submit == 'remove')
			{
				$submodule = 'insert_action';
				if (isset($_SESSION['submission']['submission_id'])) {$search_submission_id = $_SESSION['submission']['submission_id'];}
				if ($submit == 'cancel' || $submit == 'send') {$submodule = '';}
			}

			if (isset($_GET['offset']) || isset($_GET['backtolist']))
			{
				$submit = 'search submissions';
				extract($_SESSION['criteria']);
			}

			if ($submodule == 'forwards')
			{
				$search_genre_id = 'all submissions';
				$search_action_type_id = 'all forwards';
				$search_receiver_id = $_SESSION['contact']['contact_id'];
				$submit = 'search submissions';
			}

			get_submissions();

			echo '
			<table style="border-collapse: collapse; width: 100%;">
				<tr>
					<td style="width: 34%;">

						<table class="padding_lr_5">
							<tr>
								<td class="row_left"><label for="search_keyword" id="label_search_keyword">find keywords:</label></td>
								<td><input type="text" id="search_keyword" name="search_keyword" value="'; if (isset($_SESSION['criteria']['search_keyword'])) {echo htmlspecialchars($_SESSION['criteria']['search_keyword']);} echo '" style="width: 150px;"></td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td class="row_left"><label for="search_genre_id" id="label_search_genre_id">genre:</label></td>
								<td>
									<select id="search_genre_id" name="search_genre_id" style="width: 150px;">
									';

									$genres_form = array_keys($genres['all']);
									array_unshift($genres_form, 'all submissions', 'all no genre');

									foreach ($genres_form as $value)
									{
										echo '<option value="' . $value . '"';
										if (isset($_SESSION['criteria']['search_genre_id']) && $_SESSION['criteria']['search_genre_id'] == $value) {echo ' selected';}
										if (is_numeric($value)) {echo ' style="color: #800000;">' . $genres['all'][$value]['name'];} else {echo '>' . $value;}
										echo '</option>' . "\n";
										if ($value == 'all no genre') {echo '<optgroup label="genres">';}
									}

									echo '
									</optgroup>
									</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td class="row_left"><label for="search_action_type_id" id="label_search_action_type_id">last action:</label></td>
								<td>
									<select id="search_action_type_id" name="search_action_type_id" style="width: 150px;">
									';

									make_action_types_form();

									foreach ($action_types_form as $key => $value)
									{
										echo '<option value="' . $key . '"';
										if (isset($_SESSION['criteria']['search_action_type_id']) && $_SESSION['criteria']['search_action_type_id'] == $key) {echo ' selected';}
										if (is_numeric($key)) {echo ' style="color: #800000;"';}
										echo '>' . $value;
										if (isset($action_types['all'][$key]) && $action_types['all'][$key]['description']) {echo ' - ' . $action_types['all'][$key]['description'];}
										echo '</option>' . "\n";
										if ($value == 'all rejects') {echo '<optgroup label="actions">';}
									}

									echo '
									</optgroup>
									</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td class="row_left"><label for="search_receiver_id" id="label_search_receiver_id">to:</label></td>
								<td>
									<select id="search_receiver_id" name="search_receiver_id" style="width: 150px;">
									<option value="anyone">anyone</option>
									';

									foreach ($readers['active'] as $value)
									{
										echo '<option value="' . $value . '"';
										if (isset($_SESSION['criteria']['search_receiver_id']) && $_SESSION['criteria']['search_receiver_id'] == $value) {echo ' selected';}
										echo '>' . $readers['all'][$value]['last_name'] . ', ' . $readers['all'][$value]['first_name'] . '</option>' . "\n";
									}

									if (isset($readers['inactive']) && $readers['inactive'])
									{
										echo '<optgroup label="inactive">';
										foreach ($readers['inactive'] as $value)
										{
											echo '<option value="' . $value . '"';
											if (isset($_SESSION['criteria']['search_receiver_id']) && $_SESSION['criteria']['search_receiver_id'] == $value) {echo ' selected';}
											echo '>' . $readers['all'][$value]['last_name'] . ', ' . $readers['all'][$value]['first_name'] . '</option>' . "\n";
										}
										echo '</optgroup>';
									}

									echo '
									</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td class="row_left"><label for="search_date_order" id="label_search_date_order">sort:</label></td>
								<td>
									<select id="search_date_order" name="search_date_order" style="width: 150px;">
									';

									if (!isset($_SESSION['criteria']['search_date_order']) && isset($config['default_sort_order'])) {$_SESSION['criteria']['search_date_order'] = $config['default_sort_order'];}

									$date_order_array = array('ascending', 'descending');
									foreach ($date_order_array as $value)
									{
										echo '<option value="' . $value . '"';
										if (isset($_SESSION['criteria']['search_date_order']) && $_SESSION['criteria']['search_date_order'] == $value) {echo ' selected';}
										echo '>date ' . $value . '</option>';
									}

									echo '
									</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							';

							if ($config['show_date_paid'])
							{
								$payment_array = array('all', 'paid', 'unpaid');

								echo '
								<tr>
								<td class="row_left"><label for="search_payment" id="label_search_payment">payment:</label></td>
								<td>
								<select id="search_payment" name="search_payment" style="width: 150px;">
								';

								foreach ($payment_array as $value)
								{
									echo '<option value="' . $value . '"';
									if (isset($_SESSION['criteria']['search_payment']) && $_SESSION['criteria']['search_payment'] == $value) {echo ' selected';}
									echo '>' . $value . '</option>';
								}

								echo '
								</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
								</tr>
								';
							}

							echo '
							<tr>
								<td>
								';

								// "search_genre_id" must be set in "criteria" so that "back to list" has a valid search query
								$extra = '';
								$anchor = '';
								if (isset($submission_id)) {$anchor = '#' . $submission_id;}
								if ($single_display && isset($_SESSION['criteria']['search_genre_id'])) {$extra = '<b>[ <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&backtolist=1' . $anchor . '">back to list</a> ]</b>';}

								if (isset($_GET['from_reports']))
								{
									$allowed_reports = array('daily', 'monthly', 'status', 'forwards');
									if (!in_array($_GET['from_reports'], $allowed_reports)) {exit_error('unauthorized report');}

									$extra = '<b>[ <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=reports&report=[url_rest]">back to reports</a> ]</b>';

									if ($_GET['from_reports'] == 'daily' && $single_display)
									{
										if (isset($_GET['submission_id']) || isset($_GET['contact_id'])) {$row = 'submission_' . $_GET['submission_id'];}
										if (isset($_GET['submission_id']) && isset($_GET['action_id'])) {$row = 'action_' . $_GET['action_id'];}
										$url_rest = $_GET['from_reports'] . '&date_report=' . $_GET['date_report'] . '#row_' . $row;
									}

									if ($_GET['from_reports'] == 'monthly' || $_GET['from_reports'] == 'status' || $_GET['from_reports'] == 'forwards')
									{
										$anchor = '';
										if ($_GET['from_reports'] == 'forwards' && isset($_GET['search_receiver_id'])) {$anchor = '#reader_' . $_GET['search_receiver_id'];}
										$url_rest = $_GET['from_reports'] . $anchor;
									}

									$extra = str_replace('[url_rest]', $url_rest, $extra);
									$_SESSION['from_reports_extra'] = $extra;
								}

								if (!$extra && isset($_SESSION['from_reports_extra'])) {$extra = $_SESSION['from_reports_extra'];}

								echo '
								</td>
								<td><input type="submit" name="submit" value="search submissions" class="form_button" style="width: 150px; margin-top: 5px;"></td>
								<td style="background-color: ' . $config['color_background'] . '; white-space: nowrap; vertical-align: bottom; padding-bottom: 4px;">' . $extra . '&nbsp;</td>
							</tr>
						</table>

					</td>
					<td style="width: 33%;">
					';

						if ($notice) {echo '<div class="foreground notice" style="border: 1px solid ' . $config['color_text'] . '; width: 200px; padding: 5px;">' . $notice . '</div>';}

					echo '
					&nbsp;
					</td>
					<td style="text-align: right; width: 33%;">
					';

						// tag form
						if ($display_results && $submit == 'search submissions' && !$single_display)
						{
							if (!isset($tag_confirm))
							{
								echo '
								<table class="padding_lr_5" style="display: inline-block;">
									<tr>
										<td class="row_left"><label for="tag_action_type_id" id="label_tag_action_type_id">create action:</label></td>
										<td>
											<select id="tag_action_type_id" name="tag_action_type_id" style="width: 150px;">
											<option value="">&nbsp;</option>
											';

											foreach ($action_types['active'] as $value)
											{
												echo '<option value="' . $value . '"';
												if (isset($_POST['tag_action_type_id']) && $_POST['tag_action_type_id'] == $value) {echo ' selected';}
												echo '>' . $action_types['all'][$value]['name'];
												if ($action_types['all'][$value]['description']) {echo ' - ' . $action_types['all'][$value]['description'];}
												echo '</option>' . "\n";
											}

											echo '
											</select>
										</td>
									</tr>
									<tr>
										<td class="row_left"><label for="tag_receiver_id" id="label_tag_receiver_id">to:</label></td>
										<td>
											<select id="tag_receiver_id" name="tag_receiver_id" style="width: 150px;">
											<option value="">&nbsp;</option>
											';

											$allowed_forwards_array = explode(',', $groups[$_SESSION['contact']['access']]['allowed_forwards']);
											foreach ($readers['active'] as $value)
											{
												$access = str_replace('active ', '', $readers['all'][$value]['access']);
												if (in_array($access, $allowed_forwards_array))
												{
													echo '<option value="' . $value . '"';
													if (isset($_POST['tag_receiver_id']) && $_POST['tag_receiver_id'] == $value) {echo ' selected';}
													echo '>' . $readers['all'][$value]['last_name'] . ', ' . $readers['all'][$value]['first_name'] . '</option>' . "\n";
												}
											}

											echo '
											</select>
										</td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><input type="submit" id="submit_apply_to_tagged" name="submit" value="apply to tagged" class="form_button" style="width: 150px;"></td>
									</tr>
								</table>
								';
							}

							if ($submodule == 'tag' && $form_check)
							{
								$_SESSION['tag_action_type_id'] = $_POST['tag_action_type_id'];
								unset($_SESSION['tag_receiver_id']);
								if (isset($_POST['tag_receiver_id'])) {$_SESSION['tag_receiver_id'] = $_POST['tag_receiver_id'];}

								// action tooltip that shows email body
								$action_text = str_replace('[message]', '', $action_types['all'][$_POST['tag_action_type_id']]['body']);
								foreach ($placeholders as $key => $value)
								{
									if ($key == 'first_name' || $key == 'last_name') {unset($GLOBALS[$key]);} // otherwise these will reflect the first tagged submission rather than generic [variables]
									if (isset($config[$key])) {$$key = $config[$key];} else {$$key = '[' . $key . ']';}
								}
								$reader = $_SESSION['contact']['first_name'] . ' ' . $_SESSION['contact']['last_name'];
								if (in_array($_POST['tag_action_type_id'], $action_types['forwards'])) {$receiver = $readers['all'][$_POST['tag_receiver_id']]['first_name'] . ' ' . $readers['all'][$_POST['tag_receiver_id']]['last_name'];}
								$action_text = replace_placeholders($action_text);
								foreach ($placeholders as $key => $value) {unset($$key);} // flush out conflicting global vars

								$GLOBALS['js_object'] .= 'var action_tooltip = ' . make_tooltip($action_text) . ';' . "\n";

								echo '
								<div class="foreground" style="text-align: center; border: 1px solid ' . $config['color_text'] . '; padding: 5px; width: 250px; display: inline-block;">
								Please confirm that you wish<br>to create <b>' . count($_POST['tag']) . '</b> new action(s):<br>
								<table style="border-collapse: collapse; display: inline-block;">
								<tr><td class="row_left">action type:</td><td id="tag_action_td" style="text-align: left; font-weight: bold;">' . $action_types['all'][$_POST['tag_action_type_id']]['name']; if ($action_types['all'][$_POST['tag_action_type_id']]['description']) {echo ' - ' . $action_types['all'][$_POST['tag_action_type_id']]['description'];} echo '</td></tr>';
								if (in_array($_POST['tag_action_type_id'], $action_types['forwards'])) {echo '<tr><td class="row_left">receiver:</td><td style="text-align: left; font-weight: bold;">' . $readers['all'][$_POST['tag_receiver_id']]['first_name'] . ' ' . $readers['all'][$_POST['tag_receiver_id']]['last_name'] . '</td></tr>';}
								echo '
								</table>
								<br>
								<input type="checkbox" id="send_action_mail" name="send_action_mail" value="Y" checked><label for="send_action_mail" id="label_send_action_mail">send mail?</label><br>
								<input type="submit" id="submit_confirm" name="submit" value="confirm" class="form_button"> <input type="submit" name="submit" value="cancel" class="form_button">
								</div>
								';
							}
						}

					echo '
					&nbsp;
					</td>
				</tr>
			</table>
			';

			if ($display_results)
			{
				echo '<br>';
				display_submissions();
			}
		}

		// active 1-5
		if (in_array($_SESSION['contact']['access'], $access_grouping['active']))
		{
			if (isset($_SESSION['submission']) && in_array($_SESSION['submission']['submission_id'], $_SESSION['forwards']) && $submit == 'insert new action' || $submit == 'preview' || $submit == 'cancel' || $submit == 'send')
			{
				$submodule = 'insert_action';
				$search_submission_id = $_SESSION['submission']['submission_id'];
				if ($submit == 'cancel' || $submit == 'send') {$submodule = '';}
			}

			if ($submit == 'login' || $submodule == 'forwards')
			{
				$submodule = 'forwards';
				$keep = array('login', 'contact', 'forwards');
				flush_session($keep);
				$submit = 'search submissions';
			}

			$search_genre_id = 'all submissions';
			$search_action_type_id = 'all forwards';
			$search_receiver_id = $_SESSION['contact']['contact_id'];

			if ($submit != 'search submissions') {get_forwards();}
			get_submissions();

			if ($notice && $result_count)
			{
				echo '<div class="foreground notice" style="border: 1px solid ' . $config['color_text'] . '; width: 200px; padding: 5px; margin-bottom: 20px;">' . $notice . '</div>';
			}

			if ($display_results) {display_submissions();}

			if (!$_SESSION['forwards'])
			{
				echo '<p>You currently have no forwarded submissions.</p>';
				exit_error();
			}
		}
	}

	if ($module == 'contacts')
	{
		if ((in_array($_SESSION['contact']['access'], $access_grouping['no_access'])) || (in_array($_SESSION['contact']['access'], $access_grouping['active']) && isset($_REQUEST['contact_id']) && $_REQUEST['contact_id'] != $_SESSION['contact']['contact_id']) || (in_array($_SESSION['contact']['access'], $access_grouping['active']) && $submodule == 'insert'))
		{
			echo '<p>You are not authorized to access this area.</p>';
			exit_error();
		}

		$contact_id = '';
		$contact_id_safe = '';
		$header = '';
		$contacts = array();
		$contact = array();
		$access_array = field2array('enum', $describe['contacts']['access']);

		// coming from reports
		if (isset($_GET['from_reports']) && $_GET['from_reports'] == 'contacts')
		{
			if ($_REQUEST['search_access'] == 'all') {$_REQUEST['search_access'] = 'any contact';}
			if ($_REQUEST['search_access'] == 'non-staff') {$_REQUEST['search_access'] = 'any non-staff';}
			if ($_REQUEST['search_access'] == 'staff') {$_REQUEST['search_access'] = 'any staff';}
			$_REQUEST['search_access'] = str_replace('_', ' ', $_REQUEST['search_access']);
			$_REQUEST['search_field'] = 'contact_id';
			$_REQUEST['search_operator'] = 'contains';
			$submit = 'search contacts';
		}

		$search_fields = array(
		'search_access',
		'search_field',
		'search_operator',
		'search_value'
		);

		foreach ($search_fields as $value)
		{
			if (isset($_REQUEST[$value])) {$$value = $_REQUEST[$value];} else {$$value = '';}
		}

		if (isset($_REQUEST['contact_id']) && $_REQUEST['contact_id'])
		{
			if (is_numeric($_REQUEST['contact_id']))
			{
				$contact_id = (int) $_REQUEST['contact_id'];
				$contact_id_safe = $contact_id; // to re-insert into global scope

				if (isset($_GET['single_contact']))
				{
					$search_access = 'any contact';
					$search_field = 'contact_id';
					$search_operator = 'equals';
					$search_value = $contact_id;
					$submit = 'search contacts';
				}
			}
			else
			{
				$notice = 'invalid search value';
			}
		}

		if (isset($_SESSION['sql'])) {$sql = $_SESSION['sql'];}

		if ($submit == 'search contacts')
		{
			if ($search_value == '') {$search_operator = 'contains';} // so a blank search_value will use wildcard
			foreach ($search_fields as $value) {$_SESSION['criteria'][$value] = $$value;}

			$sql = 'FROM contacts WHERE ' . $search_field;

			if ($search_operator == 'contains')
			{
				$operator = 'LIKE';
				if (strpos($search_field, '_id') === false && strpos($search_value, '%') === false) {$search_value = '%' . $search_value . '%';}
			}
			if ($search_operator == 'equals') {$operator = '=';}
			if ($search_value == 'NULL') {$operator = 'IS NULL'; $search_value = '';}

			$sql .= ' ' . $operator;

			if ($search_value) {$sql .= " '" . mysqli_real_escape_string($GLOBALS['db_connect'], $search_value) . "'";}
			elseif ($operator == 'IS NULL') {$sql .= '';}
			else {$sql .= " '%'";}

			if (isset($_REQUEST['search_access']) && $_REQUEST['search_access'] != 'any contact')
			{
				if ($_REQUEST['search_access'] == 'any non-staff') {$sql .= " AND access IS NULL OR access = 'blocked'";}
				elseif ($_REQUEST['search_access'] == 'any staff') {$sql .= " AND access IS NOT NULL AND access != 'blocked'";}
				else {$sql .= " AND access = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $_REQUEST['search_access']) . "'";}
			}

			$_SESSION['sql'] = $sql;
			$offset = 0;
		}

		if (isset($_SESSION['criteria']))
		{
			$_SESSION['criteria'] = array_map('htmlspecialchars', $_SESSION['criteria']);
			extract($_SESSION['criteria']);
		}

		if ($sql)
		{
			$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT COUNT(*) AS count ' . $sql) or exit_error('query failure: SELECT COUNT list contacts');
			$row = mysqli_fetch_assoc($result);
			$result_count = $row['count'];

			if ($result_count == 0)
			{
				if (!$notice) {$notice = '0 records matching your search criteria';}
			}
			else
			{
				$display_results = true;

				$sql = 'SELECT * ' . $sql . ' ORDER BY ' . $search_field;
				if ($config['pagination_limit'] && $result_count > $config['pagination_limit'])
				{
					$pagination = true;
					$sql .= ' LIMIT ' . $offset . ', ' . $config['pagination_limit'];
				}

				$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT contacts list');
				while ($row = mysqli_fetch_assoc($result)) {$contacts[$row['contact_id']] = $row;}

				if ($config['pagination_limit'] && $result_count > $config['pagination_limit'])
				{
					$count = mysqli_num_rows($result);
					$header = paginate();
				}

				if ($result_count == 1 && !$contact_id)
				{
					$contact_id = key($contacts);
					// $contact_id_safe = $contact_id; // to re-insert into global scope
				}
			}
		}

		if ($contact_id)
		{
			$sql_single = 'SELECT * FROM contacts WHERE contact_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $contact_id);
			$result = @mysqli_query($GLOBALS['db_connect'], $sql_single) or exit_error('query failure: SELECT single contact');
			if (mysqli_num_rows($result))
			{
				$display_results = true;
				$contact = mysqli_fetch_assoc($result);
				// moved to display level
				// if ($contact['date_time']) {$contact['date_time'] = timezone_adjust($contact['date_time']);}

				$_SESSION['current_contact_id'] = $contact_id;
				$_SESSION['current_contact_array'] = $contact;

				get_min_max('contacts', 'contact_id');

				unset($_SESSION['prev_contact_id']);
				unset($_SESSION['next_contact_id']);

				// get prev contact
				if ($contact_id != $min_max['contacts']['min_id'])
				{
					$result = @mysqli_query($GLOBALS['db_connect'], "SELECT contact_id FROM contacts WHERE contact_id < '$contact_id' ORDER BY contact_id DESC LIMIT 1") or exit_error('query failure: SELECT prev contact');
					$row = mysqli_fetch_assoc($result);
					$prev_contact_id = $row['contact_id'];
					$_SESSION['prev_contact_id'] = $prev_contact_id;
				}

				// get next contact
				if ($contact_id != $min_max['contacts']['max_id'])
				{
					$result = @mysqli_query($GLOBALS['db_connect'], "SELECT contact_id FROM contacts WHERE contact_id > '$contact_id' LIMIT 1") or exit_error('query failure: SELECT next contact');
					$row = mysqli_fetch_assoc($result);
					$next_contact_id = $row['contact_id'];
					$_SESSION['next_contact_id'] = $next_contact_id;
				}

				$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT COUNT(*) AS count FROM submissions WHERE submitter_id = ' . $contact_id) or exit_error('query failure: SELECT COUNT submissions');
				$row = mysqli_fetch_assoc($result);
				$submission_count = $row['count'];
				if ($submission_count) {$submission_count = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&contact_id=' . $contact_id . '">' . $submission_count . '</a>';}

				if ($submodule == 'update' && $submit == 'update') {$contact = $_POST;}
			}
			else
			{
				$notice = '0 records matching your search criteria';
			}
		}

		if (!$db_totals['contacts']) {$display_results = false; $notice = 'contacts table is empty (0 records)';}
		if ($submodule == 'insert') {$display_results = true;}
		if (!$result_count) {$result_count = '';}

		echo '
		<table style="border-collapse: collapse; width: 100%;">
			<tr>
				<td style="width: 34%;">
				';

					if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
					{
						echo '
						<table class="padding_lr_5">
							<tr>
								<td class="row_left"><label for="search_access" id="label_search_access">show me:</label></td>
								<td>
									<select id="search_access" name="search_access" style="width: 150px;">
									';

									$access_array_form = $access_array;
									unset($access_array_form[array_search('blocked', $access_array_form)]);
									array_unshift($access_array_form, 'any contact', 'any non-staff', 'blocked', 'any staff');

									foreach ($access_array_form as $value)
									{
										echo '<option value="' . $value . '"';
										if (isset($search_access) && $search_access == $value) {echo ' selected';}
										if (in_array($value, $access_array)) {echo ' style="color: #800000;"';}
										echo '>' . $value . '</option>' . "\n";
										if ($value == 'any staff') {echo '<optgroup label="staff">';}
									}

									echo '
									</optgroup>
									</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td class="row_left"><label for="search_field" id="label_search_field">whose:</label></td>
								<td>
									<select id="search_field" name="search_field" style="width: 150px;">
									';

									foreach ($fields_searchable as $value)
									{
										echo '<option value="' . $value . '"';
										if (isset($search_field) && $search_field == $value) {echo ' selected';}
										echo '>' . $value . '</option>' . "\n";
									}

									echo '
									</select>
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td>
									<select name="search_operator" style="width: 75px;">
									';

									$operators = array('contains', 'equals');

									foreach ($operators as $value)
									{
										echo '<option value="' . $value . '"';
										if (isset($search_operator) && $search_operator == $value) {echo ' selected';}
										echo '>' . $value . '</option>' . "\n";
									}

									echo '
									</select>
								</td>
								<td class="small">
									<input type="text" name="search_value" value="'; if (isset($search_value)) {echo $search_value;} echo '" style="width: 150px;"><br>
									use % for wildcards (blank = %)
								</td>
								<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td><input type="submit" name="submit" value="search contacts" class="form_button" style="width: 150px; margin-top: 5px;"></td>
								';

								$extra = '';
								if (isset($_GET['from_reports']))
								{
									$allowed_reports = array('daily', 'contacts');
									if (!in_array($_GET['from_reports'], $allowed_reports)) {exit_error('unauthorized report');}

									$extra = '<b>[ <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=reports&report=[url_rest]">back to reports</a> ]</b>';

									if ($_GET['from_reports'] == 'daily')
									{
										$url_rest = $_GET['from_reports'] . '&date_report=' . $_GET['date_report'] . '#row_submission_' . $_GET['submission_id'];
									}

									if ($_GET['from_reports'] == 'contacts')
									{
										$url_rest = $_GET['from_reports'] . '#row_' . $_GET['search_access'];
									}

									$extra = str_replace('[url_rest]', $url_rest, $extra);
								}

								echo '
								<td style="background-color: ' . $config['color_background'] . '; white-space: nowrap; vertical-align: bottom; padding-bottom: 4px;">' . $extra . '&nbsp;</td>
							</tr>
						</table>
						';
					}

				echo '
				</td>
				<td style="width: 33%;">
				';

					if ($notice)
					{
						echo '<div class="foreground notice" style="border: 1px solid ' . $config['color_text'] . '; width: 200px; padding: 5px;">' . $notice . '</div>';
					}

				echo '
				</td>
				<td style="text-align: right; vertical-align: bottom; width: 33%;">
				';

					if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
					{
						echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=insert"><img src="button_insert.png" alt="insert" width="13" height="12"> <b>insert a new contact</b></a>';
					}

				echo '
				</td>
			</tr>
		</table>
		';

		if ($display_results)
		{
			// only admins/editors see form at top of contact area
			if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) {echo '<hr>';}

			echo '
			<table style="border-collapse: collapse; width: 100%;">
			<tr>
			<td style="width: 50%;">
			';
					if ($contacts)
					{
						echo '
						<table style="border-collapse: collapse; width: 100%;">
						<tr>
						<td style="width: 50%;">
						';
							if ($search_value == '') {$search_value = '<span class="small">(anything)</span>';}

							echo '
							<table class="padding_lr_5">
							<tr class="foreground">
							<td class="row_left">showing:</td><td><b>' . $search_access . '</b></td>
							</tr>
							<tr class="foreground">
							<td class="row_left">whose:</td><td><b>' . $search_field . '</b></td>
							</tr>
							<tr class="foreground">
							<td class="row_left">' . $search_operator . ':</td><td><b>' . $search_value . '</b></td>
							</tr>
							<tr class="foreground">
							<td class="row_left">total:</td><td><b>' . $result_count . '</b></td>
							</tr>
							</table>

						</td>
						<td style="width: 50%; text-align: center;">
						';

							echo $header;

						echo '
						</td>
						</tr>
						</table>
						';
					}
					else
					{
						echo '&nbsp;';
					}

				echo '
				<table class="table_list">
				<tr>
				<th>ID</th>
				<th>name</th>
				<th>email</th>
				<th>access</th>
				</tr>
				';

				$GLOBALS['js_object'] .= 'var contacts = new Object();' . "\n";

				foreach ($contacts as $value)
				{
					$value = array_map('strval', $value);
					$value = array_map('htmlspecialchars', $value);
					if (isset($value[$search_field]) && ($search_field == 'first_name' || $search_field == 'last_name')) {$value[$search_field] = '<u>' . $value[$search_field] . '</u>';}
					extract($value);

					$contact_tooltip = display('text');
					if (!$first_name && !$last_name) {$name = '&nbsp;';} else {$name = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $contact_id . '" id="contact_' . $contact_id . '">' . $first_name . ' ' . $last_name . '</a>';}
					if ($email) {$email = mail_to($email);} else {$email = '&nbsp;';}
					if (!$access) {$access = '&nbsp;';}
					$extra = '';
					if ($contact_id == $contact_id_safe) {$extra = ' class="notice_row"';}

					echo '
					<tr' . $extra . ' style="white-space: nowrap;">
					<td><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $contact_id . '">' . $contact_id . '</a></td>
					<td style="text-align: left;">' . $name . '</td>
					<td style="text-align: left;">' . $email . '</td>
					<td>' . $access . '</td>
					</tr>
					';

					$GLOBALS['js_object'] .= 'contacts[' . $contact_id . '] = {contact: ' . make_tooltip($contact_tooltip) . '};' . "\n";
				}

				if ($contact_id_safe) {$contact_id = $contact_id_safe;}

				echo '
				</table>

			</td>
			<td style="padding-left: 20px; width: 50%;">
			';

				if ($contact && $submodule == 'insert_submission')
				{
					extract($contact);

					function form_insert_submission()
					{
						extract($GLOBALS);

						echo '
						<table class="padding_lr_5">
						<tr><td class="row_left"><label for="writer" id="label_writer">writer name:</label></td><td><input type="text" id="writer" name="writer" value="'; if (isset($writer)) {echo $writer;} echo '" maxlength="' . $fields['writer']['maxlength'] . '"> <span class="small">(if different from above)</span></td></tr>
						<tr><td class="row_left"><label for="title" id="label_title">submission title:</label></td><td><input type="text" id="title" name="title" value="'; if (isset($title)) {echo $title;} echo '" maxlength="' . $fields['title']['maxlength'] . '"></td></tr>
						';

						// changed so admins/editors can create submissions in inactive genres
						if ($fields['genre_id']['enabled'] && isset($genres['all']) && $genres['all'])
						{
							echo '<tr><td class="row_left"><label for="genre_id" id="label_genre_id">genre:</label></td><td><select id="genre_id" name="genre_id">';
							foreach ($genres['all'] as $key => $value)
							{
								echo '<option value="' . $key . '"'; if (isset($genre_id) && $genre_id == $key) {echo ' selected';} echo '>' . $genres['all'][$key]['name'] . '</option>' . "\n";
							}
							echo '</select></td></tr>';
						}

						echo '
						<tr><td class="row_left"><label for="file" id="label_file">file:</label></td><td>'; if ($fields['file']['maxlength']) {echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . $fields['file']['maxlength'] . '">';} echo '<input type="file" id="file" name="file">'; if ($fields['file']['maxlength']) {echo ' <span class="small">(' . $max_file_size_formatted . ' max)'; if (isset($_SESSION['file_upload']['filename'])) {echo '<span style="margin-left: 5px;">file selected:</span> <b>' . $_SESSION['file_upload']['filename'] . '</b>';} echo '</span>';} echo '</td></tr>
						<tr><td class="row_left"><label for="comments" id="label_comments">comments:</label></td><td><textarea id="comments" name="comments" maxlength="' . $fields['comments']['maxlength'] . '">'; if (isset($comments)) {echo $comments;} echo '</textarea>'; if ($fields['comments']['maxlength']) {echo ' <span class="small">(' . $fields['comments']['maxlength'] . ' characters max)</span>';} echo '</td></tr>
						<tr>
						<td>&nbsp;</td>
						<td><input type="submit" id="submit_insert_submission" name="submit" value="submit" class="form_button" style="margin-top: 10px;"> <input type="submit" name="submit" value="cancel" id="cancel" class="form_button" style="margin-top: 10px;"></td>
						</tr>
						</table>
						';
					}

					if (!$submit)
					{
						echo display('html') . '<br><br>';
						form_insert_submission();
					}

					if ($submit == 'submit')
					{
						$_SESSION['post'] = cleanup($_POST, 'strip_tags', 'stripslashes');
						$_SESSION['post_display'] = array_map('htmlspecialchars', $_SESSION['post']);
						if ($_FILES['file']['name']) {upload();} // run upload() if first time submit or re-submit with new file
						extract($_SESSION['post_display']);
						$form_type = 'login_submit';
						form_check();
						echo '<p>You entered:</p>' . display('html') . '<br><br>If the above information is correct, click <input type="submit" id="submit_continue" name="submit" value="continue" class="form_button">';
						if ($contact['email']) {echo '<input type="checkbox" id="send_action_mail" name="send_action_mail" value="Y" checked style="margin-left: 10px;"><label for="send_action_mail" id="label_send_action_mail">send mail?</label>';}
						echo '<p>If you wish to make changes, please update the information below and hit the <b>submit</b> button.</p>';
						form_insert_submission();
					}
				}

				if (($contact || $submodule == 'insert') && $submodule != 'insert_submission')
				{
					if (!isset($_SESSION['current_contact_array']['access'])) {$_SESSION['current_contact_array']['access'] = '';}

					include_once('inc_lists.php');

					if ($submodule == 'insert')
					{
						$contact = $fields_keys;
						unset($contact['contact_id']);
						unset($contact['date_time']);
						unset($contact['timestamp']);
						$contact['country'] = 'USA';
						$contact['mailing_list'] = 'Y';
					}

					if ($_SESSION['contact']['access'] == 'admin' || $submodule == 'insert' || $contact['contact_id'] == $_SESSION['contact']['contact_id']) {echo '';} else {unset($contact['password']);}

					echo '
					<table class="padding_lr_5">
					';

					if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) && $submodule != 'insert')
					{
						echo '
						<tr>
						<td class="row_left">'; if ($contact_id == $min_max['contacts']['min_id']) {echo '';} else {echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $min_max['contacts']['min_id'] . '"><img src="arrow_left_2.png" width="16" height="13" alt="first"></a> <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $prev_contact_id . '"><img src="arrow_left_1.png" width="8" height="13" alt="previous"></a>';} echo '</td>
						<td>'; if ($contact_id == $min_max['contacts']['max_id']) {echo '';} else {echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $next_contact_id . '"><img src="arrow_right_1.png" width="8" height="13" alt="next"></a> <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $min_max['contacts']['max_id'] . '"><img src="arrow_right_2.png" width="16" height="13" alt="last"></a>';} echo '</td>
						</tr>
						';
					}

					foreach ($contact as $key => $value)
					{
						$value = htmlspecialchars((string) $value);

						$type = 'text';
						if (isset($fields[$key]['maxlength'])) {$maxlength = $fields[$key]['maxlength'];} else {$maxlength = 50;}
						$input = '';
						$extra = '';
						if ($submodule != 'insert' && $_SESSION['contact']['access'] != 'admin' && $_SESSION['current_contact_array']['access'] == 'admin') {$extra = ' disabled';}

						// set field types
						if ($key == 'state') {$type = 'enum'; $enum_array = $states;}
						if ($key == 'country') {$type = 'enum'; $enum_array = $countries;}
						if (strpos($key, 'password') !== false) {$type = 'password';}
						if ($key == 'mailing_list') {$type = 'boolean';}
						if ($key == 'access')
						{
							$type = 'enum';
							$enum_array = array_combine($access_array, $access_array); // need keys and values to be the same

							// active staff cannot change their own access
							if (in_array($_SESSION['contact']['access'], $access_grouping['active'])) {$extra .= ' disabled';}

							// only admins can insert/update admins
							if ($_SESSION['contact']['access'] != 'admin' && $_SESSION['current_contact_array']['access'] != 'admin') {unset($enum_array[array_search('admin', $enum_array)]);}

						}
						if ($key == 'email_notification')
						{
							$type = 'check_list';
							$check_array = field2array('set', $describe['contacts']['email_notification']);
							if ($value) {$value_array = explode(',', $value);} else {$value_array = array();}
						}
						if ($key == 'notes') {$type = 'textarea'; $maxlength = $fields['comments']['maxlength'];}

						// type -> field
						if ($type == 'text')
						{
							$input = '<input type="text" id="' . $key . '" name="' . $key . '" value="' . $value . '" maxlength="' . $maxlength . '"' . $extra . '>';
						}

						if ($type == 'password')
						{
							$input = '<input type="password" id="' . $key . '" name="' . $key . '" value="" maxlength="' . $maxlength . '"' . $extra . '>';
						}

						if ($type == 'textarea')
						{
							$input = '<textarea id="' . $key . '" name="' . $key . '" maxlength="' . $maxlength . '"' . $extra . '>' . $value . '</textarea>';
						}

						if ($type == 'enum')
						{
							$input = '<select id="' . $key . '" name="' . $key . '"' . $extra . '><option value="">&nbsp;</option>';
							foreach ($enum_array as $enum_key => $enum_value)
							{
								$input .= '<option value="' . $enum_key . '"';
								if ($enum_key == $value) {$input .= ' selected';}
								$input .= '>' . $enum_value . '</option>' . "\n";
							}
							$input .= '</select>';
						}

						if ($type == 'check_list')
						{
							$input = '
							<table style="border-collapse: collapse;">
							<tr>
							<td style="padding: 0px;">
							';

							foreach ($check_array as $check_value)
							{
								$input .= '<input type="checkbox" id="' . $key . '[' . $check_value . ']" name="' . $key . '[' . $check_value . ']" value="' . $check_value . '"';
								if (in_array($check_value, $value_array)) {$input .= ' checked';}
								$input .= ' ><label for="' . $key . '[' . $check_value . ']" id="label_' . $key . '[' . $check_value . ']">' . $check_value . '</label><br>';
							}

							$input .= '
							</td>
							<td class="small" style="font-style: italic; padding: 0px 0px 0px 5px; vertical-align: middle;">
							(for staff only)
							</td>
							</tr>
							</table>
							';
						}

						if ($type == 'boolean')
						{
							$input = '<input type="checkbox" id="' . $key . '" name="' . $key . '" value="Y"';
							if ($value) {$input .= ' checked';}
							$input .= $extra . ' >';
						}

						if ($key == 'contact_id')
						{
							$input = '<b><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $value . '" id="contact_id">' . $value . '</a></b>';
						}

						if ($key == 'date_time' || $key == 'timestamp')
						{
							if ($key == 'date_time' && $value) {$value = timezone_adjust($value);}
							$input = '<b>' . $value . '</b>';
						}

						if ($key == 'contact_id' || $key == 'date_time' || $key == 'timestamp' || $key == 'email_notification') {$key_display = $key . ':';} else {$key_display =' <label for="' . $key . '" id="label_' . $key . '">' . $key . ':</label>';}

						echo '
						<tr>
						<td class="row_left">' . $key_display . '</td>
						<td>' . $input . '</td>
						</tr>
						';
					}

					if ($submodule != 'insert')
					{
						$extra = '';
						if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor'])) {$extra = ' <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&contact_id=' . $contact_id . '&submodule=insert_submission" style="margin-left: 10px;">insert new submission</a>';}

						echo '
						<tr>
						<td class="row_left">submissions:</td>
						<td><b>' . $submission_count . $extra . '</b></td>
						</tr>
						';
					}

					if ($_SESSION['contact']['access'] == 'admin' || ($_SESSION['contact']['access'] != 'admin' && ($submodule == 'insert' || $_SESSION['current_contact_array']['access'] != 'admin')))
					{
						$submit1 = 'update';
						$submit2 = 'delete';
						$extra = '';

						if ($submodule == 'insert')
						{
							$submit1 = 'insert';
							$submit2 = 'cancel';
						}

						if (in_array($_SESSION['contact']['access'], $access_grouping['active']))
						{
							// active staff cannot delete themselves
							$submit2 = 'cancel';
						}

						echo '
						<tr>
						<td>&nbsp;</td>
						<td style="padding-top: 5px;">
						<input type="submit" id="submit_contacts1" name="submit" value="' . $submit1 . '" class="form_button"> <input type="submit" id="submit_contacts2" name="submit" value="' . $submit2 . '" class="form_button">
						</td>
						</tr>
						</table>
						';
					}
				}

			echo '
			</td>
			</tr>
			</table>
			';
		}
	}

	if ($module == 'reports')
	{
		if (!in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
		{
			echo '<p><b>Admins and Editors only!</b><br>You are not authorized to access this area.</p>';
			exit_error();
		}

		if (!$genres) {exit_error('genres table unavailable');}

		$reports = array(
		'daily' => 'daily report',
		'monthly' => 'monthly counts',
		'status' => 'submissions by status',
		'actions' => 'actions by staff',
		'forwards' => 'forwards by staff',
		'contacts' => 'contacts by access'
		);

		$report = '';
		if (isset($_REQUEST['report']) && isset($reports[$_REQUEST['report']])) {$report = $_REQUEST['report'];}

		unset($_SESSION['sql']); // conflict with contacts report
		unset($_SESSION['from_reports_extra']);

		echo '
		<table style="border-collapse: collapse; width: 100%;">
			<tr>
				<td class="foreground" style="width: 200px; padding: 5px;">

					<table class="foreground" style="width: 190px; font-weight: bold; border-spacing: 5px; border: 1px solid ' . $config['color_text'] . ';">
					<tr>
					<td style="white-space: nowrap;">
					choose a report:
					<ul class="nav_list">
					';

					foreach ($reports as $key => $value)
					{
						if ($key == $report) {$value = '<span style="color: ' . $config['color_link_hover'] . ';">' . $value . '</span>';}
						echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&report=' . $key . '">' . $value . '</a></li>' . "\n";
					}

					echo '
					</ul>
					</td>
					</tr>
					</table>

				</td>
				<td style="padding-left: 20px;">
					';

					if ($report) {echo '<div class="header">' . $reports[$report] . ':</div><br>';}

					$counts = array();
					$colspan = count($genres['all']) + 3;

					$genre_headers = '<th>all</th><th>no genre</th>';
					$genres_keys['all'] = 0;
					$genres_keys['no genre'] = 0;
					foreach ($genres['all'] as $key => $value)
					{
						$genre_headers .= '<th>' . $value['name'] . '</th>' . "\n";
						$genres_keys[$key] = 0;
					}

					if ($report == 'daily')
					{
						$_POST = cleanup($_POST, 'strip_tags', 'stripslashes');

						if (isset($_REQUEST['date_report']) && $_REQUEST['date_report']) {$date_report = $_REQUEST['date_report'];} else {$date_report = substr($local_date_time, 0, 10);}

						$date_report_ts = strtotime($date_report);
						$date_report = date('Y-m-d', $date_report_ts);
						$date_report_formatted = date('l, F j, Y', $date_report_ts);
						$date_prev = date('Y-m-d', strtotime($date_report . ' -1 day'));
						$date_next = date('Y-m-d', strtotime($date_report . ' +1 day'));

						// $date_report_ts += $config['timezone'] * 3600;
						// if ($config['dst'] && date('I', $date_report_ts) == 1) {$date_report_ts += 3600;}

						// must push timezone in opposite direction from GMT
						if (strpos($config['timezone'], '-') !== false) {$timezone = str_replace('-', '', $config['timezone']);} else {$timezone = '-' . $config['timezone'];}
						$date_report_ts += $timezone * 3600;
						if ($config['dst'] && date('I', $date_report_ts) == 1) {$date_report_ts -= 3600;}

						$date_report_start = date('Y-m-d H:i:s', $date_report_ts);
						$date_report_end = date('Y-m-d H:i:s', $date_report_ts + ((60 * 60 * 24) - 1));

						echo '
						<table class="padding_lr_5">
						<tr><td>&nbsp;</td><td><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&report=' . $report . '&date_report=' . $date_prev . '"><img src="arrow_left_1.png" width="8" height="13" alt="previous" style="margin-right: 2px;"></a> <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&report=' . $report . '&date_report=' . $date_next . '"><img src="arrow_right_1.png" width="8" height="13" alt="next" style="margin-left: 2px;"></a></td></tr>
						<tr><td><label for="date" id="label_date">date:</label></td><td><input type="text" id="date" name="date_report" value="' . $date_report . '" style="width: 100px;"> <span class="small">(YYYY-MM-DD)</span></td></tr>
						<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="show report" class="form_button" style="margin-top: 5px;"></td></tr>
						</table>
						<input type="hidden" name="report" value="daily">
						';

						echo '<br><br><div class="header">' . $date_report_formatted . '</div><br>';

						$submissions = array();
						$actions = array();

						// get submissions
						$sql = "SELECT * FROM submissions WHERE date_time BETWEEN '$date_report_start' AND '$date_report_end' ORDER BY date_time";
						$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT submissions for report');
						if (mysqli_num_rows($result))
						{
							while ($row = mysqli_fetch_assoc($result))
							{
								// moved to display level
								// if ($row['date_time']) {$row['date_time'] = timezone_adjust($row['date_time']);}
								$row['contact'] = array();
								$submissions[$row['submission_id']] = $row;
								$submitters[$row['submitter_id']] = $row['submitter_id'];
							}

							$result_contacts = @mysqli_query($GLOBALS['db_connect'], 'SELECT * FROM contacts WHERE contact_id IN(' . implode(',', $submitters) . ')') or exit_error('query failure: SELECT contacts for report');
							$submitters = array();
							while ($row = mysqli_fetch_assoc($result_contacts)) {$submitters[$row['contact_id']] = $row;}
							foreach ($submissions as $key => $value)
							{
								if (isset($submitters[$value['submitter_id']])) {$value['contact'] = $submitters[$value['submitter_id']];}
								$submissions[$key] = $value;
							}
						}

						// get actions
						$sql = "SELECT * FROM actions WHERE date_time BETWEEN '$date_report_start' AND '$date_report_end' ORDER BY date_time";
						$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT submissions for report');
						if (mysqli_num_rows($result))
						{
							if (isset($_SESSION['readers'])) {$readers = $_SESSION['readers'];} else {get_readers();}

							while ($row = mysqli_fetch_assoc($result))
							{
								// moved to display level
								// if ($row['date_time']) {$row['date_time'] = timezone_adjust($row['date_time']);}
								$row['reader'] = array();
								if (isset($readers['all'][$row['reader_id']])) {$row['reader'] = $readers['all'][$row['reader_id']];} else {$readers_submitters[$row['reader_id']] = $row['reader_id'];}
								$actions[$row['action_id']] = $row;
							}

							// submitters can be readers (withdraw themselves)
							if (isset($readers_submitters))
							{
								$result_contacts = @mysqli_query($GLOBALS['db_connect'], 'SELECT contact_id, first_name, last_name, email, access FROM contacts WHERE contact_id IN(' . implode(',', $readers_submitters) . ')') or exit_error('query failure: SELECT contacts for report');
								$readers_submitters = array();
								while ($row = mysqli_fetch_assoc($result_contacts)) {$readers_submitters[$row['contact_id']] = $row;}
								foreach ($actions as $key => $value)
								{
									if (isset($readers_submitters[$value['reader_id']])) {$value['reader'] = $readers_submitters[$value['reader_id']];}
									$actions[$key] = $value;
								}
							}
						}

						$GLOBALS['js_object'] .= 'var submissions = new Object();' . "\n" . 'var actions = new Object();' . "\n";

						echo '<b>Submissions:</b> (' . count($submissions) . ')<br>';
						if ($submissions)
						{
							$headers = array(
							'ID',
							'date / time',
							'writer',
							'title(s)',
							'genre',
							'status'
							);

							echo '
							<table class="table_list" style="width: auto;">
							<tr>
							';

							foreach ($headers as $value) {echo '<th>' . $value . '</th>';}

							echo '</tr>';

							foreach ($submissions as $key => $value)
							{
								$value = cleanup($value, 'htmlspecialchars');
								$contact_tooltip = '';
								$title = ''; // so display() won't show extra data

								if ($value['contact'])
								{
									foreach ($value['contact'] as $contact_key => $contact_value)
									{
										$GLOBALS[$contact_key] = ''; // flush out GLOBAL vars
										if ($contact_value)
										{
											$contact_value = htmlspecialchars($contact_value);
											$value['contact'][$contact_key] = $contact_value;
											$GLOBALS[$contact_key] = $contact_value;
										}
									}
									$contact_tooltip = display('text');
								}
								else
								{
									$value['contact']['first_name'] = '???';
									$value['contact']['last_name'] = '???';
									$contact_tooltip = 'contact missing!';
								}

								extract($value);

								$submission_id = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&submission_id=' . $submission_id . '&from_reports=daily&date_report=' . $date_report . '">' . $submission_id . '</a>';
								$date_time = timezone_adjust($date_time);
								if ($writer) {$writer = '<span style="color: red;">' . $writer . '</span>';} else {$writer = $value['contact']['first_name'] . ' ' . $value['contact']['last_name'];}
								$writer = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=contacts&contact_id=' . $submitter_id . '&submission_id=' . $value['submission_id'] . '&from_reports=daily&date_report=' . $date_report . '" id="writer_' . $value['submission_id'] . '">' . $submitter_id . ' - ' . $writer . '</a>';
								if ($genre_id && isset($genres['all'][$genre_id])) {$genre = $genres['all'][$genre_id]['name'];} else {$genre = '&nbsp;';}
								$status = calc_submission_status($key);

								echo '
								<tr id="row_submission_' . $value['submission_id'] . '">
								<td>' . $submission_id . '</td>
								<td>' . $date_time . '</td>
								<td style="text-align: left;">' . $writer . '</td>
								<td style="text-align: left;">' . $title . '</td>
								<td style="text-align: left;">' . $genre . '</td>
								<td>' . $status . '</td>
								</tr>
								';

								$GLOBALS['js_object'] .= 'submissions[' . $value['submission_id'] . '] = {writer: ' . make_tooltip($contact_tooltip) . '};' . "\n";
							}

							echo '</table>';
						}

						echo '<br><b>Actions:</b> (' . count($actions) . ')<br>';
						if ($actions)
						{
							$headers = array(
							'ID',
							'date / time',
							'submission',
							'reader',
							'type',
							'receiver'
							);

							echo '
							<table class="table_list" style="width: auto;">
							<tr>
							';

							foreach ($headers as $value) {echo '<th>' . $value . '</th>';}

							echo '</tr>';

							foreach ($actions as $key => $value)
							{
								$value = cleanup($value, 'htmlspecialchars');
								extract($value);

								$date_time = timezone_adjust($date_time);
								$submission_id = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&submission_id=' . $submission_id . '&action_id=' . $action_id . '&from_reports=daily&date_report=' . $date_report . '">' . $submission_id . '</a>';
								$reader_tooltip = '';
								$receiver_tooltip = '';

								if (isset($reader_id) && $reader_id)
								{
									$display_array = array();

									if (isset($readers['all'][$reader_id])) {$display_array = $readers['all'][$reader_id];}
									if ($reader) {$display_array = $reader;}

									if ($display_array)
									{
										$reader_tooltip = $display_array['first_name'] . ' ' . $display_array['last_name'] . '<br>' . $display_array['email'] . '<br>' . $display_array['access'];
										$reader_display = '<span id="reader_' . $action_id . '">' . $display_array['first_name'] . ' ' . $display_array['last_name'] . '</span>';
									}
									else
									{
										$reader_display = '???';
									}
								}
								else
								{
									$reader_display = '&nbsp;';
								}

								if (isset($receiver_id) && $receiver_id)
								{
									if (isset($readers['all'][$receiver_id]))
									{
										$receiver_tooltip = $readers['all'][$receiver_id]['first_name'] . ' ' . $readers['all'][$receiver_id]['last_name'] . '<br>' . $readers['all'][$receiver_id]['email'] . '<br>' . $readers['all'][$receiver_id]['access'];
										$receiver_display = '<span id="receiver_' . $action_id . '">' . $readers['all'][$receiver_id]['first_name'] . ' ' . $readers['all'][$receiver_id]['last_name'] . '</span>';
									}
									else
									{
										$receiver_display = '???';
									}
								}
								else
								{
									$receiver_display = '&nbsp;';
								}

								$action_type = $action_types['all'][$action_type_id]['name'];
								if ($action_types['all'][$action_type_id]['description']) {$action_type .= ' - ' . $action_types['all'][$action_type_id]['description'];}

								echo '
								<tr id="row_action_' . $action_id . '">
								<td>' . $action_id . '</td>
								<td>' . $date_time . '</td>
								<td>' . $submission_id . '</td>
								<td style="text-align: left;">' . $reader_display . '</td>
								<td style="text-align: left;">' . $action_type . '</td>
								<td style="text-align: left;">' . $receiver_display . '</td>
								</tr>
								';

								$GLOBALS['js_object'] .= 'actions[' . $action_id . '] = {reader: ' . make_tooltip($reader_tooltip) . ', receiver: ' . make_tooltip($receiver_tooltip) . '};' . "\n";
							}

							echo '</table>';
						}
					}

					if ($report == 'monthly')
					{
						if (!$db_totals['submissions'] && !$db_totals['actions'])
						{
							echo 'no submissions/actions';
							exit_error();
						}

						include_once('inc_lists.php');

						function get_minmax($table)
						{
							global $range;

							$result = @mysqli_query($GLOBALS['db_connect'], "SELECT YEAR(MIN(date_time)) AS min_year, CONCAT(YEAR(MIN(date_time)), DATE_FORMAT(MIN(date_time), '%m')) AS min_month, YEAR(MAX(date_time)) AS max_year, CONCAT(YEAR(MAX(date_time)), DATE_FORMAT(MAX(date_time), '%m')) AS max_month FROM `$table`") or exit_error('query failure: SELECT MAX MIN date FROM ' . $table);
							if (mysqli_num_rows($result))
							{
								$row = mysqli_fetch_assoc($result);
								$range['min_year'][$table] = (int) $row['min_year'];
								$range['min_month'][$table] = (int) $row['min_month'];
								$range['max_year'][$table] = (int) $row['max_year'];
								$range['max_month'][$table] = (int) $row['max_month'];
							}

							return $range;
						}

						$range = array();
						if ($db_totals['submissions']) {$range = get_minmax('submissions');}
						if ($db_totals['actions']) {$range = get_minmax('actions');}
						asort($range['min_year']);
						asort($range['max_year']);
						$min_year = reset($range['min_year']);
						$max_year = end($range['max_year']);

						$action_keys['all'] = 0;
						foreach ($action_types['all'] as $key => $value) {$action_keys[$key] = 0;}

						foreach ($months_long as $key => $value)
						{
							$months_keys[$key]['submissions'] = $genres_keys;
							$months_keys[$key]['actions'] = $action_keys;
						}

						$years = range($max_year, $min_year);

						foreach ($years as $value)
						{
							$counts['years_total'][$value]['submissions'] = $genres_keys;
							$counts['years_total'][$value]['actions'] = $action_keys;
							$counts['years'][$value] = $months_keys;
						}

						$counts['totals']['submissions'] = $genres_keys;
						$counts['totals']['actions'] = $action_keys;

						function get_counts($table, $field)
						{
							global $counts;

							$result = @mysqli_query($GLOBALS['db_connect'], "SELECT YEAR(date_time) AS year, DATE_FORMAT(date_time,'%m') AS month, $field FROM `$table` ORDER BY year DESC, month ASC") or exit_error("query failure: SELECT $table for COUNT");
							if (mysqli_num_rows($result))
							{
								while ($row = mysqli_fetch_assoc($result))
								{
									$counts['totals'][$table]['all']++;
									$counts['years_total'][$row['year']][$table]['all']++;
									$counts['years'][$row['year']][$row['month']][$table]['all']++;

									if ($table == 'submissions')
									{
										if ($row['genre_id'])
										{
											$counts['totals'][$table][$row['genre_id']]++;
											$counts['years_total'][$row['year']][$table][$row['genre_id']]++;
											$counts['years'][$row['year']][$row['month']][$table][$row['genre_id']]++;
										}
										else
										{
											$counts['totals'][$table]['no genre']++;
											$counts['years_total'][$row['year']][$table]['no genre']++;
											$counts['years'][$row['year']][$row['month']][$table]['no genre']++;
										}
									}

									if ($table == 'actions')
									{
											$counts['totals'][$table][$row['action_type_id']]++;
											$counts['years_total'][$row['year']][$table][$row['action_type_id']]++;
											$counts['years'][$row['year']][$row['month']][$table][$row['action_type_id']]++;
									}
								}
							}

							return $counts;
						}

						if ($db_totals['submissions']) {$counts = get_counts('submissions', 'genre_id');}
						if ($db_totals['actions']) {$counts = get_counts('actions', 'action_type_id');}

						$last = end($years);

						$action_headers = '<th>all</th>';
						foreach ($action_types['all'] as $value)
						{
							$action_headers .= '<th>' . $value['name'];
							if ($value['description']) {$action_headers .= ' - ' . $value['description'];}
							$action_headers .= '</th>';
						}

						function display_counts($table, $headers)
						{
							extract($GLOBALS);

							if ($table == 'actions') {$colspan = count($action_types['all']) + 2;}

							echo '
							<table class="table_list" style="width: auto; white-space: nowrap;">
							<tr><th>&nbsp;</th>' . $headers . '</tr>
							<tr><th>Totals</th>
							';

							foreach ($counts['totals'][$table] as $key => $value)
							{
								if (strpos($key, ' ') !== false) {$key = str_replace(' ', '_', $key);}
								if ($table == 'submissions') {$value_display = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&from_reports=monthly&search_genre_id=' . $key . '&search_action_type_id=all">' . $value . '</a>';}
								if ($table == 'actions') {$value_display = $value_display = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&from_reports=monthly&search_genre_id=all&search_action_type_id=' . $key . '">' . $value . '</a>';}
								echo '<th>' . $value_display . '</th>';
							}

							echo '
							</tr>
							<tr class="transparent_row"><td colspan="' . $colspan . '" style="border: 0px;">&nbsp;</td></tr>
							';

							foreach ($counts['years'] as $key => $value)
							{
								echo '
								<tr>
								<th>' . $key . '</th>' . $headers . '</tr>
								<tr>
								<th>Year Total</th>
								';

								foreach ($counts['years_total'][$key][$table] as $sub_value)
								{
									echo '<th>' . $sub_value . '</th>';
								}

								echo '</tr>';

								foreach ($value as $sub_key => $sub_value)
								{
									$color_text = $config['color_text'];
									$month = $key . sprintf('%02s', $sub_key);
									if ($month > $range['max_month'][$table] || $month < $range['min_month'][$table]) {$color_text = $config['color_foreground'];}

									echo '
									<tr>
									<td style="color: ' . $color_text . ';">' . $months_long[$sub_key] . '</td>
									';

									foreach ($sub_value[$table] as $sub_sub_value)
									{
										echo '<td style="color: ' . $color_text . ';">' . $sub_sub_value . '</td>' . "\n";
									}

									echo '</tr>';
								}

								if ($key != $last) {echo '<tr class="transparent_row"><td colspan="' . $colspan . '" style="border: 0px;">&nbsp;</td></tr>';}
							}

							echo '</table>';
						}

						echo '
						<table style="border-collapse: collapse; white-space: nowrap;">
						<tr class="header">
						<td>submissions:</td>
						<td>actions:</td>
						</tr>
						<tr>
						<td style="padding-right: 20px;">'; if ($db_totals['submissions']) {display_counts('submissions', $genre_headers);} echo '</td>
						<td>'; if ($db_totals['actions']) {display_counts('actions', $action_headers);} echo '</td>
						</tr>
						</table>
						';
					}

					if ($report == 'status')
					{
						if (!$db_totals['submissions'])
						{
							echo 'no submissions';
							exit_error();
						}

						$counts['no action'] = $genres_keys;
						$counts['all forwards'] = $genres_keys;
						$counts['all rejects'] = $genres_keys;
						foreach ($action_types['all'] as $key => $value) {$counts[$key] = $genres_keys;}

						// subquery (replaced with last_action)
						// $sql = 'SELECT submissions.genre_id FROM submissions WHERE submission_id NOT IN(SELECT DISTINCT submission_id FROM actions)';
						$sql = 'SELECT genre_id FROM submissions WHERE last_action_id IS NULL';
						$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT no action submissions FOR report');
						while ($row = mysqli_fetch_assoc($result))
						{
							$counts['no action']['all']++;
							if ($row['genre_id']) {$counts['no action'][$row['genre_id']]++;} else {$counts['no action']['no genre']++;}
						}

						// subquery (replaced with last_action)
						// $sql = 'SELECT sub.genre_id, act.action_type_id FROM submissions sub INNER JOIN (SELECT submissions.submission_id, MAX(actions.date_time) AS LastActDate FROM submissions INNER JOIN actions ON submissions.submission_id = actions.submission_id GROUP BY submissions.submission_id) lad ON sub.submission_id = lad.submission_id INNER JOIN actions act ON sub.submission_id = act.submission_id AND lad.LastActDate = act.date_time';
						$sql = 'SELECT genre_id, last_action_type_id AS action_type_id FROM submissions WHERE last_action_type_id IS NOT NULL';
						$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT action submissions for report');
						while ($row = mysqli_fetch_assoc($result))
						{
							$counts[$row['action_type_id']]['all']++;

							if (in_array($row['action_type_id'], $action_types['forwards']))
							{
								$counts['all forwards']['all']++;
								if ($row['genre_id']) {$counts['all forwards'][$row['genre_id']]++;} else {$counts['all forwards']['no genre']++;}
							}

							if (in_array($row['action_type_id'], $action_types['rejects']))
							{
								$counts['all rejects']['all']++;
								if ($row['genre_id']) {$counts['all rejects'][$row['genre_id']]++;} else {$counts['all rejects']['no genre']++;}
							}

							if ($row['genre_id']) {$counts[$row['action_type_id']][$row['genre_id']]++;} else {$counts[$row['action_type_id']]['no genre']++;}
						}

						echo '
						<table class="table_list" style="width: auto; white-space: nowrap;">
						<tr><th>&nbsp;</th>' . $genre_headers . '</tr>
						';

						foreach ($counts as $key => $value)
						{
							if (is_numeric($key))
							{
								$last_action = $action_types['all'][$key]['name'];
								if ($action_types['all'][$key]['description']) {$last_action .= ' - ' . $action_types['all'][$key]['description'];}
								$color = '#800000';
							}
							else
							{
								$last_action = $key;
								$color = $config['color_text'];
							}

							if (strpos($key, ' ') !== false) {$key = str_replace(' ', '_', $key);}

							echo '
							<tr>
							<td style="text-align: left; color: ' . $color . ';">' . $last_action . '</td>
							';

							foreach ($value as $sub_key => $sub_value)
							{
								if (strpos($sub_key, ' ') !== false) {$sub_key = str_replace(' ', '_', $sub_key);}
								echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&from_reports=status&search_genre_id=' . $sub_key . '&search_action_type_id=' . $key . '">' . $sub_value . '</a></td>';
							}

							echo '</tr>';
						}

						echo '</table>';
					}

					if ($report == 'actions')
					{
						if (!in_array('action_types', $show_tables)) {exit_error('action_types table unavailable');}

						if (isset($_SESSION['readers'])) {$readers = $_SESSION['readers'];} else {get_readers();}

						if (!$readers)
						{
							echo 'no staff contacts';
							exit_error();
						}

						foreach ($action_types['all'] as $key => $value) {$action_types_counts[$key] = 0;}

						// order readers by access
						$access_array = field2array('enum', $describe['contacts']['access']);
						foreach ($access_array as $value)
						{
							foreach ($readers['all'] as $sub_key => $sub_value)
							{
								if ($sub_value['access'] == $value)
								{
									$sub_value['action_types_counts'] = $action_types_counts;
									$readers_access[$sub_key] = $sub_value;
								}
							}
						}

						$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT COUNT(*) AS count, reader_id, action_type_id FROM actions GROUP BY reader_id, action_type_id') or exit_error('query failure: SELECT actions count for report');
						while ($row = mysqli_fetch_assoc($result))
						{
							if (isset($readers_access[$row['reader_id']]['action_types_counts'][$row['action_type_id']])) {$readers_access[$row['reader_id']]['action_types_counts'][$row['action_type_id']] = $row['count'];}
						}

						$array_keys = array_keys($readers_access);
						$last = end($array_keys);

						echo '<table class="table_list" style="width: auto; white-space: nowrap;">';

						foreach ($readers_access as $key => $value)
						{
							echo '
							<tr>
							<th colspan="2"><span class="header">' . $readers['all'][$key]['last_name'] . ', ' . $readers['all'][$key]['first_name'] . '</span> <span class="small">[' . $readers['all'][$key]['access'] . ']</span></th>
							</tr>
							<tr>
							<th style="text-align: left;">total</th>
							<th>' . array_sum($value['action_types_counts']) . '</th>
							</tr>
							';

							foreach ($value['action_types_counts'] as $sub_key => $sub_value)
							{
								$action_name = $action_types['all'][$sub_key]['name'];
								if ($action_types['all'][$sub_key]['description']) {$action_name .= ' - ' . $action_types['all'][$sub_key]['description'];}

								echo '
								<tr>
								<td style="text-align: left;">' . $action_name . '</td>
								<td>' . $sub_value . '</td>
								</tr>
								';
							}

							if ($key != $last) {echo '<tr class="transparent_row"><td colspan="2" style="border: 0px;">&nbsp;</td></tr>';}
						}

						echo '</table>';
					}

					if ($report == 'forwards')
					{
						if (!in_array('action_types', $show_tables)) {exit_error('action_types table unavailable');}

						if (isset($_SESSION['readers'])) {$readers = $_SESSION['readers'];} else {get_readers();}

						if (!$readers)
						{
							echo 'no staff contacts';
							exit_error();
						}

						foreach ($action_types['forwards'] as $value)
						{
							$forwards_keys[$value] = $genres_keys;
						}

						// order readers by access
						$access_array = field2array('enum', $describe['contacts']['access']);
						foreach ($access_array as $value)
						{
							foreach ($readers['all'] as $sub_key => $sub_value)
							{
								if ($sub_value['access'] == $value) {$readers_access[$sub_key] = $sub_value;}
							}
						}

						foreach ($readers_access as $key => $value)
						{
							$counts[$key] = $forwards_keys;
							$counts[$key]['totals'] = $genres_keys;
						}

						// subquery (replaced with last_action)
						// $sql = 'SELECT sub.genre_id, act.receiver_id, act.action_type_id, contacts.access FROM contacts, submissions sub INNER JOIN (SELECT submissions.submission_id, MAX(actions.date_time) AS LastActDate FROM submissions INNER JOIN actions ON submissions.submission_id = actions.submission_id GROUP BY submissions.submission_id) lad ON sub.submission_id = lad.submission_id INNER JOIN actions act ON sub.submission_id = act.submission_id AND lad.LastActDate = act.date_time WHERE act.receiver_id = contacts.contact_id AND act.action_type_id IN(' . implode(',', $action_types['forwards']) . ')';
						$sql = 'SELECT submissions.genre_id, submissions.last_receiver_id AS receiver_id, submissions.last_action_type_id AS action_type_id, contacts.access FROM contacts, submissions WHERE submissions.last_receiver_id = contacts.contact_id AND submissions.last_action_type_id IN(' . implode(',', $action_types['forwards']) . ')';
						$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT submissions for report');
						while ($row = mysqli_fetch_assoc($result))
						{
							$counts[$row['receiver_id']]['totals']['all']++;
							$counts[$row['receiver_id']][$row['action_type_id']]['all']++;

							if ($row['genre_id'])
							{
								$counts[$row['receiver_id']]['totals'][$row['genre_id']]++;
								$counts[$row['receiver_id']][$row['action_type_id']][$row['genre_id']]++;
							}
							else
							{
								$counts[$row['receiver_id']]['totals']['no genre']++;
								$counts[$row['receiver_id']][$row['action_type_id']]['no genre']++;
							}
						}

						$array_keys = array_keys($counts);
						$last = end($array_keys);

						echo '<table class="table_list" style="width: auto; white-space: nowrap;">';

						foreach ($counts as $key => $value)
						{
							echo '
							<tr id="reader_' . $key . '"><th><span class="header">' . $readers['all'][$key]['last_name'] . ', ' . $readers['all'][$key]['first_name'] . '</span> <span class="small">[' . $readers['all'][$key]['access'] . ']</span></th>' . $genre_headers . '</tr>
							<tr>
							<th>all forwards</th>
							';

							foreach ($value['totals'] as $sub_key => $sub_value)
							{
								if (strpos($sub_key, ' ') !== false) {$sub_key = str_replace(' ', '_', $sub_key);}
								echo '<th><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&from_reports=forwards&search_genre_id=' . $sub_key . '&search_action_type_id=all_forwards&search_receiver_id=' . $key . '">' . $sub_value . '</a></th>';
							}

							echo '
							</tr>
							';

							foreach ($value as $sub_key => $sub_value)
							{
								if (is_numeric($sub_key))
								{
									echo '
									<tr>
									<td style="text-align: left;">' . $action_types['all'][$sub_key]['name']; if ($action_types['all'][$sub_key]['description']) {echo ' - ' . $action_types['all'][$sub_key]['description'];} echo '</td>
									';

									foreach ($sub_value as $sub_sub_key => $sub_sub_value)
									{
										if (strpos($sub_sub_key, ' ') !== false) {$sub_sub_key = str_replace(' ', '_', $sub_sub_key);}
										echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&from_reports=forwards&search_genre_id=' . $sub_sub_key . '&search_action_type_id=' . $sub_key . '&search_receiver_id=' . $key . '">' . $sub_sub_value . '</a></td>';
									}

									echo '</tr>';
								}
							}

							if ($key != $last) {echo '<tr class="transparent_row"><td colspan="' . $colspan . '" style="border: 0px;">&nbsp;</td></tr>';}
						}

						echo '</table>';
					}

					if ($report == 'contacts')
					{
						if (!$db_totals['contacts'])
						{
							echo 'no contacts';
							exit_error();
						}

						$access_array = field2array('enum', $describe['contacts']['access']);
						unset($access_array[array_search('blocked', $access_array)]);

						$counts['all'] = 0;
						$counts['non-staff'] = 0;
						$counts['blocked'] = 0;
						$counts['staff'] = 0;
						foreach ($access_array as $value) {$counts[$value] = 0;}

						$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT access FROM contacts') or exit_error('query failure: SELECT access FROM contacts (reports)');
						while ($row = mysqli_fetch_assoc($result))
						{
							$counts['all']++;
							if ($row['access'] && $row['access'] != 'blocked')
							{
								$counts['staff']++;
								$counts[$row['access']]++;
							}
							else
							{
								$counts['non-staff']++;
							}

							if ($row['access'] == 'blocked')
							{
								$counts['blocked']++;
							}
						}

						echo '
						<table class="table_list" style="width: auto; white-space: nowrap;">
						<tr>
						<th>access</th>
						<th>count</th>
						</tr>
						';

						foreach ($counts as $key => $value)
						{
							$color_text = $config['color_text'];
							if (in_array($key, $access_array)) {$color_text = '#800000';}
							$key_url = str_replace(' ', '_', $key);

							echo '
							<tr id="row_' . $key_url . '">
							<td style="color: ' . $color_text . ';">' . $key . '</td>
							<td><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=contacts&from_reports=contacts&search_access=' . $key_url . '">' . $value . '</a></td>
							</tr>
							';

						}
						echo '</table>';
					}

					echo '
					&nbsp;

				</td>
			</tr>
		</table>
		';
	}

	if ($module == 'configuration')
	{
		if ($_SESSION['contact']['access'] != 'admin')
		{
			echo '<p><b>Admins only!</b><br>You are not authorized to access this area.</p>';
			exit_error();
		}

		$submodules = array(
		'general' => 'general configuration',
		'action_types' => 'action types',
		'file_types' => 'file types',
		'fields' => 'fields',
		'groups' => 'groups',
		'genres' => 'genres',
		'payment_vars' => 'payment variables'
		);

		if (!$submodule) {$submodule = 'general';}
		$extra = '';
		$colspan = '';

		echo '
		<table style="border-collapse: collapse; width: 100%;">
			<tr>
				<td class="foreground" style="width: 200px; padding: 5px;">

					<table class="foreground" style="width: 190px; font-weight: bold; border-spacing: 5px; border: 1px solid ' . $config['color_text'] . ';">
					<tr>
					<td style="white-space: nowrap;">
					choose configuration:
					<ul class="nav_list">
					';

					foreach ($submodules as $key => $value)
					{
						if ($key == $submodule) {$value = '<span style="color: ' . $config['color_link_hover'] . ';">' . $value . '</span>';}
						echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=' . $key . '">' . $value . '</a></li>' . "\n";
					}

					echo '
					</ul>
					</td>
					</tr>
					</table>
					';

					if ($submodule == 'action_types')
					{
						echo '<br><b><u>Placeholder Legend:</u></b><br><br><span class="small">';
						foreach ($placeholders as $key => $value)
						{
							echo '<b>[' . $key . ']</b> : ' . $value . '<br><br>';
						}
						echo '</span>';
					}

					if ($submodule == 'payment_vars')
					{
						echo '<br><b><u>Local Variables:</u></b><br><br><span class="small">';
						foreach ($local_variables as $key => $value)
						{
							echo '<b><u>' . $key . '</u></b><br>';
							foreach ($value as $sub_value)
							{
								echo '$' . $sub_value . '<br>';
							}
							echo '<br>';
						}
						echo '</span>';
					}

				echo '
				</td>
				<td style="padding-left: 20px;">
				';

					// if (!isset($post_config)) {check_config($config);} // so error notice is displayed
					if ($notice) {echo '<div class="notice">' . $notice . '</div><br>';}

					if ($submodule == 'general')
					{
						$colspan = 2;
						$_SESSION['config'] = $config;

						if ($submit == 'update' && !$form_check) {$config_array = $post_config;} else {$config_array = $config;}

						foreach ($config_defaults as $key => $value)
						{
							if (array_key_exists($key, $config_array)) {$config_array_sorted[$key] = $config_array[$key];}
						}

						include_once('inc_lists.php');

						echo '
						<span class="header">General Configuration</span><br><br>
						<table class="padding_lr_5">
						<tr style="font-weight: bold; text-decoration: underline;">
						<td style="text-align: right;">Name:</td>
						<td>Value:</td>
						<td>Description:</td>
						</tr>
						';

						foreach ($config_array_sorted as $key => $value)
						{
							$value = htmlspecialchars((string) $value);

							$extra1 = '';
							$extra2 = '';
							$class = '';
							$description = '';
							if (in_array($key, $config_invalid)) {$class = 'error';}

							$input = '<input type="text" id="config_' . $key . '" name="config[' . $key . ']" value="' . $value . '" class="' . $class . '">';
							if (strpos($defaults['config'][$key]['type'], 'select|') !== false)
							{
								$select = array();
								$explode = explode('|', $defaults['config'][$key]['type']);
								if (isset($GLOBALS[$explode[1]])) {$select = $GLOBALS[$explode[1]];} else {$explode2 = explode(',', $explode[1]);}
								if (isset($explode2)) {foreach ($explode2 as $sub_value) {$select[$sub_value] = $sub_value;} unset($explode2);}
								$input = '<select id="config_' . $key . '" name="config[' . $key . ']" class="' . $class . '">';
								foreach ($select as $sub_key => $sub_value)
								{
									if ($sub_value == 'NULL') {$sub_key_display = ''; $sub_value_display = '&nbsp;';} else {$sub_key_display = htmlspecialchars($sub_key); $sub_value_display = htmlspecialchars($sub_value);}
									$input .= '<option value="' . $sub_key_display . '"';
									if ($sub_key == $value) {$input .= ' selected';}
									$input .= '>' . $sub_value_display . '</option>' . "\n";
								}
								$input .= '</select>';
							}
							if ($defaults['config'][$key]['type'] == 'checkbox') {$input = '<input type="checkbox" id="config_' . $key . '" name="config[' . $key . ']" value="Y"'; if ($value) {$input .= ' checked';} $input .= ' >';}
							if ($defaults['config'][$key]['type'] == 'textarea') {$input = '<textarea id="config_' . $key . '" name="config[' . $key . ']" class="' . $class . '">' . $value . '</textarea>';}
							if ($key == 'upload_path' && isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'])
							{
								$suggested_path = dirname($_SERVER['DOCUMENT_ROOT']);
								$suggested_path = str_replace('\\', '/', $suggested_path);
								if (substr($suggested_path, -1) != '/') {$suggested_path .= '/';}
								$extra2 = '<br><span class="small" style="font-weight: bold;">suggested path: ' . $suggested_path . 'submissions/</span>';
							}

							if (isset($defaults['config'][$key]['description'])) {$description = $defaults['config'][$key]['description'];}
							$key_display = '<label for="config_' . $key . '" id="label_config_' . $key . '" class="' . $class . '">' . $key . ':</label>';

							echo '
							<tr>
							<td class="row_left">' . $extra1 . $key_display . '</td>
							<td>' . $input . '</td>
							<td>' . $description . $extra2 . '</td>
							</tr>
							';
						}
					}

					if ($submodule == 'action_types')
					{
						$colspan = 0;
						if (!in_array('action_types', $show_tables)) {exit_error('action_types table unavailable');}
						$_SESSION['action_types'] = $action_types['all'];
						if ($submit == 'update' && !$form_check) {$action_types_array = $post_action_types;} else {$action_types_array = $action_types['all'];}

						echo '
						<span class="header">Action Types</span> (' . count($action_types_array) . ')<br><br>
						<table class="padding_lr_5">
						';

						foreach ($action_types_array as $key => $value)
						{
							$value = array_map('strval', $value);
							$value = array_map('htmlspecialchars', $value);

							foreach ($value as $field_name => $field_value)
							{
								if ($field_name == 'description' || $field_name == 'status' || $field_name == 'subject')
								{
									if ($field_name == 'description') {$max_length = 10; $width = 80;}
									if ($field_name == 'status') {$max_length = 50; $width = 80;}
									if ($field_name == 'subject') {$max_length = 255; $width = 600;}
									$field_value = '<input type="text" id="action_types_' . $key . '_' . $field_name . '" name="action_types[' . $key . '][' . $field_name . ']" value="' . $field_value . '" maxlength="' . $max_length . '" style="width:' . $width . 'px;">';
								}
								if ($field_name == 'body') {$field_value = '<textarea id="action_types_' . $key . '_' . $field_name . '" name="action_types[' . $key . '][' . $field_name . ']" style="width: 600px; height: 200px;">' . $field_value . '</textarea>';}
								if ($field_name == 'active' || $field_name == 'from_reader') {$field_value = '<input type="checkbox" id="action_types_' . $key . '_' . $field_name . '" name="action_types[' . $key . '][' . $field_name . ']" value="Y"'; if ($value[$field_name]) {$field_value .= ' checked';} $field_value .= ' >';}
								if ($field_name == 'access_groups')
								{
									// get the array of active access groups
									$access_groups_array = field2array('set', $describe['action_types']['access_groups']);
									$set = '';
									foreach ($access_groups_array as $num)
									{
										$num = (string) $num;
										$set .= '<label for="action_types_' . $key . '_' . $field_name . '_' . $num . '" id="label_action_types_' . $key . '_' . $field_name . '_' . $num . '">' . $num . '.</label><input type="checkbox" id="action_types_' . $key . '_' . $field_name . '_' . $num . '" name="action_types[' . $key . '][' . $field_name . '][]" value="' . $num . '"';
										if (strpos($field_value, $num) !== false) {$set .= ' checked';}
										$set .= ' style="margin-right: 20px;">';
									}
									$field_value = $set;
								}

								if ($field_name == 'action_type_id' || $field_name == 'name' || $field_name == 'access_groups') {$field_name_display = $field_name . ':';} else {$field_name_display = '<label for="action_types_' . $key . '_' . $field_name . '" id="label_action_types_' . $key . '_' . $field_name . '">' . $field_name . ':</label>';}

								echo '
								<tr class="foreground">
								<td class="row_left">' . $field_name_display . '</td>
								<td><b>' . $field_value . '</b></td>
								</tr>
								';
							}

							echo '<tr><td colspan="2" style="margin-top: 10px;">&nbsp;</td></tr>';
						}
					}

					if ($submodule == 'file_types')
					{
						if (!isset($describe['file_types']))
						{
							echo 'file_types table unavailable';
							exit_error();
						}

						$colspan = 0;

						echo '
						<span class="header">File Types</span> (' . count($file_types) . ')<br><br>
						<table class="padding_lr_5">
						<tr>
						<td>&nbsp;</td>
						<td style="font-weight: bold; text-decoration: underline;">Extension</td>
						</tr>
						';

						foreach ($file_types as $value)
						{
							$value = htmlspecialchars($value);

							echo '
							<tr>
							<td style="text-align: center;"><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=' . $submodule . '&ext=' . urlencode($value) . '&delete=1" id="file_type_' . $value . '" class="file_type"><img src="button_delete.png" alt="delete" width="11" height="13"></a></td>
							<td><input type="text" name="file_types[' . $value . ']" value="' . $value . '" maxlength="10" style="width: 100px;"></td>
							</tr>
							';
						}

						echo '
						<tr>
						<td style="text-align: center;">new</td>
						<td><input type="text" name="file_types[new]" value="" maxlength="10" style="width: 100px;"></td>
						</tr>
						';
					}

					if ($submodule == 'fields')
					{
						if (!isset($describe['fields']))
						{
							echo 'fields table unavailable';
							exit_error();
						}

						$colspan = 7;

						echo '
						<span class="header">Fields</span> (' . count($fields) . ')<br><br>
						<table class="table_list">
						<tr>
						<th>Field</th>
						<th>Name</th>
						<th>Type</th>
						<th>Section</th>
						<th>Default Value</th>
						<th>Max Length</th>
						<th>Enabled</th>
						<th>Required</th>
						</tr>
						';

						foreach ($fields as $key => $value)
						{
							unset($value['error']);
							unset($value['list']);

							$row = '<tr>'. "\n";

							foreach ($value as $sub_key => $sub_value)
							{
								$sub_value = htmlspecialchars((string) $sub_value);

								$row .= '<td>';

								if (isset($fields_editable[$sub_key]))
								{
									$width = 150;
									if ($sub_key == 'value' || $sub_key == 'maxlength') {$width = 100;}

									$checked = '';
									if ($sub_value) {$checked = ' checked';}

									$disabled = '';
									if (in_array($key, $fields_checkbox_disabled) && ($sub_key == 'enabled' || $sub_key == 'required')) {$disabled = ' disabled';}

									$class = '';
									if (isset($errors[$key]) && in_array($sub_key, $errors[$key])) {$class = 'error';}

									if (isset($post_fields[$key][$sub_key])) {$sub_value = htmlspecialchars((string) $post_fields[$key][$sub_key]);}

									$row .= '<input type="' . $fields_editable[$sub_key] . '" id="' . $key . '_' . $sub_key . '" name="fields[' . $key . '][' . $sub_key . ']" ';
									if ($fields_editable[$sub_key] == 'text') {$row .= 'value="' . $sub_value . '" style="width: ' . $width . 'px;"';}
									if ($fields_editable[$sub_key] == 'checkbox') {$row .= 'value="Y"' . $checked;}
									$row .= $disabled . ' class="' . $class . '">';
								}
								else
								{
									$row .= $sub_value;
								}

								$row .= '</td>'. "\n";
							}

							$row .= '</tr>' . "\n";

							echo $row;
						}
					}

					if ($submodule == 'groups')
					{
						if (!$groups)
						{
							echo 'groups table unavailable';
							exit_error();
						}

						$colspan = 0;
						$groups_keys = array_keys($groups);

						echo '
						<span class="header">Groups</span> (' . count($defaults['groups']) . ')<br><br>
						<table class="padding_lr_5">
						';

						foreach ($groups as $key => $value)
						{
							$value = array_map('strval', $value);
							$value = array_map('htmlspecialchars', $value);
							extract($value);
							$allowed_forwards_array = explode(',', $allowed_forwards);

							echo '
							<tr class="foreground">
							<td class="row_left">group name:</td>
							<td><b>' . $key . '</b></td>
							</tr>
							<tr class="foreground">
							<td class="row_left">allowed forwards:</td>
							<td><b>';

							foreach ($groups_keys as $sub_value)
							{
								$checked = '';
								if (in_array($sub_value, $allowed_forwards_array)) {$checked = ' checked';}
								echo '<label for="groups_' . $key . '_allowed_forwards_' . $sub_value . '" id="label_groups_' . $key . '_allowed_forwards_' . $sub_value . '">' . $sub_value . '</label><input type="checkbox" id="groups_' . $key . '_allowed_forwards_' . $sub_value . '" name="groups[' . $key . '][allowed_forwards][]" value="' . $sub_value . '"' . $checked . ' style="margin-right: 20px;">';
							}

							$checked = '';
							if ($blind) {$checked = ' checked';}

							echo '</b></td>
							</tr>
							<tr class="foreground">
							<td class="row_left"><label for="groups_' . $key . '_blind" id="label_groups_' . $key . '_blind">blind:</label></td>
							<td><input type="checkbox" id="groups_' . $key . '_blind" name="groups[' . $key . '][blind]" value="Y"' . $checked . '></td>
							</tr>
							<tr><td colspan="2">&nbsp;</td></tr>
							';
						}
					}

					if ($submodule == 'genres')
					{
						if (!$genres)
						{
							echo 'genres table unavailable';
							exit_error();
						}

						function genre_class($genre_id)
						{
							global $errors;
							$class = array();
							$genre_fields = array('name', 'submission_limit', 'price');

							foreach ($genre_fields as $value)
							{
								$class[$value] = '';
								if (isset($errors[$genre_id]) && in_array($value, $errors[$genre_id])) {$class[$value] = 'error';}
							}

							return $class;
						}

						$colspan = 6;

						echo '
						<span class="header">Genres</span> (' . count($genres['all']) . ')<br><br>
						<table class="padding_lr_5">
						<tr style="font-weight: bold; text-decoration: underline;">
						<td style="text-align: center; width: 50px;">ID</td>
						<td>Name</td>
						<td>Submission Limit</td>
						<td>Redirect URL</td>
						<td>Price</td>
						<td>Active</td>
						<td>Blind</td>
						</tr>
						';

						foreach ($genres['all'] as $key => $value)
						{
							if (isset($post_genres[$key])) {$value = $post_genres[$key];}
							$value = array_map('strval', $value);
							$value = array_map('htmlspecialchars', $value);
							extract($value);
							$class = genre_class($key);

							echo '
							<tr style="text-align: center;">
							<td style="text-align: right; white-space: nowrap;">' . $key . ' <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=' . $submodule . '&genre_id=' . $key . '&delete=1" id="genre_' . $key . '" class="genre"><img src="button_delete.png" alt="delete" width="11" height="13"></a></td>
							<td><input type="text" id="genres_' . $key . '_name" name="genres[' . $key . '][name]" value="' . $name . '" maxlength="50" class="' . $class['name'] . '"></td>
							<td><input type="text" id="genres_' . $key . '_submission_limit" name="genres[' . $key . '][submission_limit]" value="' . $submission_limit . '" maxlength="3" style="width: 50px;" class="' . $class['submission_limit'] . '"></td>
							<td><input type="text" id="genres_' . $key . '_redirect_url" name="genres[' . $key . '][redirect_url]" value="' . $redirect_url . '"></td>
							<td><input type="text" id="genres_' . $key . '_price" name="genres[' . $key . '][price]" value="' . $price . '" maxlength="7" style="width: 50px;" class="' . $class['price'] . '"></td>
							<td><input type="checkbox" id="genres_' . $key . '_active" name="genres[' . $key . '][active]" value="Y"'; if ($active) {echo ' checked';} echo '></td>
							<td><input type="checkbox" id="genres_' . $key . '_blind" name="genres[' . $key . '][blind]" value="Y"'; if ($blind) {echo ' checked';} echo '></td>
							</tr>
							';
						}

						if (isset($post_genres['new']))
						{
							$post_genres['new'] = array_map('htmlspecialchars', $post_genres['new']);
						}
						else
						{
							$genre_fields = array_keys($value); // $value from the previous foreach loop
							foreach ($genre_fields as $value) {$post_genres['new'][$value] = '';}
						}

						$class = genre_class('new');

						echo '
						<tr style="text-align: center;">
						<td style="text-align: right;">new</td>
						<td><input type="text" id="genres_new_name" name="genres[new][name]" value="' . $post_genres['new']['name'] . '" maxlength="50" class="' . $class['name'] . '"></td>
						<td><input type="text" id="genres_new_submission_limit" name="genres[new][submission_limit]" value="' . $post_genres['new']['submission_limit'] . '" maxlength="3" style="width: 50px;" class="' . $class['submission_limit'] . '"></td>
						<td><input type="text" id="genres_new_redirect_url" name="genres[new][redirect_url]" value="' . $post_genres['new']['redirect_url'] . '"></td>
						<td><input type="text" id="genres_new_price" name="genres[new][price]" value="' . $post_genres['new']['price'] . '" maxlength="7" style="width: 50px;" class="' . $class['price'] . '"></td>
						<td><input type="checkbox" id="genres_new_active" name="genres[new][active]" value="Y" checked></td>
						<td><input type="checkbox" id="genres_new_blind" name="genres[new][blind]" value="Y"></td>
						</tr>
						';
					}

					if ($submodule == 'payment_vars')
					{
						include('payment_vars_presets.php');

						function payment_vars_preset_display($arg)
						{
							$arg = str_replace('_', ' ', $arg);
							$arg = str_replace('AuthorizeNet', 'Authorize.net', $arg);
							return $arg;
						}

						if (isset($_GET['payment_vars_preset']) && $_GET['payment_vars_preset'])
						{
							if (isset($payment_vars_presets[$_GET['payment_vars_preset']]))
							{
								@mysqli_query($GLOBALS['db_connect'], 'TRUNCATE payment_vars') or exit_error('query failure: TRUNCATE payment_vars');

								$sql = $payment_vars_presets[$_GET['payment_vars_preset']]['sql'];
								$sql = str_replace("\n", '', $sql);
								$sql = str_replace("\r", '', $sql);
								$result = mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT payment_vars FROM payment_vars_preset');

								foreach ($payment_vars_presets[$_GET['payment_vars_preset']]['config'] as $key => $value)
								{
									if (strpos($key, 'redirect_url') === false)
									{
										if ($value == '') {$value_sql = 'NULL';} else {$value_sql = "'" . $value . "'";}
										$sql = "UPDATE config SET value = $value_sql WHERE name = '$key'";
										$result = mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT config FROM payment_vars_preset');
									}
								}

								@mysqli_query($GLOBALS['db_connect'], 'ALTER TABLE `payment_vars` COMMENT = "' . $_GET['payment_vars_preset'] . '"') or exit('query failure: ALTER TABLE payment_vars COMMENT');
							}
						}

						get_payment_vars();

						$result = @mysqli_query($GLOBALS['db_connect'], 'SHOW TABLE STATUS LIKE "payment_vars"') or exit_error('query failure: SHOW TABLE STATUS payment_vars');
						$row = mysqli_fetch_assoc($result);
						$payment_vars_preset_comment = $row['Comment'];

						$colspan = 3;
						$colspan_plus = $colspan + 1;

						$extra = '';
						if ($payment_vars_preset_comment)
						{
							$payment_vars_preset_comment_display = payment_vars_preset_display($payment_vars_preset_comment);
							$extra = '
							<table class="padding_lr_5" style="float: left; margin-left: 20px;">
							<tr><td class="row_left">Current Payment Variable Preset:</td><td><b>' . $payment_vars_preset_comment_display . '</b></td></tr>
							<tr><td class="row_left">redirect_url TEST:</td><td><b>' . $payment_vars_presets[$payment_vars_preset_comment]['config']['redirect_url TEST'] . '</b></td></tr>
							<tr><td class="row_left">redirect_url LIVE:</td><td><b>' . $payment_vars_presets[$payment_vars_preset_comment]['config']['redirect_url LIVE'] . '</b></td></tr>
							</table>
							';
						}

						echo '
						<div style="float: left;"><span class="header">Payment Variables</span> (' . $payment_vars_count . ')</div>' . $extra . '<br><br>
						<div style="clear: both;"></div>
						<table class="padding_lr_5">
						';

						function display_payment_vars($arg)
						{
							extract($GLOBALS);

							if ($arg == 'out') {$arg_display = 'Outgoing';}
							if ($arg == 'in') {$arg_display = 'Incoming';}

							echo '<tr><td colspan="' . $colspan_plus . '"><span class="header">' . $arg_display . '</span> (' . count($payment_vars[$arg]) . ')</td></tr>';

							if ($payment_vars[$arg])
							{
								echo '
								<tr style="font-weight: bold; text-decoration: underline;">
									<td style="text-align: center; width: 50px;">ID</td>
									<td>Name</td>
									<td>Value</td>
									<td></td>
								</tr>
								';

								foreach ($payment_vars[$arg] as $value)
								{
									$value = array_map('htmlspecialchars', $value);
									extract($value);

									echo '
									<tr>
									<td style="text-align: right; white-space: nowrap;">' . $payment_var_id . ' <a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=' . $submodule . '&payment_var_id=' . $payment_var_id . '&delete=1" id="payment_var_' . $payment_var_id . '" class="payment_var"><img src="button_delete.png" alt="delete" width="11" height="13"></a></td>
									<td><input type="text" id="payment_vars_' . $payment_var_id . '_name" name="payment_vars[' . $payment_var_id . '][name]" value="' . $name . '" maxlength="255"></td>
									<td><input type="text" id="payment_vars_' . $payment_var_id . '_value" name="payment_vars[' . $payment_var_id . '][value]" value="' . $value . '" maxlength="255"><input type="hidden" id="payment_vars_' . $payment_var_id . '_direction" name="payment_vars[' . $payment_var_id . '][direction]" value="' . $arg . '"></td>
									<td style="background-color: ' . $config['color_background'] . ';">&nbsp;</td>
									</tr>
									';
								}
							}
						}

						display_payment_vars('out');
						echo '<tr><td colspan="' . $colspan_plus . '">&nbsp;</td></tr>';
						display_payment_vars('in');

						echo '
						<tr><td colspan="' . $colspan_plus . '">&nbsp;<hr></td></tr>
						<tr style="font-weight: bold;">
							<td>&nbsp;</td>
							<td><u>Name</u></td>
							<td><u>Value</u></td>
							<td><u>Direction</u></td>
						</tr>
						<tr>
							<td style="text-align: center;">new</td>
							<td><input type="text" id="payment_vars_new_name" name="payment_vars[new][name]" value="" maxlength="255"></td>
							<td><input type="text" id="payment_vars_new_value" name="payment_vars[new][value]" value="" maxlength="255"></td>
							<td><select id="payment_vars_new_direction" name="payment_vars[new][direction]" style="width: 100px;"><option value="out">outgoing</option><option value="in">incoming</option></select></td>
						</tr>
						';
					}

					if (isset($submodules[$submodule]))
					{
						if ($colspan) {$colspan_tag = ' colspan="' . $colspan . '"';} else {$colspan_tag = '';}

						echo '
						<tr class="transparent_row">
						<td style="border: 0px;">&nbsp;</td>
						<td' . $colspan_tag . ' style="text-align: left; padding-top: 10px; border: 0px;">
							<input type="submit" id="submit_update" name="submit" value="update" class="form_button">
							<input type="reset" id="submit_reset" name="reset" value="reset" class="form_button">
							<input type="submit" id="submit_reset_defaults" name="submit" value="reset defaults" class="form_button">
							';

							if ($submodule == 'fields')
							{
								$upload_max_filesize_bytes = 0;
								$upload_max_filesize = ini_get('upload_max_filesize');
								if (strtolower(substr($upload_max_filesize, -1)) == 'm') {$upload_max_filesize_bytes = substr($upload_max_filesize, 0, -1) * 1048576;} else {$upload_max_filesize_bytes = $upload_max_filesize;}
								if ($upload_max_filesize_bytes) {echo '<span style="margin-left: 380px;">upload_max_filesize = <a href="#' . $upload_max_filesize_bytes . '" id="upload_max_filesize">' . $upload_max_filesize_bytes . '</a> (' . get_bytes_formatted($upload_max_filesize_bytes) . ')</span>';}
							}

							echo '
						</td>
						</tr>
						</table>
						<input type="hidden" id="submodule" name="submodule" value="' . $submodule . '">
						';
					}

					if ($submodule == 'payment_vars')
					{
						echo '
						<p style="margin-top: 40px;"><span class="header">Payment Variable Presets</span> (WARNING: These will overwrite your current payment variables)</p>
						<ul>
						';

						foreach ($payment_vars_presets as $key => $value)
						{
							$key_display = payment_vars_preset_display($key);
							echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=' . $submodule . '&payment_vars_preset=' . $key . '" class="payment_vars_preset">' . $key_display . '</a></li>';
						}

						echo '
						</ul>
						';
					}

					echo '
				</td>
			</tr>
		</table>
		';
	}

	if ($module == 'maintenance')
	{
		if ($_SESSION['contact']['access'] != 'admin')
		{
			echo '<p><b>Admins only!</b><br>You are not authorized to access this area.</p>';
			exit_error();
		}

		$maintenance_submodules = array(
		'cleanup' => 'cleanup temp files',
		'sample' => 'sample data',
		'export' => 'export data',
		'backup' => 'backup data',
		'purge' => 'purge data',
		'test_mail' => 'test mail',
		'test_upload' => 'test upload',
		'update_structure' => 'update data structure',
		'versions' => 'versions'
		);

		echo '
		<table style="border-collapse: collapse; width: 100%;">
			<tr>
				<td class="foreground" style="width: 200px; padding: 5px;">

					<table class="foreground" style="width: 190px; font-weight: bold; border-spacing: 5px; border: 1px solid ' . $config['color_text'] . ';">
					<tr>
					<td style="white-space: nowrap;">
					choose maintenance:
					<ul class="nav_list">
					';

					foreach ($maintenance_submodules as $key => $value)
					{
						if ($key == $submodule) {$value = '<span style="color: ' . $config['color_link_hover'] . ';">' . $value . '</span>';}
						echo '<li><a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=' . $module . '&submodule=' . $key . '">' . $value . '</a></li>' . "\n";
					}

					echo '
					</ul>
					</td>
					</tr>
					</table>

				</td>
				<td style="padding-left: 20px;">
				';

					if (isset($copy) && $copy) {echo $copy . '<br><br>';}
					if ($notice) {echo '<div class="notice">' . $notice . '</div>';}

					if ($submodule == 'cleanup')
					{
						function get_files($path)
						{
							$file_array = array();

							$dir = scandir($path);
							foreach ($dir as $value)
							{
								if ($value != '.' && $value != '..') {$file_array[] = $value;}
							}

							natsort($file_array);
							return $file_array;
						}

						$record_array = array();
						$dates_array = array();

						$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT submission_id, date_time, YEAR(date_time) AS year, ext FROM submissions') or exit_error('query failure: SELECT submissions for cleanup');
						if (mysqli_num_rows($result))
						{
							while ($row = mysqli_fetch_assoc($result))
							{
								$record_array[$row['year']][] = $row['submission_id'] . '.' . $row['ext'];
								$dates_array[$row['submission_id'] . '.' . $row['ext']] = $row['date_time'];
							}
						}
						else
						{
							echo 'no records';
							exit_error();
						}

						$result = @mysqli_query($GLOBALS['db_connect'], 'SELECT action_id, submission_id, date_time, YEAR(date_time) AS year, ext FROM actions WHERE ext IS NOT NULL') or exit_error('query failure: SELECT actions for cleanup');
						if (mysqli_num_rows($result))
						{
							while ($row = mysqli_fetch_assoc($result))
							{
								$record_array[$row['year']][] = 'action_' . $row['action_id'] . '.' . $row['ext'];
								$dates_array['action_' . $row['action_id'] . '.' . $row['ext']] = $row['date_time'];
								$actions2submissions[$row['action_id']] = $row['submission_id'];
							}
						}

						$compare = array();
						$dir = scandir($config['upload_path']);
						foreach ($dir as $value)
						{
							if ($value != '.' && $value != '..' && is_dir($config['upload_path'] . $value))
							{
								$compare[$value]['records'] = array();
								$compare[$value]['files'] = array();
							}
						}

						if (!$compare)
						{
							echo 'no uploaded files';
							exit_error();
						}

						ksort($compare);

						foreach ($compare as $key => $value)
						{
							if (isset($record_array[$key])) {$compare[$key]['records'] = $record_array[$key];}
							$compare[$key]['files'] = get_files($config['upload_path'] . $key);
						}

						unset($_SESSION['unrecorded files']);
						unset($_SESSION['missing files']);

						foreach ($compare as $key => $value)
						{
							if ($value)
							{
								$compare[$key]['unrecorded files'] = array_diff($compare[$key]['files'], $compare[$key]['records']);
								$compare[$key]['missing files'] = array_diff($compare[$key]['records'], $compare[$key]['files']);
								if ($compare[$key]['unrecorded files']) {$_SESSION['unrecorded files'][$key] = $compare[$key]['unrecorded files'];}
								if ($compare[$key]['missing files']) {$_SESSION['missing files'][$key] = $compare[$key]['missing files'];}
							}
						}

						$columns = array(
						'year',
						'records',
						'files',
						'unrecorded files',
						'missing files'
						);

						echo '
						<table class="table_list" style="width: auto;">
						<tr>
						';

						foreach ($columns as $value)
						{
							echo '<th>' . $value . '</th>';
							if ($value != 'year') {$totals[$value] = 0;}
						}

						echo '</tr>';

						foreach ($compare as $key => $value)
						{
							echo '
							<tr style="text-align: right;">
							<td style="text-align: center;">' . $key . '</td>
							';

							foreach ($value as $sub_key => $sub_value)
							{
								$count = count($value[$sub_key]);
								$totals[$sub_key] += $count;

								$extra = '';
								if (($sub_key == 'unrecorded files' || $sub_key == 'missing files') && $count)
								{
									$filemtime = '???';
									$count = '<span class="notice">' . $count . '</span>';
									$extra = '<br>';
									foreach ($value[$sub_key] as $file)
									{
										if ($sub_key == 'unrecorded files')
										{
											$filemtime = filemtime($config['upload_path'] . $key . '/' . $file);
											$file = '<a href="download.php?year=' . $key . '&file=' . urlencode($file) . '">' . $file . '</a>';
										}

										if ($sub_key == 'missing files')
										{
											$filemtime = strtotime($dates_array[$file]);

											$explode = explode('.', $file);

											if (substr($file, 0, 7) == 'action_')
											{
												$sub_explode = explode('_', $explode[0]);
												$submission_id = $actions2submissions[$sub_explode[1]];
											}
											else
											{
												$submission_id = $explode[0];
											}

											$file = '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&module=submissions&submission_id=' . $submission_id . '">' . $file . '</a>';
										}

										$extra .= $file . ' <span class="small" style="font-weight: bold;">[' . timezone_adjust(gmdate('Y-m-d H:i:s', $filemtime)) . ']</span><br>';
									}
								}

								echo '<td>' . $count . $extra . '</td>';
							}
						}

						echo '
						<tr style="text-align: right;">
						<th>totals</th>
						';

						foreach ($totals as $key => $value)
						{
							$color = $config['color_text'];
							if ($value && ($key == 'unrecorded files' || $key == 'missing files')) {$color = 'red';}
							echo '<th style="color: ' . $color . ';">' . $value . '</th>';
						}

						echo '
						<tr class="transparent_row">
						<td style="border: 0px;">&nbsp;</td>
						<td style="border: 0px;">&nbsp;</td>
						<td style="border: 0px;">&nbsp;</td>
						<td style="border: 0px;"><input type="submit" id="submit_delete" name="submit" value="delete temp files" class="form_button" style="width: 150px;"></td>
						<td style="border: 0px;">&nbsp;</td>
						</tr>
						</table>
						';

						if ($deletes['temp_files']) {echo '<div class="notice" style="font-style: italic;">' . $deletes['temp_files'] . ' file(s) deleted</div>';}
						if ($deletes['resets']) {echo '<div class="notice" style="font-style: italic;">' . $deletes['resets'] . ' resets(s) deleted</div>';}
						if ($deletes['truncate_resets']) {echo '<div class="notice" style="font-style: italic;">TRUNCATE resets</div>';}
					}

					if ($submodule == 'purge')
					{
						echo'
						<p style="margin-top: 40px;">
						This function will purge legacy password hashes.<br>
						Submission Manager < version 3.33 (running on PHP < version 5.5) stored passwords using the legacy SHA1 hashing algorithm.<br>
						This function will purge all SHA1 password hashes and force those users to reset their passwords.
						</p>
						<p class="notice"><i>WARNING:</i> This will permanently delete data from your database! Please backup and archive your database before proceeding.</p>
						<input type="submit" id="submit_purge_hashes" name="submit" value="purge hashes" class="form_button" style="width: 100px;">
						';

						if ($submit == 'purge hashes')
						{
							$sql = 'UPDATE contacts SET password = NULL WHERE password IS NOT NULL AND CHAR_LENGTH(password) <= 40';
							$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: PURGE password hash');
							echo '<p class="notice">Legacy password hashes purged: ' . mysqli_affected_rows($GLOBALS['db_connect']) . '</p>';
						}
					}

					if ($submodule == 'test_mail')
					{
						echo '
						<p>This function will test mail settings.</p>
						<table class="padding_lr_5">
						<tr style="font-weight: bold; text-decoration: underline;">
						<td style="text-align: right;">Name:</td>
						<td>Value:</td>
						<td>Description:</td>
						</tr>
						';

						foreach ($config_defaults as $key => $value)
						{
							if (array_key_exists($key, $config)) {$config_array_sorted[$key] = $config[$key];}
						}

						foreach ($config_array_sorted as $key => $value)
						{
							if ($key == 'mail_method' || strpos($key, 'smtp') !== false) {$test_mail[$key] = $value;}
						}

						$test_mail['from_name'] = $config['company_name'];
						$test_mail['from_email'] = $config['general_dnr_email'];
						$test_mail['to_email'] = '';

						if ($submit == 'test mail')
						{
							if (!isset($_POST['test_mail']['smtp_auth'])) {$_POST['test_mail']['smtp_auth'] = '';}
							$_POST['test_mail'] = cleanup($_POST['test_mail'], 'strip_tags', 'stripslashes');
							foreach ($test_mail as $key => $value) {$test_mail[$key] = $_POST['test_mail'][$key];}
						}

						foreach ($test_mail as $key => $value)
						{
							$value = htmlspecialchars((string) $value);
							$description = '';
							$input = '<input type="text" id="test_mail_' . $key . '" name="test_mail[' . $key . ']" value="' . $value . '">';
							if (isset($defaults['config'][$key]) && strpos($defaults['config'][$key]['type'], 'select|') !== false)
							{
								$select = array();
								$explode = explode('|', $defaults['config'][$key]['type']);
								$explode2 = explode(',', $explode[1]);
								foreach ($explode2 as $sub_value) {$select[$sub_value] = $sub_value;}
								$input = '<select id="test_mail_' . $key . '" name="test_mail[' . $key . ']">';
								foreach ($select as $sub_key => $sub_value)
								{
									if ($sub_value == 'NULL') {$sub_key_display = ''; $sub_value_display = '&nbsp;';} else {$sub_key_display = htmlspecialchars($sub_key); $sub_value_display = htmlspecialchars($sub_value);}
									$input .= '<option value="' . $sub_key_display . '"';
									if ($sub_key == $value) {$input .= ' selected';}
									$input .= '>' . $sub_value_display . '</option>' . "\n";
								}
								$input .= '</select>';
							}
							if (isset($defaults['config'][$key]) && $defaults['config'][$key]['type'] == 'checkbox') {$input = '<input type="checkbox" id="test_mail_' . $key . '" name="test_mail[' . $key . ']" value="Y"'; if ($value) {$input .= ' checked';} $input .= ' >';}
							if (isset($defaults['config'][$key]['description'])) {$description = $defaults['config'][$key]['description'];}
							$key_display = '<label for="test_mail_' . $key . '" id="label_test_mail_' . $key . '">' . $key . ':</label>';

							echo '
							<tr>
							<td class="row_left">' . $key_display . '</td>
							<td>' . $input . '</td>
							<td>' . $description . '</td>
							</tr>
							';

							if ($key == 'smtp_password') {echo '<tr><td colspan="3">&nbsp;</td></tr>';}
						}

						echo '
						<tr>
						<td>&nbsp;</td>
						<td colspan="2"><br><input type="submit" id="submit_test_mail" name="submit" value="test mail" class="form_button" style="width: 100px;"></td>
						</tr>
						</table>
						<br>
						';

						if ($submit == 'test mail')
						{
							echo '<div style="font-family: monospace; font-weight: bold; white-space: nowrap;">';
							$test_from = 'test mail from ' . $test_mail['from_name'] . ' Submission Manager using ' . strtoupper($test_mail['mail_method']);
							$config = $test_mail; // needed for mail_setup()
							$mail = mail_setup();
							$mail->SMTPDebug = 2;
							$mail->Debugoutput = 'html';
							$mail->SMTPKeepAlive = false;
							$mail->SetFrom($test_mail['from_email'], $test_mail['from_name']);
							$mail->AddAddress($test_mail['to_email']);
							$mail->Subject = $test_from;
							$mail->Body = $test_from;
							if ($mail->Send()) {echo '<div class="notice">Message Sent</div>';} else {echo '<div class="notice">Mailer Error</div>' . $mail->ErrorInfo;}
							echo '</div>';
						}
					}

					if ($submodule == 'test_upload')
					{
						echo '
						<p>This function will help diagnose file upload problems.</p>
						<label for="file" id="label_file" class="">file:</label> <input type="file" id="file" name="file" style="margin-right: 10px;"> upload_path: <b>' . $config['upload_path'] . '</b><br>
						<input type="submit" id="submit_test_upload" name="submit" value="test upload" class="form_button" style="width: 100px; margin: 10px 0px 0px 25px;">
						';

						if ($submit == 'test upload')
						{
							echo '<pre style="font-weight: bold;">';

							print_r($_FILES);

							$is_uploaded_file = is_uploaded_file($_FILES['file']['tmp_name']);
							echo "\n" . 'is_uploaded_file: '; var_dump($is_uploaded_file);

							$test_upload_file_path = $config['upload_path'] . $_FILES['file']['name'];

							$file_exists = false;
							if ($_FILES['file']['name'] && file_exists($test_upload_file_path)) {$file_exists = true;}

							$extension = true;
							if ($_FILES['file']['name'])
							{
								$pathinfo = pathinfo($_FILES['file']['name']);
								if (!isset($pathinfo['extension']) || (isset($pathinfo['extension']) && $pathinfo['extension'] == '')) {$extension = false;}
								if (isset($pathinfo['extension']) && !in_array(strtolower($pathinfo['extension']), $file_types)) {$extension = false;}
							}

							if ($file_exists || !$extension)
							{
								if ($file_exists) {echo "\n" . 'test file already exists: ' . $test_upload_file_path;}
								if (!$extension) {echo "\n" . 'test file invalid extension: ' . $test_upload_file_path;}
							}
							else
							{
								$move_uploaded_file = move_uploaded_file($_FILES['file']['tmp_name'], $test_upload_file_path);
								echo 'move_uploaded_file: '; var_dump($move_uploaded_file);
								if ($_FILES['file']['name'] && file_exists($test_upload_file_path))
								{
									echo "\n" . 'test file: ' . $test_upload_file_path . ' | ' . filesize($test_upload_file_path) . ' bytes | ' . timezone_adjust(gmdate('Y-m-d H:i:s', filemtime($test_upload_file_path)));
									$unlink = unlink($test_upload_file_path);
									if ($unlink) {echo ' | deleted';}
								}

							}

							echo '</pre>';
						}
					}

					if ($submodule == 'update_structure')
					{
						check_version('SubMgr');
						$extra = '';

						if (!$submit)
						{
							check_version('structure');

							if ($version_structure)
							{
								echo '
								<table class="padding_lr_5">
								<tr><td colspan="2"><b>Data Structure versions</b></td></tr>
								<tr class="foreground"><td class="row_left">your version:</td><td><b>' . $version_structure . '</b></td></tr>
								<tr class="foreground"><td class="row_left">latest version:</td><td><b>' . $version_local . '</b></td></tr>
								</table>
								';

								if (!isset($describe['submissions']['last_action_id'])) {$extra = ' checked';}
							}

							echo '
							<p>This function will update the structure of your Submission Manager database to the latest version.</p>
							<p class="notice"><i>WARNING:</i> Backup your Submission Manager database before proceeding! Please speak to your system administrator if you do not know how to backup your data.</p>
							<p>This process may take a minute. Please be patient.</p>
							<input type="checkbox" id="sync_last_actions" name="sync_last_actions" value="Y"' . $extra . '> <label for="sync_last_actions" id="label_sync_last_actions">sync last actions?</label><br>
							<input type="checkbox" id="optimize_tables" name="optimize_tables" value="Y"> <label for="optimize_tables" id="label_optimize_tables">optimize tables?</label><br><br>
							<input type="submit" id="submit_update_data_structure" name="submit" value="update data structure" class="form_button" style="width: 150px;">
							';
						}

						if ($submit == 'update data structure')
						{
							$updates = array();

							// needed here becuase password field type will change below
							$hash_passwords = false;
							if ($describe['contacts']['password'] == 'varchar(6)') {$hash_passwords = true;}

							// check database for UTF-8
							$sql = 'SHOW VARIABLES LIKE "%database%"';
							$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SHOW VARIABLES');
							while ($row = mysqli_fetch_assoc($result)) {$show_varibles[$row['Variable_name']] = $row['Value'];}
							if (strpos($show_varibles['character_set_database'], 'utf8') === false || strpos($show_varibles['collation_database'], 'utf8') === false)
							{
								$sql = 'ALTER DATABASE `' . $config_db['name'] . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';
								$result = @mysqli_query($GLOBALS['db_connect'], $sql);
								if ($result) {$updates[] = 'database altered to UTF-8: ' . $config_db['name'];}
							}

							// check for missing tables
							$missing_tables = array_diff(array_keys($schema), array_keys($describe));
							if ($missing_tables)
							{
								foreach ($missing_tables as $value)
								{
									$sql = '';
									$sql_fields = '';
									$sql_indexes = '';

									$sql = "CREATE TABLE `$value` (" . "\r\n";
									foreach ($schema[$value]['fields'] as $sub_key => $sub_value) {$sql_fields .= "`$sub_key` $sub_value[type] $sub_value[extra]," . "\r\n";}
									foreach ($schema[$value]['indexes'] as $sub_key => $sub_value) {$sql_indexes .= "$sub_value[type] `$sub_key` ($sub_value[fields])," . "\r\n";}
									$sql_indexes = substr(trim($sql_indexes), 0, -1);
									$sql .= $sql_fields . $sql_indexes . ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

									@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: CREATE TABLE ' . $value);
									$updates[] = 'new table created: ' . $value;

									if (isset($defaults[$value]))
									{
										insert_from_array($value, $defaults[$value]);
										$updates[] = 'default values inserted into ' . $value;
									}
								}

								// re-query the database for the table list
								get_tables();
								get_describe();
							}

							// check tables for UTF-8
							$result = @mysqli_query($GLOBALS['db_connect'], 'SHOW TABLE STATUS') or exit_error('query failure: SHOW TABLE STATUS');
							while ($row = mysqli_fetch_assoc($result))
							{
								$show_tables_status[$row['Name']] = $row;

								if ($row['Collation'] && strpos($row['Collation'], 'utf8') === false)
								{
									$sql = 'ALTER TABLE `' . $row['Name'] . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';
									@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: ALTER TABLE UTF-8');
									$updates[] = 'table altered to UTF-8: ' . $row['Name'];
								}
							}

							// change to country codes. needed after UTF-8 but before country field type is changed.
							if ($describe['contacts']['country'] != 'char(3)')
							{
								include_once('inc_lists.php');
								$countries_flipped = array_flip($countries);
								$sql = 'SELECT contact_id, country FROM contacts WHERE country != "USA"';
								$result = mysqli_query($db_connect, $sql) or exit_error('query failure: SELECT countries');
								while ($row = mysqli_fetch_assoc($result))
								{
									if (isset($countries_flipped[$row['country']])) {$country = '"' . mysqli_real_escape_string($GLOBALS['db_connect'], $countries_flipped[$row['country']]) . '"';} else {$country = 'NULL';}
									$sql = 'UPDATE contacts SET country = ' . $country . ' WHERE contact_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $row['contact_id']);
									$result_update = mysqli_query($db_connect, $sql) or exit_error('query failure: UPDATE countries');
								}
								$updates[] = 'converted to country codes';
							}

							// check for missing/extra fields
							foreach ($schema as $key => $value)
							{
								$keys_schema = array_keys($value['fields']);
								$keys_describe = array_keys($describe[$key]);
								$missing_fields = array_diff($keys_schema, $keys_describe);
								$extra_fields = array_diff($keys_describe, $keys_schema);

								if ($missing_fields)
								{
									foreach ($missing_fields as $sub_key => $sub_value)
									{
										$sql = "ALTER TABLE `$key` ADD `$sub_value` " . $schema[$key]['fields'][$sub_value]['type'] . ' ' . $schema[$key]['fields'][$sub_value]['extra'];
										if ($sub_key == 0) {$sql .= ' FIRST';} else {$sql .= ' AFTER `' . $keys_schema[$sub_key - 1] . '`';}
										@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: ' . $sql);
										$updates[] = 'field added: ' . $key . '.' . $sub_value;
									}
								}

								if ($extra_fields)
								{
									foreach ($extra_fields as $sub_key => $sub_value)
									{
										$sql = "ALTER TABLE `$key` DROP `$sub_value`";
										@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: ' . $sql);
										$updates[] = 'field deleted: ' . $key . '.' . $sub_value;
									}
								}
							}

							get_describe($full_columns = true); // must requery to get new fields. needs full column info.

							// check for changed fields. needs to happen in separate loop after missing/extra fields are updated.
							foreach ($schema as $key => $value)
							{
								foreach ($value['fields'] as $sub_key => $sub_value)
								{
									$needs = array();
									// mySQL >= 8 deprecated parentheses in integer types
									if (preg_match('~\(.*\)~', $describe[$key][$sub_key]['Type'])) {$type_compare = $sub_value['type'];} else {$type_compare = preg_replace('~\(.*\)~', '', $sub_value['type']);}
									if (strtolower($type_compare) != strtolower($describe[$key][$sub_key]['Type'])) {$needs[] = 'type';}
									if ($describe[$key][$sub_key]['Collation'] && strpos($describe[$key][$sub_key]['Collation'], 'utf8') === false) {$needs[] = 'utf8';}
									if ($needs)
									{
										if (in_array('utf8', $needs)) {$utf8 = ' CHARACTER SET utf8 COLLATE utf8_unicode_ci';} else {$utf8 = '';}
										$sql = "ALTER TABLE `$key` MODIFY `$sub_key` " . $sub_value['type'] . $utf8 . ' ' . $sub_value['extra'];
										@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: ALTER TABLE ' . $key);
										$updates[] = 'field altered (' . implode(',', $needs) . '): ' . $key . '.' . $sub_key;
									}
								}
							}

							// add/delete indexes. must come after collation changes above.
							foreach ($schema as $key => $value)
							{
								$sql = "SHOW INDEX FROM `$key`";
								$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SHOW INDEX');
								while ($row = mysqli_fetch_assoc($result))
								{
									if ($row['Key_name'] != 'PRIMARY') {$db_indexes[$key][$row['Key_name']][] = $row['Column_name'];}
								}
								foreach ($value['indexes'] as $sub_key => $sub_value)
								{
									if ($sub_value['type'] != 'PRIMARY KEY') {$schema_indexes[$key][$sub_key] = $sub_value['fields'];}
								}
							}
							foreach ($db_indexes as $key => $value)
							{
								foreach ($value as $sub_key => $sub_value) {$db_indexes[$key][$sub_key] = implode(',', $sub_value);} // needed for array comparison to $schema_indexes (comma separated list of fields)
							}
							foreach ($schema_indexes as $key => $value)
							{
								$keys_schema = array_keys($value);
								$keys_db = array_keys($db_indexes[$key]);
								$missing_indexes = array_diff($keys_schema, $keys_db);
								$extra_indexes = array_diff($keys_db, $keys_schema);

								if ($missing_indexes)
								{
									$sql_array = array();
									foreach ($missing_indexes as $sub_value)
									{
										if ($schema[$key]['indexes'][$sub_value]['type'] == 'KEY') {$sql_array[] = "ADD INDEX `$sub_value` (" . $schema[$key]['indexes'][$sub_value]['fields'] . ')';}
										if ($schema[$key]['indexes'][$sub_value]['type'] == 'FULLTEXT') {$sql_array[] = "ADD FULLTEXT `$sub_value` (" . $schema[$key]['indexes'][$sub_value]['fields'] . ')';}
									}
									if ($sql_array)
									{
										$sql = "ALTER TABLE `$key` " . implode(', ', $sql_array);
										@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: ADD INDEX ' . $key);
										$updates[] = 'index added: ' . $key . '.' . $sub_value;
									}
								}

								if ($extra_indexes)
								{
									$sql_array = array();
									foreach ($extra_indexes as $sub_value)
									{
										$sql_array[] = 'DROP INDEX ' . $sub_value;
									}
									if ($sql_array)
									{
										$sql = "ALTER TABLE `$key` " . implode(', ', $sql_array);
										@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: DROP INDEX ' . $key);
										$updates[] = 'index deleted: ' . $key . '.' . $sub_value;
									}
								}
							}

							// hash passwords
							if ($hash_passwords)
							{
								// @mysqli_query($GLOBALS['db_connect'], 'UPDATE contacts SET password = SHA1(password) WHERE password IS NOT NULL AND CHAR_LENGTH(password) = 6') or exit_error('query failure: UPDATE password hash');
								// $updates[] = 'passwords hashed';

								$sql = 'SELECT contact_id, password FROM contacts WHERE password IS NOT NULL AND CHAR_LENGTH(password) < 40';
								$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT password hash');
								if (mysqli_num_rows($result))
								{
									while ($row = mysqli_fetch_assoc($result))
									{
										$password = password_wrapper('hash', $row['password']);
										$sql = "UPDATE contacts SET password = '$password' WHERE contact_id = " . mysqli_real_escape_string($GLOBALS['db_connect'], $row['contact_id']);
										@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: UPDATE password hash');
									}

									$updates[] = 'passwords hashed';
								}
							}

							// add/delete config rows
							get_config();
							compare_configs();

							if ($missing_configs)
							{
								if (isset($config['payment_vars_post']) && $config['payment_vars_post']) {$defaults['config']['payment_redirect_method']['value'] = 'POST';}

								foreach ($missing_configs as $value)
								{
									if ($defaults['config'][$value]['value'] != '') {$value_insert = "'" . mysqli_real_escape_string($GLOBALS['db_connect'], $defaults['config'][$value]['value']) . "'";} else {$value_insert = 'NULL';}
									$sql = "INSERT INTO config SET name = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $value) . "', value = $value_insert";
									@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: INSERT config ' . $value);
									$updates[] = 'configuration added: ' . $value;
								}
							}

							if ($extra_configs)
							{
								foreach ($extra_configs as $value)
								{
									$sql = "DELETE FROM config WHERE name = '$value'";
									@mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: DELETE config ' . $value);
									$updates[] = 'configuration deleted: ' . $value;
								}
							}

							// sync_last_actions
							if (isset($_POST['sync_last_actions']))
							{
								ini_set('max_execution_time', '9999');
								ini_set('max_input_time', '-1');
								ini_set('memory_limit', '-1');
								ini_set('default_socket_timeout', '-1');

								$sql = 'SELECT submission_id FROM submissions';
								$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit_error('query failure: SELECT submissions FOR sync last actions');
								if (mysqli_num_rows($result))
								{
									while ($row = mysqli_fetch_assoc($result)) {sync_last_action($row['submission_id']);}
								}
								$updates[] = 'last actions synchronized';
							}

							// optimize tables
							if (isset($_POST['optimize_tables']))
							{
								foreach ($show_tables as $key => $value) {$tables[$key] = "`$value`";}
								$tables = implode(',', $tables);
								@mysqli_query($GLOBALS['db_connect'], 'OPTIMIZE TABLE ' . $tables) or exit('query failure: OPTIMIZE TABLE');
								$updates[] = 'tables optimized: ' . $tables;
							}

							// update structure version
							if (isset($version_local) && $version_local && $version_local != $show_tables_status['config']['Comment'])
							{
								@mysqli_query($GLOBALS['db_connect'], "ALTER TABLE `config` COMMENT = '$version_local'") or exit('query failure: ALTER TABLE config COMMENT');
								$updates[] = 'data structure version updated: ' . $version_local;
							}

							// display list of updates
							if ($updates)
							{
								echo 'Updates successfully applied. Below is a list of the updates that were made:<ul style="font-weight: bold;">';
								foreach ($updates as $value) {echo '<li>' . $value . '</li>' . "\n";}
								echo '</ul><div class="small">updates applied ' . $local_date_time . '</div>';
							}
							else
							{
								echo 'No updates needed. Your data structure is already up to date.';
							}
						}
					}

					if ($submodule == 'versions')
					{
						if (!isset($version_local) || !isset($version_remote)) {check_version('SubMgr', true);}

						// $version_remote_url = 'https://www.submissionmanager.net/changelog.txt';
						$version_remote_url = 'https://github.com/devinemke/submgr/blob/main/changelog.txt';
						if ($version_remote != '???') {$version_remote_display = '<a href="' . $version_remote_url . '" target="_blank">' . $version_remote . '</a>';} else {$version_remote_display = $version_remote;}

						echo '
						<table class="padding_lr_5">
						<tr><td colspan="2"><b>Versions</b></td></tr>
						<tr class="foreground"><td>Submission Manager (installed)</td><td><a href="#" id="popup_version_sm"><b>' . $version_local . '</b></a></td></tr>
						<tr class="foreground"><td>Submission Manager (latest)</td><td><b>' . $version_remote_display . '</b></td></tr>
						<tr class="foreground"><td>PHP</td><td><a href="#" id="popup_version_php"><b>' . PHP_VERSION . '</b></a></td></tr>
						<tr class="foreground"><td>mySQL</td><td><a href="#" id="popup_version_mysql"><b>' . $version_mysql . '</b></a></td></tr>
						</table>
						';

						$contact_sm = 'The latest version of Submission Manager can always be found on <a href="https://github.com/devinemke/submgr" target="_blank"><b>GitHub</b></a>.<br>Please contact <b>' . mail_to('devin@submissionmanager.net') . '</b> for information on upgrading to the latest version.<br>Please include the following in your email:<ul><li>the name of your publication</li><li>the name/email of the person who originally registered for Submission Manager</li><li>the public URL of your Submission Manager (if already installed)</li></ul>';
						$version_ouput = '<p>Congratulations! You are using the latest version of Submission Manager.</p>';
						if ($version_local < $version_remote) {$version_ouput = '<p>There is a newer version of Submission Manager available. Newer versions typically include new features, bug fixes and security patches.</p>' . $contact_sm;}
						if ($version_remote == '???') {$version_ouput = '<p>Submision Manager cannot currently determine if there is a newer version available.</p>' . $contact_sm;}
						echo $version_ouput;
					}

				echo '
				</td>
			</tr>
		</table>
		';
	}

	echo '
	</form>
	';
}

if ($module == 'account' || $module == 'submissions' || $module == 'maintenance')
{
	$extra = '';
	if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') !== false) {$extra = ' style="position: absolute; top: 5%; left: 5%;"';}
	$lightbox = '
	<div id="foreground"' . $extra . '>
	<a href="#" id="lightbox_off" style="font-weight: bold; margin-right: 10px;">close</a>
	<iframe id="popframe"></iframe>
	</div>
	<div id="background"></div>
	';
	echo $lightbox;
}

if ($GLOBALS['js_object'])
{
	echo '<script nonce="' . $GLOBALS['nonce'] . '">' . "\n" . $GLOBALS['js_object'];

	if ($module == 'account')
	{
		echo '
		for (var key in submissions)
		{
			(function()
			{
				var submission_id = key;

				if (submissions[submission_id]["file"])
				{
					var file = document.getElementById("file_" + submission_id);
					file.addEventListener("mouseover", function(event) { tooltip_show("file missing!", file, event, ""); });
					file.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (submissions[submission_id]["comments"])
				{
					var comments = document.getElementById("comments_" + submission_id);
					comments.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["comments"], comments, event, 400); });
					comments.addEventListener("mouseout", function(event) { tooltip_hide(); });
					comments.addEventListener("click", function(event) { lightbox("on","popup.php?page=view&field=comments_submitter&submission_id=" + submission_id,500,400,400,100); event.preventDefault(); });
				}

				if (submissions[submission_id]["last_action_message"])
				{
					var last_action_message = document.getElementById("last_action_message_" + submission_id);
					last_action_message.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["last_action_message"], last_action_message, event, 400); });
					last_action_message.addEventListener("mouseout", function(event) { tooltip_hide(); });
					last_action_message.addEventListener("click", function(event) { lightbox("on","popup.php?page=view&field=comments_staff&submission_id=" + submission_id,500,400,400,100); event.preventDefault(); });
				}

				if (submissions[submission_id]["withdraw"])
				{
					var withdraw = document.getElementById("withdraw_" + submission_id);
					withdraw.addEventListener("click", function(event) { if (!confirm_prompt("withdraw", "submission", submission_id)) {event.preventDefault();} });
				}
			})();
		}
		';
	}

	if ($module == 'submissions')
	{
		echo '
		for (var key in submissions)
		{
			(function()
			{
				var submission_id = key;

				if (submissions[submission_id]["writer"])
				{
					var writer = document.getElementById("writer_" + submission_id);
					writer.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["writer"], writer, event, ""); });
					writer.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (submissions[submission_id]["file"])
				{
					var file = document.getElementById("file_" + submission_id);
					file.addEventListener("mouseover", function(event) { tooltip_show("file missing!", file, event, ""); });
					file.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (submissions[submission_id]["comments"])
				{
					var comments = document.getElementById("comments_" + submission_id);
					comments.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["comments"], comments, event, 400); });
					comments.addEventListener("mouseout", function(event) { tooltip_hide(); });
					comments.addEventListener("click", function(event) { lightbox("on","popup.php?page=view&table=submissions&id_name=submission_id&id_value=" + submission_id + "&field=comments",500,400,400,100); event.preventDefault(); });
				}

				if (submissions[submission_id]["notes"])
				{
					var notes = document.getElementById("notes_" + submission_id);
					notes.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["notes"], notes, event, 400); });
					notes.addEventListener("mouseout", function(event) { tooltip_hide(); });
					notes.addEventListener("click", function(event) { lightbox("on","popup.php?page=view&table=submissions&id_name=submission_id&id_value=" + submission_id + "&field=notes",500,400,400,100); event.preventDefault(); });
				}

				if (submissions[submission_id]["action_count"])
				{
					var action_count = document.getElementById("action_count_" + submission_id);
					action_count.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["action_count"], action_count, event, ""); });
					action_count.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (submissions[submission_id]["tag"])
				{
					var check = document.getElementById("check_" + submissions[submission_id]["tag"]);
					check.addEventListener("click", function(event) { clickage(event); });
					check.addEventListener("click", function(event) { change_row_color(submissions[submission_id]["tag"]); });
					check.addEventListener("click", function(event) { tag_checked_count(); });
				}
				';

				if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']) && $single_display)
				{
					echo '
					document.getElementById("update_submission_" + submission_id).addEventListener("click", function(event) { lightbox("on","popup.php?page=update&submission_id=" + submission_id,0,0,300,100); event.preventDefault(); });
					document.getElementById("delete_submission_" + submission_id).addEventListener("click", function(event) { if (!confirm_prompt("delete", "submission", submission_id)) {event.preventDefault();} });
					';
				}

				echo '
			})();
		}

		for (var key in actions)
		{
			(function()
			{
				var action_id = key;

				if (actions[action_id]["reader"])
				{
					var reader = document.getElementById("reader_" + action_id);
					reader.addEventListener("mouseover", function(event) { tooltip_show(actions[action_id]["reader"], reader, event, ""); });
					reader.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (actions[action_id]["receiver"])
				{
					var receiver = document.getElementById("receiver_" + action_id);
					receiver.addEventListener("mouseover", function(event) { tooltip_show(actions[action_id]["receiver"], receiver, event, ""); });
					receiver.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (actions[action_id]["file"])
				{
					var file = document.getElementById("file_" + action_id);
					file.addEventListener("mouseover", function(event) { tooltip_show("file missing!", file, event, ""); });
					file.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}
				';

				if (in_array($_SESSION['contact']['access'], $access_grouping['admin_editor']))
				{
					echo '
					document.getElementById("update_action_" + action_id).addEventListener("click", function(event) { lightbox("on","popup.php?page=update&action_id=" + action_id,0,0,300,100); event.preventDefault(); });
					document.getElementById("delete_action_" + action_id).addEventListener("click", function(event) { if (!confirm_prompt("delete", "action", action_id)) {event.preventDefault();} });
					';
				}

				echo '
			})();
		}
		';
	}

	if ($module == 'contacts')
	{
		echo '
		for (var key in contacts)
		{
			(function()
			{
				var contact_id = key;

				if (contacts[contact_id]["contact"])
				{
					var contact = document.getElementById("contact_" + contact_id);
					contact.addEventListener("mouseover", function(event) { tooltip_show(contacts[contact_id]["contact"], contact, event, ""); });
					contact.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}
			})();
		}
		';
	}

	if ($module == 'reports')
	{
		echo '
		for (var key in submissions)
		{
			(function()
			{
				var submission_id = key;

				if (submissions[submission_id]["writer"])
				{
					var writer = document.getElementById("writer_" + submission_id);
					writer.addEventListener("mouseover", function(event) { tooltip_show(submissions[submission_id]["writer"], writer, event, ""); });
					writer.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}
			})();
		}

		for (var key in actions)
		{
			(function()
			{
				var action_id = key;

				if (actions[action_id]["reader"])
				{
					var reader = document.getElementById("reader_" + action_id);
					reader.addEventListener("mouseover", function(event) { tooltip_show(actions[action_id]["reader"], reader, event, ""); });
					reader.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}

				if (actions[action_id]["receiver"])
				{
					var receiver = document.getElementById("receiver_" + action_id);
					receiver.addEventListener("mouseover", function(event) { tooltip_show(actions[action_id]["receiver"], receiver, event, ""); });
					receiver.addEventListener("mouseout", function(event) { tooltip_hide(); });
				}
			})();
		}
		';
	}

	echo '</script>';
}
?>
<?php
ob_start();
include('inc_common.php');

$title = '';
$copy = '';
$viewport = '';

extract($_REQUEST);

if ($page == 'view')
{
	$viewport = '<meta name="viewport" content="width=device-width">';

	if ($_SESSION['contact']['access'])
	{
		$result = @mysqli_query($GLOBALS['db_connect'], "SELECT $field FROM `$table` WHERE $id_name = $id_value") or exit_error('query failure: SELECT [for view]');
		extract(mysqli_fetch_assoc($result));
		$title = $field . ': ' . $table . '.' . $id_name . ' #' . $id_value;
		$copy = nl2br(htmlspecialchars($$field));
	}

	if (!$_SESSION['contact']['access'] && isset($_GET['submission_id']) && $_GET['submission_id'] && is_numeric($_GET['submission_id']) && isset($_SESSION['submissions'][$_GET['submission_id']]) && isset($_GET['field']) && $_GET['field'])
	{
		$submission_id = (int) $_GET['submission_id'];
		$title .= '"' . htmlspecialchars($_SESSION['submissions'][$submission_id]['title']) . '"';

		if ($_GET['field'] == 'comments_submitter')
		{
			$title = 'comments by submitter on ' . $title;
			$text = 'comments';
		}

		if ($_GET['field'] == 'comments_staff')
		{
			$title = 'comments by staff on ' . $title;
			$text = 'last_action_message';
		}

		$copy = nl2br(htmlspecialchars($_SESSION['submissions'][$submission_id][$text]));
	}
}

if ($page == 'update')
{
	if ($_SESSION['contact']['access'] != 'admin' && $_SESSION['contact']['access'] != 'editor') {exit('unauthorized access');}

	$viewport = '<meta name="viewport" content="width=600">';

	if (isset($submission_id))
	{
		$_SESSION['table'] = 'submissions';
		$_SESSION['id_name'] = 'submission_id';
		$_SESSION['id_value'] = $submission_id;
		$row = $_SESSION['submission'];
		if (!$config['show_date_paid']) {unset($row['date_paid']);}
	}

	if (isset($action_id))
	{
		$_SESSION['table'] = 'actions';
		$_SESSION['id_name'] = 'action_id';
		$_SESSION['id_value'] = $action_id;
		$row = $_SESSION['submission']['actions'][$action_id];
		$readers = $_SESSION['readers'];

		// if submitter withdrawn
		if ($row['action_type_id'] == 2 && !isset($readers['all'][$row['reader_id']]))
		{
			$readers['all'][$row['reader_id']]['first_name'] = $_SESSION['submission']['contact']['first_name'] . ' (submitter)';
			$readers['all'][$row['reader_id']]['last_name'] = $_SESSION['submission']['contact']['last_name'];
		}
		else
		{
			$readers = $_SESSION['readers'];
		}
	}

	if (isset($row['contact'])) {unset($row['contact']);}
	if (isset($row['actions'])) {unset($row['actions']);}

	form_hash('session');
	$title = 'update : ' . $_SESSION['table'] . '.' . $_SESSION['id_name'] . ' #' . $_SESSION['id_value'];
	$action = 'index.php?page=login&module=submissions&submission_id=' . $_SESSION['submission']['submission_id'];
	$copy = '
	<form action="' . $action . '" method="post" name="form_update" id="form_update" target="_top">
	<table class="padding_lr_5">
	';

	$fixed_fields = array(
	'submission_id',
	'action_id',
	'date_time',
	'timestamp',
	'last_action_id',
	'last_action_type_id',
	'last_receiver_id'
	);

	foreach ($row as $key => $value)
	{
		if (!in_array($key, $fixed_fields)) {$key_display = '<label for="row_' . $key . '" id="label_row_' . $key . '">' . $key . ':</label>';} else {$key_display = $key . ':';}

		$copy .= '
		<tr class="foreground">
		<td class="row_left">' . $key_display . '</td>
		';

		$value = htmlspecialchars((string) $value);
		$enum_array = array();
		$type = 'text';
		$extra = '';
		if ($key == 'comments' || $key == 'message' || $key == 'notes') {$type = 'textarea';}
		if ($key == 'genre_id' && isset($_SESSION['genres']))
		{
			$type = 'enum';
			foreach ($_SESSION['genres']['all'] as $genre_key => $genre_value) {$enum_array[$genre_key] = $genre_key . ' - ' . $genre_value['name'];}
		}
		if ($key == 'reader_id' || $key == 'receiver_id')
		{
			$type = 'enum';
			foreach ($readers['all'] as $reader_key => $reader_value) {$enum_array[$reader_key] = $reader_key . ' - ' . $reader_value['last_name'] . ', ' . $reader_value['first_name'];}
		}
		if ($key == 'action_type_id')
		{
			$type = 'enum';
			foreach ($_SESSION['action_types']['all'] as $type_key => $type_value)
			{
				$enum_value = $type_key . ' - ' . $type_value['name'];
				if ($type_value['description']) {$enum_value .= ' - ' . $type_value['description'];}
				$enum_array[$type_key] = $enum_value;
			}
		}

		if ($type == 'text')
		{
			if ($key == 'writer') {$extra .= ' maxlength="50"';}
			if ($key == 'title') {$extra .= ' maxlength="255"';}
			if ($key == 'ext') {$extra .= ' maxlength="10"';}
			if ($_SESSION['table'] == 'submissions' && $key == 'writer' && ($_SESSION['groups'][$_SESSION['contact_access']]['blind'] || (isset($_SESSION['genres']['all'][$row['genre_id']]) && $_SESSION['genres']['all'][$row['genre_id']]['blind'])))
			{
				$value = 'blind groups/genres cannot see/edit writer';
				$extra .= ' disabled';
				$login_required_fields['submissions'][] = $key; // to prevent NULL link
			}
			$input = '<input type="text" id="row_' . $key . '" name="row[' . $key . ']" value="' . $value . '"' . $extra . '>';
		}

		if ($type == 'textarea')
		{
			if ($_SESSION['table'] == 'submissions' && $key == 'comments' && ($_SESSION['groups'][$_SESSION['contact_access']]['blind'] || (isset($_SESSION['genres']['all'][$row['genre_id']]) && $_SESSION['genres']['all'][$row['genre_id']]['blind'])))
			{
				$value = 'blind groups/genres cannot see/edit comments';
				$extra .= ' disabled';
				$login_required_fields['submissions'][] = $key; // to prevent NULL link
			}
			$input = '<textarea id="row_' . $key . '" name="row[' . $key . ']" cols="30" rows="4"' . $extra . '>' . $value . '</textarea>';
		}

		if ($type == 'enum')
		{
			$input = '<select id="row_' . $key . '" name="row[' . $key . ']" ' . $extra . '><option value="">&nbsp;</option>';
			foreach ($enum_array as $enum_key => $enum_value)
			{
				$input .= '<option value="' . $enum_key . '"';
				if ($enum_key == $value) {$input .= ' selected';}
				$input .= '>' . $enum_value . '</option>' . "\n";
			}
			$input .= '</select>';
		}

		if (in_array($key, $fixed_fields))
		{
			if ($key == 'date_time') {$value = timezone_adjust($value);}
			$input = '<b>' . $value . '</b>';
		}

		$copy .= '<td>' . $input;
		if (!in_array($key, $fixed_fields) && !in_array($key, $login_required_fields[$_SESSION['table']]))
		{
			$copy .= ' <span class="small" style="font-weight: bold; vertical-align: top;"><a href="#" id="nullify_' . $key . '" class="nullify">NULL</a>';
			if ($key == 'date_paid') {$copy .= ' | <a href="#" id="date_paid">today</a>';}
			$copy .= '</span>';
		}
		$copy .= '</td></tr>';
	}

	$copy .= '
	<tr><td colspan="2"></td></tr>
	<tr>
	<td>&nbsp;</td>
	<td>
	<input type="submit" id="submit_update" name="submit" value="update" class="form_button">
	<input type="button" id="submit_cancel" name="cancel" value="cancel" class="form_button">
	</td>
	</tr>
	</table>
	<input type="hidden" id="form_hash_popup" name="form_hash" value="' . $GLOBALS['form_hash'] . '">
	</form>
	';
}

if ($page == 'phpinfo')
{
	if ($_SESSION['contact']['access'] != 'admin') {exit('unauthorized access');}
	phpinfo();
	exit();
}

if ($page == 'changelog')
{
	if ($_SESSION['contact']['access'] != 'admin') {exit('unauthorized access');}
	$changelog = file_get_contents('changelog.txt');
	$title = 'changelog';
	$copy = nl2br(htmlspecialchars($changelog));
}

echo '<!DOCTYPE html>
<html lang="en">
<head>
<title>' . $title . '</title>
<meta charset="UTF-8">
';

if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') !== false) {echo $viewport;}
include('css.php');

echo '
<style>
body {margin: 0px;}
</style>

<script nonce="' . $GLOBALS['nonce'] . '">

function event_listener(eventName, onElement, event_object)
{
	document.addEventListener("DOMContentLoaded", function()
	{
		if (document.getElementById(onElement))
		{
			document.getElementById(onElement).addEventListener(eventName, event_object);
		}
	});
}
';

if ($page == 'update')
{
	echo '
	function nullify(element, type)
	{
		if (type == "text" || type == "textarea") {document.getElementById("row_" + element).value = "";}
		if (type == "select-one") {document.getElementById("row_" + element).selectedIndex = 0;}
		if (type == "select-multiple") {document.getElementById("row_" + element).selectedIndex = -1;}
	}

	document.addEventListener("DOMContentLoaded", function()
	{
		var nullifies = document.getElementsByClassName("nullify");
		for (i = 0; i < nullifies.length; i++)
		{
			(function()
			{
				var key = nullifies[i].id.replace("nullify_","");
				var type = document.getElementById("row_" + key).type;
				document.getElementById("nullify_" + key).addEventListener("click", function(event) { if (!nullify(key, type)) {event.preventDefault();} });
			})();
		}
	});

	function form_update_check()
	{
		var form_check = true;
		var error = "ERROR: required fields missing:\n\n";
		var fields = new Array();

		for (i = 0; i < document.getElementById("form_update").length; i++)
		{
			document.getElementById("form_update")[i].value = document.getElementById("form_update")[i].value.trim();
		}
		';

		foreach ($login_required_fields[$_SESSION['table']] as $value) {echo 'fields["' . $value . '"] = "' . $value . '";' . "\n";}

		echo '
		for (var key in fields)
		{
			var key_row = "row_" + key;

			if (document.getElementById(key_row) && !document.getElementById(key_row).value)
			{
				document.getElementById(key_row).className = "error";
				document.getElementById("label_" + key_row).className = "error";
				error += fields[key] + "\n";
				form_check = false;
			}
			else
			{
				document.getElementById(key_row).className = "";
				document.getElementById("label_" + key_row).className = "";
			}
		}

		if (!form_check)
		{
			alert(error);
			return false;
		}
		';

		if ($_SESSION['table'] == 'actions')
		{
			echo '
			var action_text = document.getElementById("row_action_type_id").options[document.getElementById("row_action_type_id").selectedIndex].text;
			if (action_text.match("forward") && !document.getElementById("row_receiver_id").value)
			{
				document.getElementById("row_receiver_id").className = "error";
				document.getElementById("label_row_receiver_id").className = "error";
				alert("missing receiver (action type forward requires receiver)");
				return false;
			}
			else
			{
				document.getElementById("row_receiver_id").className = "";
				document.getElementById("label_row_receiver_id").className = "";
			}
			';
		}

		echo '

		return true;
	}

	event_listener("click", "submit_update", function(event) { if (!form_update_check()) {event.preventDefault();} });
	event_listener("click", "submit_cancel", function(event) { window.parent.document.getElementById("lightbox_off").click(); });
	';

	if (isset($submission_id))
	{
		echo '
		function today()
		{
			document.getElementById("row_date_paid").value = "' . $local_date . '";
			return false;
		}

		event_listener("click", "date_paid", function(event) { if (!today()) {event.preventDefault();} });
		';
	}

	if (isset($action_id))
	{
		echo '
		function nullify_receiver()
		{
			var action_text = document.getElementById("row_action_type_id").options[document.getElementById("row_action_type_id").selectedIndex].text;
			if (!action_text.match("forward")) {document.getElementById("row_receiver_id").value = "";}
		}

		event_listener("change", "row_action_type_id", function(event) { nullify_receiver(); });
		';
	}
}

echo '
</script>

</head>
<body>
' . $copy . '
</body>
</html>';

output_tidy();
?>
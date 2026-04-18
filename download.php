<?php
include('inc_common.php');
if (!isset($_SESSION['contact'])) {exit('access denied');}

if (isset($_GET['submission_id']) && $_GET['submission_id'] && is_numeric($_GET['submission_id']))
{
	$submission_id = (int) preg_replace('/[^0-9]/', '', $_GET['submission_id']);
	if (!$_SESSION['contact']['access'] && !isset($_SESSION['submissions'][$submission_id])) exit('access denied'); // if submitter
	if ($_SESSION['contact']['access'] && $_SESSION['contact']['access'] != 'admin' && $_SESSION['contact']['access'] != 'editor' && !in_array($submission_id, $_SESSION['forwards'])) {exit('access denied');} // if staff
	if (isset($_GET['action_id']) && $_GET['action_id'] && is_numeric($_GET['action_id'])) {$action_id = (int) preg_replace('/[^0-9]/', '', $_GET['action_id']);} else {$action_id = '';}

	$table = 'submissions';
	$id_name = 'submission_id';
	$id_value = $submission_id;
	if ($action_id)
	{
		$table = 'actions';
		$id_name = 'action_id';
		$id_value = $action_id;
	}

	$sql = "SELECT YEAR(date_time) AS year, ext FROM `$table` WHERE $id_name = '" . mysqli_real_escape_string($GLOBALS['db_connect'], $id_value) . "'";
	$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit('query failure: SELECT for download');
	$row = mysqli_fetch_assoc($result);
	$file = $id_value . '.' . $row['ext'];
	if ($action_id) {$file = 'action_' . $file;}
	$path = $upload_path . $row['year'] . '/' . $file;
	if (file_exists($path)) {download($path, $file, 'application/octet-stream');} else {exit('file missing');}
}

// from maintenance/cleanup
if (isset($_GET['year']) && $_GET['year'] && is_numeric($_GET['year']) && isset($_GET['file']) && $_GET['file'] && $_SESSION['contact']['access'] == 'admin')
{
	$year = (int) substr(preg_replace('/[^0-9]/', '', $_GET['year']), 0, 4);
	$file = basename($_GET['file']);
	$path = realpath($upload_path . $year . '/' . $file);
	if (file_exists($path)) {download($path, $file, 'application/octet-stream');} else {exit('file missing');}
}
?>
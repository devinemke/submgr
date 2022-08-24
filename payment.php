<?php
include('inc_common.php');
get_payment_vars();

if (!isset($payment_vars['in']) || (isset($payment_vars['in']) && !$payment_vars['in'])) {exit('payment_vars not set');}

$fields = array(
'submission_id' => '',
'result_code' => '',
'hash' => ''
);

foreach ($payment_vars['in'] as $value)
{
	foreach ($fields as $sub_key => $sub_value)
	{
		if ($value['value'] == '$' . $sub_key) {$fields[$sub_key] = $value['name']; break;}
	}
}

foreach ($fields as $key => $value)
{
	if (isset($_REQUEST[$value])) {$$key = $_REQUEST[$value];} else {exit('unauthorized access: ' . $key . ' not set');}
}

$hash_compare = get_hash($submission_id);
if ($hash_compare != $hash) {exit('unauthorized access: invalid hash');}

$ipn = false;
if (isset($_POST['ipn_track_id'])) {$ipn = true;}
// if (isset($_POST['txn_id'])) {$ipn = true;}

if ($ipn)
{
	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=' . urlencode('_notify-validate');

	foreach ($_POST as $key => $value)
	{
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
	}

	// $url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	$url = 'https://www.paypal.com/cgi-bin/webscr';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: www.paypal.com'));
	$res = curl_exec($ch);
	curl_close($ch);

	// assign posted variables to local variables
	$payment_status = $_POST['payment_status'];
	if (isset($_POST['invoice'])) {$submission_id = $_POST['invoice'];}

	if (strcmp($res, 'VERIFIED') == 0)
	{
		// check the payment_status is Completed
		// check that txn_id has not been previously processed
		// check that receiver_email is your Primary PayPal email
		// check that payment_amount/payment_currency are correct
		// process payment
		if ($payment_status == 'Completed') {$result_code = 'Completed';}
	}
	elseif (strcmp($res, 'INVALID') == 0)
	{
		// log for manual investigation
		exit('invalid IPN response');
	}
}

$sql = 'SELECT date_paid FROM submissions WHERE submission_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $submission_id);
$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit('query failure: SELECT submissions payment');
if (!mysqli_num_rows($result)) {exit('invalid submission_id');}
$row = mysqli_fetch_assoc($result);

if (!$row['date_paid'] && strtolower($result_code) == strtolower($config['success_result_code']))
{
	$sql = "UPDATE submissions SET date_paid = '$gm_date' WHERE submission_id = " . mysqli_real_escape_string($GLOBALS['db_connect'], $submission_id);
	$result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit('query failure: UPDATE submissions payment');
}
else
{
	// $sql = 'UPDATE submissions SET date_paid = NULL WHERE submission_id = ' . mysqli_real_escape_string($GLOBALS['db_connect'], $submission_id);
	// $result = @mysqli_query($GLOBALS['db_connect'], $sql) or exit('query failure: UPDATE submissions payment');
}

if ($ipn)
{
	header('HTTP/1.1 200 OK');
}
else
{
	$app_url_header = $app_url_slash;
	$app_url_header .= 'index.php?result_code=' . urlencode($result_code);
	if (isset($_REQUEST['email']) && $_REQUEST['email']) {$app_url_header .= '&email=' . urlencode($_REQUEST['email']);}
	header('location: ' . $app_url_header);
}

exit();
?>
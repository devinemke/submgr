<?php
$payment_vars_presets['PayPal_Payments_Standard']['sql'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "cmd", "_xclick"),
(2, "out", "business", "paypal@example.com"),
(3, "out", "item_name", "Example Submission"),
(4, "out", "invoice", "$submission_id"),
(5, "out", "notify_url", "https://www.example.com/payment.php"),
(6, "out", "return", "https://www.example.com/payment.php"),
(7, "out", "rm", "2"),
(8, "out", "first_name", "$first_name"),
(9, "out", "last_name", "$last_name"),
(10, "out", "email", "$email"),
(11, "out", "address1", "$address1"),
(12, "out", "address2", "$address2"),
(13, "out", "city", "$city"),
(14, "out", "state", "$state"),
(15, "out", "zip", "$zip"),
(16, "out", "country", "$country"),
(17, "out", "amount", "$price"),
(18, "out", "currency_code", "USD"),
(19, "out", "custom", "$hash"),
(20, "in", "invoice", "$submission_id"),
(21, "in", "payment_status", "$result_code"),
(22, "in", "custom", "$hash");';

$payment_vars_presets['PayPal_Payments_Standard']['config'] = array(
'redirect_url TEST' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
'redirect_url LIVE' => 'https://www.paypal.com/cgi-bin/webscr',
'payment_redirect_method' => 'GET',
'success_result_code' => 'Completed',
'cc_exp_date_format' => 'MMYYYY',
'show_date_paid' => 'Y',
'show_payment_fields' => ''
);


$payment_vars_presets['PayPal_Payments_Pro_NVP']['sql'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "METHOD", "DoDirectPayment"),
(2, "out", "VERSION", "51.0"),
(3, "out", "USER", "PayPal_NVP_username"),
(4, "out", "PWD", "PayPal_NVP_password"),
(5, "out", "SIGNATURE", "PayPal_NVP_signature"),
(6, "out", "PAYMENTACTION", "Sale"),
(7, "out", "FIRSTNAME", "$first_name"),
(8, "out", "LASTNAME", "$last_name"),
(9, "out", "EMAIL", "$email"),
(10, "out", "STREET", "$address1"),
(11, "out", "STREET2", "$address2"),
(12, "out", "CITY", "$city"),
(13, "out", "STATE", "$state"),
(14, "out", "ZIP", "$zip"),
(15, "out", "COUNTRYCODE", "$country"),
(16, "out", "SHIPTOPHONENUM", "$phone"),
(17, "out", "ACCT", "$cc_number"),
(18, "out", "EXPDATE", "$cc_exp_date"),
(19, "out", "CVV2", "$cc_csc"),
(20, "out", "AMT", "$price"),
(21, "out", "CURRENCYCODE", "USD"),
(22, "out", "INVNUM", "$submission_id"),
(23, "out", "CUSTOM", "$submission_id"),
(24, "out", "DESC", "$genre_id"),
(25, "in", "ACK", "$result_code"),
(26, "in", "L_ERRORCODE0", "$error"),
(27, "in", "L_SHORTMESSAGE0", "$error"),
(28, "in", "L_LONGMESSAGE0", "$error");';

$payment_vars_presets['PayPal_Payments_Pro_NVP']['config'] = array(
'redirect_url TEST' => 'https://api-3t.sandbox.paypal.com/nvp',
'redirect_url LIVE' => 'https://api-3t.paypal.com/nvp',
'payment_redirect_method' => 'cURL',
'success_result_code' => 'Success|SuccessWithWarning',
'cc_exp_date_format' => 'MMYYYY',
'show_date_paid' => 'Y',
'show_payment_fields' => 'Y'
);


$payment_vars_presets['PayPal_Payments_Pro_REST']['sql'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "PayPal_REST_clientID", "PayPal_REST_clientID"),
(2, "out", "PayPal_REST_secret", "PayPal_REST_secret"),
(3, "out", "custom_id", "$submission_id"),
(4, "out", "description", "Example Submission"),
(5, "out", "currency_code", "USD"),
(6, "out", "value", "$price"),
(7, "out", "given_name", "$first_name"),
(8, "out", "surname", "$last_name"),
(9, "out", "email_address", "$email"),
(10, "out", "address_line_1", "$address1"),
(11, "out", "address_line_2", "$address2"),
(12, "out", "admin_area_2", "$city"),
(13, "out", "admin_area_1", "$state"),
(14, "out", "postal_code", "$zip"),
(15, "out", "country_code", "$country"),
(16, "out", "number", "$cc_number"),
(17, "out", "expiry", "$cc_exp_date"),
(18, "out", "security_code", "$cc_csc"),
(19, "in", "status", "$result_code"),
(20, "in", "error", "$error");';

$payment_vars_presets['PayPal_Payments_Pro_REST']['config'] = array(
'redirect_url TEST' => 'https://api-m.sandbox.paypal.com',
'redirect_url LIVE' => 'https://api-m.paypal.com',
'payment_redirect_method' => 'cURL',
'success_result_code' => 'COMPLETED',
'cc_exp_date_format' => 'YYYY-MM',
'show_date_paid' => 'Y',
'show_payment_fields' => 'Y'
);


$payment_vars_presets['PayPal_Payflow_Link']['sql'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "LOGIN", "login"),
(2, "out", "PARTNER", "partner"),
(3, "out", "TYPE", "S"),
(4, "out", "METHOD", "CC"),
(5, "out", "ORDERFORM", "false"),
(6, "out", "SHOWCONFIRM", "false"),
(7, "out", "SILENTTRAN", "false"),
(8, "out", "CUSTID", "$contact_id"),
(9, "out", "INVOICE", "$submission_id"),
(10, "out", "NAME", "$name"),
(11, "out", "EMAIL", "$email"),
(12, "out", "ADDRESS", "$address1"),
(13, "out", "CITY", "$city"),
(14, "out", "STATE", "$state"),
(15, "out", "ZIP", "$zip"),
(16, "out", "COUNTRY", "$country"),
(17, "out", "PHONE", "$phone"),
(18, "out", "CARDNUM", "$cc_number"),
(19, "out", "EXPDATE", "$cc_exp_date"),
(20, "out", "CSC", "$cc_csc"),
(21, "out", "AMOUNT", "$price"),
(22, "out", "DESCRIPTION", "$genre_id"),
(23, "out", "USER4", "$hash"),
(24, "in", "INVOICE", "$submission_id"),
(25, "in", "RESULT", "$result_code"),
(26, "in", "USER4", "$hash");';

$payment_vars_presets['PayPal_Payflow_Link']['config'] = array(
'redirect_url TEST' => 'https://pilot-payflowlink.paypal.com',
'redirect_url LIVE' => 'https://payflowlink.paypal.com',
'payment_redirect_method' => 'POST',
'success_result_code' => '0',
'cc_exp_date_format' => 'MMYYYY',
'show_date_paid' => 'Y',
'show_payment_fields' => 'Y'
);


$payment_vars_presets['TouchNet_uPay']['sql'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "UPAY_SITE_ID", "UPAY_SITE_ID"),
(2, "out", "EXT_TRANS_ID", "$submission_id"),
(3, "out", "EXT_TRANS_ID_LABEL", "Example Submission"),
(4, "out", "AMT", "$price"),
(5, "out", "BILL_NAME", "$name"),
(6, "out", "BILL_EMAIL_ADDRESS", "$email"),
(7, "out", "BILL_STREET1", "$address1"),
(8, "out", "BILL_STREET2", "$address2"),
(9, "out", "BILL_CITY", "$city"),
(10, "out", "BILL_STATE", "$state"),
(11, "out", "BILL_POSTAL_CODE", "$zip"),
(12, "out", "BILL_COUNTRY", "$country"),
(13, "out", "SUCCESS_LINK", "https://www.example.com"),
(14, "out", "SUCCESS_LINK_TEXT", "return to Example"),
(15, "out", "hash", "$hash"),
(16, "in", "EXT_TRANS_ID", "$submission_id"),
(17, "in", "pmt_status", "$result_code"),
(18, "in", "hash", "$hash");';

$payment_vars_presets['TouchNet_uPay']['config'] = array(
'redirect_url TEST' => 'https://secure.touchnet.com/C21797_upay/ext_site_test.jsp',
'redirect_url LIVE' => 'https://secure.touchnet.net/UPAY_SITE_ID_upay/web/index.jsp',
'payment_redirect_method' => 'POST',
'success_result_code' => 'success',
'cc_exp_date_format' => 'MMYYYY',
'show_date_paid' => 'Y',
'show_payment_fields' => ''
);


$payment_vars_presets['AuthorizeNet']['sql'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "name", "AuthorizeNet_APILoginID"),
(2, "out", "transactionKey", "AuthorizeNet_TransactionKey"),
(3, "out", "refId", "$submission_id"),
(4, "out", "amount", "$price"),
(5, "out", "cardNumber", "$cc_number"),
(6, "out", "expirationDate", "$cc_exp_date"),
(7, "out", "cardCode", "$cc_csc"),
(8, "out", "customer_id", "$contact_id"),
(9, "out", "email", "$email"),
(10, "out", "firstName", "$first_name"),
(11, "out", "lastName", "$last_name"),
(12, "out", "company", "$company"),
(13, "out", "address", "$address1"),
(14, "out", "city", "$city"),
(15, "out", "state", "$state"),
(16, "out", "zip", "$zip"),
(17, "out", "country", "$country"),
(18, "in", "responseCode", "$result_code"),
(19, "in", "errorCode", "$error"),
(20, "in", "errorText", "$error");';

$payment_vars_presets['AuthorizeNet']['config'] = array(
'redirect_url TEST' => 'https://apitest.authorize.net/xml/v1/request.api',
'redirect_url LIVE' => 'https://api.authorize.net/xml/v1/request.api',
'payment_redirect_method' => 'cURL',
'success_result_code' => '1',
'cc_exp_date_format' => 'YYYY-MM',
'show_date_paid' => 'Y',
'show_payment_fields' => 'Y'
);
?>
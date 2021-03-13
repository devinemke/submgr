<?php
// PayPal Payments Standard
// redirect_url: GET https://www.paypal.com/cgi-bin/webscr
// success_result_code: payment_status = Completed
$payment_vars_presets['paypal_payments_standard'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "cmd", "_xclick"),
(2, "out", "business", "paypal@example.com"),
(3, "out", "item_name", "Submission"),
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


// PayPal Payments Pro
// redirect_url: cURL https://api-3t.paypal.com/nvp
// success_result_code: ACK = Success|SuccessWithWarning
$payment_vars_presets['paypal_payments_pro'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
(1, "out", "METHOD", "DoDirectPayment"),
(2, "out", "VERSION", "51.0"),
(3, "out", "USER", "API_UserName"),
(4, "out", "PWD", "API_Password"),
(5, "out", "SIGNATURE", "API_Signature"),
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
(23, "out", "DESC", "$genre_id"),
(24, "out", "CUSTOM", "$submission_id"),
(25, "in", "ACK", "$result_code"),
(26, "in", "L_ERRORCODE0", "$error"),
(27, "in", "L_SHORTMESSAGE0", "$error"),
(28, "in", "L_LONGMESSAGE0", "$error");';


// PayPal Payflow Link
// redirect_url: POST https://payflowlink.paypal.com
// success_result_code: RESULT = 0
$payment_vars_presets['paypal_payflow_link'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
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


// TouchNet uPay
// redirect_url: POST https://secure.touchnet.net/UPAY_SITE_ID_upay/web/index.jsp
// success_result_code: pmt_status = success
$payment_vars_presets['touchnet_upay'] = 'INSERT INTO `payment_vars` (`payment_var_id`, `direction`, `name`, `value`) VALUES
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
?>
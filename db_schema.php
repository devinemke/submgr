<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

$schema = array(

'actions' => array(
	'fields' => array(
		'action_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'date_time' => array('type' => 'datetime', 'extra' => 'DEFAULT NULL'),
		'timestamp' => array('type' => 'timestamp', 'extra' => 'NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
		'submission_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'reader_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'action_type_id' => array('type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'),
		'receiver_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'ext' => array('type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'),
		'notes' => array('type' => 'text', 'extra' => 'DEFAULT NULL'),
		'message' => array('type' => 'text', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'action_id' => array('type' => 'PRIMARY KEY', 'fields' => 'action_id'),
		'submission_id' => array('type' => 'KEY', 'fields' => 'submission_id'),
		'type_receiver' => array('type' => 'KEY', 'fields' => 'action_type_id,receiver_id'),
		'notes_message' => array('type' => 'FULLTEXT', 'fields' => 'notes,message')
	)
	),

'action_types' => array(
	'fields' => array(
		'action_type_id' => array('type' => 'tinyint(3) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'name' => array('type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'),
		'description' => array('type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'),
		'status' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'active' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL'),
		'access_groups' => array('type' => 'set(\'1\',\'2\',\'3\',\'4\',\'5\')', 'extra' => 'DEFAULT NULL'),
		'from_reader' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL'),
		'subject' => array('type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'),
		'body' => array('type' => 'text', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'action_type_id' => array('type' => 'PRIMARY KEY', 'fields' => 'action_type_id')
	)
	),

'config' => array(
	'fields' => array(
		'name' => array('type' => 'varchar(50)', 'extra' => 'NOT NULL DEFAULT \'\''),
		'value' => array('type' => 'text', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'name' => array('type' => 'PRIMARY KEY', 'fields' => 'name')
	)
	),

'contacts' => array(
	'fields' => array(
		'contact_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'date_time' => array('type' => 'datetime', 'extra' => 'DEFAULT NULL'),
		'timestamp' => array('type' => 'timestamp', 'extra' => 'NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
		'first_name' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'last_name' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'email' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'company' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'address1' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'address2' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'city' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'state' => array('type' => 'char(2)', 'extra' => 'DEFAULT NULL'),
		'zip' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'country' => array('type' => 'char(3)', 'extra' => 'DEFAULT NULL'),
		'phone' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'password' => array('type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'),
		'mailing_list' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL'),
		'access' => array('type' => 'enum(\'admin\',\'editor\',\'active 1\',\'active 2\',\'active 3\',\'active 4\',\'active 5\',\'inactive\',\'blocked\')', 'extra' => 'DEFAULT NULL'),
		'email_notification' => array('type' => 'set(\'submissions\',\'actions\',\'updates\')', 'extra' => 'DEFAULT NULL'),
		'notes' => array('type' => 'text', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'contact_id' => array('type' => 'PRIMARY KEY', 'fields' => 'contact_id'),
		'email' => array('type' => 'KEY', 'fields' => 'email'),
		'access' => array('type' => 'KEY', 'fields' => 'access'),
		'email_notification' => array('type' => 'KEY', 'fields' => 'email_notification'),
		'first_name_last_name_email' => array('type' => 'FULLTEXT', 'fields' => 'first_name,last_name,email')
	)
	),

'file_types' => array(
	'fields' => array(
		'ext' => array('type' => 'varchar(10)', 'extra' => 'NOT NULL DEFAULT \'\'')
	),
	'indexes' => array(
		'ext' => array('type' => 'PRIMARY KEY', 'fields' => 'ext')
	)
	),

'fields' => array(
	'fields' => array(
		'field' => array('type' => 'varchar(20)', 'extra' => 'NOT NULL DEFAULT \'\''),
		'name' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'type' => array('type' => 'enum(\'text\',\'select\',\'password\',\'checkbox\',\'file\',\'textarea\')', 'extra' => 'DEFAULT NULL'),
		'section' => array('type' => 'enum(\'contact\',\'submission\',\'payment\')', 'extra' => 'DEFAULT NULL'),
		'value' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'maxlength' => array('type' => 'int(10) unsigned', 'extra' => 'DEFAULT NULL'),
		'enabled' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL'),
		'required' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'field' => array('type' => 'PRIMARY KEY', 'fields' => 'field')
	)
	),


'genres' => array(
	'fields' => array(
		'genre_id' => array('type' => 'tinyint(3) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'name' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'submission_limit' => array('type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'),
		'redirect_url' => array('type' => 'text', 'extra' => 'DEFAULT NULL'),
		'price' => array('type' => 'decimal(6,2) unsigned', 'extra' => 'DEFAULT NULL'),
		'active' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL'),
		'blind' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'genre_id' => array('type' => 'PRIMARY KEY', 'fields' => 'genre_id')
	)
	),

'groups' => array(
	'fields' => array(
		'name' => array('type' => 'varchar(10)', 'extra' => 'NOT NULL DEFAULT \'\''),
		'allowed_forwards' => array('type' => 'set(\'admin\',\'editor\',\'1\',\'2\',\'3\',\'4\',\'5\')', 'extra' => 'DEFAULT NULL'),
		'blind' => array('type' => 'char(1)', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'name' => array('type' => 'PRIMARY KEY', 'fields' => 'name')
	)
	),

'payment_vars' => array(
	'fields' => array(
		'payment_var_id' => array('type' => 'tinyint(3) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'direction' => array('type' => 'enum(\'out\',\'in\')', 'extra' => 'DEFAULT NULL'),
		'name' => array('type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'),
		'value' => array('type' => 'varchar(255)', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'payment_var_id' => array('type' => 'PRIMARY KEY', 'fields' => 'payment_var_id')
	)
	),

'resets' => array(
	'fields' => array(
		'reset_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'date_time' => array('type' => 'datetime', 'extra' => 'DEFAULT NULL'),
		'contact_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'token' => array('type' => 'char(40)', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'reset_id' => array('type' => 'PRIMARY KEY', 'fields' => 'reset_id'),
		'date_time' => array('type' => 'KEY', 'fields' => 'date_time'),
		'contact_id' => array('type' => 'KEY', 'fields' => 'contact_id'),
		'token' => array('type' => 'KEY', 'fields' => 'token')
	)
	),

'submissions' => array(
	'fields' => array(
		'submission_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'),
		'date_time' => array('type' => 'datetime', 'extra' => 'DEFAULT NULL'),
		'timestamp' => array('type' => 'timestamp', 'extra' => 'NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
		'date_paid' => array('type' => 'date', 'extra' => 'DEFAULT NULL'),
		'submitter_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'writer' => array('type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'),
		'title' => array('type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'),
		'genre_id' => array('type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'),
		'ext' => array('type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'),
		'comments' => array('type' => 'text', 'extra' => 'DEFAULT NULL'),
		'notes' => array('type' => 'text', 'extra' => 'DEFAULT NULL'),
		'last_action_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'last_reader_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'),
		'last_action_type_id' => array('type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'),
		'last_receiver_id' => array('type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL')
	),
	'indexes' => array(
		'submission_id' => array('type' => 'PRIMARY KEY', 'fields' => 'submission_id'),
		'date_paid' => array('type' => 'KEY', 'fields' => 'date_paid'),
		'submitter_id' => array('type' => 'KEY', 'fields' => 'submitter_id'),
		'genre_id' => array('type' => 'KEY', 'fields' => 'genre_id'),
		'last_action_type' => array('type' => 'KEY', 'fields' => 'last_action_type_id'),
		'last_action_type_receiver' => array('type' => 'KEY', 'fields' => 'last_action_type_id,last_receiver_id'),
		'writer_title_comments_notes' => array('type' => 'FULLTEXT', 'fields' => 'writer,title,comments,notes')
	)
	)

);
?>
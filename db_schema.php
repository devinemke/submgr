<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

$schema = [

'actions' => [
	'fields' => [
		'action_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'date_time' => ['type' => 'datetime', 'extra' => 'DEFAULT NULL'],
		'timestamp' => ['type' => 'timestamp', 'extra' => 'NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
		'submission_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'reader_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'action_type_id' => ['type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'],
		'receiver_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'ext' => ['type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'],
		'notes' => ['type' => 'text', 'extra' => 'DEFAULT NULL'],
		'message' => ['type' => 'text', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'action_id' => ['type' => 'PRIMARY KEY', 'fields' => 'action_id'],
		'submission_id' => ['type' => 'KEY', 'fields' => 'submission_id'],
		'type_receiver' => ['type' => 'KEY', 'fields' => 'action_type_id,receiver_id'],
		'notes_message' => ['type' => 'FULLTEXT', 'fields' => 'notes,message']
	]
	],

'action_types' => [
	'fields' => [
		'action_type_id' => ['type' => 'tinyint(3) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'name' => ['type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'],
		'description' => ['type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'],
		'status' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'active' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL'],
		'access_groups' => ['type' => 'set(\'1\',\'2\',\'3\',\'4\',\'5\')', 'extra' => 'DEFAULT NULL'],
		'from_reader' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL'],
		'subject' => ['type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'],
		'body' => ['type' => 'text', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'action_type_id' => ['type' => 'PRIMARY KEY', 'fields' => 'action_type_id']
	]
	],

'config' => [
	'fields' => [
		'name' => ['type' => 'varchar(50)', 'extra' => 'NOT NULL DEFAULT \'\''],
		'value' => ['type' => 'text', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'name' => ['type' => 'PRIMARY KEY', 'fields' => 'name']
	]
	],

'contacts' => [
	'fields' => [
		'contact_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'date_time' => ['type' => 'datetime', 'extra' => 'DEFAULT NULL'],
		'timestamp' => ['type' => 'timestamp', 'extra' => 'NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
		'first_name' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'last_name' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'email' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'company' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'address1' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'address2' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'city' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'state' => ['type' => 'char(2)', 'extra' => 'DEFAULT NULL'],
		'zip' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'country' => ['type' => 'char(3)', 'extra' => 'DEFAULT NULL'],
		'phone' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'password' => ['type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'],
		'mailing_list' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL'],
		'access' => ['type' => 'enum(\'admin\',\'editor\',\'active 1\',\'active 2\',\'active 3\',\'active 4\',\'active 5\',\'inactive\',\'blocked\')', 'extra' => 'DEFAULT NULL'],
		'email_notification' => ['type' => 'set(\'submissions\',\'actions\',\'updates\')', 'extra' => 'DEFAULT NULL'],
		'notes' => ['type' => 'text', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'contact_id' => ['type' => 'PRIMARY KEY', 'fields' => 'contact_id'],
		'email' => ['type' => 'KEY', 'fields' => 'email'],
		'access' => ['type' => 'KEY', 'fields' => 'access'],
		'email_notification' => ['type' => 'KEY', 'fields' => 'email_notification'],
		'first_name_last_name_email' => ['type' => 'FULLTEXT', 'fields' => 'first_name,last_name,email']
	]
	],

'file_types' => [
	'fields' => [
		'ext' => ['type' => 'varchar(10)', 'extra' => 'NOT NULL DEFAULT \'\'']
	],
	'indexes' => [
		'ext' => ['type' => 'PRIMARY KEY', 'fields' => 'ext']
	]
	],

'fields' => [
	'fields' => [
		'field' => ['type' => 'varchar(20)', 'extra' => 'NOT NULL DEFAULT \'\''],
		'name' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'type' => ['type' => 'enum(\'text\',\'select\',\'password\',\'checkbox\',\'file\',\'textarea\')', 'extra' => 'DEFAULT NULL'],
		'section' => ['type' => 'enum(\'contact\',\'submission\',\'payment\')', 'extra' => 'DEFAULT NULL'],
		'value' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'maxlength' => ['type' => 'int(10) unsigned', 'extra' => 'DEFAULT NULL'],
		'enabled' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL'],
		'required' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'field' => ['type' => 'PRIMARY KEY', 'fields' => 'field']
	]
	],


'genres' => [
	'fields' => [
		'genre_id' => ['type' => 'tinyint(3) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'name' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'submission_limit' => ['type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'],
		'redirect_url' => ['type' => 'text', 'extra' => 'DEFAULT NULL'],
		'price' => ['type' => 'decimal(6,2) unsigned', 'extra' => 'DEFAULT NULL'],
		'active' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL'],
		'blind' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'genre_id' => ['type' => 'PRIMARY KEY', 'fields' => 'genre_id']
	]
	],

'groups' => [
	'fields' => [
		'name' => ['type' => 'varchar(10)', 'extra' => 'NOT NULL DEFAULT \'\''],
		'allowed_forwards' => ['type' => 'set(\'admin\',\'editor\',\'1\',\'2\',\'3\',\'4\',\'5\')', 'extra' => 'DEFAULT NULL'],
		'blind' => ['type' => 'char(1)', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'name' => ['type' => 'PRIMARY KEY', 'fields' => 'name']
	]
	],

'payment_vars' => [
	'fields' => [
		'payment_var_id' => ['type' => 'tinyint(3) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'direction' => ['type' => 'enum(\'out\',\'in\')', 'extra' => 'DEFAULT NULL'],
		'name' => ['type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'],
		'value' => ['type' => 'varchar(255)', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'payment_var_id' => ['type' => 'PRIMARY KEY', 'fields' => 'payment_var_id']
	]
	],

'resets' => [
	'fields' => [
		'reset_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'date_time' => ['type' => 'datetime', 'extra' => 'DEFAULT NULL'],
		'contact_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'token' => ['type' => 'char(40)', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'reset_id' => ['type' => 'PRIMARY KEY', 'fields' => 'reset_id'],
		'date_time' => ['type' => 'KEY', 'fields' => 'date_time'],
		'contact_id' => ['type' => 'KEY', 'fields' => 'contact_id'],
		'token' => ['type' => 'KEY', 'fields' => 'token']
	]
	],

'submissions' => [
	'fields' => [
		'submission_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'NOT NULL AUTO_INCREMENT'],
		'date_time' => ['type' => 'datetime', 'extra' => 'DEFAULT NULL'],
		'timestamp' => ['type' => 'timestamp', 'extra' => 'NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
		'date_paid' => ['type' => 'date', 'extra' => 'DEFAULT NULL'],
		'submitter_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'writer' => ['type' => 'varchar(50)', 'extra' => 'DEFAULT NULL'],
		'title' => ['type' => 'varchar(255)', 'extra' => 'DEFAULT NULL'],
		'genre_id' => ['type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'],
		'ext' => ['type' => 'varchar(10)', 'extra' => 'DEFAULT NULL'],
		'comments' => ['type' => 'text', 'extra' => 'DEFAULT NULL'],
		'notes' => ['type' => 'text', 'extra' => 'DEFAULT NULL'],
		'last_action_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'last_reader_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL'],
		'last_action_type_id' => ['type' => 'tinyint(3) unsigned', 'extra' => 'DEFAULT NULL'],
		'last_receiver_id' => ['type' => 'mediumint(8) unsigned', 'extra' => 'DEFAULT NULL']
	],
	'indexes' => [
		'submission_id' => ['type' => 'PRIMARY KEY', 'fields' => 'submission_id'],
		'date_paid' => ['type' => 'KEY', 'fields' => 'date_paid'],
		'submitter_id' => ['type' => 'KEY', 'fields' => 'submitter_id'],
		'genre_id' => ['type' => 'KEY', 'fields' => 'genre_id'],
		'last_action_type' => ['type' => 'KEY', 'fields' => 'last_action_type_id'],
		'last_action_type_receiver' => ['type' => 'KEY', 'fields' => 'last_action_type_id,last_receiver_id'],
		'writer_title_comments_notes' => ['type' => 'FULLTEXT', 'fields' => 'writer,title,comments,notes']
	]
	]

];
?>
<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

if (isset($config['font_size']) && $config['font_size']) {$font_size = $config['font_size'];} else {$font_size = 10;}
$font_size_plus = $font_size + 2;
$font_size_plus10 = $font_size + 10;
$font_size_minus = $font_size - 2;

echo '
<style>

html
{
	box-sizing: border-box;
}

*, *:before, *:after
{
	box-sizing: inherit;
}

body
{
	color: ' . $config['color_text'] . ';
	background-color: ' . $config['color_background'] . ';
	font-size: ' . $font_size . 'pt;
	font-family: ' . $config['fonts'] . ';
}

a
{
	color: ' . $config['color_link'] . ';
	text-decoration: none;
}

a:hover
{
	color: ' . $config['color_link_hover'] . ';
	background-color: ' . $config['color_background'] . ';
	text-decoration: underline;
}

hr
{
	height: 1px;
	background-color: ' . $config['color_text'] . ';
	border: none;
}

img
{
	border: none;
}

table
{
	font-size: ' . $font_size . 'pt;
}

tr
{
	vertical-align: top;
}

.header
{
	font-size: ' . $font_size_plus . 'pt;
	font-weight: bold;
}

.padding_lr_5 td
{
	padding: 0px 5px 0px 5px;
}

.row_left
{
	text-align: right;
	padding-right: 5px;
	white-space: nowrap;
}

.table_list
{
	border: 0px;
	width: 100%;
	font-size: ' . $font_size_minus . 'pt;
	font-weight: bold;
	text-align: center;
}

.table_list tr:hover
{
	background-color: ' . $config['color_foreground'] . ';
}

.table_list tr.transparent_row
{
	background-color: ' . $config['color_background'] . ';
}

.table_list th
{
	padding: 5px;
	vertical-align: top;
	background-color: ' . $config['color_foreground'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
}

.table_list td
{
	padding: 5px;
	vertical-align: top;
	border: 2px solid ' . $config['color_foreground'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
}

form
{
	margin: 0px;
}

input[type="text"], input[type="password"], input[type="file"]
{
	color: ' . $config['color_text'] . ';
	background-color: ' . $config['color_form'] . ';
	font-family: ' . $config['fonts'] . ';
	border: 1px solid ' . $config['color_text'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
	width: 300px;
}

input[type="checkbox"], input[type="radio"]
{
	background-color: transparent;
	border: 0px;
	width: auto;
}

select
{
	color: ' . $config['color_text'] . ';
	background-color: ' . $config['color_form'] . ';
	font-family: ' . $config['fonts'] . ';
	border: 1px solid ' . $config['color_text'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
	width: 300px;
}

textarea
{
	color: ' . $config['color_text'] . ';
	background-color: ' . $config['color_form'] . ';
	font-family: ' . $config['fonts'] . ';
	border: 1px solid ' . $config['color_text'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
	width: 300px;
	height: 100px;
}

.form_button
{
	color: ' . $config['color_text'] . ';
	background-color: ' . $config['color_form'] . ';
	font-family: ' . $config['fonts'] . ';
	border: 2px solid ' . $config['color_text'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
	width: 100px;
}

input:disabled, select:disabled, textarea:disabled, .form_button:disabled
{
	color: Grey;
	background-color: LightGrey;
}

input.error, select.error, textarea.error
{
	border: 1px solid red;
}

label.error
{
	color: red;
}

.foreground
{
	background-color: ' . $config['color_foreground'] . ';
	border-radius: ' . $config['border_radius'] . 'px;
}

.notice
{
	color: red;
	font-weight: bold;
}

.notice_row
{
	outline: 1px solid red; outline-offset: -2px;
}

.small
{
	font-size: ' . $font_size_minus . 'pt;
}

.nav_list
{
	margin: 0px 0px 0px -25px;
}
';

if ($page == 'login' && ($module == 'account' || $module == 'submissions' || $module == 'maintenance'))
{
	echo '
	iframe
	{
		border: none;
	}

	#popframe
	{
		flex: 1 1 auto;
		overflow: auto;
		padding: 2px;
	}

	#background
	{
		display: none;
		position: fixed;
		top: 0%;
		left: 0%;
		width: 100%;
		height: 100%;
		background-color: black;
		opacity: .50;
		z-index: 1001;
	}

	#foreground
	{
		display: none;
		position: fixed;
		top: auto;
		left: auto;
		width: auto;
		height: auto;
		padding: 5px;
		border: 2px solid ' . $config['color_text'] . ';
		border-radius: ' . $config['border_radius'] . 'px;
		background-color: ' . $config['color_background'] . ';
		z-index: 1002;
		overflow: auto;
		resize: both;
	}
	';
}

if ($page == 'login' && ($module == 'account' || $module == 'submissions' || $module == 'contacts' || $module == 'reports'))
{
	echo '
	#tooltip_div
	{
		position: absolute;
		color: ' . $config['color_text'] . ';
		background-color: ' . $config['color_foreground'] . ';
		font-family: ' . $config['fonts'] . ';
		padding: 3px;
		border: 2px solid ' . $config['color_text'] . ';
		border-radius: ' . $config['border_radius'] . 'px;
		line-height: 18px;
		z-index: 100;
		visibility: hidden;
	}
	';
}

$custom = 'custom.css';
if (@file_exists($custom)) {include($custom);}

echo '
</style>
';
?>
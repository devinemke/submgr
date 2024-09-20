<?php
if (count(get_included_files()) == 1) {header('location: http://' . $_SERVER['HTTP_HOST']); exit();}

echo '
<script nonce="' . $GLOBALS['nonce'] . '">

var page = "' . $page . '";
var module = "' . $module . '";
var submodule = "' . $submodule . '";
var submit = "' . $submit_js . '";

document.addEventListener("DOMContentLoaded", function()
{
	var mail_to_links = document.getElementsByClassName("mail_to_link");
	for (i = 0; i < mail_to_links.length; i++)
	{
		var email = mail_to_links[i].innerHTML;
		split1 = email.split("[at]");
		split1 = split1.map(e => e.trim());
		split2 = split1[1].split("[dot]");
		split2 = split2.map(e => e.trim());
		email = split1[0] + "@" + split2[0] + "." + split2[1];
		mail_to_links[i].innerHTML = "<a href=\"mail" + "to:" + email + "\">" + email + "</a>";
	}

	var form_buttons = document.getElementsByClassName("form_button");
	for (i = 0; i < form_buttons.length; i++)
	{
		(function()
		{
			var button_value = form_buttons[i].value;
			form_buttons[i].addEventListener("click", function(event) { submit_clicked = button_value; });
		})();
	}
});

function email_check(email)
{
	var AtSym = email.value.indexOf("@");
	var Period = email.value.lastIndexOf(".");
	var Space = email.value.indexOf(" ");
	var Length = email.value.length - 1;

	if (AtSym < 1 || Period <= AtSym + 1 || Period == Length || Space != -1) {alert("ERROR: Invalid email address\n\n" + email.value); return false;}

	return true;
}

function password_check(password)
{
	var password_length_min = ' . $password_length_min . ';
	var password_length_max = ' . $password_length_max . ';

	if (password.value.length < password_length_min || password.value.length > password_length_max) {alert("ERROR: Passwords must be " + password_length_min + "-" + password_length_max + " characters"); return false;}
	if (password.value.indexOf(" ") >= 0) {alert("ERROR: Passwords cannot contain spaces"); return false;}

	return true;
}

function disable_submit(arg)
{
	// this must be triggered by "submit" (not "click") or else the form will not be submitted

	var form_name = document.getElementById(arg).form.name;

	var input_submit = document.createElement("input");
	input_submit.setAttribute("type", "hidden");
	input_submit.setAttribute("id", arg + "_hidden");
	input_submit.setAttribute("name", "submit");
	input_submit.setAttribute("value", submit_clicked);
	document.getElementById(form_name).appendChild(input_submit);

	var input_hash = document.createElement("input");
	input_hash.setAttribute("type", "hidden");
	input_hash.setAttribute("id", arg + "_hash");
	input_hash.setAttribute("name", "form_hash");
	input_hash.setAttribute("value", "' . $_SESSION['csrf_token'] . '");
	document.getElementById(form_name).appendChild(input_hash);

	var button_value = "please wait...";
	document.getElementById(arg).disabled = true;
	document.getElementById(arg).value = button_value;
	document.getElementById(arg).textContent = button_value;
}

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

if ($display_login || $page == 'install')
{
	echo '
	function form_login_check()
	{
		var form_check = true;
		var error = "";

		document.getElementById("login_email").value = document.getElementById("login_email").value.trim();

		if (document.getElementById("login_email").value == "")
		{
			document.getElementById("login_email").className = "error";
			document.getElementById("label_login_email").className = "error";
			error += "Please enter your email address.\n";
			form_check = false;
		}
		else
		{
			document.getElementById("login_email").className = "";
			document.getElementById("label_login_email").className = "";
		}

		if (document.getElementById("login_password").value == "")
		{
			document.getElementById("login_password").className = "error";
			document.getElementById("label_login_password").className = "error";
			error += "Please enter your password.";
			form_check = false;
		}
		else
		{
			document.getElementById("login_password").className = "";
			document.getElementById("label_login_password").className = "";
		}

		if (!form_check)
		{
			alert(error);
			return false;
		}

		if (!email_check(document.getElementById("login_email")))
		{
			document.getElementById("login_email").className = "error";
			document.getElementById("label_login_email").className = "error";
			return false;
		}

		if (!password_check(document.getElementById("login_password")))
		{
			document.getElementById("login_password").className = "error";
			document.getElementById("label_login_password").className = "error";
			return false;
		}

		disable_submit("form_login_submit");
		return true;
	}

	event_listener("submit", "form_login", function(event) { if (!form_login_check()) {event.preventDefault();} });
	';
}

if ($page == 'install')
{
	echo '
	function form_install_check()
	{
		var form_check = true;
		var error = "ERROR: Required fields missing";

		for (i = 0; i < document.getElementById("form_install").length; i++)
		{
			id = document.getElementById("form_install").elements[i].id;

			if (document.getElementById(id).name != "config_db[password]" && document.getElementById(id).name != "config_db[port]" && !document.getElementById(id).value)
			{
				document.getElementById(id).className = "error";
				document.getElementById("label_" + id).className = "error";
				form_check = false;
			}
			else if (document.getElementById(id).type == "text" || document.getElementById(id).type == "password")
			{
				document.getElementById(id).className = "";
				document.getElementById("label_" + id).className = "";
			}
		}

		if (!form_check)
		{
			alert(error);
			return false;
		}

		if (document.getElementById("admin_email") && document.getElementById("admin_password"))
		{
			document.getElementById("admin_email").value = document.getElementById("admin_email").value.trim();

			if (!email_check(document.getElementById("admin_email")))
			{
				document.getElementById("admin_email").className = "error";
				document.getElementById("label_admin_email").className = "error";
				return false;
			}

			if (!password_check(document.getElementById("admin_password")))
			{
				document.getElementById("admin_password").className = "error";
				document.getElementById("label_admin_password").className = "error";
				return false;
			}
		}

		disable_submit("form_install_submit");
		return true;
	}

	event_listener("submit", "form_install", function(event) { if (!form_install_check()) {event.preventDefault();} });
	';
}

if ($page == 'help')
{
	echo '
	function form_reset_check()
	{
		document.getElementById("reset_email").value = document.getElementById("reset_email").value.trim();

		if (!document.getElementById("reset_email").value)
		{
			document.getElementById("reset_email").className = "error";
			document.getElementById("label_reset_email").className = "error";
			alert("ERROR: Please enter email address");
			return false;
		}
		else
		{
			if (!email_check(document.getElementById("reset_email")))
			{
				document.getElementById("reset_email").className = "error";
				document.getElementById("label_reset_email").className = "error";
				return false;
			}
		}

		disable_submit("form_reset_submit");
		return true;
	}

	event_listener("submit", "form_reset", function(event) { if (!form_reset_check()) {event.preventDefault();} });
	';
}

if ($page == 'login' && isset($_SESSION['contact_reset'])) // needed here before $continue = false
{
	echo '
	function form_new_password_check()
	{
		var form_check = true;
		var error = "";

		if (!document.getElementById("password").value)
		{
			document.getElementById("password").className = "error";
			document.getElementById("label_password").className = "error";
			error += "ERROR: Please enter new password\n"
			form_check = false;
		}
		else
		{
			document.getElementById("password").className = "";
			document.getElementById("label_password").className = "";
		}

		if (!document.getElementById("password2").value)
		{
			document.getElementById("password2").className = "error";
			document.getElementById("label_password2").className = "error";
			error += "ERROR: Please confirm new password\n"
			form_check = false;
		}
		else
		{
			document.getElementById("password2").className = "";
			document.getElementById("label_password2").className = "";
		}

		if (!form_check)
		{
			alert(error);
			return false;
		}

		if (document.getElementById("password").value && document.getElementById("password2").value)
		{
			if (!password_check(document.getElementById("password")))
			{
				document.getElementById("password").className = "error";
				document.getElementById("label_password").className = "error";
				return false;
			}

			if (!password_check(document.getElementById("password2")))
			{
				document.getElementById("password2").className = "error";
				document.getElementById("label_password2").className = "error";
				return false;
			}

			if (document.getElementById("password").value != document.getElementById("password2").value)
			{
				document.getElementById("password").className = "error";
				document.getElementById("label_password").className = "error";
				document.getElementById("password2").className = "error";
				document.getElementById("label_password2").className = "error";
				alert("ERROR: Passwords do not match");
				return false;
			}
		}

		disable_submit("form_new_password_submit");
		return true;
	}

	event_listener("submit", "form_new_password", function(event) { if (!form_new_password_check()) {event.preventDefault();} });
	';
}

if ($continue)
{
	if ($page == 'home' || ($page == 'login' && ($module == 'submit' || $module == 'update' || $module == 'pay_submission')))
	{
		echo '
		function form_main_check()
		{
			if (submit_clicked == "cancel")
			{
				document.getElementById("form_main").action = "' . $_SERVER['PHP_SELF'] . '?page=login&module=account";
				return true;
			}

			var form_check = true;
			var error = "ERROR: Required fields missing or incomplete:\n\n";
			var fields = new Object();
			';

			if ($page == 'home' || ($page == 'login' && $module == 'submit'))
			{
				// only echo file validation if it's the first time hitting submit, otherwise "file" will always have to be revalidated
				if (isset($_SESSION['file_upload']['filename']) || isset($_FILES['file']['name'])) {$fields['file']['required'] = '';}
			}

			if ($page == 'login' && $module == 'update')
			{
				$fields['password']['required'] = '';
				$fields['password2']['required'] = '';
			}

			// build the fields object
			foreach ($fields as $key => $value)
			{
				$value = array_map('json_encode', $value);
				echo 'fields["' . $key . '"] = {name: ' . $value['name'] . ', type: ' . $value['type'] . ', required: ' . $value['required'] . '};' . "\n";
			}

			echo '
			if (document.getElementById("country"))
			{
				if (document.getElementById("country").value == "USA")
				{
					if (document.getElementById("state")) {fields["state"]["required"] = "Y";}
					if (document.getElementById("zip")) {fields["zip"]["required"] = "Y";}
				}
				else
				{
					if (document.getElementById("state")) {fields["state"]["required"] = "";}
					if (document.getElementById("zip")) {fields["zip"]["required"] = "";}
				}
			}

			if (typeof price != "undefined" && typeof show_payment_fields != "undefined")
			{
				if (price && show_payment_fields)
				{
					fields["cc_number"]["required"] = "Y";
					fields["cc_exp_month"]["required"] = "Y";
					fields["cc_exp_year"]["required"] = "Y";
					fields["cc_csc"]["required"] = "Y";
				}
				else
				{
					fields["cc_number"]["required"] = "";
					fields["cc_exp_month"]["required"] = "";
					fields["cc_exp_year"]["required"] = "";
					fields["cc_csc"]["required"] = "";
				}
			}

			for (var key in fields)
			{
				if (document.getElementById(key) && document.getElementById(key).name != "password" && document.getElementById(key).name != "password2" && document.getElementById(key).name != "file") {document.getElementById(key).value = document.getElementById(key).value.trim();}

				if (document.getElementById(key) && !document.getElementById(key).value && fields[key]["required"])
				{
					document.getElementById(key).className = "error";
					document.getElementById("label_" + key).className = "error";
					error += fields[key]["name"] + "\n";
					form_check = false;
				}
				else if (document.getElementById(key))
				{
					document.getElementById(key).className = "";
					document.getElementById("label_" + key).className = "";
				}
			}

			if (!form_check)
			{
				alert(error);
				return false;
			}

			if (document.getElementById("email") && document.getElementById("email").value && !email_check(document.getElementById("email")))
			{
				document.getElementById("email").className = "error";
				document.getElementById("label_email").className = "error";
				return false;
			}

			if (document.getElementById("password") && document.getElementById("password").value && !password_check(document.getElementById("password")))
			{
				document.getElementById("password").className = "error";
				document.getElementById("label_password").className = "error";
				return false;
			}

			if (document.getElementById("password2") && document.getElementById("password2").value && !password_check(document.getElementById("password2")))
			{
				document.getElementById("password2").className = "error";
				document.getElementById("label_password2").className = "error";
				return false;
			}

			if (document.getElementById("password") && document.getElementById("password2") && document.getElementById("password").value != document.getElementById("password2").value)
			{
				document.getElementById("password").className = "error";
				document.getElementById("password2").className = "error";
				document.getElementById("label_password").className = "error";
				document.getElementById("label_password2").className = "error";
				alert("ERROR: Passwords do not match");
				return false;
			}

			if (document.getElementById("country") && document.getElementById("country").value == "USA")
			{
				if (document.getElementById("zip")) {document.getElementById("zip").value = document.getElementById("zip").value.replace(/[^0-9]/g, "");}
				if (document.getElementById("zip") && document.getElementById("zip").value.length < 5)
				{
					document.getElementById("zip").className = "error";
					document.getElementById("label_zip").className = "error";
					alert("ERROR: Incomplete zip code");
					return false;
				}
			}

			if (document.getElementById("cc_exp_month") && document.getElementById("cc_exp_year"))
			{
				var cc_exp_year = parseInt(document.getElementById("cc_exp_year").value);
				var cc_exp_month = parseInt(document.getElementById("cc_exp_month").value) - 1;
				var cc_exp_date = new Date(cc_exp_year, cc_exp_month);
				var current_date = new Date();
				if (cc_exp_date < current_date)
				{
					document.getElementById("cc_exp_year").className = "error";
					document.getElementById("cc_exp_month").className = "error";
					document.getElementById("label_cc_exp_year").className = "error";
					document.getElementById("label_cc_exp_month").className = "error";
					alert("ERROR: Expiration Date entered indicates that your credit card has expired.");
					return false;
				}
			}

			disable_submit("form_main_submit");
			return true;
		}

		event_listener("submit", "form_main", function(event) { if (!form_main_check()) {event.preventDefault();} });

		function form_confirmation_check()
		{
		';
			if (isset($use_captcha) && $use_captcha && isset($captcha_version) && $captcha_version == 2 && $submit == 'submit' && ($page == 'home' || ($page == 'login' && $module == 'submit')))
			{
				echo '
				var captcha_response = grecaptcha.getResponse();
				if (captcha_response.length == 0)
				{
					document.getElementById("g-recaptcha").style = "border: 2px solid red;";
					alert("ERROR: Please verify that you are not a robot.");
					return false;
				}
				else
				{
					disable_submit("form_confirmation_submit");
					return true;
				}
				';
			}
			else
			{
				echo '
				disable_submit("form_confirmation_submit");
				return true;
				';
			}

		echo '
		}

		event_listener("submit", "form_confirmation", function(event) { if (!form_confirmation_check()) {event.preventDefault();} });
		event_listener("click", "form_main_show", function(event) { document.getElementById("form_main").style.display = "block"; event.preventDefault(); });
		window.addEventListener("load", function() { if (document.getElementById("form_confirmation")) {document.getElementById("form_main").style.display = "none";} });
		';
	}

	if ($page == 'home' || ($page == 'login' && $module == 'submit' || $module == 'pay_submission'))
	{
		if (isset($fields['comments']['maxlength']) && $fields['comments']['maxlength'])
		{
			echo '
			function comments_limit()
			{
				if (document.getElementById("comments").value.length >= ' . $fields['comments']['maxlength'] . ') {alert("' . $fields['comments']['name'] . ' can only be ' . $fields['comments']['maxlength'] . ' characters long");}
			}

			event_listener("input", "comments", function(event) { comments_limit(); });
			';
		}

		echo '
		function cc_display()
		{
			var genres = new Object();
			price = 0;
			show_payment_fields = false;
			';

			if ($config['redirect_url'] && $config['submission_price'])
			{
				echo 'price = ' . $config['submission_price'] . ';' . "\n";
			}

			if (isset($fields['genre_id']['enabled']) && $fields['genre_id']['enabled'] && isset($genres['price']) && $genres['price'])
			{
				foreach ($genres['price'] as $value)
				{
					if ($genres['all'][$value]['redirect_url']) {$genre_url = $genres['all'][$value]['redirect_url'];} else {$genre_url = '';}
					if ((float) $genres['all'][$value]['price']) {$genre_price = $genres['all'][$value]['price'];} else {$genre_price = 0.00;} // string "0.00" returns TRUE. cannot be empty string. must be zero decimal/float.
					echo 'genres[' . $value . '] = {url: "' . $genre_url . '", price: ' . $genre_price . '};' . "\n";
				}
			}

			if ($config['show_payment_fields'])
			{
				echo 'show_payment_fields = true;' . "\n";
			}

			if ($module == 'pay_submission')
			{
				if (isset($price) && (float) $price) {echo 'price = ' . $price . ';' . "\n";}
			}
			else
			{
				if (isset($fields['genre_id']['enabled']) && $fields['genre_id']['enabled'] && isset($genres['price']) && $genres['price'])
				{
					echo '
					if (document.getElementById("genre_id"))
					{
						var selected_genre = document.getElementById("genre_id").value;
						if (genres[selected_genre] && typeof genres[selected_genre]["url"] != "undefined" && typeof genres[selected_genre]["price"] != "undefined") {price = genres[selected_genre]["price"];}
					}
					';
				}
			}

			echo '
			if (price && show_payment_fields)
			{
				document.getElementById("header_payment").style.display = "";
				document.getElementById("row_cc_number").style.display = "";
				document.getElementById("row_cc_exp_month").style.display = "";
				document.getElementById("row_cc_exp_year").style.display = "";
				document.getElementById("row_cc_csc").style.display = "";
				document.getElementById("cc_number").disabled = false;
				document.getElementById("cc_exp_month").disabled = false;
				document.getElementById("cc_exp_year").disabled = false;
				document.getElementById("cc_csc").disabled = false;
				document.getElementById("price_display").textContent = price.toFixed(2);
			}
			else
			{
				document.getElementById("header_payment").style.display = "none";
				document.getElementById("row_cc_number").style.display = "none";
				document.getElementById("row_cc_exp_month").style.display = "none";
				document.getElementById("row_cc_exp_year").style.display = "none";
				document.getElementById("row_cc_csc").style.display = "none";
				document.getElementById("cc_number").disabled = true;
				document.getElementById("cc_exp_month").disabled = true;
				document.getElementById("cc_exp_year").disabled = true;
				document.getElementById("cc_csc").disabled = true;
			}
		}

		document.addEventListener("DOMContentLoaded", function()
		{
			if (document.getElementById("cc_number"))
			{
				cc_display();
				if (document.getElementById("genre_id")) {document.getElementById("genre_id").addEventListener("change", function(event) { cc_display(); });}
			}
		});
		';
	}

	if ($page == 'login')
	{
		echo '
		function confirm_prompt(action, name, value)
		{
			var confirm_display = "CONFIRM: Are you sure you wish to " + action;
			if (typeof name != "undefined") {confirm_display += " " + name;}
			if (typeof value != "undefined")
			{
				if (isNaN(value)) {confirm_display += " " + value;} else {confirm_display += " #" + value;}
			}
			confirm_display += "?";
			if (name == "genre") {confirm_display += "\n" + "All submissions tied to this genre will have now be under \"no genre\".";}
			if (name == "payment variable presets") {confirm_display += "\n" + "WARNING: This will delete your current payment variables.";}
			if (name == "sample data") {confirm_display += "\n" + "This will delete all @example.com contacts as well as all related submissions, actions, and files.";}
			if (action == "reset") {confirm_display += "\n" + "WARNING: This will reset all " + name + " settings back the their defaults values." + "\n" + "All custom settings will be lost.";}
			var confirmed = confirm(confirm_display);
			if (confirmed) {return true;} else {return false;}
		}
		';

		if (in_array($module, $modules_admin))
		{
			echo '
			event_listener("change", "module", function(event) { document.getElementById("form_nav").submit(); });

			// this function is needed because these form buttons will otherwise never get disabled as there are no form check functions
			function form_submit(arg)
			{
				if (typeof submit_clicked == "undefined" || submit_clicked == "cancel") {return true;}

				if (arg == "submissions")
				{
					if (submit_clicked == "send") {disable_submit("submit_send");}
					if (submit_clicked == "confirm") {disable_submit("submit_confirm");}
				}

				if (arg == "contacts")
				{
					if (submit_clicked == "delete") {disable_submit("submit_contacts2");}
					if (submit_clicked == "continue") {disable_submit("submit_continue");}
				}

				if (arg == "configuration")
				{
					if (submit_clicked == "update") {disable_submit("submit_update");}
					if (submit_clicked == "reset defaults") {disable_submit("submit_reset_defaults");}
				}

				if (arg == "maintenance")
				{
					if (submit_clicked == "delete temp files") {disable_submit("submit_delete");}
					if (submit_clicked == "insert sample data") {disable_submit("submit_insert_sample_data");}
					if (submit_clicked == "delete sample data") {disable_submit("submit_delete_sample_data");}
					if (submit_clicked == "purge") {disable_submit("submit_purge");}
					if (submit_clicked == "purge hashes") {disable_submit("submit_purge_hashes");}
					if (submit_clicked == "test mail") {disable_submit("submit_test_mail");}
					if (submit_clicked == "test upload") {disable_submit("submit_test_upload");}
					if (submit_clicked == "update data structure") {disable_submit("submit_update_data_structure");}
				}
			}

			event_listener("submit", "form_" + module, function(event) { form_submit(module); });
			';
		}

		if ($module == 'account' || $module == 'submissions' || $module == 'maintenance')
		{
			echo '
			function lightbox(on_off, src, width, height, left, top)
			{
				if (on_off == "on")
				{
					document.getElementById("background").style.display = "block";
					document.getElementById("foreground").style.display = "flex";
					document.getElementById("popframe").src = src;
					if (left) {document.getElementById("foreground").style.left = left + "px";}
					if (top) {document.getElementById("foreground").style.top = top + "px";}

					document.getElementById("popframe").addEventListener("load", function()
					{
						document.getElementById("popframe").style.minWidth = "auto";
						document.getElementById("popframe").style.minHeight = "auto";
						if (width) {document.getElementById("popframe").style.minWidth = width + "px";} else {document.getElementById("popframe").style.minWidth = (document.getElementById("popframe").contentWindow.document.body.scrollWidth + 80) + "px";}
						if (height) {document.getElementById("popframe").style.minHeight = height + "px";} else {document.getElementById("popframe").style.minHeight = (document.getElementById("popframe").contentWindow.document.body.scrollHeight + 10) + "px";}
					});
				}

				if (on_off == "off")
				{
					document.getElementById("background").style.display = "none";
					document.getElementById("foreground").style.display = "none";
					document.getElementById("foreground").style.width = "auto";
					document.getElementById("foreground").style.height = "auto";
				}
			}

			event_listener("click", "lightbox_off", function(event) { lightbox("off"); event.preventDefault(); });
			event_listener("click", "background", function(event) { lightbox("off"); });
			';
		}

		if ($module == 'submissions')
		{
			echo '
			function form_tag_check()
			{
				if (!document.getElementById("tag_action_type_id").value)
				{
					document.getElementById("tag_action_type_id").className = "error";
					document.getElementById("label_tag_action_type_id").className = "error";
					alert("ERROR: Missing tag action type");
					return false;
				}
				else
				{
					document.getElementById("tag_action_type_id").className = "";
					document.getElementById("label_tag_action_type_id").className = "";
				}

				var action_text = document.getElementById("tag_action_type_id").options[document.getElementById("tag_action_type_id").selectedIndex].text;
				if (action_text.indexOf("forward") >= 0 && !document.getElementById("tag_receiver_id").value)
				{
					document.getElementById("tag_receiver_id").className = "error";
					document.getElementById("label_tag_receiver_id").className = "error";
					alert("ERROR: Missing tag receiver (action type forward requires receiver)");
					return false;
				}
				else
				{
					document.getElementById("tag_receiver_id").className = "";
					document.getElementById("label_tag_receiver_id").className = "";
				}

				var checked = false;
				for (i = 0; i < document.getElementById("form_submissions").length; i++)
				{
					if (document.getElementById("form_submissions").elements[i].name == "tag[]" && document.getElementById("form_submissions").elements[i].checked)
					{
						checked = true;
						break;
					}
				}
				if (!checked) {alert("ERROR: No submissions tagged"); return false;}

				return true;
			}

			event_listener("click", "submit_apply_to_tagged", function(event) { if (!form_tag_check()) {event.preventDefault();} });

			function tag_all(arg)
			{
				var i_tag = 1;
				var tag_checked_count = 0;

				for (i = 0; i < document.getElementById("form_submissions").length; i++)
				{
					if (document.getElementById("form_submissions").elements[i].name == "tag[]")
					{
						// must use incrementing integer for shift click to work
						// submission_id = document.getElementById("form_submissions").elements[i].value;

						if (arg == "tag")
						{
							document.getElementById("check_" + i_tag).checked = true;
							document.getElementById("tr_" + i_tag).style.backgroundColor = "' . $config['color_foreground'] . '";
							tag_checked_count++;
						}

						if (arg == "untag")
						{
							document.getElementById("check_" + i_tag).checked = false;
							document.getElementById("tr_" + i_tag).style.backgroundColor = "";
						}

						i_tag++;
					}
				}

				document.getElementById("checked_count").textContent = tag_checked_count;
			}

			document.addEventListener("DOMContentLoaded", function()
			{
				var tag_alls = document.getElementsByClassName("tag_all");
				for (i = 0; i < tag_alls.length; i++)
				{
					(function()
					{
						var action = tag_alls[i].href.split("#").pop();
						tag_alls[i].addEventListener("click", function(event) { tag_all(action); event.preventDefault(); });
					})();
				}

				if (tag_alls.length)
				{
					if (submit == "apply to tagged") {tag_all("tag");}
					if (submit == "cancel") {tag_all("untag");}
				}
			});

			function tag_checked_count()
			{
				var tag_checked_count = 0;

				for (i = 0; i < document.getElementById("form_submissions").length; i++)
				{
					if (document.getElementById("form_submissions").elements[i].name == "tag[]" && document.getElementById("form_submissions").elements[i].checked) {tag_checked_count++;}
				}

				document.getElementById("checked_count").textContent = tag_checked_count;
			}

			function change_row_color(i)
			{
				if (document.getElementById("check_" + i).checked)
				{
					document.getElementById("tr_" + i).style.backgroundColor = "' . $config['color_foreground'] . '";
				}
				else
				{
					document.getElementById("tr_" + i).style.backgroundColor = "";
				}
			}

			var oldInp = 0;
			function clickage(evt)
			{
				evt = (evt)?evt:event;
				var target = (evt.target)?evt.target:evt.srcElement;

				if (!evt.shiftKey)
				{
					oldInp = target.id.split("_").pop();
					return false;
				}

				target.checked = 1;

				var low = Math.min(target.id.split("_").pop(), oldInp);
				var high = Math.max(target.id.split("_").pop(), oldInp);
				var uncheck = 1;

				for (var i = low; i <= high; i++)
				{
					uncheck &= document.getElementById("check_" + i).checked;
					document.getElementById("check_" + i).checked = 1;
					change_row_color(i);
				}

				if (uncheck)
				{
					for (i = low; i <= high; i++)
					{
						document.getElementById("check_" + i).checked = 0;
						change_row_color(i);
					}
				}

				return true;
			}

			function form_action_check()
			{
				if (!document.getElementById("new_action_type_id").value)
				{
					document.getElementById("new_action_type_id").className = "error";
					document.getElementById("label_new_action_type_id").className = "error";
					alert("ERROR: Missing new action type");
					return false;
				}
				else
				{
					document.getElementById("new_action_type_id").className = "";
					document.getElementById("label_new_action_type_id").className = "";
				}

				var action_text = document.getElementById("new_action_type_id").options[document.getElementById("new_action_type_id").selectedIndex].text;
				if (action_text.indexOf("forward") >= 0 && !document.getElementById("new_receiver_id").value)
				{
					document.getElementById("new_receiver_id").className = "error";
					document.getElementById("label_new_receiver_id").className = "error";
					alert("ERROR: Missing new receiver (action forward requires receiver)");
					return false;
				}
				else
				{
					document.getElementById("new_receiver_id").className = "";
					document.getElementById("label_new_receiver_id").className = "";
				}

				return true;
			}

			event_listener("click", "submit_preview", function(event) { if (!form_action_check()) {event.preventDefault();} });
			event_listener("mouseover", "tag_action_td", function(event) { tooltip_show(action_tooltip, tag_action_td, event, 400); });
			event_listener("mouseout", "tag_action_td", function(event) { tooltip_hide(); });

			function enable_disable()
			{
				// for search
				if (document.getElementById("search_action_type_id"))
				{
					var action_text = document.getElementById("search_action_type_id").options[document.getElementById("search_action_type_id").selectedIndex].text;

					if (action_text.indexOf("forward") >= 0)
					{
						document.getElementById("search_receiver_id").disabled = false;
						document.getElementById("search_receiver_id").options[0].text = "anyone";
					}
					else
					{
						document.getElementById("search_receiver_id").disabled = true;
						document.getElementById("search_receiver_id").options[0].selected = true;
						document.getElementById("search_receiver_id").options[0].text = "only for forwards";
					}
				}

				// for tag
				if (document.getElementById("tag_action_type_id"))
				{
					var action_text = document.getElementById("tag_action_type_id").options[document.getElementById("tag_action_type_id").selectedIndex].text;

					if (action_text.indexOf("forward") >= 0)
					{
						document.getElementById("tag_receiver_id").disabled = false;
						document.getElementById("tag_receiver_id").options[0].text = "";
					}
					else
					{
						document.getElementById("tag_receiver_id").disabled = true;
						document.getElementById("tag_receiver_id").options[0].selected = true;
						document.getElementById("tag_receiver_id").options[0].text = "only for forwards";
					}
				}

				// for new action
				if (document.getElementById("new_action_type_id"))
				{
					var action_text = document.getElementById("new_action_type_id").options[document.getElementById("new_action_type_id").selectedIndex].text;

					if (action_text.indexOf("forward") >= 0)
					{
						document.getElementById("new_receiver_id").disabled = false;
						document.getElementById("new_receiver_id").options[0].text = "";
					}
					else
					{
						document.getElementById("new_receiver_id").disabled = true;
						document.getElementById("new_receiver_id").options[0].selected = true;
						document.getElementById("new_receiver_id").options[0].text = "only for forwards";
					}
					';

					if ($action_types)
					{
						foreach ($action_types['all'] as $key => $value)
						{
							if (strpos($value['body'], '[message]') !== false) {$message_bodies[] = $key;}
						}
					}

					if (isset($message_bodies))
					{
						echo '
						var message_bodies = new Array(' . implode(',', $message_bodies) . ');
						var message_warning = "only for message actions";
						var message_enable = false;

						for (i = 0; i < message_bodies.length; i++)
						{
							if (message_bodies[i] == parseInt(document.getElementById("new_action_type_id").value))
							{
								message_enable = true;
								break;
							}
						}

						if (message_enable)
						{
							document.getElementById("message").disabled = false;
							if (document.getElementById("message").value == message_warning) {document.getElementById("message").value = "";}
						}
						else
						{
							document.getElementById("message").disabled = true;
							document.getElementById("message").value = message_warning;
						}
						';
					}

					echo '
				}
			}

			event_listener("change", "new_action_type_id", function(event) { enable_disable(); });
			event_listener("change", "search_action_type_id", function(event) { enable_disable(); });
			event_listener("change", "tag_action_type_id", function(event) { enable_disable(); });
			document.addEventListener("DOMContentLoaded", function() { enable_disable(); });
			';
		}

		if ($module == 'contacts')
		{
			echo '
			function form_update_insert_contacts_check()
			{
				for (i = 0; i < document.getElementById("form_contacts").length; i++)
				{
					var id = document.getElementById("form_contacts").elements[i].id;
					if (id && document.getElementById(id) && document.getElementById(id).name != "password") {document.getElementById(id).value = document.getElementById(id).value.trim();}
				}

				var selected_access = document.getElementById("access").value;

				if (!document.getElementById("first_name").value && !document.getElementById("last_name").value)
				{
					document.getElementById("first_name").className = "error";
					document.getElementById("last_name").className = "error";
					document.getElementById("label_first_name").className = "error";
					document.getElementById("label_last_name").className = "error";
					alert("ERROR: You must enter a first or last name");
					return false;
				}

				if (document.getElementById("email").value && !email_check(document.getElementById("email")))
				{
					document.getElementById("email").className = "error";
					document.getElementById("label_email").className = "error";
					return false;
				}

				if (document.getElementById("country").value == "USA")
				{
					if (document.getElementById("address1").value && !document.getElementById("state").value)
					{
						document.getElementById("state").className = "error";
						document.getElementById("label_state").className = "error";
						alert("ERROR: State required for USA address");
						return false;
					}

					document.getElementById("zip").value = document.getElementById("zip").value.replace(/[^0-9]/g, "");
					if (document.getElementById("zip").value && document.getElementById("zip").value.length < 5)
					{
						document.getElementById("zip").className = "error";
						document.getElementById("label_zip").className = "error";
						alert("ERROR: Incomplete zip code");
						return false;
					}
				}

				if (selected_access && selected_access != "inactive" && selected_access != "blocked")
				{
					if (!document.getElementById("email").value)
					{
						document.getElementById("email").className = "error";
						document.getElementById("label_email").className = "error";
						alert("ERROR: Missing email (all staff members must have an email address)");
						return false;
					}
				}

				if (document.getElementById("password") && document.getElementById("password").value && !password_check(document.getElementById("password")))
				{
					document.getElementById("password").className = "error";
					document.getElementById("label_password").className = "error";
					return false;
				}

				disable_submit("submit_contacts1");
				return true;
			}

			function enable_email_notification()
			{
				var selected_access = document.getElementById("access").value;
				var access_state = document.getElementById("access").disabled;

				if (selected_access == "" || selected_access == "inactive" || selected_access == "blocked" || access_state)
				{
					document.getElementById("email_notification[submissions]").disabled = true;
					document.getElementById("email_notification[actions]").disabled = true;
					document.getElementById("email_notification[updates]").disabled = true;

					document.getElementById("email_notification[submissions]").checked = false;
					document.getElementById("email_notification[actions]").checked = false;
					document.getElementById("email_notification[updates]").checked = false;

					document.getElementById("label_email_notification[submissions]").style = "color: Grey;";
					document.getElementById("label_email_notification[actions]").style = "color: Grey;";
					document.getElementById("label_email_notification[updates]").style = "color: Grey;";
				}
				else
				{
					document.getElementById("email_notification[submissions]").disabled = false;
					document.getElementById("email_notification[actions]").disabled = false;
					document.getElementById("email_notification[updates]").disabled = false;

					document.getElementById("label_email_notification[submissions]").style = "color: ' . $config['color_text'] . ';";
					document.getElementById("label_email_notification[actions]").style = "color: ' . $config['color_text'] . ';";
					document.getElementById("label_email_notification[updates]").style = "color: ' . $config['color_text'] . ';";
				}
			}

			event_listener("change", "access", function(event) { enable_email_notification(); });
			document.addEventListener("DOMContentLoaded", function() { if (document.getElementById("access")) {enable_email_notification();} });

			function form_insert_submission_check()
			{
				var form_check = true;
				var error = "ERROR: Required fields missing or incomplete:\n\n";

				if (!document.getElementById("title").value)
				{
					document.getElementById("title").className = "error";
					document.getElementById("label_title").className = "error";
					error += "title" + "\n";
					form_check = false;
				}
				else
				{
					document.getElementById("title").className = "";
					document.getElementById("label_title").className = "";
				}
				';

				// only echo file validation if it's the first time hitting submit, otherwise "file" will always have to be revalidated
				if (!isset($_SESSION['file_upload']['filename']) && !isset($_FILES['file']['name']))
				{
					echo '
					if (!document.getElementById("file").value)
					{
						document.getElementById("file").className = "error";
						document.getElementById("label_file").className = "error";
						error += "file" + "\n";
						form_check = false;
					}
					else
					{
						document.getElementById("file").className = "";
						document.getElementById("label_file").className = "";
					}
					';
				}

				echo '
				if (!form_check)
				{
					alert(error);
					return false;
				}

				disable_submit("submit_insert_submission");
				return true;
			}

			event_listener("submit", "form_contacts", function(event)
			{
				if (submit_clicked == "update" || submit_clicked == "insert") { if (!form_update_insert_contacts_check()) {event.preventDefault();} }
				if (submit_clicked == "submit") { if (!form_insert_submission_check()) {event.preventDefault();} }
			});

			document.addEventListener("DOMContentLoaded", function()
			{
				if (document.getElementById("submit_contacts2") && document.getElementById("submit_contacts2").value == "delete")
				{
					var contact_id = document.getElementById("contact_id").textContent;
					document.getElementById("submit_contacts2").addEventListener("click", function(event) { if (!confirm_prompt("delete", "contact", contact_id)) {event.preventDefault();} });
				}
			});
			';
		}

		if ($module == 'configuration')
		{
			if ($submodule == 'general')
			{
				echo '
				function form_general_configuration_check()
				{
					var form_check = true;
					var error = "ERROR: Required fields missing:\n\n";
					var fields_config = new Object();
					';

					// build the fields object
					foreach ($defaults['config'] as $key => $value) {echo 'fields_config["' . $key . '"] = {name: "' . $value['name'] . '", type: "' . $value['type'] . '", required: "' . $value['required'] . '"};' . "\n";}

					echo '
					for (var key in fields_config)
					{
						var config_key = "config_" + key;
						document.getElementById(config_key).value = document.getElementById(config_key).value.trim();

						if (document.getElementById(config_key) && document.getElementById(config_key).value == "" && fields_config[key]["required"])
						{
							document.getElementById(config_key).className = "error";
							document.getElementById("label_" + config_key).className = "error";
							error += fields_config[key]["name"] + "\n";
							form_check = false;
						}
						else
						{
							document.getElementById(config_key).className = "";
							document.getElementById("label_" + config_key).className = "";
						}
					}

					if (!form_check)
					{
						alert(error);
						return false;
					}

					if (document.getElementById("config_general_dnr_email").value && !email_check(document.getElementById("config_general_dnr_email")))
					{
						document.getElementById("config_general_dnr_email").className = "error";
						document.getElementById("label_config_general_dnr_email").className = "error";
						return false;
					}
					else
					{
						document.getElementById("config_general_dnr_email").className = "";
						document.getElementById("label_config_general_dnr_email").className = "";
					}

					if (document.getElementById("config_admin_email").value && !email_check(document.getElementById("config_admin_email")))
					{
						document.getElementById("config_admin_email").className = "error";
						document.getElementById("label_config_admin_email").className = "error";
						return false;
					}
					else
					{
						document.getElementById("config_admin_email").className = "";
						document.getElementById("label_config_admin_email").className = "";
					}

					return true;
				}

				event_listener("click", "submit_update", function(event) { if (!form_general_configuration_check()) {event.preventDefault();} });
				';
			}

			if ($submodule == 'action_types')
			{
				echo '
				function form_action_types_check()
				{
					var form_check = true;
					var error = "ERROR: Required fields missing:\n\n";

					for (i = 0; i < document.getElementById("form_configuration").length; i++)
					{
						var id = document.getElementById("form_configuration").elements[i].id;
						document.getElementById(id).value = document.getElementById(id).value.trim();

						if (document.getElementById(id).name.indexOf("action_type") >= 0 && document.getElementById(id).name.indexOf("description") < 0 && document.getElementById(id).name.indexOf("status") < 0 && document.getElementById(id).value == "")
						{
							document.getElementById(id).className = "error";
							document.getElementById("label_" + id).className = "error";
							error += document.getElementById(id).name + "\n";
							form_check = false;
						}
						else if (document.getElementById(id).type == "text" || document.getElementById(id).type == "checkbox" || document.getElementById(id).type == "texarea")
						{
							document.getElementById(id).className = "";
							document.getElementById("label_" + id).className = "";
						}
					}

					if (!form_check)
					{
						alert(error);
						return false;
					}

					return true;
				}

				event_listener("click", "submit_update", function(event) { if (!form_action_types_check()) {event.preventDefault();} });
				';
			}

			if ($submodule == 'fields')
			{
				echo '
				function form_fields_check()
				{
					var form_check = true;
					var error = "ERROR: ";

					for (i = 0; i < document.getElementById("form_configuration").length; i++)
					{
						var id = document.getElementById("form_configuration").elements[i].id;
						document.getElementById(id).value = document.getElementById(id).value.trim();
						if (document.getElementById(id).id.indexOf("maxlength") >= 0) {document.getElementById(id).value = document.getElementById(id).value.replace(/[^0-9]/g, "");}

						if (document.getElementById(id).id.slice(-5) == "_name" && document.getElementById(id).value == "")
						{
							document.getElementById(id).className = "error";
							error += "Field Names cannot be blank";
							form_check = false;
							break;
						}

						if (document.getElementById(id).id.indexOf("maxlength") >= 0 && (isNaN(document.getElementById(id).value) || document.getElementById(id).value == 0))
						{
							document.getElementById(id).className = "error";
							error += "Field Max Lengths must be numeric and greater than 0";
							form_check = false;
							break;
						}

						if ((document.getElementById(id).id == "password_maxlength" || document.getElementById(id).id == "password2_maxlength") && (document.getElementById(id).value < 8 || document.getElementById(id).value > 72))
						{
							document.getElementById(id).className = "error";
							error += "Password Max Length must be between 8 and 72";
							form_check = false;
							break;
						}

						if (document.getElementById(id).id == "file_maxlength" && document.getElementById(id).value > 4294967295)
						{
							document.getElementById(id).className = "error";
							error += "File maximum size is 4294967295 (4 GB)";
							form_check = false;
							break;
						}
					}

					if (!form_check)
					{
						alert(error);
						return false;
					}

					return true;
				}

				event_listener("click", "submit_update", function(event) { if (!form_fields_check()) {event.preventDefault();} });

				function upload_max_filesize()
				{
					if (document.getElementById("upload_max_filesize"))
					{
						var upload_max_filesize = document.getElementById("upload_max_filesize").href.split("#").pop();
						document.getElementById("file_maxlength").value = upload_max_filesize;
						document.getElementById("file_maxlength").style.backgroundColor = "yellow";
					}
				}

				event_listener("click", "upload_max_filesize", function(event) { upload_max_filesize(); event.preventDefault(); });
				';
			}

			if ($submodule == 'genres')
			{
				echo '
				function form_genres_check()
				{
					var form_check = true;
					var error = "ERROR: ";
					var genre_names = new Array();

					for (i = 0; i < document.getElementById("form_configuration").length; i++)
					{
						var id = document.getElementById("form_configuration").elements[i].id;
						document.getElementById(id).value = document.getElementById(id).value.trim();
						if (document.getElementById(id).name.indexOf("submission_limit") >= 0) {document.getElementById(id).value = document.getElementById(id).value.replace(/[^0-9]/g, "");}
						if (document.getElementById(id).name.indexOf("price") >= 0) {document.getElementById(id).value = document.getElementById(id).value.replace(/[^0-9.]/g, "");}

						if (document.getElementById(id).name.indexOf("name") >= 0 && document.getElementById(id).name.indexOf("new") < 0 && document.getElementById(id).value == "")
						{
							document.getElementById(id).className = "error";
							error += "Genre Names cannot be blank";
							form_check = false;
							break;
						}

						if ((document.getElementById(id).name.indexOf("submission_limit") >= 0 || document.getElementById(id).name.indexOf("price") >= 0) && isNaN(document.getElementById(id).value))
						{
							document.getElementById(id).className = "error";
							error += "Submission Limits and Prices must be numeric";
							form_check = false;
							break;
						}

						if (document.getElementById(id).name.indexOf("submission_limit") >= 0 && parseInt(document.getElementById(id).value) > 255)
						{
							document.getElementById(id).className = "error";
							error += "Maximum submission limit is 255";
							form_check = false;
							break;
						}

						if (document.getElementById(id).name.indexOf("price") >= 0 && parseFloat(document.getElementById(id).value) > 9999.99)
						{
							document.getElementById(id).className = "error";
							error += "Maximum price is $9999.99";
							form_check = false;
							break;
						}

						if (document.getElementById(id).name.indexOf("name") >= 0) {genre_names[i] = document.getElementById(id).value;}
					}

					genre_names.sort();

					for (var key in genre_names)
					{
						if (typeof last != "undefined" && last == genre_names[key] && form_check)
						{
							error += "All genre names must be unique";
							form_check = false;
							break;
						}

						var last = genre_names[key];
					}

					if (!form_check)
					{
						alert(error);
						return false;
					}

					return true;
				}

				event_listener("click", "submit_update", function(event) { if (!form_genres_check()) {event.preventDefault();} });
				';
			}

			if ($submodule == 'payment_vars')
			{
				echo '
				function form_payment_vars_check()
				{
					var form_check = true;
					var error = "ERROR: ";
					var payment_vars = new Object();
					var count_submission_id_in = 0;
					var count_result_code_in = 0;
					var count_result_code_out = 0;

					for (i = 0; i < document.getElementById("form_configuration").length; i++)
					{
						var id = document.getElementById("form_configuration").elements[i].id;
						document.getElementById(id).value = document.getElementById(id).value.trim();

						// get the row index
						if (document.getElementById(id).id.indexOf("payment_vars") >= 0)
						{
							var split = document.getElementById(id).id.split("_");
							var payment_var_index = split[2];
						}

						// build the object properties
						if (document.getElementById(id).name.indexOf("name") >= 0 && document.getElementById(id).value) {payment_var_name = document.getElementById(id).value;}
						if (document.getElementById(id).name.indexOf("value") >= 0 && document.getElementById(id).value) {payment_var_value = document.getElementById(id).value;}
						if (document.getElementById(id).name.indexOf("direction") >= 0 && document.getElementById(id).value) {payment_var_direction = document.getElementById(id).value;}

						// if the "new" row has any blanks then exclude from object
						if (document.getElementById(id).name == "payment_vars[new][name]" && document.getElementById(id).value == "") {payment_var_name = "";}
						if (document.getElementById(id).name == "payment_vars[new][value]" && document.getElementById(id).value == "") {payment_var_value = "";}

						// add row to object
						if (typeof payment_var_name != "undefined" && payment_var_name && typeof payment_var_value != "undefined" && payment_var_value && typeof payment_var_direction != "undefined" && payment_var_direction) {payment_vars[payment_var_index] = {name: payment_var_name, value: payment_var_value, direction: payment_var_direction};}

						if (document.getElementById(id).name.indexOf("payment_vars") >= 0 && document.getElementById(id).name.indexOf("new") < 0 && document.getElementById(id).value == "")
						{
							document.getElementById(id).className = "error";
							error += "Both name and value must not be blank. ";
							form_check = false;
							break;
						}
					}

					if ((document.getElementById("payment_vars_new_name").value != "" && document.getElementById("payment_vars_new_value").value == "") || (document.getElementById("payment_vars_new_name").value == "" && document.getElementById("payment_vars_new_value").value != ""))
					{
						if (form_check) // to suppress multiple errors
						{
							document.getElementById("payment_vars_new_name").className = "error";
							document.getElementById("payment_vars_new_value").className = "error";
							error += "Both name and value must not be blank. ";
							form_check = false;
						}
					}

					// get counts
					for (var key in payment_vars)
					{
						if (payment_vars[key]["value"] == "$submission_id" && payment_vars[key]["direction"] == "in") {count_submission_id_in++;}
						if (payment_vars[key]["value"] == "$result_code" && payment_vars[key]["direction"] == "in") {count_result_code_in++;}
						if (payment_vars[key]["value"] == "$result_code" && payment_vars[key]["direction"] == "out") {count_result_code_out++;}
					}

					if (count_submission_id_in > 1)
					{
						error += "Only one incoming submission_id payment variable can be used. ";
						form_check = false;
					}

					if (count_result_code_out > 0)
					{
						error += "result_code must only be an incoming payment variable (not outgoing). ";
						form_check = false;
					}

					if (count_result_code_in > 1)
					{
						error += "Only one incoming result_code payment variable can be used. ";
						form_check = false;
					}

					if (!form_check)
					{
						alert(error);
						return false;
					}

					return true;
				}

				event_listener("click", "submit_update", function(event) { if (!form_payment_vars_check()) {event.preventDefault();} });
				';
			}

			echo '
			function form_reset_previous(arg) {alert("Previous " + arg + " settings have been reset.");}

			var submodule_display = submodule.replace("_"," ");
			submodule_display = submodule_display.toLowerCase().replace(/\b[a-z]/g, function(letter) {return letter.toUpperCase();});
			event_listener("click", "submit_reset", function(event) { form_reset_previous(submodule_display); });
			event_listener("click", "submit_reset_defaults", function(event) { if (!confirm_prompt("reset", submodule_display)) {event.preventDefault();} });

			document.addEventListener("DOMContentLoaded", function()
			{
				var file_types = document.getElementsByClassName("file_type");
				for (i = 0; i < file_types.length; i++)
				{
					(function()
					{
						var ext = file_types[i].id.split("_").pop();
						file_types[i].addEventListener("click", function(event) { if (!confirm_prompt("delete", "file type", ext)) {event.preventDefault();} });
					})();
				}

				var genres = document.getElementsByClassName("genre");
				for (i = 0; i < genres.length; i++)
				{
					(function()
					{
						var genre_id = genres[i].id.split("_").pop();
						genres[i].addEventListener("click", function(event) { if (!confirm_prompt("delete", "genre", genre_id)) {event.preventDefault();} });
					})();
				}

				var payment_vars = document.getElementsByClassName("payment_var");
				for (i = 0; i < payment_vars.length; i++)
				{
					(function()
					{
						var payment_var_id = payment_vars[i].id.split("_").pop();
						payment_vars[i].addEventListener("click", function(event) { if (!confirm_prompt("delete", "payment variable", payment_var_id)) {event.preventDefault();} });
					})();
				}

				var payment_vars_presets = document.getElementsByClassName("payment_vars_preset");
				for (i = 0; i < payment_vars_presets.length; i++)
				{
					(function()
					{
						payment_vars_presets[i].addEventListener("click", function(event) { if (!confirm_prompt("overwrite", "payment variable presets")) {event.preventDefault();} });
					})();
				}
			});
			';
		}

		if ($module == 'maintenance')
		{
			echo '
			event_listener("click", "submit_delete_sample_data", function(event) { if (!confirm_prompt("delete", "sample data")) {event.preventDefault();} });
			event_listener("click", "submit_purge", function(event) { if (!confirm_prompt("purge", "submissions")) {event.preventDefault();} });
			event_listener("click", "submit_purge_hashes", function(event) { if (!confirm_prompt("purge", "password hashes")) {event.preventDefault();} });
			event_listener("click", "popup_version_sm", function(event) { lightbox("on","popup.php?page=changelog",1200,700,100,10); event.preventDefault(); });
			event_listener("click", "popup_version_php", function(event) { lightbox("on","popup.php?page=phpinfo",1200,700,100,10); event.preventDefault(); });
			event_listener("click", "popup_version_mysql", function(event) { lightbox("on","popup.php?page=mysqlinfo",1200,700,100,10); event.preventDefault(); });
			';
		}
	}

	if ($config['payment_redirect_method'] == 'POST')
	{
		if ($submit == 'continue' || (!$config['show_payment_fields'] && $module == 'pay_submission'))
		{
			echo '
			window.addEventListener("load", function() { if (document.getElementById("form_post")) {document.getElementById("form_post").submit();} });
			';
		}
	}
}

$custom = 'custom.js';
if (@file_exists($custom)) {include($custom);}

echo '
</script>
';
?>
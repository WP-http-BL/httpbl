<?php
/*
Plugin Name: http:BL WordPress Plugin
Plugin URI: http://stepien.com.pl/2007/04/28/httpbl_wordpress_plugin/
Description: http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org">Project Honey Pot</a> database. 
Author: Jan Stępień
Version: SVN
Author URI: http://stepien.com.pl
License: This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
*/
	
	add_action("init", "httpbl_check_visitor");
	add_action("admin_menu", "httpbl_config_page");
	
	function httpbl_debug($message)
	{
		// This function will display debug messages
		// in HTML comments somewhere in the output.
		// Most probably in the header.
	}

	// Add a line to the log table
	function httpbl_add_log($ip, $user_agent, $response, $blocked)
	{
	$time = gmdate("Y-m-d H:i:s",
		time() + get_option('gmt_offset') * 60 * 60 );
	$blocked = ($blocked ? 1 : 0);
	$wpdb =& $GLOBALS['wpdb'];
	$query = "INSERT INTO ".$GLOBALS['table_prefix']."httpbl_log ".
		"(ip, time, user_agent, httpbl_response, blocked) VALUES ( ".
		"'$ip', '$time', '$user_agent', '$response', $blocked);";
	$results = $wpdb->query($query);
	}

	// Get latest 50 entries from the log table
	function httpbl_get_log()
	{
	$query = "SELECT * FROM ".$GLOBALS['table_prefix'].
		"httpbl_log ORDER BY id DESC LIMIT 50";
	$wpdb =& $GLOBALS['wpdb'];
	return $wpdb->get_results($query);
	}
	
	// The visitor verification function
	function httpbl_check_visitor()
	{
		$key = get_option( "httpbl_key" );

		// The http:BL query
		$result = explode( ".", gethostbyname( $key . "." .
			implode ( ".", array_reverse( explode( ".",
			$_SERVER["REMOTE_ADDR"] ) ) ) .
			".dnsbl.httpbl.org" ) );

		// If the response is positive,
		if ( $result[0] == 127 ) {

			// Get thresholds
			$age_thres = get_option('httpbl_age_thres');
			$threat_thres = get_option('httpbl_threat_thres');

			for ($i = 0; pow(2, $i) <= 4; $i++) {
				$value = pow(2, $i);
				$denied[$value] = get_option('httpbl_deny_'
					. $value);
			}
			
			$hp = get_option('httpbl_hp');
			
			// Assume that visitor's OK
			$age = false;
			$threat = false;
			$deny = false;
			$blocked = false;
			
			if ( $result[1] < $age_thres )
				$age = true;
			if ( $result[2] > $threat_thres )
				$threat = true;
			foreach ( $denied as $key => $value ) {
				if ( ($result[3] - $result[3] % $key) > 0
					and $value)
					$deny = true;
			}
			
			// If he's not OK
			if ( $deny && $age && $threat ) {
				$blocked = true;

				// If we've got a Honey Pot link
				if ( $hp ) {
					header( "HTTP/1.1 301 Moved Permanently ");
					header( "Location: $hp" );
				}

			}

			// Are we logging?
			if (get_option("httpbl_log") == true) {

				// Checking if he's not one of those, who
				// are not logged
				$ips = explode(" ",
					get_option("httpbl_not_logged_ips"));
				$log = true;
				foreach ($ips as $ip) {
					if ($ip == $_SERVER["REMOTE_ADDR"])
						$log = false;
				}

				// If he can be logged, we log him
				if ($log)
					httpbl_add_log($_SERVER["REMOTE_ADDR"],
					$_SERVER["HTTP_USER_AGENT"],
					implode($result, "."), $blocked);
			}
			if ($blocked) die();	// My favourite line.
		}
	}


	function httpbl_config_page()
	{
		add_submenu_page("plugins.php", "http:BL WordPress Plugin",
			"http:BL", 10, __FILE__, "httpbl_configuration");
	}

	function httpbl_configuration()
	{
		if($_POST["httpbl_save"])
		{
			// If the save button was clicked
			// the options are updated.
			update_option('httpbl_key', $_POST["key"] );
			update_option('httpbl_age_thres', $_POST["age_thres"] );
			update_option('httpbl_threat_thres',
				$_POST["threat_thres"] );
			for ($i = 0; pow(2, $i) <= 4; $i++) {
				$value = pow(2, $i);
				$denied[$value] = update_option('httpbl_deny_'.
					$value, ($_POST["deny_".$value] == 1 ?
					true : false));
			}
			update_option('httpbl_hp', $_POST["hp"] );
			update_option('httpbl_log',
				( $_POST["enable_log"] == 1 ? true : false ));
			update_option('httpbl_not_logged_ips',
				$_POST["not_logged_ips"] );
		}
		
		// If it seems like the first launch,
		// few options should be set as defaults.
		if ( get_option( "httpbl_key" ) == "" ) {
			update_option( "httpbl_key" , "abcdefghijkl" );
		}
		if ( get_option( "httpbl_age_thres" ) == 0 )
			update_option( "httpbl_age_thres" , "14" );
		if ( get_option( "httpbl_threat_thres" ) == 0 )
			update_option( "httpbl_threat_thres" , "30" );
		
		// Get data to be displayed in the form.
		$key = get_option('httpbl_key');
		$threat_thres = get_option('httpbl_threat_thres');
		$age_thres = get_option('httpbl_age_thres');
		for ($i = 0; pow(2, $i) <= 4; $i++) {
			$value = pow(2, $i);
			$denied[$value] = get_option('httpbl_deny_' . $value);
			$deny_checkbox[$value] = ($denied[$value] ?
				"checked='true'" : "");
		}
		$hp = get_option('httpbl_hp');
		$not_logged_ips = get_option('httpbl_not_logged_ips');
		$log_checkbox = ( get_option('httpbl_log') ?
			"checked='true'" : "");

		// The page contents.
?>
<div class='wrap'>
	<h2>http:BL WordPress Plugin</h2>
	<p><a href="#conf">Configuration</a> | <a href="#log">Log</a></p>
	<p>The http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org">Project Honey Pot</a> database.</p>
	<a name="conf"></a>
	<h3>Configuration</h3>
	<form action='' method='post' id='httpbl'>
		<p>http:BL Access Key <input type='text' name='key' value='<?php echo $key ?>' /> </p>
		<p><small>An Access Key is required to perform a http:BL query. You can get your key at <a href="http://www.projecthoneypot.org/httpbl_configure.php">http:BL Access Management page</a>. You need to register a free account at the Project Honey Pot website to get one.</small></p>
		<p>Age threshold <input type='text' name='age_thres' value='<?php echo $age_thres ?>'/></p>
		<p><small>http:BL service provides you information about the date of the last activity of a checked IP. Due to the fact that the information in the Project Honey Pot database may be obsolete, you may set an age threshold, counted in days. If the verified IP hasn't been active for a period of time longer than the threshold it will be regarded as harmless.</small></p>
		<p>Threat score threshold <input type='text' name='threat_thres' value='<?php echo $threat_thres ?>'/></p>
		<p><small>Each suspicious IP address is given a threat score. This scored is asigned by Project Honey Pot basing on various factors, such as the IP's activity or the damage done during the visits. The score is a number between 0 and 255, where 0 is no threat at all and 255 is extremely harmful. In the field above you may set the threat score threshold. IP address with a score greater than the given number will be regarded as harmful.</small></p>
		<fieldset>
		<label>Types of visitors to be treated as malicious</label>
		<p><input type='checkbox' name='deny_1' value='1' <?php echo $deny_checkbox[1] ?>/> Suspicious</p>
		<p><input type='checkbox' name='deny_2' value='1' <?php echo $deny_checkbox[2] ?>/> Harvesters</p>
		<p><input type='checkbox' name='deny_4' value='1' <?php echo $deny_checkbox[4] ?>/> Comment spammers</p>
		</fieldset>
		<p><small>The field above allows you to specify which types of visitors should be regarded as harmful. It is recommended to tick all of them.</small></p>
		<p>Honey Pot <input type='text' name='hp' value='<?php echo $hp ?>'/></p>
		<p><small>If you've got a Honey Pot or a Quick Link you may redirect all unwelcome visitors to it. If you leave the following field empty all harmful visitors will be given a blank page instead of your blog.</small></p>
		<p>Enable logging <input type='checkbox' name='enable_log' value='1' <?php echo $log_checkbox ?>/></p>
		<p><small>If you enable logging all visitors which are recorded in the Project Honey Pot's database will be logged in the database and listed in the table below. Remember to create a proper table in the database before you enable this option!</small></p>
		<p>Not logged IP addresses <input type='text' name='not_logged_ips' value='<?php echo $not_logged_ips ?>'/></p>
		<p><small>Enter a space-separated list of IP addresses which will not be recorded in the log.</small></p>
		<p><small>More details are available at the <a href="http://www.projecthoneypot.org/httpbl_api.php">http:BL API Specification page</a>.</small></p>
	<div style="float:right"><a href="http://www.projecthoneypot.org/?rf=28499"><img src="<?php echo get_option("siteurl") . "/wp-content/plugins/httpBL/";?>project_honey_pot_button.png" height="31px" width="88px" border="0" alt="Stop Spam Harvesters, Join Project Honey Pot"></a></div>
		<p><input type='submit' name='httpbl_save' value='Save Settings' /></p>
	</form>
	<hr/>
	<a name="log"></a>
	<h3>Log</h3>
	<p>A list of 50 most recent visitors listed in the Project Honey Pot's database.</p>
	<table cellpadding="5px" cellspacing="3px">
	<tr>
		<th>ID</th>
		<th>IP</th>
		<th>Date</th>
		<th>User agent</th>
		<th>http:BL</th>
		<th>Blocked</th>
	</tr>
<?php
	// Table with logs.
	$results = httpbl_get_log();
	$i = 0;
	foreach ($results as $row) {
		$style = ($i % 2 ? " class='alternate'" : "" );
		$i++;
		echo "\n\t<tr$style>";
		foreach ($row as $key => $val) {
			if ($key == "blocked")
				$val = ($val ? "<strong>YES</strong>" : "No");
			echo "\n\t\t<td><small>$val</small></td>";
		}
		echo "\n\t</tr>";
	}
?>
	</table>
</div>
<?php
	}	
?>

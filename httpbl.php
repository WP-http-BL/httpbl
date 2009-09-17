<?php
/*
Plugin Name: http:BL WordPress Plugin
Plugin URI: http://wordpress.org/extend/plugins/httpbl/
Description: http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org/?rf=28499">Project Honey Pot</a> database. 
Author: Jan Stępień
Version: SVN
Author URI: http://stepien.cc/~jan
License: This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
*/
	
	add_action("init", "httpbl_check_visitor",1);
	if ( get_option('httpbl_stats') )
		add_action("init", "httpbl_get_stats",10);
	add_action("admin_menu", "httpbl_config_page");
	
	// Add a line to the log table
	function httpbl_add_log($ip, $user_agent, $response, $blocked)
	{
		global $GLOBALS;
		$time = gmdate("Y-m-d H:i:s",
			time() + get_option('gmt_offset') * 60 * 60 );
		$blocked = ($blocked ? 1 : 0);
		$wpdb =& $GLOBALS['wpdb'];
		$user_agent = mysql_real_escape_string($user_agent);
		$query = "INSERT INTO ".$GLOBALS['table_prefix']."httpbl_log ".
			"(ip, time, user_agent, httpbl_response, blocked)".
			" VALUES ( '$ip', '$time', '$user_agent',".
			"'$response', $blocked);";
		$results = $wpdb->query($query);
	}

	// Get latest 50 entries from the log table
	function httpbl_get_log()
	{
		global $GLOBALS;
		$query = "SELECT * FROM ".$GLOBALS['table_prefix'].
			"httpbl_log ORDER BY id DESC LIMIT 50";
		$wpdb =& $GLOBALS['wpdb'];
		return $wpdb->get_results($query);
	}
	
	// Get numbers of blocked and passed visitors from the log table
	// and place them in $httpbl_stats_data[]
	function httpbl_get_stats()
	{
		global $GLOBALS, $httpbl_stats_data;
		$query = "SELECT blocked,count(*) FROM ".$GLOBALS['table_prefix'].
			"httpbl_log GROUP BY blocked";
		$wpdb =& $GLOBALS['wpdb'];
		$results = $wpdb->get_results($query,ARRAY_N);
		foreach ((array)$results as $row) {
			if ($row[0] == 1) {
				$httpbl_stats_data['blocked'] = $row[1];
			} else {
				$httpbl_stats_data['passed'] = $row[1];
			}
		}
		$results = NULL;
	}
	
	// Display stats. Output may be configured at the plugin's config page.
	function httpbl_stats()
	{
		global $httpbl_stats_data;
		$pattern = get_option('httpbl_stats_pattern');
		$link = get_option('httpbl_stats_link');
		$search = array(
			'$block',
			'$pass',
			'$total'
			);
		$replace = array(
			$httpbl_stats_data['blocked'],
			$httpbl_stats_data['passed'],
			$httpbl_stats_data['blocked']+$httpbl_stats_data['passed']
			);
		$link_prefix = array(
			"",
			"<a href='http://www.projecthoneypot.org/?rf=28499'>",
			"<a href='http://wordpress.org/extend/plugins/httpbl/'>"
			);
		$link_suffix = array(
			"",
			"</a>",
			"</a>"
			);
		echo $link_prefix[$link].
			str_replace($search, $replace, $pattern).
			$link_suffix[$link];
	}
	
	// Check whether the table exists
	function httpbl_check_log_table()
	{
		global $GLOBALS;
		$wpdb =& $GLOBALS['wpdb'];
		$result = $wpdb->get_results("SHOW TABLES");
		foreach ($result as $stdobject) {
			foreach ($stdobject as $table) {
				if ($GLOBALS['table_prefix'].
					"httpbl_log" == $table) {
					return true;
				}
			}
		}
		return false;
	}
	
	// Truncate the log table
	function httpbl_truncate_log_table()
	{
		global $GLOBALS;
		$wpdb =& $GLOBALS['wpdb'];
		return $wpdb->get_results("TRUNCATE ".
			$GLOBALS['table_prefix']."httpbl_log;");
	}

	// Drop the log table
	function httpbl_drop_log_table()
	{
		global $GLOBALS;
		update_option('httpbl_log', false);
		$wpdb =& $GLOBALS['wpdb'];
		return $wpdb->get_results("DROP TABLE ".
			$GLOBALS['table_prefix']."httpbl_log;");
	}
	
	// Create a new log table
	function httpbl_create_log_table()
	{
		global $GLOBALS;
		// No "IF NOT EXISTS" as we create it only if it does
		// not exist.
		$sql = 'CREATE TABLE `' . $GLOBALS['table_prefix'] . 'httpbl_log` ('
			.'	`id` INT( 6 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
			.'	`ip` VARCHAR( 16 ) NOT NULL DEFAULT \'unknown\' ,'
			.'	`time` DATETIME NOT NULL ,'
			.'	`user_agent` VARCHAR( 255 ) NOT NULL DEFAULT \'unknown\' ,'
			.'	`httpbl_response` VARCHAR( 16 ) NOT NULL ,'
			.'	`blocked` BOOL NOT NULL'
			.')';
		$wpdb =& $GLOBALS['wpdb'];
		// TODO check for errors.
		$wpdb->query($sql);
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
			$threat_thres_s = get_option('httpbl_threat_thres_s');
			$threat_thres_h = get_option('httpbl_threat_thres_h');
			$threat_thres_c = get_option('httpbl_threat_thres_c');

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

			if ( $threat_thres_s && ($result[3] & 1) ) {
				// Check suspicious threat
				if ( $result[2] > $threat_thres_s )
					$threat = true;
			} else if ( $threat_thres_h && ($result[3] & 2) ) {
				// Check harvester threat
				if ( $result[2] > $threat_thres_h )
					$threat = true;
			} else if ( $threat_thres_c && ($result[3] & 4) ) {
				// Check comment spammer threat
				if ( $result[2] > $threat_thres_c )
					$threat = true;
			} else {
				if ( $result[2] > $threat_thres )
					$threat = true;
			}

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

				// At first we assume that the visitor
				// should be logged
				$log = true;

				// Checking if he's not one of those, who
				// are not logged
				$ips = explode(" ",
					get_option("httpbl_not_logged_ips"));
				foreach ($ips as $ip) {
					if ($ip == $_SERVER["REMOTE_ADDR"])
						$log = false;
				}

				// Don't log search engine bots
				if ($result[3] == 0) $log = false;

				// If we log only blocked ones
				if (get_option("httpbl_log_blocked_only")
					and !$blocked) {
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
		// If the save button was clicked...
		if (isset($_POST["httpbl_save"])) {
			// ...the options are updated.
			update_option('httpbl_key', $_POST["key"] );
			update_option('httpbl_age_thres', $_POST["age_thres"] );
			update_option('httpbl_threat_thres',
				$_POST["threat_thres"] );
			update_option('httpbl_threat_thres_s', 
				$_POST["threat_thres_s"] );
			update_option('httpbl_threat_thres_h', 
				$_POST["threat_thres_h"] );
			update_option('httpbl_threat_thres_c', 
				$_POST["threat_thres_c"] );

			for ($i = 0; pow(2, $i) <= 4; $i++) {
				$value = pow(2, $i);
				$denied[$value] = update_option('httpbl_deny_'.
					$value, ($_POST["deny_".$value] == 1 ?
					true : false));
			}
			update_option('httpbl_hp', $_POST["hp"] );
			update_option('httpbl_log',
				( $_POST["enable_log"] == 1 ? true : false ));
			update_option('httpbl_log_blocked_only',
				( $_POST["log_blocked_only"] == 1 ?
				true : false ));
			update_option('httpbl_not_logged_ips',
				$_POST["not_logged_ips"] );
			update_option('httpbl_stats',
				( $_POST["enable_stats"] == 1 ? true : false ));
			update_option('httpbl_stats_pattern',
				$_POST["stats_pattern"] );
			update_option('httpbl_stats_link',
				$_POST["stats_link"] );
		}
		
		// Should we purge the log table?
		if (isset($_POST["httpbl_truncate"]))
			httpbl_truncate_log_table();

		// Should we delete the log table?
		if (isset($_POST["httpbl_drop"]))
			httpbl_drop_log_table();
		
		// Should we create a new log table?
		if (isset($_POST["httpbl_create"]))
			httpbl_create_log_table();
		
		// If we log, but there's no table.
		if (get_option('httpbl_log') and !httpbl_check_log_table()) {
			httpbl_create_log_table();
		}

		// If it seems like the first launch,
		// few options should be set as defaults.
		if ( get_option( "httpbl_key" ) == "" )
			update_option( "httpbl_key" , "abcdefghijkl" );
		if ( get_option( "httpbl_age_thres" ) == 0 )
			update_option( "httpbl_age_thres" , "14" );
		if ( get_option( "httpbl_threat_thres" ) == 0 )
			update_option( "httpbl_threat_thres" , "30" );
		
		// Get data to be displayed in the form.
		$key = get_option('httpbl_key');
		$threat_thres = get_option('httpbl_threat_thres');
		$threat_thres_s = get_option('httpbl_threat_thres_s');
		$threat_thres_h = get_option('httpbl_threat_thres_h');
		$threat_thres_c = get_option('httpbl_threat_thres_c');
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
		$log_blocked_only_checkbox = ( 
			get_option('httpbl_log_blocked_only') ?
			"checked='true'" : "");
		$stats_checkbox = ( get_option('httpbl_stats') ?
			"checked='true'" : "");
		$stats_pattern = get_option('httpbl_stats_pattern');
		$stats_link = get_option('httpbl_stats_link');
		$stats_link_radio = array();
		for ($i = 0; $i < 3; $i++) {
			if ($stats_link == $i) {
				$stats_link_radio[$i] = "checked='true'";
				break;
			}
		}

		// The page contents.
?>
<div class='wrap'>
	<h2>http:BL WordPress Plugin</h2>
	<p><a href="#conf">Configuration</a>
<?php
	// No need to link to the log section, if we're not logging
	if (get_option("httpbl_log")) {
?>
| <a href="#log">Log</a></p>
<?php
	}
?>
	<p>The http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org/?rf=28499">Project Honey Pot</a> database.</p>
	<a name="conf"></a>
	<h3>Configuration</h3>
	<form action='' method='post' id='httpbl_conf'>
	<h4>Main options</h4>
		<p>http:BL Access Key <input type='text' name='key' value='<?php echo $key ?>' /> </p>
		<p><small>An Access Key is required to perform a http:BL query. You can get your key at <a href="http://www.projecthoneypot.org/httpbl_configure.php">http:BL Access Management page</a>. You need to register a free account at the Project Honey Pot website to get one.</small></p>
		<p>Age threshold <input type='text' name='age_thres' value='<?php echo $age_thres ?>'/></p>
		<p><small>http:BL service provides you information about the date of the last activity of a checked IP. Due to the fact that the information in the Project Honey Pot database may be obsolete, you may set an age threshold, counted in days. If the verified IP hasn't been active for a period of time longer than the threshold it will be regarded as harmless.</small></p>
		<p>General threat score threshold <input type='text' name='threat_thres' value='<?php echo $threat_thres ?>'/></p>
		<p><small>Each suspicious IP address is given a threat score. This scored is asigned by Project Honey Pot basing on various factors, such as the IP's activity or the damage done during the visits. The score is a number between 0 and 255, where 0 is no threat at all and 255 is extremely harmful. In the field above you may set the threat score threshold. IP address with a score greater than the given number will be regarded as harmful.</small></p>
		<p><ul>
		<li>Suspicious threat score threshold <input type='text' name='threat_thres_s' value='<?php echo $threat_thres_s ?>'/></li>
		<li>Harvester threat score threshold <input type='text' name='threat_thres_h' value='<?php echo $threat_thres_h ?>'/></li>
		<li>Comment spammer threat score threshold <input type='text' name='threat_thres_c' value='<?php echo $threat_thres_c ?>'/></li>
		</ul></p>
		<p><small>These values override the general threat score threshold. Leave blank to use the general threat score threshold.</small></p>
		<fieldset>
		<label>Types of visitors to be treated as malicious</label>
		<p><input type='checkbox' name='deny_1' value='1' <?php echo $deny_checkbox[1] ?>/> Suspicious</p>
		<p><input type='checkbox' name='deny_2' value='1' <?php echo $deny_checkbox[2] ?>/> Harvesters</p>
		<p><input type='checkbox' name='deny_4' value='1' <?php echo $deny_checkbox[4] ?>/> Comment spammers</p>
		</fieldset>
		<p><small>The field above allows you to specify which types of visitors should be regarded as harmful. It is recommended to tick all of them.</small></p>
		<p>Honey Pot <input type='text' name='hp' value='<?php echo $hp ?>'/></p>
		<p><small>If you've got a Honey Pot or a Quick Link you may redirect all unwelcome visitors to it. If you leave the following field empty all harmful visitors will be given a blank page instead of your blog.</small></p>
		<p><small>More details are available at the <a href="http://www.projecthoneypot.org/httpbl_api.php">http:BL API Specification page</a>.</small></p>
	<h4>Logging options</h4>
		<p>Enable logging <input type='checkbox' name='enable_log' value='1' <?php echo $log_checkbox ?>/></p>
		<p><small>If you enable logging all visitors which are recorded in the Project Honey Pot's database will be logged in the database and listed in the table below. Remember to create a proper table in the database before you enable this option!</small></p>
		<p>Log only blocked visitors <input type='checkbox' name='log_blocked_only' value='1' <?php echo $log_blocked_only_checkbox ?>/></p>
		<p><small>Enabling this option will result in logging only blocked visitors. The rest shall be forgotten.</small></p>
		<p>Not logged IP addresses <input type='text' name='not_logged_ips' value='<?php echo $not_logged_ips ?>'/></p>
		<p><small>Enter a space-separated list of IP addresses which will not be recorded in the log.</small></p>
	<h4>Statistics options</h4>
		<p>Enable stats <input type='checkbox' name='enable_stats' value='1' <?php echo $stats_checkbox ?>/></p>
		<p><small>If stats are enabled the plugin will get information about its performance from the database, allowing it to be displayed using <code>httpbl_stats()</code> function.</small></p>
		<p>Output pattern <input type='text' name='stats_pattern' value='<?php echo $stats_pattern ?>'/></p>
		<p><small>This input field allows you to specify the output format of the statistics. You can use following variables: <code>$block</code> will be replaced with the number of blocked visitors, <code>$pass</code> with the number of logged but not blocked visitors, and <code>$total</code> with the total number of entries in the log table. HTML is welcome. PHP won't be compiled.</small></p>
		<fieldset>
		<label>Output link</label>
		<p><input type="radio" name="stats_link" value="0" <?php echo $stats_link_radio[0]; ?>/> Disabled</p>
		<p><input type="radio" name="stats_link" value="1" <?php echo $stats_link_radio[1]; ?>/> <a href="http://www.projecthoneypot.org/?rf=28499">Project Honey Pot</a></p>
		<p><input type="radio" name="stats_link" value="2" <?php echo $stats_link_radio[2]; ?>/> <a href="http://wordpress.org/extend/plugins/httpbl/">http:BL WordPress Plugin</a></p>
		</fieldset>
		<p><small>Should we enclose the output specified in the field above with a hyperlink?</small></p>
	<div style="float:right"><a href="http://www.projecthoneypot.org/?rf=28499"><img src="<?php echo get_option("siteurl") . "/wp-content/plugins/httpbl/";?>project_honey_pot_button.png" height="31px" width="88px" border="0" alt="Stop Spam Harvesters, Join Project Honey Pot"></a></div>
		<p><input type='submit' name='httpbl_save' value='Save Settings' /></p>
	</form>
<?php
	if (get_option("httpbl_log")) {
?>
	<hr/>
	<a name="log"></a>
	<h3>Log</h3>
	<form action='' method='post' name='httpbl_log'><p>
<?php
	// Does a log table exist?
	$httpbl_table_exists = httpbl_check_log_table();
	// If it exists display a log purging form and output log
	// in a nice XHTML table.
	if ($httpbl_table_exists === true) {
?>
	<script language="JavaScript"><!--
	var response;
	// Delete or purge confirmation.
	function httpblConfirm(action) {
		response = confirm("Do you really want to "+action+
			" the log table ?");
		return response;
	}
	//--></script>
	<input type='submit' name='httpbl_truncate' value='Purge the log table' onClick='return httpblConfirm("purge")'/>
	<input type='submit' name='httpbl_drop' value='Delete the log table' style="margin:0 0 0 30px" onClick='return httpblConfirm("delete")'/>
	</p></form>
	<p>A list of 50 most recent visitors listed in the Project Honey Pot's database.</p>
	<table cellpadding="5px" cellspacing="3px">
	<tr>
		<th>ID</th>
		<th>IP</th>
		<th>Date</th>
		<th>User agent</th>
		<th>Last seen<sup>1</sup></th>
		<th>Threat</th>
		<th>Type<sup>2</sup></th>
		<th>Blocked</th>
	</tr>
<?php
	// Table with logs.
	// Get data from the database.
	$results = httpbl_get_log();
	$i = 0;
	$threat_type = array( "", "S", "H", "S/H", "C", "S/C", "H/C", "S/H/C");
	foreach ($results as $row) {
		// Odd and even rows look differently.
		$style = ($i++ % 2 ? " class='alternate'" : "" );
		echo "\n\t<tr$style>";
		foreach ($row as $key => $val) {
			if ($key == "ip")
				// IP address lookup in the Project Honey Pot database.
				$val = "<a href='http://www.projecthoneypot.org/ip_" . $val .
					"' target='_blank'>" . $val . "</a>";
			if ($key == "user_agent")
				// In case the user agent string contains
				// unwelcome characters.
				$val = htmlentities($val, ENT_QUOTES);
			if ($key == "blocked")
				$val = ($val ? "<strong>YES</strong>" : "No");
			if ($key == "httpbl_response") {
				// Make the http:BL response human-readible.
				$octets = explode( ".", $val);
				$plural = ( $octets[1] == 1 ? "" : "s");
				$lastseen = $octets[1]." day$plural";
				$td = "\n\t\t<td><small>$lastseen</small></td>".
					"\n\t\t<td><small>".$octets[2].
					"</small></td>\n\t\t<td><small>".
					$threat_type[$octets[3]].
					"</small></td>";
			} else {
				// If it's not an http:BL response it's
				// displayed in one column.
				$td = "\n\t\t<td><small>$val</small></td>";
			}
			echo $td;
		}
		echo "\n\t</tr>";
	}
?>
	</table>
	<p><small><sup>1</sup> Counting from the day of visit.</small></p>
	<p><small><sup>2</sup> S - suspicious, H - harvester, C - comment spammer.</small></p>
<?php
	} else if ($httpbl_table_exists === false) {
?>
	It seems that you haven't got a log table yet. Maybe you'd like to <input type='submit' name='httpbl_create' value='create it' /> ?
	</p></form>
<?php
	}

	// End of if (get_option("httpbl_log"))
	}
?>
</div>
<?php
	}	
?>

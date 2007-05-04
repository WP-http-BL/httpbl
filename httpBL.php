<?php
/*
Plugin Name: http:BL
Plugin URI: http://stepien.com.pl/2007/04/28/httpbl_wordpress_plugin/
Description: http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org">Project Honey Pot</a> database. 
Author: Jan Stępień
Version: SVN
Author URI: http://stepien.com.pl
License: This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
*/
	
	add_action("init", "httpbl_check_referer");
	add_action("admin_menu", "httpbl_config_page");
	
	function httpbl_check_referer()
	{
		$key = get_option( "httpbl_key" );
		$result = explode( ".", gethostbyname( $key . "." .
			implode ( ".", array_reverse( explode( ".",
			$_SERVER["REMOTE_ADDR"] ) ) ) . ".dnsbl.httpbl.org" ) );
		if ( $result[0] == 127 ) {
			$age_thres = get_option('httpbl_age_thres');
			$threat_thres = get_option('httpbl_threat_thres');
			$denied = get_option('httpbl_denied');
			$hp = get_option('httpbl_hp');
			
			$age = false;
			$threat = false;
			$deny = false;
			
			if ( $result[1] < $age_thres )
				$age = true;
			if ( $result[2] > $threat_thres )
				$threat = true;
			foreach ( explode( ",", $denied ) as $value ) {
				if ( $value == $result[3] )
					$deny = true;
			}
			if ( $deny && $age && $threat ) {
				if ( $hp ) {
					header( "HTTP/1.1 301 Moved Permanently ");
					header( "Location: $hp" );
				}
				die();
			}
		}
	}


	function httpbl_config_page()
	{
		add_submenu_page("plugins.php", "http:BL Configuration", "http:BL", 10, __FILE__, "httpbl_configuration");
	}

	function httpbl_configuration()
	{
		if($_POST["httpbl_save"])
		{
			update_option('httpbl_key', $_POST["key"] );
			update_option('httpbl_age_thres', $_POST["age_thres"] );
			update_option('httpbl_threat_thres', $_POST["threat_thres"] );
			update_option('httpbl_denied', $_POST["denied"] );
			update_option('httpbl_hp', $_POST["hp"] );
		}

		if ( get_option( "httpbl_key" ) == "" ) {
			update_option( "httpbl_key" , "abcdefghijkl" );
		}
		if ( get_option( "httpbl_age_thres" ) == 0 )
			update_option( "httpbl_age_thres" , "14" );
		if ( get_option( "httpbl_threat_thres" ) == 0 )
			update_option( "httpbl_threat_thres" , "30" );
		if ( get_option( "httpbl_denied" ) == "" )
			update_option( "httpbl_denied" , "1,2,3,4,5,6,7" );
					
		$key = get_option('httpbl_key');
		$threat_thres = get_option('httpbl_threat_thres');
		$age_thres = get_option('httpbl_age_thres');
		$denied = get_option('httpbl_denied');
		$hp = get_option('httpbl_hp');
?>
<div class='wrap'>
	<h2>http:BL WordPress Plugin</h2>
	<p>The http:BL WordPress Plugin allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org">Project Honey Pot</a> database.</p>
	<form action='' method='post' id='httpbl'>
		<p>Access Key is required to perform a http:BL query. You can get your key at <a href="http://www.projecthoneypot.org/httpbl_configure.php">http:BL Access Management page</a>. You need to register a free account at the Project Honey Pot website to get one.
		<p>http:BL Access Key <input type='text' name='key' value='<?php echo $key ?>' /> </p>
		<p>http:BL service provides you information about the date of the last activity of a checked IP. Due to the fact that the information in the Project Honey Pot database may be obsolete, you may set a age threshold, counted in days. If the verified IP hasn't been active for a period of time longer than the threshold it will be regarded as harmless.</p>
		<p>Age threshold <input type='text' name='age_thres' value='<?php echo $age_thres ?>'/></p>
		<p>Each suspicious IP address is given a threat score. This scored is asigned by Project Honey Pot basing on various factors, such as the IP's activity or the damage done during the visits. The score is a number between 0 to 255, where 0 is no threat at all and 255 is extremely harmful. In the following field you may decide the threat score threshold. IP address with a score greater than the given number will be regarded as harmful.</p>
		<p>Threat score threshold <input type='text' name='threat_thres' value='<?php echo $threat_thres ?>'/></p>
		<p>The following field allow you to specify comma-seperated list containing types of visitors which should be regarded as harmful. The available types are:</p>
		<ul>
			<li>0 - Search Engine</li>
			<li>1 - Suspicious</li>
			<li>2 - Harvester</li>
			<li>4 - Comment Spammer</li>
		</ul>
		<p> More details are available at the <a href="http://www.projecthoneypot.org/httpbl_api.php">http:BL API Specification page</a>.
		<p>Denied visitors <input type='text' name='denied' value='<?php echo $denied ?>'/></p>
		<p>If you've got a Honey Pot or a Quick Link you may redirect all unwelcome visitors to it. If you leave the following field empty all harmful visitors will be given a blank page instead of your blog.</p>
		<p>Honey Pot <input type='text' name='hp' value='<?php echo $hp ?>'/></p>
	<div style="float:right"><a href="http://www.projecthoneypot.org/?rf=28499"><img src="<?php echo get_option("siteurl") . "/wp-content/plugins/httpBL/";?>project_honey_pot_button.png" height="31px" width="88px" border="0" alt="Stop Spam Harvesters, Join Project Honey Pot"></a></div>
		<p><input type='submit' name='httpbl_save' value='Save Settings' /></p>
	</form>
</div>
<?php
	}	
?>

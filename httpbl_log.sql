/*
 * Remember to replace wp_ accordingly to your WordPress setup.
 */
CREATE TABLE `wp_httpbl_log` (
	`id` INT( 6 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`ip` VARCHAR( 16 ) NOT NULL DEFAULT 'unknown' ,
	`time` DATETIME NOT NULL ,
	`user_agent` VARCHAR( 255 ) NOT NULL DEFAULT 'unknown' ,
	`httpbl_response` VARCHAR( 16 ) NOT NULL ,
	`blocked` BOOL NOT NULL
)

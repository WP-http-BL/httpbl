/*
 * If you are creating the table manually,
 * do remember to replace %PREFIX% accordingly to your WordPress setup.
 */
CREATE TABLE `%PREFIX%httpbl_log` (
	`id` INT( 6 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`ip` VARCHAR( 16 ) NOT NULL DEFAULT 'unknown' ,
	`time` DATETIME NOT NULL ,
	`user_agent` VARCHAR( 255 ) NOT NULL DEFAULT 'unknown' ,
	`httpbl_response` VARCHAR( 16 ) NOT NULL ,
	`blocked` BOOL NOT NULL
)

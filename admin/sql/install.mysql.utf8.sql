-- ==================================
-- Drop existing tables if they exist
-- ==================================


DROP TABLE IF EXISTS `#__scaler_image`;
DROP TABLE IF EXISTS `#__scaler_path`;

-- ==================================
-- Create tables
-- ==================================

CREATE TABLE `#__scaler_image` (
	`path_id` INT(11) UNSIGNED NOT NULL,
	`width` INT(5) UNSIGNED NOT NULL,
	`height` INT(5) UNSIGNED NOT NULL,
	`request_date` DATETIME NOT NULL,
	`create_date` DATETIME NOT NULL,
	PRIMARY KEY  (`path_id`, `width`, `height`),
	INDEX (`request_date`),
	FOREIGN KEY (`path_id`) REFERENCES `#__scaler_path` (`id`)
		ON DELETE CASCADE
		ON UPDATE CASCADE
)
ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__scaler_path` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`path_hash` INT UNSIGNED NOT NULL,
	`path` VARCHAR(1024) NOT NULL,
	PRIMARY KEY  (`id`),
	KEY (`path_hash`)
)
ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


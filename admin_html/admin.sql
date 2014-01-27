CREATE TABLE site (
	id VARCHAR(16) NOT NULL,
	description VARCHAR(128) NOT NULL,
	last_contact DATETIME,
	absence_reported TINYINT(1),
	PRIMARY KEY(id)
) ENGINE=INNODB;

CREATE TABLE site_info (
	site_id VARCHAR(16) NOT NULL,
	inst_type ENUM('unknown', 'public', 'private', 'commercial') DEFAULT 'unknown',
	completed VARCHAR(32) DEFAULT 'Not Complete',
	panel_desc VARCHAR(128) DEFAULT 'Many Panels',
	panel_angle VARCHAR(128) DEFAULT 'Highly South',
	inverter VARCHAR(128) DEFAULT 'Yes',
	rated_output INT DEFAULT 1000,
	installer VARCHAR(128) DEFAULT 'SolarYpsi Volunteers',
	contact VARCHAR(128) DEFAULT 'Davesensi',
	list_desc VARCHAR(1024) DEFAULT 'Description for list of all sites page',
	status ENUM('complete', 'planned') DEFAULT 'planned',
	loc_city ENUM('in', 'out') DEFAULT 'in',
	loc_long FLOAT DEFAULT 0.0,
	loc_lat FLOAT DEFAULT 0.0,
	max_wh INT DEFAULT 0,
	max_kw FLOAT DEFAULT 0.0,
	meter_type ENUM('none', 'solarypsi', 'enphase') DEFAULT 'none',
	PRIMARY KEY(site_id),
	FOREIGN KEY(site_id) REFERENCES site(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=INNODB;

CREATE TABLE site_resource (
	id UNSIGNED SMALLINT NOT NULL AUTO_INCREMENT,
	site_id VARCHAR(16) NOT NULL,
	res_type ENUM('image', 'document', 'report', 'qr_video') NOT NULL,
	disp_order SMALLINT NOT NULL,
	title VARCHAR(128) NOT NULL,
	res_desc VARCHAR(512) DEFAULT '',
	file_path VARCHAR(512) NOT NULL,
	width INT DEFAULT 0,
	height INT DEFAULT 0,
	thumb_width INT DEFAULT 0,
	thumb_height INT DEFAULT 0,
	deleted TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY(id),
	FOREIGN KEY(site_id) REFERENCES site(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=INNODB;

CREATE TABLE website_link (
	id UNSIGNED TINYINT NOT NULL AUTO_INCREMENT,
	title VARCHAR(256) NOT NULL,
	link_desc VARCHAR(256),
	visible_link VARCHAR(256) NOT NULL,
	full_link VARCHAR(1024) NOT NULL,
	disp_order INT NOT NULL,
	PRIMARY KEY (id)
) ENGINE=INNODB;

CREATE TABLE website_presentation (
	id UNSIGNED TINYINT NOT NULL AUTO_INCREMENT,
	title VARCHAR(128) NOT NULL,
	pres_type ENUM('file', 'video') NOT NULL,
	pres_path VARCHAR(512) NOT NULL,
	file_type ENUM('external', 'flash') NOT NULL DEFAULT 'external',
	preview_image_path VARCHAR(512),
	PRIMARY KEY (id)
) ENGINE=INNODB;
-- phpMyAdmin SQL Dump
-- version 4.1.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 06, 2014 at 10:09 PM
-- Server version: 5.5.32-cll-lve
-- PHP Version: 5.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `solaryps_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `cron_log`
--

CREATE TABLE IF NOT EXISTS `cron_log` (
  `name` varchar(32) NOT NULL,
  `run_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `result` varchar(16) NOT NULL DEFAULT '',
  `message` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`,`run_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cron_schedule`
--

CREATE TABLE IF NOT EXISTS `cron_schedule` (
  `name` varchar(32) NOT NULL,
  `path` varchar(512) NOT NULL,
  `schedule` varchar(64) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `error_email` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `data_daily`
--

CREATE TABLE IF NOT EXISTS `data_daily` (
  `site_id` varchar(16) NOT NULL,
  `point_date` date NOT NULL,
  `point_index` smallint(5) unsigned NOT NULL,
  `inflow` float DEFAULT NULL,
  `outflow` float DEFAULT NULL,
  `generation` float DEFAULT NULL,
  `inflow_purchased` float DEFAULT NULL,
  `inflow_mixed` float DEFAULT NULL,
  `inflow_free` float DEFAULT NULL,
  PRIMARY KEY (`site_id`,`point_date`,`point_index`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `data_monthly`
--

CREATE TABLE IF NOT EXISTS `data_monthly` (
  `site_id` varchar(16) NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `inflow` float DEFAULT NULL,
  `outflow` float DEFAULT NULL,
  `generation` float DEFAULT NULL,
  PRIMARY KEY (`site_id`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `data_weekly`
--

CREATE TABLE IF NOT EXISTS `data_weekly` (
  `site_id` varchar(16) NOT NULL,
  `point_date` date NOT NULL,
  `point_index` tinyint(3) unsigned NOT NULL,
  `inflow` float DEFAULT NULL,
  `outflow` float DEFAULT NULL,
  `generation` float DEFAULT NULL,
  PRIMARY KEY (`site_id`,`point_date`,`point_index`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `data_yearly`
--

CREATE TABLE IF NOT EXISTS `data_yearly` (
  `site_id` varchar(16) NOT NULL,
  `point_date` date NOT NULL,
  `point_year` int(11) NOT NULL,
  `point_month` int(11) NOT NULL,
  `inflow` float DEFAULT NULL,
  `outflow` float DEFAULT NULL,
  `generation` float DEFAULT NULL,
  PRIMARY KEY (`site_id`,`point_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `enphase_system`
--

CREATE TABLE IF NOT EXISTS `enphase_system` (
  `site_id` varchar(16) NOT NULL,
  `earliest_date` varchar(10) NOT NULL,
  `system_id` varchar(8) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `num_units` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `historical_system`
--

CREATE TABLE IF NOT EXISTS `historical_system` (
  `site_id` varchar(16) NOT NULL,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `images_to_convert`
--

CREATE TABLE IF NOT EXISTS `images_to_convert` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `path` varchar(512) NOT NULL,
  `thumb_width` int(11) NOT NULL,
  `thumb_height` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `site`
--

CREATE TABLE IF NOT EXISTS `site` (
  `id` varchar(16) NOT NULL,
  `description` varchar(128) NOT NULL,
  `last_contact` datetime DEFAULT NULL,
  `absence_reported` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `site_info`
--

CREATE TABLE IF NOT EXISTS `site_info` (
  `site_id` varchar(16) NOT NULL,
  `inst_type` enum('unknown','public','municipal','private','semiprivate','commercial','demonstration') DEFAULT 'unknown',
  `completed` varchar(32) DEFAULT 'Not Complete',
  `panel_desc` varchar(128) DEFAULT 'Many Panels',
  `panel_angle` varchar(128) DEFAULT 'Highly South',
  `inverter` varchar(128) DEFAULT 'Yes',
  `rated_output` int(11) DEFAULT '1000',
  `installer` varchar(128) DEFAULT 'SolarYpsi Volunteers',
  `installer_url` varchar(512) NOT NULL,
  `contact` varchar(128) DEFAULT 'Davesensi',
  `contact_url` varchar(512) NOT NULL,
  `list_desc` varchar(1024) NOT NULL DEFAULT 'Description for list of all sites page',
  `status` enum('hidden','active','inactive') DEFAULT 'inactive',
  `loc_city` enum('in','out') DEFAULT 'in',
  `loc_long` float DEFAULT '0',
  `loc_lat` float DEFAULT '0',
  `max_wh` int(11) NOT NULL DEFAULT '0',
  `max_kw` float NOT NULL DEFAULT '0',
  `meter_type` enum('none','solarypsi','enphase','historical') DEFAULT 'none',
  `qr_code` varchar(16) DEFAULT NULL,
  `max_y_axis` smallint(5) unsigned zerofill DEFAULT NULL,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `site_resource`
--

CREATE TABLE IF NOT EXISTS `site_resource` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(16) NOT NULL,
  `res_type` enum('image','document','report','link','qr_video') NOT NULL,
  `disp_order` smallint(6) NOT NULL,
  `title` varchar(128) NOT NULL,
  `res_desc` varchar(512) DEFAULT '',
  `file_path` varchar(512) NOT NULL,
  `width` int(11) DEFAULT '0',
  `height` int(11) DEFAULT '0',
  `thumb_width` int(11) DEFAULT '0',
  `thumb_height` int(11) DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_resource_display` (`site_id`,`deleted`,`res_type`,`disp_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=778 ;

-- --------------------------------------------------------

--
-- Table structure for table `weather_data`
--

CREATE TABLE IF NOT EXISTS `weather_data` (
  `site_id` varchar(16) NOT NULL,
  `day` varchar(10) NOT NULL,
  `sunrise_hour` tinyint(3) unsigned NOT NULL,
  `sunrise_minute` tinyint(3) unsigned NOT NULL,
  `noon_hour` tinyint(3) unsigned NOT NULL,
  `noon_minute` tinyint(3) unsigned NOT NULL,
  `sunset_hour` tinyint(3) unsigned NOT NULL,
  `sunset_minute` tinyint(3) unsigned NOT NULL,
  `description` varchar(128) NOT NULL,
  `icon` varchar(64) NOT NULL,
  `temperature_min` smallint(6) NOT NULL,
  `temperature_min_time` varchar(10) NOT NULL,
  `temperature_max` smallint(6) NOT NULL,
  `temperature_max_time` varchar(10) NOT NULL,
  `apparent_temperature_min` smallint(6) NOT NULL,
  `apparent_temperature_min_time` varchar(10) NOT NULL,
  `apparent_temperature_max` smallint(6) NOT NULL,
  `apparent_temperature_max_time` varchar(10) NOT NULL,
  PRIMARY KEY (`site_id`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `website_link`
--

CREATE TABLE IF NOT EXISTS `website_link` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `link_desc` varchar(256) DEFAULT NULL,
  `visible_link` varchar(256) NOT NULL,
  `full_link` varchar(1024) NOT NULL,
  `disp_order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Table structure for table `website_presentation`
--

CREATE TABLE IF NOT EXISTS `website_presentation` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `pres_type` enum('file','video') NOT NULL,
  `pres_path` varchar(512) NOT NULL,
  `file_type` enum('external','flash') NOT NULL DEFAULT 'external',
  `preview_image_path` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `data_daily`
--
ALTER TABLE `data_daily`
  ADD CONSTRAINT `data_daily_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

--
-- Constraints for table `data_monthly`
--
ALTER TABLE `data_monthly`
  ADD CONSTRAINT `data_monthly_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

--
-- Constraints for table `data_weekly`
--
ALTER TABLE `data_weekly`
  ADD CONSTRAINT `data_weekly_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

--
-- Constraints for table `data_yearly`
--
ALTER TABLE `data_yearly`
  ADD CONSTRAINT `data_yearly_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

--
-- Constraints for table `enphase_system`
--
ALTER TABLE `enphase_system`
  ADD CONSTRAINT `enphase_system_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

--
-- Constraints for table `historical_system`
--
ALTER TABLE `historical_system`
  ADD CONSTRAINT `historical_system_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

--
-- Constraints for table `site_info`
--
ALTER TABLE `site_info`
  ADD CONSTRAINT `site_info_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `site_resource`
--
ALTER TABLE `site_resource`
  ADD CONSTRAINT `site_resource_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `weather_data`
--
ALTER TABLE `weather_data`
  ADD CONSTRAINT `weather_data_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

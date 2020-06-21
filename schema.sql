-- Adminer 4.7.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `minerbadges`;
CREATE TABLE `minerbadges` (
  `minername` varchar(255) NOT NULL,
  `badgeclass` varchar(255) NOT NULL,
  `badgestate` text NOT NULL,
  PRIMARY KEY (`badgeclass`,`minername`) USING BTREE,
  KEY `minername` (`minername`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `minerscore`;
CREATE TABLE `minerscore` (
  `username` varchar(255) NOT NULL,
  `score` int NOT NULL DEFAULT '0',
  `month` int NOT NULL DEFAULT '20189',
  PRIMARY KEY (`username`,`month`) USING BTREE,
  KEY `index_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `minerstatus`;
CREATE TABLE `minerstatus` (
  `ip_addr` varchar(255) NOT NULL,
  `last_action_at` datetime NOT NULL,
  `last_action_change` datetime DEFAULT NULL,
  `action` int NOT NULL,
  PRIMARY KEY (`ip_addr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `seedqueue`;
CREATE TABLE `seedqueue` (
  `id_seedqueue` int NOT NULL AUTO_INCREMENT,
  `id0` varchar(32) NOT NULL,
  `part1b64` varchar(4096) NOT NULL,
  `friendcode` varchar(12) DEFAULT NULL,
  `taskId` varchar(32) NOT NULL,
  `state` int NOT NULL DEFAULT '0',
  `keyY` varchar(32) DEFAULT NULL,
  `time_started` datetime NOT NULL,
  `ip_addr` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_seedqueue`),
  KEY `index_state` (`state`),
  KEY `taskId_state` (`friendcode`,`id0`),
  KEY `tid_index` (`taskId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2020-06-21 16:47:53

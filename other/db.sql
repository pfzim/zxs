CREATE DATABASE `zxs` /*!40100 DEFAULT CHARACTER SET utf8 */;

DROP TABLE IF EXISTS `zxs`.`zxs_files`;
CREATE TABLE  `zxs`.`zxs_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(2048) NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `expire` date DEFAULT NULL,
  `desc` varchar(4096) NOT NULL,
  `deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=232 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `zxs`.`zxs_link_files`;
CREATE TABLE  `zxs`.`zxs_link_files` (
  `lid` int(10) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `zxs`.`zxs_links`;
CREATE TABLE  `zxs`.`zxs_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `pin` varchar(4) CHARACTER SET latin1 NOT NULL,
  `desc` varchar(4096) NOT NULL,
  `deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `zxs`.`zxs_log`;
CREATE TABLE  `zxs`.`zxs_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `oid` int(10) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL,
  `ip` varchar(256) NOT NULL,
  `desc` varchar(1024) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=297 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `zxs`.`zxs_users`;
CREATE TABLE  `zxs`.`zxs_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `mail` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


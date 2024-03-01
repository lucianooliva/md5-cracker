CREATE TABLE IF NOT EXISTS `rainbow_table` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `word` varbinary(256) NOT NULL DEFAULT '',
  `hash` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `key_hash` (`hash`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
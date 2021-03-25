<?php
$installer = $this;

$connection = $installer->getConnection();

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('paysafecash_data')}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) unsigned DEFAULT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `cid` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `amount` DECIMAL(12,4),
  `currency` char(3),
  `error_no` varchar(50) DEFAULT NULL,
  `error_txt` varchar(150) DEFAULT NULL,
  `http_status` varchar(50) DEFAULT NULL,
  `mid` varchar(50) DEFAULT NULL,
  `mtid` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `status` varchar(150) DEFAULT NULL,
  `paysafecashurl` text DEFAULT NULL,
  `webhook` varchar(150) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$installer->endSetup();
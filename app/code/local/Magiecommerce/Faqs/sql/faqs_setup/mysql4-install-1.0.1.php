<?php

$installer = $this;
$installer->startSetup();
$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('ltpl_faq_faqitems')} (
  `faqs_id` int(11) unsigned NOT NULL auto_increment,
  `store_id` SMALLINT(5) UNSIGNED NOT NULL,
  `cat_id` int(11) unsigned NOT NULL,
  `cat_name` varchar(255) DEFAULT '',
  `itemsortorder` smallint(5) DEFAULT '0',
  `status` smallint(6) NOT NULL default '0',
  `quetion` text  NOT NULL,
  `answer` text NOT NULL,
  PRIMARY KEY (`faqs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('ltpl_faq_categories')} (
  `cat_id` int(11) unsigned NOT NULL auto_increment,
  `store_id` SMALLINT(5) UNSIGNED NOT NULL,
  `cat_name` varchar(255) DEFAULT '',
  `cat_sortorder` smallint(5) DEFAULT '0',
  `status` smallint(6) NOT NULL default '0',
  `description` text,
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");


$installer->endSetup();


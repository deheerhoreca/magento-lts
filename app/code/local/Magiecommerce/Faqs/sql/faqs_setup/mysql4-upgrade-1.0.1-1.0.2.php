<?php
$installer = $this;
$installer->startSetup();
$installer->run("
	ALTER TABLE {$this->getTable('ltpl_faq_categories')}
	ADD COLUMN `parentcatid` smallint(5) DEFAULT '0' AFTER `cat_id`,
	ADD COLUMN `urlkey` varchar(100) DEFAULT NULL AFTER `parentcatid`,
	ADD COLUMN `page_layout` text DEFAULT NULL AFTER `urlkey`,
	ADD COLUMN `metatitle` varchar(255) DEFAULT NULL AFTER `description`,
	ADD COLUMN `metakeyword` varchar(255) DEFAULT NULL AFTER `metatitle`,
	ADD COLUMN `metadescription` varchar(255) DEFAULT NULL AFTER `metakeyword`;
	");
$installer->endSetup();
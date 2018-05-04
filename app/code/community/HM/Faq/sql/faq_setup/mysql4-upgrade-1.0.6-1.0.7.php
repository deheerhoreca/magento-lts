<?php
/**
 * FAQ accordion for Magento

 */

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE `{$this->getTable('hm_faq/faq')}`
    MODIFY COLUMN `creation_time` DATETIME NOT NULL;
ALTER TABLE `{$this->getTable('hm_faq/faq')}`
    MODIFY COLUMN `update_time` DATETIME NOT NULL;

CREATE TABLE `{$this->getTable('hm_faq/category')}` (
    `category_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT(10) UNSIGNED NULL,
    `category_name` VARCHAR(255) NOT NULL,
    `creation_time` DATETIME NOT NULL,
    `update_time` DATETIME NOT NULL,
    `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`category_id`),
    CONSTRAINT `FK_FAQ_CATEGORY_PARENT_ID` FOREIGN KEY (`parent_id`) REFERENCES `{$this->getTable('hm_faq/category')}` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='FAQ Categories';

-- DROP TABLE IF EXISTS `{$this->getTable('hm_faq/category_item')}`;
CREATE TABLE `{$this->getTable('hm_faq/category_item')}` (
  `category_id` INT(10) UNSIGNED NOT NULL,
  `faq_id` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`category_id`,`faq_id`),
  CONSTRAINT `FK_FAQ_CATEGORY_ITEM_CATEGORY` FOREIGN KEY (`category_id`) REFERENCES `{$this->getTable('hm_faq/category')}` (`category_id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `FK_FAQ_CATEGORY_ITEM_ITEM` FOREIGN KEY (`faq_id`) REFERENCES `{$this->getTable('hm_faq/faq')}` (`faq_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='FAQ Items to Cateories';

-- DROP TABLE IF EXISTS `{$this->getTable('hm_faq/category_store')}`;
CREATE TABLE `{$this->getTable('hm_faq/category_store')}` (
  `category_id` INT(10) UNSIGNED NOT NULL,
  `store_id` SMALLINT(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`category_id`,`store_id`),
  CONSTRAINT `FK_FAQ_CATEGORY_STORE_CATEGORY` FOREIGN KEY (`category_id`) REFERENCES `{$this->getTable('hm_faq/category')}` (`category_id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `FK_FAQ_CATEGORY_STORE_STORE` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='FAQ Categories to Stores';

");

$installer->endSetup();

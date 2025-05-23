<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_SalesRule
 */

/** @var Mage_Sales_Model_Resource_Setup $installer */
$installer = $this;

$installer->run("
CREATE TABLE `{$this->getTable('salesrule/product_attribute')}` (
  `rule_id` int(10) unsigned NOT NULL,
  `website_id` smallint(5) unsigned NOT NULL,
  `customer_group_id` smallint(5) unsigned NOT NULL,
  `attribute_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`rule_id`,`website_id`,`customer_group_id`,`attribute_id`),
  KEY `IDX_WEBSITE` (`website_id`),
  KEY `IDX_CUSTOMER_GROUP` (`customer_group_id`),
  KEY `IDX_ATTRIBUTE` (`attribute_id`),
  CONSTRAINT `FK_SALESRULE_PRODUCT_ATTRIBUTE_ATTRIBUTE` FOREIGN KEY (`attribute_id`) REFERENCES `{$this->getTable('eav/attribute')}` (`attribute_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_SALESRULE_PRODUCT_ATTRIBUTE_CUSTOMER_GROUP` FOREIGN KEY (`customer_group_id`) REFERENCES `{$this->getTable('customer/customer_group')}` (`customer_group_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_SALESRULE_PRODUCT_ATTRIBUTE_RULE` FOREIGN KEY (`rule_id`) REFERENCES `{$this->getTable('salesrule/rule')}` (`rule_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_SALESRULE_PRODUCT_ATTRIBUTE_WEBSITE` FOREIGN KEY (`website_id`) REFERENCES `{$this->getTable('core/website')}` (`website_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

/**
 * We need to fill new table with product attributes already used in promo rules
 */
$installer->run("
INSERT INTO `{$this->getTable('salesrule/product_attribute')}`
    SELECT
      sr.rule_id,
      cw.website_id,
      cg.customer_group_id,
      ea.attribute_id
    FROM `{$this->getTable('salesrule/rule')}` AS sr
      INNER JOIN `{$this->getTable('core/website')}` AS cw
        ON FIND_IN_SET(cw.website_id, sr.website_ids)
      INNER JOIN `{$this->getTable('customer/customer_group')}` AS cg
        ON FIND_IN_SET(cg.customer_group_id , sr.customer_group_ids)
      INNER JOIN `{$this->getTable('eav/attribute')}` AS ea
        ON ea.entity_type_id = {$installer->getEntityTypeId('catalog_product')}
    WHERE
      sr.conditions_serialized LIKE CONCAT('%s:32:\"salesrule/rule_condition_product\";s:9:\"attribute\";s:', LENGTH(ea.attribute_code), ':\"', ea.attribute_code, '\"%')
      OR sr.actions_serialized LIKE CONCAT('%s:32:\"salesrule/rule_condition_product\";s:9:\"attribute\";s:', LENGTH(ea.attribute_code), ':\"', ea.attribute_code, '\"%')
");

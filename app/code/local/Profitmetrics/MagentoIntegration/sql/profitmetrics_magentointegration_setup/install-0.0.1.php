<?php
/** @var Mage_Eav_Model_Entity_Setup $this */
/**
 * Create attribute "buy_price" and add it to all attribute sets (General attribute group)
 */

/** @var Mage_Eav_Model_Entity_Setup $installer */
$installer = $this;

$installer->startSetup();
/** @var Varien_Db_Adapter_Interface $connection */
$connection = $this->getConnection();
$attributeCode = 'buy_price';

$connection->beginTransaction();
try {
    $installer->addAttribute('catalog_product', $attributeCode, array(
        'type'          => 'decimal',
        'label'         => 'Buy Price',
        'input'         => 'text',
        'required'      => false,
        'default'       => '',
        'sort_order'    => 100,
        'user_defined'  => true,
        'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'group'         => 'General',
        'note'          => 'Price for each the product was bought by the store owner. Used in ProfitMetrics data'
    ));

    $entityTypeId = Mage::getSingleton('eav/config')->getEntityType(
        Mage_Catalog_Model_Product::ENTITY
    )->getId();


    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $attributeModel */
    $attributesCollection = Mage::getResourceModel('catalog/product_attribute_collection')
        ->addFieldToFilter('attribute_code', $attributeCode)
        ->addFieldToFilter('entity_type_id', $entityTypeId)
        ->setPageSize(1);

    $attribute = $attributesCollection->getFirstItem();

    foreach ($installer->getAllAttributeSetIds('catalog_product') as $attributeSetId)
    {
        try {
            $attributeGroupId = $installer->getAttributeGroupId('catalog_product', $attributeSetId, 'General');
        } catch (Exception $e) {
            $attributeGroupId = $installer->getDefaultAttributeGroupId('catalog_product', $attributeSetId);
        }
        $installer->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupId, $attribute->getId());
    }

    $connection->commit();
} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    $connection->rollBack();
    throw $exception;
}

$installer->endSetup();

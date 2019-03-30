<?php
//
// Magento Convert Attributes
// Author: Jeroen Schermer
// Original Author: Michele Marcucci
// Email: michele@simplicissimus.it
// Script to convert a old Magento text attribute to a new one in dropdown format (useful to be used in navigation layer)
//
// Modified by mechanicalgux:
//    - automatic temporary attribute creation
//    - automatic replacement of old attribute by the newly created attribute
//    - command line parameter to specify which attribute to convert
//
// Put this file in Magento var/tasks
// Usage: php text2dropdown.php attribute_code
//
// Tested with Magento 1.9.0.1
//

if(!$argv[1] || !$argv[2])
    die("Usage: php text2dropdown.php attribute_code new_attribute_code\n");

/**
 * Launch the process
 * @param $attrCodeOLD
 */
function launch($attrCodeOLD, $attrCodeNEW)
{
    $attrCodeTEMP = $attrCodeOLD."_temp".rand(1,1000);

    /** @var Mage_Eav_Model_Entity_Attribute $attributeOLD */
    $attributeOLD = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attrCodeOLD);

    //
    // Collect/Export all the current old attribute values
    // in a CSV file to be imported in the new one later
    //
    $collection = Mage::getModel('catalog/product')
        ->getCollection()
        ->addAttributeToSelect('sku')
        ->addAttributeToSelect($attrCodeOLD);

    $fp = fopen($attrCodeOLD.".csv", 'w+');
    foreach ($collection as $product) {
        $data = trim($product->getData($attrCodeOLD));
        if(!empty($data))
            fputcsv($fp, array($product->getId(), $data));
    }

    echo "CSV Created\n";

    //
    // Get unique values to create the new attribute
    //

    $_resource = Mage::getSingleton('core/resource');
    $_readConnection = $_resource->getConnection('core_read');
    $_query = "SELECT distinct value FROM catalog_product_entity_varchar WHERE attribute_id in (SELECT attribute_id FROM eav_attribute WHERE attribute_code='".$attrCodeOLD."') AND LENGTH(TRIM(value)) > 0";

    $values = $_readConnection->fetchCol($_query);

    //
    // Create the new attribute
    //
    createAttribute($attributeOLD->getStoreLabel(), $attrCodeTEMP, true);
    echo "New attribute created\n";

    //
    // Assign it to attribute sets
    //
    assignToAttributeSets($attributeOLD, $attrCodeTEMP);
    echo "New attribute assigned to attribute sets\n";

    //
    // Add the unique values to the new attribute
    //

    $installer = new Mage_Eav_Model_Entity_Setup('core_setup');
    $installer->startSetup();

    $iProductEntityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
    $aOption = array();
    $aOption['attribute_id'] = $installer->getAttributeId($iProductEntityTypeId, $attrCodeTEMP);
    for($iCount=0;$iCount<sizeof($values);$iCount++){
        $aOption['value']['option'.$iCount][0] = $values[$iCount];
    }

    $installer->addAttributeOption($aOption);

    $installer->endSetup();

    echo "New values configured:\n";

    //
    // Import all the products values into the new attribute
    // This could take a while if you have a large products catalog
    // Thanks to updateAttributes method we can save a lot of memory
    // running this step smoothly also on huge db
    //

    $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attrCodeTEMP); //change to your attribute code
    $allOptions = $attribute->getSource()->getAllOptions(true, true);
    foreach ($allOptions as $instance)
    {
        $myArray[$instance['label']] = $instance['value'];
    }

    if (($handle = fopen($attrCodeOLD.".csv", "r")) !== FALSE)
    {
        while (($data = fgetcsv($handle, 1000)) !== FALSE)
        {
            list($_id, $_value) = $data;

            if (empty($myArray[$_value]) or !isset($myArray[$_value]))
                $_v = null;
            else
                $_v = $myArray[$_value];

            Mage::getModel('catalog/product_action')->updateAttributes(array($_id), array($attrCodeTEMP => $_v), 0);
            echo $_id." Saved (".memory_get_usage().")\n";

        }
        fclose($handle);
    }

    unlink($attrCodeOLD.".csv");
    echo "New attribute configured successfully\n";

    //
    //// Rename the old attribute
    //

    $installer->removeAttribute('catalog_product', $attrCodeOLD);
    echo "Old attribute deleted\n";

    //
    // Rename temp attribute code to old attribute code
    //

    $installer->updateAttribute('catalog_product', $attrCodeTEMP, array('attribute_code' => $attrCodeOLD));
    echo "Temp attribute code renamed to the original attribute code\n";

    //
    // Reindex
    //

    echo "Reindexing...";
    $ids = array(1,2,3,4,5,6,7,8,9);
    foreach($ids as $id)
    {
        $process = Mage::getModel('index/process')->load($id);
        $process->reindexAll();
    }
    echo " Done\n";
}


/**
 * Create an attribute.
 *
 * @param $name
 * @param $attributeCode
 * @param bool $configurable
 * @param bool $visible
 */
function createAttribute($name, $attributeCode, $configurable = false, $visible = true)
{
    /** @var Mage_Catalog_Model_Resource_Setup $setup */
    $setup = Mage::getResourceModel('catalog/setup','catalog_setup');

    if($setup->getAttributeId('catalog_product', $attributeCode))
        return;

    $data = array(
        'label' => $name,
        'input' => 'text',
        'type'  => 'varchar',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible' => true,
        'unique' => false,
        'required' => false,
        'used_in_product_listing' => $visible,
        'visible_on_front' => $visible,
        'is_html_allowed_on_front' => false,
        'user_defined' => true,
    );

    if($configurable)
    {
        $data = array_merge($data, array(
            'input' => 'select',
            'is_configurable' => true,
            'option' => array('values' => array())
        ));
    }

    $setup->addAttribute('catalog_product', $attributeCode, $data);
}

/**
 * @param Mage_Eav_Model_Entity_Attribute $attributeToDuplicate
 * @param $newAttributeCode
 */
function assignToAttributeSets(Mage_Eav_Model_Entity_Attribute $attributeToDuplicate, $newAttributeCode)
{
    /** @var Mage_Catalog_Model_Resource_Setup $setup */
    $setup = Mage::getResourceModel('catalog/setup','catalog_setup');

    $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter($entityType);
    $attributeID = $setup->getAttributeId('catalog_product', $newAttributeCode);

    foreach($attributeSetCollection as $attributeSet)
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */

        $groups = Mage::getModel('eav/entity_attribute_group')
            ->getResourceCollection()
            ->setAttributeSetFilter($attributeSet->getAttributeSetId())
            ->setSortOrder()
            ->load();

        $found = false;

        foreach ($groups as $group)
        {
            /** @var Mage_Eav_Model_Entity_Attribute_Group $group */

            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->setAttributeGroupFilter($group->getId())
                ->addVisibleFilter()
                ->checkConfigurableProducts()
                ->load();

            if ($attributes->getSize() > 0)
            {
                foreach ($attributes->getItems() as $attribute)
                {
                    /* @var Mage_Eav_Model_Entity_Attribute $attribute */
                    if($attribute->getId() == $attributeToDuplicate->getId())
                    {
                        // OK to duplicate
                        $setup->addAttributeToSet('catalog_product', $attributeSet->getAttributeSetId(), $group->getId(), $attributeID);
                        $found = true;
                        break;
                    }
                }
            }

            if($found)
                break;
        }
    }
}

//
// Launch
//

require_once '../../app/Mage.php';
umask( 0 );
Mage::app();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

Mage::register('isSecureArea', true);

launch($argv[1], $argv[2]);

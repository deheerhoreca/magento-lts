<?php
/**
 * Add a field to store the billink workflow with order
 */
/**
 * @var Mage_Core_Model_Resource_Setup $this
 */
$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$fieldsToAdd = array(
    'sales/order' => array(
        'billink_workflow_number',
    ),
    'sales/quote' => array(
        'billink_workflow_number',
    ),
);

foreach($fieldsToAdd as $entity => $fields)
{
    $tableName = $installer->getTable($entity);

    foreach($fields as $field)
    {
        if(!$connection->tableColumnExists($tableName, $field))
        {
            $connection->addColumn(
                $tableName,
                $field,
                "varchar(255)"
            );
        }
        else
        {
            $installer->run('alter table ' .$tableName. ' modify '.$field.' varchar(255)');
        }
    }
}


/**
 * There was only 1 workflow setting, but with this new version there is a seperate workflow setting
 * for business and particulars. This script populates the new settings with the old data.
 */
$oldWorkflowSettingPath = 'payment/billink/billink_workflow_number';

/** @var Mage_Core_Model_Config $model */
$settings = Mage::getModel('core/config_data')
    ->getCollection()
    ->addFieldToFilter('path',$oldWorkflowSettingPath);

$model = Mage::getModel('core/config');
$resource = $model->getResourceModel();

foreach($settings as $setting)
{
    $resource->saveConfig('payment/billink/billink_workflow_number_personal', $setting['value'], $setting['scope'], $setting['scope_id']);
    $resource->saveConfig('payment/billink/billink_workflow_number_business', $setting['value'], $setting['scope'], $setting['scope_id']);
}



$installer->endSetup();
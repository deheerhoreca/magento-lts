<?php

/* @var $installer MagicToolbox_MagicZoomPlus_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$oldModulesInstalled = Mage::helper('magiczoomplus/params')->checkForOldModules();
if (!empty($oldModulesInstalled)) {
    $connectionObject = $installer->getConnection();
    $mtResult = $connectionObject->query("SELECT * FROM `{$this->getTable('magiczoomplus/settings')}`");
    if ($mtResult) {
        while ($mtRow = $mtResult->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($mtRow['value'])) {
                $mtSettings = unserialize($mtRow['value']);
                foreach ($mtSettings as $mtPlatform => $mtPlatformData) {
                    foreach ($mtPlatformData as $mtProfile => $mtProfileData) {
                        foreach ($mtProfileData as $mtParam => $mtValue) {
                            if ($mtParam == 'enable-effect' || $mtParam == 'include-headers-on-all-pages') {
                                $mtSettings[$mtPlatform][$mtProfile][$mtParam] = 'No';
                            }
                        }
                    }
                }
                $mtSettings = serialize($mtSettings);
                $installer->run("UPDATE `{$this->getTable('magiczoomplus/settings')}` SET `value` = '{$mtSettings}' WHERE `setting_id` = {$mtRow['setting_id']}");
            }
        }
    }
}

$attribute = $installer->getAttribute('catalog_product', 'product_videos');
if (!$attribute) {
    $installer->installEntities();
}

$installer->endSetup();


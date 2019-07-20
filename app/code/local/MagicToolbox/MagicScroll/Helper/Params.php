<?php

class MagicToolbox_MagicScroll_Helper_Params extends Mage_Core_Helper_Abstract
{

    public function checkForOldModules()
    {
        static $oldModulesInstalled = null;
        if ($oldModulesInstalled === null) {
            $oldModulesInstalled = array();
            $modules = array(
                'magicthumb' => 'Magic Thumb',
                'magic360' => 'Magic 360',
                'magiczoom' => 'Magic Zoom',
                'magiczoomplus' => 'Magic Zoom Plus',
                'magicslideshow' => 'Magic Slideshow',
            );
            $inModules = "'".implode("_setup', '", array_keys($modules))."_setup'";
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_read');
            $table = $resource->getTableName('core/resource');
            $result = $connection->query("SELECT * FROM `{$table}` WHERE `code` IN ({$inModules})");
            if ($result) {
                while ($module = $result->fetch(PDO::FETCH_ASSOC)) {
                    if (version_compare($module['version'], '4.12.0', '<')) {
                        $key = str_replace('_setup', '', $module['code']);
                        if ($this->isModuleEnabled('MagicToolbox_'.str_replace(' ', '', $modules[$key]))) {
                            $oldModulesInstalled[] = array('name' => $modules[$key], 'version' => $module['version']);
                        }
                    }
                }
            }
        }
        return $oldModulesInstalled;
    }

    public function getFixedDefaultValues()
    {
        $defaultValues = self::getDefaultValues();
        foreach ($defaultValues as $platform => $platformData) {
            foreach ($platformData as $profile => $profileData) {
                foreach ($profileData as $param => $value) {
                    if ($param == 'enable-effect' || $param == 'include-headers-on-all-pages') {
                        $defaultValues[$platform][$profile][$param] = 'No';
                    }
                }
            }
        }
        return $defaultValues;
    }

    public function getProfiles()
    {
        return array(
            'default' => 'Defaults',
            'customslideshowblock' => 'Homepage or custom block',
            'product' => 'Product page',
            'recentlyviewedproductsblock' => 'Recently Viewed Products block'
        );
    }

    public function getDefaultValues()
    {
        return array(
            'desktop' => array(
                'customslideshowblock' => array(
                    'enable-effect' => 'No',
                    'orientation' => 'horizontal'
                ),
                'product' => array(
                    'enable-effect' => 'Yes',
                    'arrows' => 'inside'
                ),
                'recentlyviewedproductsblock' => array(
                    'enable-effect' => 'No',
                    'thumb-max-width' => '235',
                    'thumb-max-height' => '235'
                )
            ),
            'mobile' => array(
            )
        );
    }

    public function getParamsMap($block)
    {
        $blocks = array(
            'default' => array(
                'General' => array(
                    'include-headers-on-all-pages'
                ),
                'Positioning and Geometry' => array(
                    'thumb-max-width',
                    'thumb-max-height',
                    'square-images'
                ),
                'Scroll' => array(
                    'width',
                    'height',
                    'orientation',
                    'mode',
                    'items',
                    'speed',
                    'autoplay',
                    'loop',
                    'step',
                    'arrows',
                    'pagination',
                    'easing',
                    'scrollOnWheel',
                    'lazy-load',
                    'scroll-extra-styles',
                    'show-image-title'
                ),
                'Miscellaneous' => array(
                    'link-to-product-page'
                )
            ),
            'customslideshowblock' => array(
                'General' => array(
                    'enable-effect',
                    'block-title'
                ),
                'Positioning and Geometry' => array(
                    'thumb-max-width',
                    'thumb-max-height',
                    'square-images'
                ),
                'Scroll' => array(
                    'width',
                    'height',
                    'orientation',
                    'mode',
                    'items',
                    'speed',
                    'autoplay',
                    'loop',
                    'step',
                    'arrows',
                    'pagination',
                    'easing',
                    'scrollOnWheel',
                    'lazy-load',
                    'scroll-extra-styles',
                    'show-image-title'
                )
            ),
            'product' => array(
                'General' => array(
                    'enable-effect'
                ),
                'Positioning and Geometry' => array(
                    'thumb-max-width',
                    'thumb-max-height',
                    'square-images'
                ),
                'Scroll' => array(
                    'width',
                    'height',
                    'orientation',
                    'mode',
                    'items',
                    'speed',
                    'autoplay',
                    'loop',
                    'step',
                    'arrows',
                    'pagination',
                    'easing',
                    'scrollOnWheel',
                    'lazy-load',
                    'scroll-extra-styles',
                    'show-image-title'
                ),
                'Multiple images' => array(
                    'use-individual-titles'
                )
            ),
            'recentlyviewedproductsblock' => array(
                'General' => array(
                    'enable-effect'
                ),
                'Positioning and Geometry' => array(
                    'thumb-max-width',
                    'thumb-max-height',
                    'square-images'
                ),
                'Scroll' => array(
                    'width',
                    'height',
                    'orientation',
                    'mode',
                    'items',
                    'speed',
                    'autoplay',
                    'loop',
                    'step',
                    'arrows',
                    'pagination',
                    'easing',
                    'scrollOnWheel',
                    'lazy-load',
                    'scroll-extra-styles',
                    'show-image-title'
                ),
                'Miscellaneous' => array(
                    'link-to-product-page'
                )
            )
        );
        return $blocks[$block];
    }
}

<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2020 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
class Anowave_Ec_Model_System_Config_Throttle
{
	public function toOptionArray()
	{
		return array
		(
			array
			(
				'value' => 250000,
				'label' => Mage::helper('ec')->__('4.0 queries per second')
			),
			array
			(
				'value' => 500000,
				'label' => Mage::helper('ec')->__('2.0 queries per second')
			),
			array
			(
				'value' => 1000000,
				'label' => Mage::helper('ec')->__('1.0 queries per second')
			),
			array
			(
				'value' => 2000000,
				'label' => Mage::helper('ec')->__('0.5 queries per second')
			),
			array
			(
				'value' => 3000000,
				'label' => Mage::helper('ec')->__('0.4 queries per second')
			),
			array
			(
				'value' => 4000000,
				'label' => Mage::helper('ec')->__('0.3 queries per second')
			),
			array
			(
				'value' => 5000000,
				'label' => Mage::helper('ec')->__('0.2 queries per second')
			)
		);
	}
}
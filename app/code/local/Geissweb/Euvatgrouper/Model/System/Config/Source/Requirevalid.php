<?php

class Geissweb_Euvatgrouper_Model_System_Config_Source_Requirevalid {

	const DISABLED = 0;
	const FRONTEND_CHECK = 1;
	const SERVER_CHECK = 2;
	const BOTH_CHECK = 3;
	const CHECK_WHEN_FILLED = 4;

	/**
	 * Options getter
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => self::DISABLED, 'label' => Mage::helper('euvatgrouper')->__('No')),
			array('value' => self::FRONTEND_CHECK, 'label' => Mage::helper('euvatgrouper')->__('Frontend Check Only')),
			array('value' => self::SERVER_CHECK, 'label' => Mage::helper('euvatgrouper')->__('Server Check Only')),
			array('value' => self::BOTH_CHECK, 'label' => Mage::helper('euvatgrouper')->__('Frontend & Server Check')),
			array('value' => self::CHECK_WHEN_FILLED, 'label' => Mage::helper('euvatgrouper')->__('Require valid VAT number when field has a value (Frontend only)'))
		);
	}
}
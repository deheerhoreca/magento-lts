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

class Anowave_Ec_Helper_Json extends Mage_Core_Helper_Data
{
	/**
	 * Encode JSON 
	 * 
	 * @param mixed $content
	 * 
	 * @return string
	 */
	public function encode($content)
	{
		if (version_compare(phpversion(), '5.4.0', '<')) 
		{
			return $this->json_encode_unicode($content);
		}
		
		/**
		 * @todo: JSON_UNESCAPED_UNICODE flag
		 */
		return json_encode($content, JSON_UNESCAPED_UNICODE);
	}
	
	/**
	 * Decode JSON 
	 * 
	 * @param string $json
	 * @param bool $assoc
	 * @return mixed
	 */
	public function decode($json, $assoc = false)
	{
		return json_decode($json, $assoc);
	}
	
	/**
	 * JSON_UNESCAPED_UNICODE compatibility for PHP < 5.4.0
	 * 
	 * @param mixed $content
	 * @return string
	 */
	private function json_encode_unicode($content)
	{
		if (is_array($content))
		{
			array_walk_recursive($content, function (&$item, $key) 
			{
				if (is_string($item)) 
				{
					$item = mb_encode_numericentity($item, array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
				}
			});
		}
		
		return mb_decode_numericentity(json_encode($content), array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
	}
}
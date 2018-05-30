<?php
/**
 * Anowave Magento Sort Products by Drag & Drop
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
 * @package 	Anowave_Sort
 * @copyright 	Copyright (c) 2017 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Sort_Model_Observer
{
	/**
	* Modify block
	*
	* @param (Varien_Event_Observer) $observer
	*/
	public function modify(Varien_Event_Observer $observer)
	{
		if ('adminhtml/catalog_category_tab_product' == $observer->getBlock()->getType())
		{
			$content = $observer->getTransport()->getHtml();

			$dom = new DOMDocument('1.0','utf-8');
			$doc = new DOMDocument('1.0','utf-8');

			$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

			foreach ($dom->getElementsByTagName('select') as $element)
			{
				if ('limit' == $element->getAttribute('name'))
				{
					$option = $dom->createElement('option');

					$option->appendChild
					(
						$dom->createTextNode('All')
					);

					$option->setAttribute('value', 0);


					if (!Mage::helper('sort')->isLimitApplied())
					{
						$option->setAttribute('selected','selected');
					}

					$option = $element->appendChild($option);
				}
			}


			$body = $dom->getElementsByTagName('body')->item(0);

			foreach ($body->childNodes as $child)
			{
			    $doc->appendChild($doc->importNode($child, true));
			}

			$content = Mage::helper('sort')->filter
			(
				$doc->saveHTML()
			);

			$content = $doc->saveHTML();

			if ($content)
			{
				$observer->getTransport()->setHtml($content);
			}

			return true;
		}
	}
}

<?php

class Psc_Paysafecash_Block_Cpay_Redirect extends Mage_Core_Block_Abstract
{

	protected function _toHtml()
    {
        $cpay = Mage::getModel('paysafecash/cpay');
		
		$form = new Varien_Data_Form();
        $form->setAction(htmlentities($cpay->getUrl()))
            ->setId('cpay_checkout')
            ->setName('cpay_checkout')
            ->setMethod('GET')
            ->setUseContainer(true);
		$form = $cpay->addPaysafecashFields($form);
			
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to paysafecash in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("cpay_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}

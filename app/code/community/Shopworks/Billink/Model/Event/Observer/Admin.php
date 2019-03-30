<?php

/**
 * Class Shopworks_Billink_Model_Event_Observer_Admin
 */
class Shopworks_Billink_Model_Event_Observer_Admin
{
    private $_errorMessageAuth = 'De authenticatie gegevens voor de Billink module zijn nog niet ingevuld. De module is nog niet actief';
    private $_errorMessageTaxConfigDiscount = 'Voor een goede werking van de Billink module moeten kortingen worden toegepast voordat de BTW berekend wordt. Dit kan aangepast worden in: Sytem -> configuration -> Tax -> Apply Customer Tax';
    private $_errorMessageTaxConfigAlgorithm = 'Om afrondingsverschillen in de Billink module te voorkomen moet BTW worden berekend over rijen. Dit kan aangepast worden in: System -> configuration -> Tax -> Tax Calculation Method Based On.';

    /**
     * @var Shopworks_Billink_Helper_Billink
     */
    private $_helper;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('billink/Billink');
    }

    /**
     * Show a notification if the Billink module is not authenticated
     */
    public function showBillinkPluginNotifications()
    {
        if($this->isBillinkEnabled() && $this->isUserLoggedIn())
        {
            // Authentication check
            $this->_checkForAuthentication();
            // Check tax and discount settings
            $this->_checkTaxDiscountSettings();
        }
    }

    /**
     * @return bool
     */
    protected function isBillinkEnabled()
    {
        return (boolean)Mage::getStoreConfig('payment/billink/active');
    }

    /**
     * @return mixed
     */
    protected function isUserLoggedIn()
    {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    /**
     * Authentication check
     */
    private function _checkForAuthentication()
    {
        if(!$this->_helper->isBillinkAuthenticated())
        {
            $this->_addMessage($this->_errorMessageAuth);
        }
    }

    /**
     * @param string $message
     */
    protected function _addMessage($message)
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        $session->addError($message);
    }

    /**
     * Check tax and discount settings
     */
    private function _checkTaxDiscountSettings()
    {
        //Can be changed in:
        //Sytem -> configuration -> Tax -> Apply Customer Tax (set to: 'after discount')
        if (!Mage::getStoreConfig("tax/calculation/apply_after_discount"))
        {
            $this->_addMessage($this->_errorMessageTaxConfigDiscount);
        }

        //Can be changed in:
        //System -> configuration -> Tax -> Tax Calculation Method Based On (set to: 'row total')
        if(Mage::getStoreConfig("tax/calculation/algorithm") != Mage_Tax_Model_Calculation::CALC_ROW_BASE)
        {
            $this->_addMessage($this->_errorMessageTaxConfigAlgorithm);
        }
    }
}
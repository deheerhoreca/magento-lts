<?php
class Afterpay_Afterpay_Block_Portfolios_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Afterpay/Afterpay/portfolios/info.phtml');
    }

    /**
     * Render as PDF
     * @return string
     */
    public function toPdf()
    {
        if (str_contains((string) $this->getMethod()->getCode(), 'portfolio_')) {
            $this->setTemplate('Afterpay/Afterpay/portfolios/pdf.phtml');
        }
        return $this->toHtml();
    }
}

<?php

class TM_FireCheckout_Helper_Checkout extends TM_FireCheckout_Helper_Checkout_Abstract
{
    /**
     * Modification to skip previously approved agreements
     *
     * @return array
     */
    public function getRequiredAgreementIds()
    {
        $this->_agreements = parent::getRequiredAgreementIds();

        $approvedIds = $this->getCheckout()->getFirecheckoutApprovedAgreementIds();
        if ($approvedIds) {
            $this->_agreements = array_diff($this->_agreements, $approvedIds);
        }

        return $this->_agreements;
    }
}

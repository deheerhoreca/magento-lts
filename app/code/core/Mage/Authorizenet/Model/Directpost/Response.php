<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Authorizenet
 */

/**
 * Authorize.net response model for DirectPost model.
 *
 * @package    Mage_Authorizenet
 */
class Mage_Authorizenet_Model_Directpost_Response extends Varien_Object
{
    /**
     * Generates an Md5 hash to compare against AuthNet's.
     *
     * @param string $merchantMd5
     * @param string $merchantApiLogin
     * @param string $amount
     * @param string $transactionId
     * @return string
     */
    public function generateHash($merchantMd5, $merchantApiLogin, $amount, $transactionId)
    {
        return strtoupper(md5($merchantMd5 . $merchantApiLogin . $transactionId . $amount));
    }

    /**
     * Return if is valid order id.
     *
     * @param string $storedHash
     * @param string $merchantApiLogin
     * @return bool
     */
    public function isValidHash($storedHash, $merchantApiLogin)
    {
        $xAmount = $this->getData('x_amount');
        if (empty($xAmount)) {
            $this->setData('x_amount', '0.00');
        }

        $xSHA2Hash = $this->getData('x_SHA2_Hash');
        $xMD5Hash = $this->getData('x_MD5_Hash');
        if (!empty($xSHA2Hash)) {
            $hash = $this->generateSha2Hash($storedHash);
            return $hash == $this->getData('x_SHA2_Hash');
        } elseif (!empty($xMD5Hash)) {
            $hash = $this->generateHash($storedHash, $merchantApiLogin, $this->getXAmount(), $this->getXTransId());
            return $hash == $this->getData('x_MD5_Hash');
        }

        return false;
    }

    /**
     * Return if this is approved response from Authorize.net auth request.
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->getXResponseCode() == Mage_Authorizenet_Model_Directpost::RESPONSE_CODE_APPROVED;
    }

    /**
     * Generates an SHA2 hash to compare against AuthNet's.
     *
     * @param string $signatureKey
     * @return string
     * @see https://support.authorize.net/s/article/MD5-Hash-End-of-Life-Signature-Key-Replacement
     */
    public function generateSha2Hash($signatureKey)
    {
        $hashFields = [
            'x_trans_id',
            'x_test_request',
            'x_response_code',
            'x_auth_code',
            'x_cvv2_resp_code',
            'x_cavv_response',
            'x_avs_code',
            'x_method',
            'x_account_number',
            'x_amount',
            'x_company',
            'x_first_name',
            'x_last_name',
            'x_address',
            'x_city',
            'x_state',
            'x_zip',
            'x_country',
            'x_phone',
            'x_fax',
            'x_email',
            'x_ship_to_company',
            'x_ship_to_first_name',
            'x_ship_to_last_name',
            'x_ship_to_address',
            'x_ship_to_city',
            'x_ship_to_state',
            'x_ship_to_zip',
            'x_ship_to_country',
            'x_invoice_num',
        ];

        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getData('x_invoice_num'));
        $billing = $order->getBillingAddress();
        if (!empty($billing)) {
            $this->setXFirstName((string) $billing->getFirstname())
                ->setXLastName((string) $billing->getLastname())
                ->setXCompany((string) $billing->getCompany())
                ->setXAddress((string) $billing->getStreet(1))
                ->setXCity((string) $billing->getCity())
                ->setXState((string) $billing->getRegion())
                ->setXZip((string) $billing->getPostcode())
                ->setXCountry((string) $billing->getCountry())
                ->setXPhone((string) $billing->getTelephone())
                ->setXFax((string) $billing->getFax())
                ->setXEmail((string) $order->getCustomerEmail());
        }
        $shipping = $order->getShippingAddress();
        if (!empty($shipping)) {
            $this->setXShipToFirstName((string) $shipping->getFirstname())
                ->setXShipToLastName((string) $shipping->getLastname())
                ->setXShipToCompany((string) $shipping->getCompany())
                ->setXShipToAddress((string) $shipping->getStreet(1))
                ->setXShipToCity((string) $shipping->getCity())
                ->setXShipToState((string) $shipping->getRegion())
                ->setXShipToZip((string) $shipping->getPostcode())
                ->setXShipToCountry((string) $shipping->getCountry());
        }

        $message = '^';
        foreach ($hashFields as $field) {
            $fieldData = $this->getData($field);
            $message .= ($fieldData ?? '') . '^';
        }

        return strtoupper(hash_hmac('sha512', $message, pack('H*', $signatureKey)));
    }
}

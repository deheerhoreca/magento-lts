<?php
/**
 * Copyright (c) 2011-2020  arvato Finance B.V.
 *
 * AfterPay reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of AfterPay.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    AfterPay
 * @package     Afterpay_Afterpay
 * @copyright   Copyright (c) 2011-2020 arvato Finance B.V.
 */

class Afterpay_Afterpay_Block_Adminhtml_Ipaddress extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_fieldRenderer;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $currentIp = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            $currentIp = trim($ips[count($ips) - 1]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $currentIp = $_SERVER['HTTP_X_REAL_IP'];
        }
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $currentIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        $html = '
            <tr id="' . $element->getId() .'" >
                <td class="label"><label for="'.$element->getHtmlId().'">'.$element->getLabel().'</label></td>
                <td class="value" id="'.$element->getHtmlId().'">'.$currentIp.'</td>
            </tr>
        ';
        return $html;
    }

    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }

        return $this->_fieldRenderer;
    }
}

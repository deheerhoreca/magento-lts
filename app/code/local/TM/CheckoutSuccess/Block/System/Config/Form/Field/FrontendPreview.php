<?php

class TM_CheckoutSuccess_Block_System_Config_Form_Field_FrontendPreview
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{

    protected $_store = null;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $useContainerId = $element->getData('use_container_id');
        $isSecure = $this->getRequest()->isSecure();
        $msgInsecureConn = $isSecure ? '' : '<span class="https-warning"><a href="#" title="Click to read more" onclick="return readMoreConnectionInsecure();">Connection Insecure...</a></span>';
        $cssClass = $isSecure ? '' : 'connection-insecure';

        $javascript = <<<HTML
<script type="text/javascript">
    var templateMessage = new Template(
            '<div id="messages"><ul class="messages">'
            + '<li class="#{msgtype}-msg"><ul><li>'
            +  '<span>#{msgtext}</span>'
            + '</li></ul></li>'
            +'</ul></div>'
        );
    function orderNotFound() {
        var text = '{$this->__("Unfortunately, we can not find order to preview success page... :(")}'
        return templateMessage.evaluate({
                msgtype: 'error',
                msgtext: text
            });
    };
    function readMoreConnectionInsecure() {
        var placeholder = $('success-preview-container');
        var text = '{$this->__("Magento Admin uses unsecure connection. Preview feature will not work if checkout success page uses secure connection.")}';
        placeholder.update(
            templateMessage.evaluate({
                msgtype: 'notice',
                msgtext: text
            })
        );
        return false; // to disable default behavior
    };
    function saveAndPreview(caller) {
        if (!$('cs-preview-order-number').value) {
            $('success-preview-container').update(orderNotFound());
            return;
        }
        if (configForm.validator.validate()) {
            new Ajax.Request(
                configForm.validator.form.action,
                {
                    parameters: configForm.validator.form.serialize(true),
                    onComplete: function(){ startSuccessPagePreview(caller); }
                }
            );
        }
    };
    function startSuccessPagePreview(caller) {
        var previewUrl = "{$this->getPreviewUrl('%orderNumber%')}"
            .replace('%orderNumber%', $('cs-preview-order-number').value);
        if (!previewUrl || !$('cs-preview-order-number').value) {
            $('success-preview-container').update(orderNotFound());
            return;
        }
        var iFrame = new Element('iframe');
        iFrame.src = previewUrl;
        iFrame.setStyle({
            width: '100%',
            maxWidth: '1280px',
            marginTop: '10px',
            height: '600px',
            border: '1px solid #d6d6d6'
        });
        caller.up('table').setStyle({width: '100%'});
        $('success-preview-container').update(iFrame);
    };
    $('cs-preview-order-number').observe('keypress', function (event){
        var key = event.which || event.keyCode;
        switch (key) {
            default:
            break;
            case Event.KEY_RETURN:
                saveAndPreview($('cs-preview-start'));
                event.stop();
            break;
        }
    });
</script>
HTML;

        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setId('cs-preview-start')
            ->setOnClick('saveAndPreview(this);')
            ->setClass('go')
            ->setStyle('margin-left: 10px;')
            ->setLabel($this->__("Save and Start Preview"))
            ->setAfterHtml($msgInsecureConn);

        $html = <<<HTML
<tr id="row_{$element->getHtmlId()}">
    <td class="label">{$element->getLabel()}</td>
    <td class="value">
        <p class="{$cssClass}"><input id="cs-preview-order-number" class="input-text" type="text" value="{$this->getLastOrder($this->getStore())}"/>{$button->toHtml()}</p>
        <p class="note">{$element->getComment()}</p>
        {$javascript}
    </td>
</tr>
<tr>
    <td id="success-preview-container" colspan="5">
    </td>
</tr>
HTML;

        return $html;
    }

    public function getStore()
    {
        if (!$this->_store) {
            $website = $this->getRequest()->getParam('website');
            $store = $this->getRequest()->getParam('store');
            if (!$store) {
                if ($website) {
                    $store = Mage::app()->getWebsite($website)
                        ->getDefaultStore()->getCode();
                } else {
                    $store = Mage::app()->getDefaultStoreView()->getCode();
                }
            }

            $this->_store = Mage::app()->getStore($store);
        }

        return $this->_store;
    }

    public function getPreviewUrl($objectId = '')
    {
        $url[] = rtrim(
            $this->getStore()->getBaseUrl(
                Mage_Core_Model_Store::URL_TYPE_LINK,
                $this->getRequest()->isSecure()
            ),
            '/'
        );
        $url[] = 'checkout';
        $url[] = 'onepage';
        $url[] = 'success';
        $url[] = 'previewObjectId';
        $url[] = $objectId;
        return implode($url, '/') . '/?___store=' . $this->getStore()->getCode();
    }

    public function getLastOrder($store)
    {
        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('store_id', $store->getId())
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(1)
            ->setCurPage(1);
        $lastOrder = $orders->getFirstItem();
        if (!$lastOrder->getId()) {
            return false;
        }

        return $lastOrder->getIncrementId();
    }

    public function getLastRecurringProfile($store)
    {
        $profiles = Mage::getModel('sales/recurring_profile')
            ->getCollection()
            ->addFieldToFilter('store_id', $store->getId())
            ->setOrder('profile_id', 'DESC')
            ->setPageSize(1)
            ->setCurPage(1);
        $lastProfile = $profiles->getFirstItem();
        if (!$lastProfile->getId()) {
            return false;
        }

        return $lastProfile->getReferenceId();
    }

}

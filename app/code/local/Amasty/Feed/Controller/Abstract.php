<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Feed
 */
class Amasty_Feed_Controller_Abstract extends Mage_Adminhtml_Controller_Action
{
    const FEED_KNOWLEDGE_URL = 'https://amasty.com/knowledge-base/topic-product-feed.html#6976';
    protected $_title     = 'Feed';
    protected $_modelName = 'profile';
    protected $_dynamic   = array();

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/amfeed/' . $this->_modelName . 's');
        $this->_addContent($this->getLayout()->createBlock('amfeed/adminhtml_' . $this->_modelName));
        $this->renderLayout();
    }

    protected function _setActiveMenu($menuPath)
    {
        $this->getLayout()->getBlock('menu')->setActive($menuPath);
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Product Feeds'))
            ->_title($this->__($this->_title));

        return $this;
    }

    public function newAction()
    {
        $this->editAction();
    }

    public function editAction()
    {
        Mage::getSingleton('adminhtml/session')
            ->addNotice(
                Mage::helper('amfeed')->__(
                    'Please keep in mind that your feed may not pass Google validation. Refer to 
                        <a href=\'%s\' target=\'_blank\'>this article</a> and double check your feed',
                    self::FEED_KNOWLEDGE_URL
                )
            );

        $id    = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('amfeed/' . $this->_modelName)->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amfeed')->__('Record does not exist'));
            $this->_redirect('*/*/');

            return;
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        } else {
            $this->prepareForEdit($model);
        }

        Mage::register('amfeed_' . $this->_modelName, $model);

        $this->loadLayout();

        $this->_setActiveMenu('catalog/amfeed/' . $this->_modelName . 's');
        $this->_title($this->__('Edit'));

        $this->_addContent($this->getLayout()->createBlock('amfeed/adminhtml_' . $this->_modelName . '_edit'));
        $this->_addLeft($this->getLayout()->createBlock('amfeed/adminhtml_' . $this->_modelName . '_edit_tabs'));

        $this->renderLayout();
    }

    protected function prepareForEdit($model)
    {
        foreach ($this->_dynamic as $field) {
            $model->setData($field, Mage::helper('amfeed')->unserialize($model->getData($field)));
        }

        return true;
    }

    public function saveAction()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = Mage::getModel('amfeed/' . $this->_modelName);
        $data  = $this->getRequest()->getPost();
        $feed = $id ? $model->load($id) : false;
        $typeFeed = $feed ? $model->getType() : $this->getRequest()->getParam('type');

        if ($data) {
            if($typeFeed == Amasty_Feed_Model_Profile::TYPE_XML) {
                $data['xml_header'] = $this->editXMLHeader($data['xml_header']);
            }

            $model->setData($data)->setId($id)->setType($typeFeed);
            try {
                $this->prepareForSave($model);

                $model->save();

                $this->_afterSave($model);

                Mage::getSingleton('adminhtml/session')->setFormData(false);

                $msg = Mage::helper('amfeed')->__($this->_title . ' has been successfully saved');
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);

                if ($this->getRequest()->getParam('continue')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId(), '_current' => true));

                    return;
                }

                $this->_redirect('*/*');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                if ($id) {
                    $this->_redirect('*/*/edit', array('id' => $id));
                } else {
                    $this->_redirect('*/*/new');
                }
            }

            return;
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('amfeed')
                ->__('Unable to find a record to save')
        );
        $this->_redirect('*/*');
    }

    /**
     * @param string $header
     *
     * @return string
     */
    protected function editXMLHeader($header)
    {
        $tagXmlCreateAt = '<created_at>{{DATE}}</created_at>';

        $countCreateAtInHeader = substr_count($header, $tagXmlCreateAt);
        if ($countCreateAtInHeader > 1) {
            $header = str_replace($tagXmlCreateAt, '', $header) . $tagXmlCreateAt;
        } elseif ($countCreateAtInHeader == 0) {
            $header = $header . $tagXmlCreateAt;
        }

        return $header;
    }

    protected function prepareForSave($model)
    {
        foreach ($this->_dynamic as $field) {
            $map = $model->getData($field);
            if (!$map) {
                $map = array();
            }
            $model->setData($field, serialize($map));
        }

        return true;
    }

    protected function _afterSave($model)
    {

    }

    public function deleteAction()
    {
        $id    = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('amfeed/' . $this->_modelName)->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Record does not exist'));
            $this->_redirect('*/*/');

            return;
        }

        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__($this->_title . ' has been successfully deleted')
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam($this->_modelName . 's');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amfeed')->__('Please select records'));
            $this->_redirect('*/*/');

            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('amfeed/' . $this->_modelName)->load($id);
                $model->delete();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                    'Total of %d record(s) were successfully deleted', count($ids)
                )
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');

    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/amfeed');


    }
}

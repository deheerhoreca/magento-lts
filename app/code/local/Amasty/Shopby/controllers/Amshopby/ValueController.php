<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */  
class Amasty_Shopby_Amshopby_ValueController extends Mage_Adminhtml_Controller_Action
{
    public function newAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/amshopby');
        $this->_addContent($this->getLayout()->createBlock('amshopby/adminhtml_value_new'));
        $this->_title($this->__('New Grouped Option'));

        $this->renderLayout();
    }

    // edit filters (uses tabs)
    public function editAction() 
    {
        $id     = (int) $this->getRequest()->getParam('id');
        /** @var Amasty_Shopby_Model_Value $model */
        $model  = Mage::getModel('amshopby/value')->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amshopby')->__('Option does not exist'));
            $this->_redirect('*/adminhtml_filter/index');
            return;
        }
        
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        // todo: save images
        
        Mage::register('amshopby_value', $model);

        $this->loadLayout();
        
        $this->_setActiveMenu('catalog/amshopby');
        $this->_addContent($this->getLayout()->createBlock('amshopby/adminhtml_value_edit'));

        $this->_title($model->getCurrentTitle() . $this->__(' Settings'));

        $this->renderLayout();
    }

    public function saveAction() 
    {
        $id = $this->getRequest()->getParam('id');
        $data = $this->getRequest()->getPost();

        if (!$data) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amshopby')->__('Unable to find an option to save'));
            $this->_redirect('*/amshopby_filter/');
            return;
        }

        $model = Mage::getModel('amshopby/value');

        if (isset($data['multistore'])) {
            foreach ($data['multistore'] as $key => $value) {
                $data[$key] = serialize($value);
            }
        }

        if (!$id) {
            $filterId = $this->getRequest()->getParam('filter_id');
            $isParent = true;
            $data['is_parent'] = true;
            $data['filter_id'] = $filterId;
            $data['meta_title'] = $data['title'];
        }  else {
            $model->load($id);

            $filterId = $model->getFilterId();
            $isParent = $model->getIsParent();

            //upload images
            $path = Mage::getBaseDir('media') . DS . 'amshopby' . DS;
            $imagesTypes = array('big', 'small', 'medium', 'small_hover');
            foreach ($imagesTypes as $type) {
                $field = 'img_' . $type;

                $isRemove = isset($data['remove_' . $field]);
                $hasNew = !empty($_FILES[$field]['name']);

                try {
                    // remove the old file
                    if ($isRemove || $hasNew) {
                        $oldName = $model->getData($field);
                        if ($oldName) {
                            @unlink($path . $oldName);
                            $data[$field] = '';
                        }
                    }

                    // upload a new if any
                    if (!$isRemove && $hasNew) {
                        $newName = $type . $id;
                        $newName .= '.' . strtolower(substr(strrchr($_FILES[$field]['name'], '.'), 1));

                        $uploader = new Varien_File_Uploader($field);
                        $uploader->setFilesDispersion(false);
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setAllowedExtensions(array('png', 'gif', 'jpg', 'jpeg'));
                        $uploader->save($path, $newName);

                        $data[$field] = $newName;
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        }
        try {
            $model->setData($data);
            if ($id) {
                $model->setId($id);
            }
            $model->save();
            Mage::getSingleton('adminhtml/session')->setFormData(false);
            
            $msg = Mage::helper('amshopby')->__('Option properties have been successfully saved');
            Mage::getSingleton('adminhtml/session')->addSuccess($msg);

            if ($this->getRequest()->getParam('continue')){
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
            }
            else {
                $tab = $isParent ? 'values_mapped' : 'values';
                $this->_redirect('*/amshopby_filter/edit', array('id'=>$filterId, 'tab'=> $tab));
            }

        } 
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            if ($id) {
                $this->_redirect('*/*/edit', array('id' => $id));
            } else {
                $this->_redirect('*/amshopby_filter/edit', array('id'=>$filterId, 'tab'=>'values_mapped'));
            }
        }

        $this->invalidateCache();
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('value_id');
        if(!$id) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Something went wrong'));
        } else {
            try {
                $model = Mage::getModel('amshopby/value')->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('Option was successfully deleted')
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            $this->invalidateCache();
        }
        $filterId = $this->getRequest()->getParam('filter_id');
        $this->_redirect('*/amshopby_filter/edit', array('id'=>$filterId, 'tab'=>'values_mapped'));
    }

    protected function invalidateCache()
    {
        /** @var Amasty_Shopby_Helper_Data $helper */
        $helper = Mage::helper('amshopby');
        $helper->invalidateCache();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/amshopby/filters');
    }
}
<?php

class Magiecommerce_Faqs_Adminhtml_CategoriesController extends Mage_Adminhtml_Controller_Action {

    public function categoriesAction() {
        $this->loadLayout()->_setActiveMenu('faqs')->_title($this->__('Manage FAQ categories'));
        $this->renderLayout();
    }

    public function indexAction() {
        $this->loadLayout()->_setActiveMenu('faqs')->_title($this->__('Manage FAQ categories'));
        $this->renderLayout();
    }

    protected function _initAction() {

        $this->loadLayout()
                ->_setActiveMenu('faqs');

        return $this;
    }

    public function newAction() {
        $catId = $this->getRequest()->getParam('id');

        $_model = Mage::getModel('faqs/categories')->load($catId);

        $this->_title($_model->getId() ? $_model->getName() : $this->__('New Category'));

        Mage::register('faqs_data', $_model);


        $this->_initAction();


        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->_addContent($this->getLayout()->createBlock('faqs/adminhtml_categories_edit'))
                ->_addLeft($this->getLayout()->createBlock('faqs/adminhtml_categories_edit_tabs'));

        $this->renderLayout();
    }

    public function editAction() {
        $catId = $this->getRequest()->getParam('id');
        $_model = Mage::getModel('faqs/categories')->load($catId);

        if ($_model->getId()) {
            $this->_title($_model->getId() ? $_model->getName() : $this->__('New Category'));

            Mage::register('faqs_data', $_model);


            $this->_initAction();
            $this->_addBreadcrumb(Mage::helper('faqs')->__('Faqs Manager'), Mage::helper('faqs')->__('Faqs Manager'), $this->getUrl('*/*/'));
            $this->_addBreadcrumb(Mage::helper('faqs')->__('Edit Category'), Mage::helper('faqs')->__('Edit Category'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
                $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
            }
            $this->_addContent($this->getLayout()->createBlock('faqs/adminhtml_categories_edit'))
                    ->_addLeft($this->getLayout()->createBlock('faqs/adminhtml_categories_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('faqs')->__('The Category does not exist.'));
            $this->_redirect('*/*/');
        }
    }

    public function saveAction() {

        $data = $this->getRequest()->getPost('store_id');

        if (in_array('0', $data)) {
            $data = '0';
        } else {
            $data = join(",", $data);
        }

        if ($this->getRequest()->getPost()) {
            try {
                //echo "<pre>";

                $postData = $this->getRequest()->getPost();
                $cat_id = $this->getRequest()->getParam('id');
                $urlKey = Mage::helper('catalog/product_url')->format($postData['cat_name']);
                $urlKey = preg_replace('#[^0-9a-z]+#i', '-', $urlKey);
                $urlKey = strtolower($urlKey);
                $urlKey = trim($urlKey, '-');


                $postData['store_id'] = $data;
                if ($postData['urlkey'] == NULL) {
                    $postData['urlkey'] = $urlKey;
                } else {
                    $postData['urlkey'] = preg_replace('#[^0-9a-z]+#i', '-', $postData['urlkey']);
                }
                if ($cat_id == "") {
                    $url_rewrites = Mage::getModel('core/url_rewrite')->getCollection()->addFieldToFilter('request_path', $postData['urlkey']);
                    if (count($url_rewrites->getData()) > 0) {
                        
                        Mage::getSingleton('core/session')->addError("Url key already exist");
                        $this->_redirect('*/*/');
                        return;
                    }
                }
                $catModel = Mage::getModel('faqs/categories');
                //  echo "<pre>"; print_r($postData); die;
                if ($this->getRequest()->getParam('id') <= 0)
                    $catModel->setCreatedTime(
                            Mage::getSingleton('core/date')
                                    ->gmtDate()
                    );
                $cat_object = $catModel
                        ->addData($postData)
                        ->setUpdateTime(
                                Mage::getSingleton('core/date')
                                ->gmtDate())
                        ->setId($this->getRequest()->getParam('id'))
                        ->save();

                $idPath = "faqs/index/category/id/" . $cat_object->getCatId();
                $object_rewrite = Mage::getModel('core/url_rewrite')->loadByIdPath($idPath);
                if ($object_rewrite->getTargetPath()) {
                    $object_rewrite->setIsSystem(0)->setIdPath($idPath)->setTargetPath($idPath)->setRequestPath($postData['urlkey'])->save();
                } else {
                    Mage::getModel('core/url_rewrite')->setIsSystem(0)->setIdPath($idPath)->setTargetPath($idPath)->setRequestPath($postData['urlkey'])->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess('Category successfully saved');
                Mage::getSingleton('adminhtml/session')
                        ->setfaqsData(false);
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')
                        ->setfaqsData($this->getRequest()
                                ->getPost()
                );
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()
                            ->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('faqs/categories');
                $idPath = "faqs/index/category/id/" . $this->getRequest()->getParam('id');
                $object_rewrite = Mage::getModel('core/url_rewrite')->loadByIdPath($idPath);
                $object_rewrite->Delete();
                $model->setId($this->getRequest()->getParam('id'))
                        ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Category was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/categories', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $IDList = $this->getRequest()->getParam('faqs');
        if (!is_array($IDList)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select category(s)'));
        } else {
            try {
                foreach ($IDList as $itemId) {
                    $_model = Mage::getModel('faqs/categories')
                                    ->setIsMassDelete(true)->load($itemId);
                    $idPath = "faqs/index/category/id/" . $itemId;
                    $object_rewrite = Mage::getModel('core/url_rewrite')->loadByIdPath($idPath);
                    $object_rewrite->Delete();
                    $_model->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__(
                                'Total of %d record(s) were successfully deleted', count($IDList)
                        )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    public function massStatusAction() {
        $IDList = $this->getRequest()->getParam('faqs');
        if (!is_array($IDList)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select categories'));
        } else {
            try {
                foreach ($IDList as $itemId) {
                    $_model = Mage::getSingleton('faqs/categories')
                            ->setIsMassStatus(true)
                            ->load($itemId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->save();
                }
                $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($IDList))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }
    
    protected function _isAllowed()
    {
        return true;  
    }
}

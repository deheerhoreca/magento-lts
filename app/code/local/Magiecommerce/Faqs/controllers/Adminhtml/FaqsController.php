<?php


class Magiecommerce_Faqs_Adminhtml_FaqsController extends Mage_Adminhtml_Controller_Action
{
    public function faqsAction() {
        $this->loadLayout()->_setActiveMenu('faqs')->_title($this->__('Manage FAQ'));
        $this->renderLayout();
    }

    public function indexAction() {
        $this->loadLayout()->_setActiveMenu('faqs')->_title($this->__('Manage FAQ'));
        $this->renderLayout();
    }

    protected function _initAction() {
        $this->loadLayout()->_setActiveMenu('faqs');
        return $this;
    }

    public function newAction() {

        $faqId = $this->getRequest()->getParam('id');

        $_model = Mage::getModel('faqs/items')->load($faqId);

        $this->_title($_model->getId() ? $_model->getName() : $this->__('New FAQ'));

        Mage::register('items_data', $_model);


        $this->_initAction();


        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled())
                        {

        $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }


        $this->_addContent($this->getLayout()->createBlock('faqs/adminhtml_faqs_edit'))
                ->_addLeft($this->getLayout()->createBlock('faqs/adminhtml_faqs_edit_tabs'));

        $this->renderLayout();
    }
    public function editAction()
    {

        $catId = $this->getRequest()->getParam('id');
        $_model = Mage::getModel('faqs/items')->load($catId);
       //echo "<pre>"; print_r($_model);die;

        if ($_model->getId()) {
            $this->_title($_model->getId() ? $_model->getName() : $this->__('New FAQ'));

            Mage::register('items_data', $_model);


            $this->_initAction();
            $this->_addBreadcrumb(Mage::helper('faqs')->__('Faqs Manager'), Mage::helper('faqs')->__('Faqs Manager'), $this->getUrl('*/*/'));
            $this->_addBreadcrumb(Mage::helper('faqs')->__('Edit FAQ'), Mage::helper('faqs')->__('Edit FAQ'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled())
                        {

        $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }

            $this->_addContent($this->getLayout()->createBlock('faqs/adminhtml_faqs_edit'))
                    ->_addLeft($this->getLayout()->createBlock('faqs/adminhtml_faqs_edit_tabs'));


            $this->renderLayout();


        } else {

            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('faqs')->__('The FAQ does not exist.'));
            $this->_redirect('*/*/');
        }
          }



      public function saveAction()
       {

       $data= $this->getRequest()->getPost('store_id');

        if( in_array('0', $data) ){
             $data = '0';
        } else {
             $data = join(",", $data);
        }

         if ($this->getRequest()->getPost())
         {
           try {
                 $postData = $this->getRequest()->getPost();

                 $postData['store_id'] = $data;
                 $catModel = Mage::getModel('faqs/items');
  		 $_model=Mage::getModel('faqs/categories')->load($postData['cat_id']);
		$postData['cat_name'] = $_model['cat_name'];
               if( $this->getRequest()->getParam('id') <= 0 )
                  $catModel->setCreatedTime(
                     Mage::getSingleton('core/date')
                            ->gmtDate()
                    );
                  $catModel
                    ->addData($postData)
                    ->setUpdateTime(
                             Mage::getSingleton('core/date')
                             ->gmtDate())
                    ->setId($this->getRequest()->getParam('id'))
                    ->save();

                 Mage::getSingleton('adminhtml/session')->addSuccess('FAQ successfully saved');
                 Mage::getSingleton('adminhtml/session')->setfaqsData(false);
                 $this->_redirect('*/*/');
                return;
          } catch (Exception $e){
                Mage::getSingleton('adminhtml/session')
                                  ->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')
                 ->setfaqsData($this->getRequest()
                                    ->getPost()
                );
                $this->_redirect('*/*/edit',
                            array('id' => $this->getRequest()
                                                ->getParam('id')));
                return;
                }
              }
              $this->_redirect('*/*/');
            }
    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('faqs/items');

                $model->setId($this->getRequest()->getParam('id'))
                        ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('FAQ was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/faqs', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }
    public function massDeleteAction()
    {
        $IDList = $this->getRequest()->getParam('faqs');
        if (!is_array($IDList)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select faq(s)'));
        } else {
            try {
                foreach ($IDList as $itemId) {
                    $_model = Mage::getModel('faqs/items')
                                    ->setIsMassDelete(true)->load($itemId);
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

    public function massStatusAction()
    {
        $IDList = $this->getRequest()->getParam('faqs');
        if (!is_array($IDList)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select FAQ(s)'));
        } else {
            try {
                foreach ($IDList as $itemId) {
                    $_model = Mage::getSingleton('faqs/items')
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

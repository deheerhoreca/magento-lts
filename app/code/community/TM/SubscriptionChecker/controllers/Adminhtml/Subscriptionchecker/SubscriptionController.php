<?php

class TM_SubscriptionChecker_Adminhtml_Subscriptionchecker_SubscriptionController extends Mage_Adminhtml_Controller_Action
{
    const MODULE_CODE = 'Swissup_Subscription';

    public function indexAction()
    {
        $module = Mage::getModel('tmcore/module');
        $module->load(self::MODULE_CODE);

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $module->addData($data);
        }

        if ($info = Mage::getSingleton('adminhtml/session')->getTmValidationInfo(true)) {
            $link = Mage::helper('tmcore/debug')->preparePopup(
                $info['response'],
                'SwissUpLabs subscription validation response'
            );
            Mage::getSingleton('adminhtml/session')->addError(
                $info['error'] . ' | ' . $link
            );
        }

        Mage::register('subscription', $module);

        $this->loadLayout()
            ->_setActiveMenu('templates_master/subscriptionchecker_subscription')
            ->_addBreadcrumb('Templates Master', 'Templates Master')
            ->_addBreadcrumb(
                Mage::helper('subscriptionchecker')->__('Activate SwissUpLabs Subscription'),
                Mage::helper('subscriptionchecker')->__('Activate SwissUpLabs Subscription')
            );

        $this->renderLayout();
    }

    /**
     * Copy from ModuleController::Run action
     */
    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->_redirect('*/*/index');
        }

        /**
         * @var TM_Core_Model_Module
         */
        $module = Mage::getModel('tmcore/module');
        $module->load(self::MODULE_CODE)
            ->setNewStores(array(0))
            ->setIdentityKey($this->getRequest()->getParam('identity_key'));

        $result = $module->validateLicense();
        if (is_array($result) && isset($result['error'])) {
            Mage::getSingleton('adminhtml/session')->setFormData($this->getRequest()->getPost());

            $error = call_user_func_array(array(Mage::helper('tmcore'), '__'), $result['error']);
            if (isset($result['response'])) {
                Mage::getSingleton('adminhtml/session')->setTmValidationInfo(
                    array(
                        'error'    => $error,
                        'response' => $result['response']
                    )
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addError($error);
            }
            return $this->_redirect('*/*/index');
        }

        $module->up();

        $groupedErrors = $module->getMessageLogger()->getErrors();
        if (count($groupedErrors)) {
            foreach ($groupedErrors as $type => $errors) {
                foreach ($errors as $error) {
                    if (is_array($error)) {
                        $message = $error['message'];
                    } else {
                        $message = $error;
                    }
                    Mage::getSingleton('adminhtml/session')->addError($message);
                }
            }
            Mage::getSingleton('adminhtml/session')->setFormData($this->getRequest()->getPost());
            return $this->_redirect('*/*/index');
        }

        Mage::getSingleton('adminhtml/session')->setFormData(false);
        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('subscriptionchecker')->__("Subscription has been activated")
        );
        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('templates_master/subscriptionchecker');
    }
}

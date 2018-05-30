<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Adminhtml_AmoactionimportController extends Mage_Adminhtml_Controller_Action
{
    const MAX_LINE   = 2000;
    const BATCH_SIZE = 1000;
    const FIELDS     = 4;

    protected function _construct()
    {
        $this->setUsedModuleName('Amasty_Oaction');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('system/amoaction');
        return $this;
    }

    public function indexAction()
    {
        $this->editAction();
    }

    public function newAction()
    {
        $this->editAction();
    }

    public function saveAction()
    {
        try {
            if (empty($_FILES['csv_file']['name'])){
                throw new Exception('No file');
            }
            $fileName = $_FILES['csv_file']['tmp_name'];

            //for Mac OS
            ini_set('auto_detect_line_endings', 1);

            //file can be very big, so we read it by small chunks
            $fp = fopen($fileName, 'r');
            if (!$fp) {
                throw new Exception($this->__('Can not open file'));
            }

            $currRow = 0;
            while (($line = fgetcsv($fp, self::MAX_LINE, ',', '"')) !== false) {
                $currRow++;

                $checkCount = self::FIELDS - count($line);
                if (!in_array($checkCount, array(0, 1))) {
                    $message = $this->__('Error: Line #%d has been skipped: expected number of columns is %d', $currRow, self::FIELDS);
                    Mage::log($message, null, 'Import_Tracks.log', true);
                    continue;
                }

                // validate  data - not empty but title
                for ($i = 0; $i < self::FIELDS-1; $i++) {
                    $line[$i] = trim($line[$i], "\r\n\t ".'"');
                    if (!$line[$i]) {
                        $message = $this->__('Error: Line #%d has been skipped: contains empty columns', $currRow);
                        Mage::log($message, null, 'Import_Tracks.log', true);
                        continue;
                    }
                }

                $order = Mage::getModel('sales/order')->loadByIncrementId($line[0]);
                $id = array(0 => $order->getId());
                Mage::app()->getRequest()->setPost('tracking', $order->getId() . '|' . $line[1]);
                Mage::app()->getRequest()->setPost('carrier', $order->getId() . '|' . $line[2]);
                if (!isset($line[3])) {
                    $line[3] = '';
                }
                Mage::app()->getRequest()->setPost('comment', $order->getId() . '|' . $line[3]);

                try {
                    $command = Amasty_Oaction_Model_Command_Abstract::factory('ship');
                    
                    $notify = (int)Mage::getStoreConfig('amoaction/ship/notify');

                    $success = $command->execute($id, $notify);

                    if ($success) {
                        Mage::log($success, null, 'Import_Tracks.log', true);
                    }

                    // show non critical errors to the user
                    foreach ($command->getErrors() as $err) {
                        $message = $this->__('Error: %s', $err);
                        Mage::log($message, null, 'Import_Tracks.log', true);
                    }
                } catch (Exception $e) {
                    if ('Mage_Api_Exception' == get_class($e)) {
                        $error = $e->getCustomMessage();
                    } else {
                        $error = $e->getMessage();
                    }
                    $message = $this->__('Error: %s', $error);
                    Mage::log($message, null, 'Import_Tracks.log', true);
                }
            }
            fclose($fp);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $message = $this->__('Error: %s', $error);
            Mage::log($message, null, 'Import_Tracks.log', true);
        }

        $this->_redirect('*/*/edit');
    }

    public function editAction()
    {
        $this->loadLayout()
            ->_initAction()
            ->_title(Mage::helper('amoaction')->__('Mass Order Actions'))
            ->_addContent($this->getLayout()->createBlock('amoaction/adminhtml_index_edit'))
            ->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/convert/amoaction_import');
    }
}
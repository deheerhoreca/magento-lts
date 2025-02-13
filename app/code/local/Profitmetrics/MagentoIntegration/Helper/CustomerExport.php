<?php
class Profitmetrics_MagentoIntegration_Helper_CustomerExport extends Mage_Core_Helper_Abstract
{
    /**
     * @var Varien_Io_File
     */
    protected $io;

    /**
     * @return string
     * @throws Exception
     */
    public function createCsv()
    {
        /** @var Mage_Sales_Model_Order $order */
        $collection = Mage::getResourceModel('sales/order_collection');
        $select = $collection->getSelect();
        $select->columns('main_table.customer_email');
        $select->columns('MIN(created_at)');
        $select->distinct(true);
        $select->group('customer_email');
        $this->io = new Varien_Io_File();
        $path = Mage::getBaseDir('var') . DS . 'export';
        $fileName = 'pm_customers_' . date("Ymd_His") . '.csv';
        $file = $path . DS . $fileName;
        $this->io->setAllowCreateFolders(true);
        $this->io->open(array('path' => $path));
        $this->io->streamOpen($file, 'w+');
        $this->io->streamLock(true);
        $this->writeHeadRow();
        foreach ($collection as $order) {
            $datetime = new \DateTime($order->getCreatedAt());
            $this->io->streamWriteCsv([hash('sha256',$order->getCustomerEmail()), $datetime->format('Y-m-d\TH:i:s\Z')]);
        }
        return $fileName;
    }

    protected function writeHeadRow()
    {
        $this->io->streamWriteCsv([
            "emailSha256",
            "firstOrderTimestamp"
        ]);
    }
}


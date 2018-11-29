<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
 * @since  2013-05-23
 */
class DeHeerHoreca_Fpc_Helper_Data extends Mage_Core_Helper_Abstract
{

    const LOG_FILE = 'dhh_fpc.log';

    /**
     * Clear the class path cache
     *
     * @return bool
     */
    public function clearCache()
    {
      $mode = Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
      $tag = "quickndirtyfpc";

      //Beaufiul, isn't it?
      echo "<pre>";
      echo "------- Clearing Redis FPC cache --------".PHP_EOL.PHP_EOL;
      echo shell_exec("redis-cli --scan --pattern *QUICKNDIRTYFPC* | xargs redis-cli del");
      echo PHP_EOL."----- Done clearing Redis FPC cache -----".PHP_EOL;
      echo "</pre>";

      $url = Mage::helper("adminhtml")->getUrl("adminhtml/cache/index");
      echo "<span><a href='{$url}'>Back</a></span><br><br>";

      return true;

    }

    /**
     * Revalidate all currently cached entries
     */
    public function revalidateCache()
    {
      return;
        $start = microtime(true);
        $cache = Varien_Autoload::getCache();
        Varien_Autoload::setCache(array());
        foreach ($cache as $className => $path) {
            Varien_Autoload::getFullPath($className);
        }
        $duration = microtime(true) - $start;
        Mage::log('[DeHeerHoreca_Fpc] Revalidated ' . count($cache) . ' classes (duration: ' . round($duration, 2) . ' sec)', 6 /* Zend_Log::INFO */, self::LOG_FILE);
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function checkUrl()
    {
        $k = base64_decode(Mage::app()->getRequest()->getParam('k'));
        $v = base64_decode(Mage::app()->getRequest()->getParam('v'));
        $ek = Mage::helper('core')->decrypt($v);
        return $k && $v && ($ek == $k);
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function getUrl()
    {
        $k = Mage::helper('core')->getRandomString(16);
        return Mage::getUrl(
            'deheerhorecafpc/index/clear',
            array(
                'k'      => base64_encode($k),
                'v'      => base64_encode(Mage::helper('core')->encrypt($k)),
                '_store' => Mage::app()->getDefaultStoreView()->getCode()
            )
        );
    }
}

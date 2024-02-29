<?php
/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 * PHP version 5
 *
 * @link      https://github.com/GordonLesti/Lesti_Fpc
 * @package   Lesti_Fpc
 * @author    Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license   http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

class DeHeerHoreca_Fpc_Model_Observer_Clean
{
    public const CACHE_TYPE = 'quickndirtyfpc';

    public function controllerActionPredispatchAdminhtmlCacheMassRefresh()
    {
        $types = Mage::app()->getRequest()->getParam('types');
        if ($this->_getFpc()->isActive()) {
            if ((is_array($types) && in_array(self::CACHE_TYPE, $types)) ||
                $types == self::CACHE_TYPE) {
                $this->_getFpc()->clean();
            }
        }
    }
}

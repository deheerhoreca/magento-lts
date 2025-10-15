<?php

class DeHeerHoreca_Fpc_IndexController extends Mage_Core_Controller_Front_Action {
  /**
   * Clear classpathcache
   *
   * @return void
   */
  public function clearAction() {
    if (Mage::helper('deheerhoreca_fpc')->checkUrl()) {
      if (Mage::helper('deheerhoreca_fpc')->clearCache()) {
        $this->getResponse()->setBody('OK');
      } else {
        $this->getResponse()->setBody('FAILED');
      }
    } else {
      $this->getResponse()->setBody('WRONG KEY');
    }
  }
}

<?php
/**
 * Extend Customer Model
 * Bug: Firecheckout causes password validation issues
 * Bugfix taken from https://magento.stackexchange.com/questions/46404/ce1-9-1-please-make-sure-your-password-match-issue-during-user-registration
 */

class DeHeerHoreca_Customer_Model_Customer extends DeHeerHoreca_Customer_Model_Customer_Amasty_Pure {

  /**
   * Validate customer attribute values.
   * For existing customer password + confirmation will be validated only when password is set (i.e. its change is requested)
   *
   * @return bool
   */
  public function validate()
  {
    $errors = [];
    if(!Zend_Validate::is(trim((string) $this->getFirstname()) , 'NotEmpty')) {
      $errors[] = Mage::helper('customer')->__('The first name cannot be empty.');
    }

    if(!Zend_Validate::is(trim((string) $this->getLastname()) , 'NotEmpty')) {
      $errors[] = Mage::helper('customer')->__('The last name cannot be empty.');
    }

    if(!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
      $errors[] = Mage::helper('customer')->__('Invalid email address "%s".', $this->getEmail());
    }

    $password = $this->getPassword();
    if(!$this->getId() && !Zend_Validate::is($password , 'NotEmpty')) {
      $errors[] = Mage::helper('customer')->__('The password cannot be empty.');
    }
    if(strlen((string) $password) && !Zend_Validate::is($password, 'StringLength', [self::MINIMUM_PASSWORD_LENGTH])) {
      $errors[] = Mage::helper('customer')
        ->__('The minimum password length is %s', self::MINIMUM_PASSWORD_LENGTH);
    }

    // Taken from https://magento.stackexchange.com/questions/46404/ce1-9-1-please-make-sure-your-password-match-issue-during-user-registration
    // To match passwords in both Create account and Checkout register pages start
    if(Mage::app()->getRequest()->getServer('HTTP_REFERER') == Mage::getUrl('customer/account/create') 
     OR Mage::app()->getRequest()->getServer('HTTP_REFERER') == Mage::getUrl('customer/account/edit')) {
      $confirmation = $this->getPasswordConfirmation();
    } else{
      $confirmation = $this->getConfirmation();
    }
    // End

    if($password != $confirmation) {
      $errors[] = Mage::helper('customer')->__('Please make sure your passwords match.');
    }

    $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
    $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'dob');
    if($attribute->getIsRequired() && '' == trim((string) $this->getDob())) {
      $errors[] = Mage::helper('customer')->__('The Date of Birth is required.');
    }
    $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'taxvat');
    if($attribute->getIsRequired() && '' == trim((string) $this->getTaxvat())) {
      $errors[] = Mage::helper('customer')->__('The TAX/VAT number is required.');
    }
    $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'gender');
    if($attribute->getIsRequired() && '' == trim((string) $this->getGender())) {
      $errors[] = Mage::helper('customer')->__('Gender is required.');
    }

    if(empty($errors)) {
      return true;
    }
    return $errors;
  }

  /**
   * Validate customer attribute values on password reset
   * @return bool
   */
  public function validateResetPassword()
  {
    $errors   = [];
    $password = $this->getPassword();
    if(!Zend_Validate::is($password, 'NotEmpty')) {
      $errors[] = Mage::helper('customer')->__('The password cannot be empty.');
    }
    if(!Zend_Validate::is($password, 'StringLength', [self::MINIMUM_PASSWORD_LENGTH])) {
      $errors[] = Mage::helper('customer')
        ->__('The minimum password length is %s', self::MINIMUM_PASSWORD_LENGTH);
    }
    // Start patch
    //$confirmation = $this->getPasswordConfirmation();
    if(isset($_POST['confirmation'])){
    $confirmation = $_POST['confirmation'];
    } else {
    $confirmation = $this->getPasswordConfirmation();
    }
    // End
    if($password != $confirmation) {
      $errors[] = Mage::helper('customer')->__('Please make sure your passwords match.');
    }

    if(empty($errors)) {
      return true;
    }
    return $errors;
  }
}

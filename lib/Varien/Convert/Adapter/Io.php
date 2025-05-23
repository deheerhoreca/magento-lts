<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Varien_Convert
 */

/**
 * Convert IO adapter
 *
 * @package    Varien_Convert
 */
class Varien_Convert_Adapter_Io extends Varien_Convert_Adapter_Abstract
{
    public function getResource()
    {
        if (!$this->_resource) {
            $type = $this->getVar('type', 'file');
            $className = 'Varien_Io_' . ucwords($type);
            $this->_resource = new $className();
            try {
                $this->_resource->open($this->getVars());
            } catch (Exception $e) {
                $this->addException('Error occured during file opening: ' . $e->getMessage(), Varien_Convert_Exception::FATAL);
            }
        }
        return $this->_resource;
    }

    public function load()
    {
        $data = $this->getResource()->read($this->getVar('filename'));
        $filename = $this->getResource()->pwd() . '/' . $this->getVar('filename');
        if (false === $data) {
            $this->addException('Could not load file: ' . $filename, Varien_Convert_Exception::FATAL);
        } else {
            $this->addException('Loaded successfully: ' . $filename . ' [' . strlen($data) . ' byte(s)]');
        }
        $this->setData($data);
        return $this;
    }

    public function save()
    {
        $data = $this->getData();
        $filename = $this->getResource()->pwd() . '/' . $this->getVar('filename');
        $result = $this->getResource()->write($filename, $data, 0777);
        if (false === $result) {
            $this->addException('Could not save file: ' . $filename, Varien_Convert_Exception::FATAL);
        } else {
            $text = 'Saved successfully: ' . $filename . ' [' . strlen($data) . ' byte(s)]';
            if ($this->getVar('link')) {
                $text .= ' <a href="' . $this->getVar('link') . '" target="_blank">Link</a>';
            }
            $this->addException($text);
        }
        return $this;
    }
}

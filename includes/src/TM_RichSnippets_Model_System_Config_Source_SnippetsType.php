<?php

class TM_RichSnippets_Model_System_Config_Source_SnippetsType
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options[0] = 'JSON Type (invisible)';
            $this->_options[1] = 'Schema.org (visible)';
        }
        return $this->_options;
    }
}

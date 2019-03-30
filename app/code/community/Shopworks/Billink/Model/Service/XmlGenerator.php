<?php

/**
 * Class Shopworks_Billink_XmlGenerator
 */
class Shopworks_Billink_Model_Service_XmlGenerator
{
    /**
     * Converts an array to xml
     * @param string $rootTag
     * @param array $nodes
     * @return string
     */
    public function createXml($rootTag, $nodes)
    {
        return $this->_createXmlTag($rootTag, $nodes);
    }

    /**
     * Generates xml tags recursively
     * @param string $outerTag
     * @param mixed $content
     * @return string
     */
    private function _createXmlTag($outerTag, $content)
    {
        $xmlContent = '';
        if(is_array($content))
        {
            foreach($content as $innerTag=>$value)
            {
                $xmlContent .= $this->_createXmlTag($innerTag, $value);
            }
        }
        else
        {
            $xmlContent = $content;
        }

        if(is_numeric($outerTag))
        {
            return $xmlContent;
        }
        else
        {
            return '<' . $outerTag . '>' . $xmlContent . '</' . $outerTag . '>';
        }
    }
}
<?php
/**
 * Backend for serialized array data
 */
class Shopworks_Billink_Model_System_Config_Backend_FeeRanges extends Mage_Core_Model_Config_Data
{
    /**
     * Process data after load
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        $ranges = unserialize($value);

        //make a one dimensional array (required for the frontend control)
        $flatData = array();
        $counter = 1;
        if(is_array($ranges))
        {
            foreach ($ranges as $range)
            {
                $flatData['from_' . $counter] = $range['from'];
                $flatData['until_' . $counter] = $range['until'];
                $flatData['fee_' . $counter] = $range['fee'];
                $counter++;
            }
        }

        $this->setValue($flatData);
    }

    /**
     * Prepare data before save
     * The frontend control returns a 1-dimensional array. This is not very convenient to work with, so convert it to
     * a 2-dimensional array.*
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if(!is_array($value))
        {
            $value = array();
        }

        $formattedValues = $this->parseValue($value);
        $formattedValues = $this->removeInvalidOptions($formattedValues);

        $value = serialize($formattedValues);
        $this->setValue($value);
    }

    /**
     * Create a nice array of values
     *
     * @param $value
     * @return array
     */
    private function parseValue($value)
    {
        $formattedVallues = array();

        foreach($value as $k=>$v)
        {
            $parts = explode('_', $k);
            if(count($parts) != 2)
            {
                continue;
            }

            $id = $parts[1];

            //create array if not exists
            if(empty($formattedVallues[$id]) || !is_array($formattedVallues[$id]))
            {
                $formattedVallues[$id] = array(
                    'from'=>'',
                    'until'=>'',
                    'fee'=>'',
                );
            };

            //Fill array
            switch($parts[0])
            {
                case 'from':
                    $formattedVallues[$id]['from'] = $v;
                    break;
                case 'until':
                    $formattedVallues[$id]['until'] = $v;
                    break;
                case 'fee':
                    $formattedVallues[$id]['fee'] = $v;
                    break;
            }
        }
        return $formattedVallues;
    }

    /**
     * Remove options that are not valid, see error message
     *
     * @param $formattedValues
     * @return array
     */
    private function removeInvalidOptions($formattedValues)
    {
        $result = array();

        foreach($formattedValues as $data)
        {
            $fieldsAreNotEmpty = $data['from'] !== '' && $data['until'] !== ''  && $data['fee'] !== '';
            $feeIsPositive = (float)$data['fee'] >= 0;
            $untilIsLargerThanFrom = (float)$data['from'] < (float)$data['until'];

            if($fieldsAreNotEmpty && $feeIsPositive && $untilIsLargerThanFrom)
            {
                $result[] = $data;
            }
            else
            {
                $msg = 'De rij met waarden van: ' . $data['from'] . ', tot: ' . $data['until'] . ', fee: ' . $data['fee'] . ', workflow: ' . $data['workflow'] . ' is ongeldig.';
                if(!$fieldsAreNotEmpty)
                {
                    $msg .= ' Een of meer velden zijn leeg';
                }
                if(!$feeIsPositive)
                {
                    $msg .= ' De fee moet een positief getal zijn.';
                }
                if(!$untilIsLargerThanFrom)
                {
                    $msg .= ' De range is ongeldig.';
                }

                $this->_showErrorMessage($msg);
            }
        }
        return $result;
    }


    /**
     * @param string $message
     */
    private function _showErrorMessage($message)
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        $session->addError($message);
    }
}

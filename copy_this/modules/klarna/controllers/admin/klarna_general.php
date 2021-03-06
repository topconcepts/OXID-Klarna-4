<?php

/**
 * Class Klarna_Config for module configuration in OXID backend
 */
class Klarna_General extends klarna_base_config
{

    protected $_sThisTemplate = 'kl_klarna_general.tpl';

    protected $_aKlarnaCountryCreds = array();

    protected $_aKlarnaCountries = array();

    /** @inheritdoc */
    protected $MLVars = array('sKlarnaAnonymizedProductTitle_');

    /**
     * Render logic
     *
     * @see admin/oxAdminDetails::render()
     * @return string
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    public function render()
    {
        parent::render();
        // force shopid as parameter
        // Pass shop OXID so that shop object could be loaded
        $sShopOXID = oxRegistry::getConfig()->getShopId();

        $this->setEditObjectId($sShopOXID);

        if(KlarnaUtils::is_ajax()){
            $output = $this->getMultiLangData();
            return oxRegistry::getUtils()->showMessageAndExit(json_encode($output));
        }

        $this->addTplParam('kl_countryCreds', $this->getKlarnaCountryCreds());
        $this->addTplParam('kl_countryList', json_encode($this->getKlarnaCountryAssocList()));
        $this->addTplParam(
            'kl_notSetUpCountries',
            array_diff_key($this->_aKlarnaCountries, $this->_aKlarnaCountryCreds) ?: false
        );
        $this->addTplParam('b2options', array('B2C', 'B2B', 'B2C_B2B', 'B2B_B2C'));

        return $this->_sThisTemplate;
    }

    /**
     * @return array|false
     */
    public function getKlarnaCountryCreds()
    {
        if($this->_aKlarnaCountryCreds){
            return $this->_aKlarnaCountryCreds;
        }
        $this->_aKlarnaCountryCreds = array();
        foreach ($this->getViewDataElement('confaarrs') as $sKey => $serializedArray) {
            if (strpos($sKey, 'aKlarnaCreds_') === 0) {

                $this->_aKlarnaCountryCreds[substr($sKey, -2)] = $serializedArray;
            }
        }
        
        return $this->_aKlarnaCountryCreds ?: false;
    }

    protected function convertNestedParams($nestedArray)
    {
        /*** get Country Specific Credentials Config Keys for all Klarna Countries ***/
        $db  = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
        $config = oxRegistry::getConfig();
        $sql = "SELECT oxvarname
                FROM oxconfig 
                WHERE oxvarname LIKE 'aKlarnaCreds_%'
                AND oxshopid = '{$config->getShopId()}'";

        $aCountrySpecificCredsConfigKeys = $db->getCol($sql);

        if (is_array($nestedArray)) {
            foreach ($nestedArray as $key => $arr) {
                if (strpos($key, 'aKlarnaCreds_') === 0) {
                    /*** remove key from the list if present in POST data ***/
                    unset($aCountrySpecificCredsConfigKeys[array_search($key, $aCountrySpecificCredsConfigKeys)]);
                }
                /*** serialize all assoc arrays ***/
                $nestedArray[$key] = $this->_aarrayToMultiline($arr);
            }
        }

        if ($aCountrySpecificCredsConfigKeys)
            /*** drop all keys that was not passed with POST data ***/
            $this->removeConfigKeys($aCountrySpecificCredsConfigKeys);

        return $nestedArray;
    }

    /**
     * @return mixed
     */
    protected function getKlarnaCountryAssocList()
    {
        if ($this->_aKlarnaCountries) {
            return $this->_aKlarnaCountries;
        }
        $sViewName = getViewName('oxcountry', $this->getViewDataElement('adminlang'));
        $isoList   = KlarnaConsts::getKlarnaCoreCountries();

        /** @var \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database $db */
        $db  = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
        $sql = 'SELECT oxisoalpha2, oxtitle 
                FROM ' . $sViewName . ' 
                WHERE oxisoalpha2 IN ("' . implode('","', $isoList) . '") AND oxactive = \'1\'';

        $aResult = $db->getArray($sql);
        foreach($aResult as $aCountry){
            $this->_aKlarnaCountries[$aCountry['OXISOALPHA2']] = $aCountry['OXTITLE'];
        }

        return $this->_aKlarnaCountries;
    }

}
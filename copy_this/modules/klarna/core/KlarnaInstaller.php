<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


class KlarnaInstaller extends shop_config
{
    const KLARNA_MODULE_ID = 'klarna';

    static private $instance = null;

    /**
     * @var database object
     */
    protected $db;

    /** @var  database name */
    protected $dbName;

    protected $moduleRelativePath = 'modules/klarna';
    protected $modulePath;

    /**
     * @return KlarnaInstaller|null|object
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance         = oxNew('KlarnaInstaller');
            self::$instance->db     = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
            self::$instance->dbName = oxRegistry::getConfig()->getConfigParam('dbName');
            self::$instance->modulePath = oxRegistry::getConfig()->getConfigParam('sShopDir') . self::$instance->moduleRelativePath;
        }

        return self::$instance;
    }

    /**
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public static function onActivate()
    {
        $instance = self::getInstance();
        $instance->updateViews();

        $instance->extendDbTables();

        $instance->updateViews();

        $instance->addConfigVars();

        $instance->addActions();

        $instance->addKlarnaPaymentsMethods();

        $instance->updateViews();
    }

    /**
     * Add klarna config vars and set defaults
     */
    protected function addConfigVars()
    {
        $config = oxRegistry::getConfig();
        $shopId = $config->getShopId();

        $currencies    = oxRegistry::getConfig()->getCurrencyArray();
        $currenciesVar = '';
        foreach ($currencies as $currency) {
            $currenciesVar .= $currency->name . '=>' . $currency->id;
            if ($currency !== end($currencies)) {
                $currenciesVar .= "\n";
            }
        }

        $defaultConfVars = array(
            'bool'   => array(

                'blIsKlarnaTestMode'                   => 1,
                'blKlarnaLoggingEnabled'               => 0,
                'blKlarnaAllowSeparateDeliveryAddress' => 1,
                'blKlarnaEnableAnonymization'          => 0,
                'blKlarnaSendProductUrls'              => 1,
                'blKlarnaSendImageUrls'                => 1,
                'blKlarnaMandatoryPhone'               => 1,
                'blKlarnaMandatoryBirthDate'           => 1,
                //                'blKlarnaSalutationMandatory'          => 1,
                'blKlarnaShowSubtotalDetail'           => 0,
                'blKlarnaEmdCustomerAccountInfo'       => 0,
                'blKlarnaEmdPaymentHistoryFull'        => 0,
                'blKlarnaEmdPassThrough'               => 0,
                'blKlarnaEnableAutofocus'              => 1,
                //'blKlarnaEnableDHLPackstations'        => 1,
                'blKlarnaEnablePreFilling'             => 1,
                'blKlarnaDisplayBanner'                => 1,
                'blKlarnaPreFillNotification'          => 1,
            ),
            'str'    => array(
                'sKlarnaActiveMode'                => KlarnaConsts::MODULE_MODE_KCO,
                'sKlarnaMerchantId'                => '',
                'sKlarnaPassword'                  => '',
                'sKlarnaDefaultCountry'            => 'DE',
                'iKlarnaActiveCheckbox'            => KlarnaConsts::EXTRA_CHECKBOX_NONE,
                'iKlarnaValidation'                => KlarnaConsts::NO_VALIDATION,
                'sKlarnaAnonymizedProductTitle'    => 'anonymized product',
                //'sKlarnaDefaultEURCountry'         => 'DE',
                'sKlarnaFooterDisplay'             => 0,

                // Multilang Data
                'sKlarnaAnonymizedProductTitle_EN' => 'Product name',
                'sKlarnaAnonymizedProductTitle_DE' => 'Produktname',
                'sKlarnaB2Option' => 'B2C',
            ),
            'arr'    => array(),
            'aarr'   => array(
                'aKlarnaCurrencies' => $currenciesVar,
            ),
            'select' => array(),
        );

        $savedConf     = $this->loadConfVars($shopId, self::KLARNA_MODULE_ID);
        $savedConfVars = $savedConf['vars'];

        foreach ($defaultConfVars as $type => $values) {
            foreach ($values as $name => $data) {
                if (key_exists($name, $savedConfVars[$type])) {
                    continue;
                }
                if ($type === 'aarr') {
                    $data = html_entity_decode($data);
                }

                $config->saveShopConfVar(
                    $type,
                    $name,
                    $this->_serializeConfVar($type, $name, $data),
                    $shopId,
                    self::KLARNA_MODULE_ID
                );
            }
        }
    }

    /**
     * Add Klarna Checkout to payments
     * @throws oxSystemComponentException
     */
    protected function addKlarnaPaymentsMethods()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->load('oxidinvoice');
        $de_prefix = $oPayment->getFieldData('oxdesc') === 'Rechnung' ? 0 : 1;
        $en_prefix = $de_prefix === 1 ? 0 : 1;

        $newPayments = array(klarna_oxpayment::KLARNA_PAYMENT_CHECKOUT_ID  =>
                                 array($de_prefix => 'Klarna Checkout', $en_prefix => 'Klarna Checkout'),
                             klarna_oxpayment::KLARNA_PAYMENT_PAY_LATER_ID =>
                                 array($de_prefix => 'Klarna Rechnung', $en_prefix => 'Klarna Pay Later'),
                             klarna_oxpayment::KLARNA_PAYMENT_SLICE_IT_ID  =>
                                 array($de_prefix => 'Klarna Ratenkauf', $en_prefix => 'Klarna Financing'),
                             klarna_oxpayment::KLARNA_PAYMENT_PAY_NOW =>
                                 array($de_prefix => 'Sofort bezahlen', $en_prefix => 'Klarna Pay Now'),
                             klarna_oxpayment::KLARNA_DIRECTDEBIT =>
                                 array($de_prefix => 'Klarna Pay Now Direct Debit', $en_prefix => 'Klarna Pay Now Direct Debit'),
                             klarna_oxpayment::KLARNA_SOFORT =>
                                 array($de_prefix => 'Klarna Sofortüberweisung', $en_prefix => 'Klarna Pay Now Sofort '),
        );
        $sort        = -350;
        $aLangs      = oxRegistry::getLang()->getLanguageArray();

        if ($aLangs) {
            foreach ($newPayments as $oxid => $aTitle) {
                /** @var $oPayment oxPayment */
                $oPayment = oxNew('oxPayment');
                $oPayment->load($oxid);
                if ($oPayment->isLoaded()) {
                    $oPayment->oxpayments__oxactive = new oxField(1, oxField::T_RAW);
                    $oPayment->save();
                    continue;
                }
                $oPayment->setEnableMultilang(false);
                $oPayment->setId($oxid);
                $oPayment->oxpayments__oxactive      = new oxField(1, oxField::T_RAW);
                $oPayment->oxpayments__oxaddsum      = new oxField(0, oxField::T_RAW);
                $oPayment->oxpayments__oxaddsumtype  = new oxField('abs', oxField::T_RAW);
                $oPayment->oxpayments__oxaddsumrules = new oxField('31', oxField::T_RAW);
                $oPayment->oxpayments__oxfromboni    = new oxField('0', oxField::T_RAW);
                $oPayment->oxpayments__oxfromamount  = new oxField('0', oxField::T_RAW);
                $oPayment->oxpayments__oxtoamount    = new oxField('1000000', oxField::T_RAW);
                $oPayment->oxpayments__oxchecked     = new oxField(0, oxField::T_RAW);
                $oPayment->oxpayments__oxsort        = new oxField(strval($sort), oxField::T_RAW);
                $oPayment->oxpayments__oxtspaymentid = new oxField('', oxField::T_RAW);

                // set multi language fields
                foreach ($aLangs as $oLang) {
                    $sTag                                     = oxRegistry::getLang()->getLanguageTag($oLang->id);
                    $oPayment->{'oxpayments__oxdesc' . $sTag} = new oxField($aTitle[$oLang->id], oxField::T_RAW);
                }

                $oPayment->save();
                $sort += 1;
            }
        }

        $updateOxPayments =
            array(
                "UPDATE `oxpayments` SET `KLPAYMENTOPTION`='card' WHERE `oxid`='oxidcreditcard';",
                "UPDATE `oxpayments` SET `KLPAYMENTOPTION`='direct banking' WHERE `oxid`='oxiddebitnote';",
            );
        foreach ($updateOxPayments as $sQuery) {
            $this->db->execute($sQuery);
        }
    }

    /**
     *
     * @throws oxConnectionException
     */
    protected function extendDbTables()
    {
        $db = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);

        $sql      = file_get_contents(__DIR__ . '/../install/install.sql');
        $sqlArray = explode(';', trim($sql));
        foreach ($sqlArray as $sql) {
            if ($sql === '') {
                break;
            }
            $db->execute($sql);
        }

        $aStructure = array(
            'oxorder'         => array(
                'KLMERCHANTID' => 'ADD COLUMN `KLMERCHANTID` VARCHAR(128)  DEFAULT \'\' NOT NULL',
                'KLSERVERMODE' => 'ADD COLUMN `KLSERVERMODE` VARCHAR(16) NOT NULL DEFAULT \'\'',
                'KLORDERID'    => 'ADD COLUMN `KLORDERID` VARCHAR(128)  DEFAULT \'\' NOT NULL',
                'KLSYNC'       => 'ADD COLUMN `KLSYNC` TINYINT UNSIGNED NOT NULL DEFAULT \'1\'',
            ),
            'oxorderarticles' => array(
                'KLTITLE'  => 'ADD COLUMN  `KLTITLE` VARCHAR(255) NOT NULL DEFAULT \'\'',
                'KLARTNUM' => 'ADD COLUMN  `KLARTNUM` VARCHAR(255) NOT NULL DEFAULT \'\'',
            ),
            'oxpayments'      => array(
                'KLPAYMENTTYPES'           => 'ADD COLUMN `KLPAYMENTTYPES` SET(\'payment\',\'checkout\') NULL DEFAULT \'\'',
                'KLEXTERNALNAME'           => 'ADD COLUMN `KLEXTERNALNAME` VARCHAR(255) NULL DEFAULT \'\'',
                'KLEXTERNALPAYMENT'        => 'ADD COLUMN `KLEXTERNALPAYMENT` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
                'KLEXTERNALCHECKOUT'       => 'ADD COLUMN `KLEXTERNALCHECKOUT` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
                'KLPAYMENTIMAGEURL'        => 'ADD COLUMN `KLPAYMENTIMAGEURL` VARCHAR(255) NULL DEFAULT \'\'',
                'KLPAYMENTIMAGEURL_1'      => 'ADD COLUMN `KLPAYMENTIMAGEURL_1` VARCHAR(255) NULL DEFAULT \'\'',
                'KLPAYMENTIMAGEURL_2'      => 'ADD COLUMN `KLPAYMENTIMAGEURL_2` VARCHAR(255) NULL DEFAULT \'\'',
                'KLPAYMENTIMAGEURL_3'      => 'ADD COLUMN `KLPAYMENTIMAGEURL_3` VARCHAR(255) NULL DEFAULT \'\'',
                'KLCHECKOUTIMAGEURL'       => 'ADD COLUMN `KLCHECKOUTIMAGEURL` VARCHAR(255) NULL DEFAULT \'\'',
                'KLCHECKOUTIMAGEURL_1'     => 'ADD COLUMN `KLCHECKOUTIMAGEURL_1` VARCHAR(255) NULL DEFAULT \'\'',
                'KLCHECKOUTIMAGEURL_2'     => 'ADD COLUMN `KLCHECKOUTIMAGEURL_2` VARCHAR(255) NULL DEFAULT \'\'',
                'KLCHECKOUTIMAGEURL_3'     => 'ADD COLUMN `KLCHECKOUTIMAGEURL_3` VARCHAR(255) NULL DEFAULT \'\'',
                'KLPAYMENTOPTION'          => 'ADD COLUMN `KLPAYMENTOPTION` SET(\'card\',\'direct banking\',\'other\') NOT NULL DEFAULT \'other\'',
                'KLEMDPURCHASEHISTORYFULL' => 'ADD COLUMN `KLEMDPURCHASEHISTORYFULL` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',

            ),
            'oxaddress' => array(
                'KLTEMPORARY' => 'ADD COLUMN `KLTEMPORARY` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'',
            ),
            'kl_logs'         => array(
                'KLORDERID'    => 'ADD COLUMN `KLORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT \'\' NOT NULL AFTER `OXID`',
                'KLMID'        => 'ADD COLUMN `KLMID` VARCHAR(50) CHARACTER SET utf8 NOT NULL AFTER `OXSHOPID`',
                'KLSTATUSCODE' => 'ADD COLUMN `KLSTATUSCODE` VARCHAR(16) CHARACTER SET utf8 NOT NULL AFTER `KLMID`',
                'KLURL'        => 'ADD COLUMN `KLURL` VARCHAR(256) CHARACTER SET utf8 AFTER `KLMETHOD`',
            ),
            'kl_ack'          => array(
                'KLORDERID' => 'ADD COLUMN `KLORDERID` VARCHAR(128) CHARACTER SET utf8 DEFAULT \'\' NOT NULL AFTER `OXID`, ADD KEY `KLORDERID` (`KLORDERID`)',
            ),
        );

        // ADD COLUMNS
        foreach ($aStructure as $sTableName => $aColumns) {

            $query = "ALTER TABLE `$sTableName` ";
            $first = true;

            foreach ($aColumns as $sColumnName => $queryPart) {
                if (!$this->dbColumnExist($sTableName, $sColumnName)) {
                    if (!$first) {
                        $query .= ', ';
                    }
                    $query .= $queryPart;
                    $first = false;
                }
            }

            $this->db->execute($query);
        }
    }


    /**
     * Checks if specific column exists in the table
     * @param $sTableName
     * @param $sColumnName
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @return bool
     */
    protected function dbColumnExist($sTableName, $sColumnName)
    {
        $query = "SELECT * FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->dbName . "' 
                  AND TABLE_NAME = '$sTableName'
                  AND COLUMN_NAME = '$sColumnName'
                  ";

        return (boolean)$this->db->execute($query)->RecordCount();
    }


    /**
     * Performs full view update
     */
    protected function updateViews()
    {
        //preventing edit for anyone except malladmin
        if (oxRegistry::getSession()->getVariable("malladmin")) {
            $oMetaData = oxNew('oxDbMetaDataHandler');
            $oMetaData->updateViews();
        }
    }

    /**
     * Adds Teaser Action
     */
    protected function addActions()
    {
        $shopId = $this->getConfig()->getShopId();
        // Klarna Teaser
        $oxId             = 'klarna_teaser_' . $shopId;
        $sTitle           = 'Klarna Teaser';
        $sLink            = '';
        $sFileName        = 'klarna-banner.png';
        $actionsMediaPath = oxRegistry::getConfig()->getConfigParam('sShopDir') . '/out/pictures/promo/';

        $oActionKlarnaTeaser = oxNew('oxActions');
        $oActionKlarnaTeaser->setShopId($shopId);
        $oActionKlarnaTeaser->load($oxId);
        $oActionKlarnaTeaser->setId($oxId);
        $active                                   = $oActionKlarnaTeaser->oxactions__oxactive->value ?: 0;                                                // assign old value
        $oActionKlarnaTeaser->oxactions__oxtype   = new oxField(3, oxField::T_RAW);
        $oActionKlarnaTeaser->oxactions__oxactive = new oxField($active, oxField::T_RAW);

        // set multi language fields
        $oActionKlarnaTeaser->setEnableMultilang(false);
        $aLangs = oxRegistry::getLang()->getLanguageArray();
        foreach ($aLangs as $oLang) {
            $langFileName                                        = $oLang->oxid . '_' . $sFileName;
            $sTag                                                = oxRegistry::getLang()->getLanguageTag($oLang->id);
            $oActionKlarnaTeaser->{'oxactions__oxtitle' . $sTag} = new oxField($sTitle, oxField::T_RAW);
            $oActionKlarnaTeaser->{'oxactions__oxlink' . $sTag}  = new oxField($sLink, oxField::T_RAW);
            $oActionKlarnaTeaser->{'oxactions__oxpic' . $sTag}   = new oxField($langFileName, oxField::T_RAW);

            $filePath = self::$instance->modulePath . '/out/img/' . $langFileName;
            if (file_exists($filePath)) {
                copy($filePath, $actionsMediaPath . $langFileName);
            }
        }
        $oActionKlarnaTeaser->save();
    }
}

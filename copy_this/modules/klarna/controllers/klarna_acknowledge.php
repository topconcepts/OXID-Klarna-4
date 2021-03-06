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


/**
 * Controller for Klarna Checkout Acknowledge push request
 */
class Klarna_Acknowledge extends oxUBase
{
    protected $aOrder;

    /**
     * @codeCoverageIgnore
     * @param string $sCountryISO
     * @return KlarnaOrderManagementClient|KlarnaClientBase $klarnaClient
     */
    protected function getKlarnaClient($sCountryISO)
    {
        return KlarnaOrderManagementClient::getInstance($sCountryISO);
    }

    /**
     * @throws oxConnectionException
     * @throws oxException
     */
    public function init()
    {
        parent::init();

        $orderId = oxRegistry::getConfig()->getRequestParameter('klarna_order_id');

        if (empty($orderId)) {
            return;
        }

        $this->registerKlarnaAckRequest($orderId);
        try {
            $oOrder     = $this->loadOrderByKlarnaId($orderId);
            $countryISO = KlarnaUtils::getCountryISO($oOrder->oxorder__oxbillcountryid->value);
            if ($oOrder->isLoaded()) {
                $this->getKlarnaClient($countryISO)->acknowledgeOrder($orderId);
            } elseif ($this->getKlarnaAckCount($orderId) > 1) {
                $this->getKlarnaClient($countryISO)->cancelOrder($orderId);
            }
        } catch (oxException $e) {
            $e->debugOut();

            return;
        }
    }

    /**
     * @param $orderId
     * @return oxOrder
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function loadOrderByKlarnaId($orderId)
    {
        $oOrder = oxNew('oxorder');
        $oxid   = oxDb::getDb()->getOne('SELECT oxid from oxorder where klorderid=?', array($orderId));
        $oOrder->load($oxid);

        return $oOrder;
    }


    /**
     * Register Klarna request in DB
     * @param $orderId
     * @throws oxConnectionException
     */
    protected function registerKlarnaAckRequest($orderId)
    {
        $sql = 'INSERT INTO `kl_ack` (`oxid`, `klreceived`, `klorderid`) VALUES (?,?,?)';
        oxDb::getDb()->Execute(
            $sql,
            array(oxUtilsObject::getInstance()->generateUID(), date('Y-m-d H:i:s'), $orderId)
        );
    }

    /**
     * Get count of Klarna ACK requests for location ID
     *
     * @param $orderId
     * @return string
     * @throws oxConnectionException
     */
    protected function getKlarnaAckCount($orderId)
    {
        $sql = 'SELECT COUNT(*) FROM `kl_ack` WHERE `klorderid` = ?';

        return oxDb::getDb()->getOne($sql, array($orderId));
    }
}
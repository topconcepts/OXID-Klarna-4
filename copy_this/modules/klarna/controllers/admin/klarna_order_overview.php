<?php

class klarna_order_overview extends klarna_order_overview_parent
{
    protected $klarnaOrderData;

    /**
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function init()
    {
        $result = parent::init();

        $oOrder = $this->getEditObject();
        if ($this->isKlarnaOrder() && $oOrder->getFieldData('oxstorno') == 0) {
            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            }

            try {
                $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
            } catch (KlarnaWrongCredentialsException $e) {
                $this->_aViewData['sErrorMessage'] = oxRegistry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");

                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            } catch (KlarnaOrderNotFoundException $e) {
                $this->_aViewData['sErrorMessage'] = oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            } catch (oxException $e) {
                $this->_aViewData['sErrorMessage'] = $e->getMessage();

                $oOrder->oxorder__klsync = new oxField(0);
                $oOrder->save();

                return $result;
            }

            if (is_array($this->klarnaOrderData)) {
                $klarnaOrderTotalSum = KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100);

                if ($this->klarnaOrderData['order_amount'] != $klarnaOrderTotalSum ||
                    ($this->klarnaOrderData['remaining_authorized_amount'] != $klarnaOrderTotalSum &&
                     $this->klarnaOrderData['remaining_authorized_amount'] != 0
                    ) || !$this->isCaptureInSync($this->klarnaOrderData)
                    || $this->klarnaOrderData['status'] === 'CANCELLED'
                ) {
                    $oOrder->oxorder__klsync = new oxField(0);
                } else {
                    $oOrder->oxorder__klsync = new oxField(1);
                }
                $oOrder->save();
            }
        }

        return $result;
    }

    /**
     * @throws oxSystemComponentException
     */
    public function render()
    {
        $parent = parent::render();

        $this->_aViewData['isKlarnaOrder'] = $this->isKlarnaOrder();
        $oOrder                            = $this->getEditObject(true);

        if ($this->_aViewData['isKlarnaOrder'] && $oOrder->getFieldData('oxstorno') == 0) {

            //check if credentials are valid
            if (!$this->isCredentialsValid()) {
                $this->_aViewData['sWarningMessage'] = sprintf(oxRegistry::getLang()->translateString("KLARNA_MID_CHANGED_FOR_COUNTRY"),
                    $this->_aViewData['sMid'],
                    $this->_aViewData['sCountryISO'],
                    $this->_aViewData['currentMid']
                );

                return $parent;
            }

            if (oxRegistry::getConfig()->getRequestParameter('fnc')) {

                try {
                    $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
                } catch (KlarnaWrongCredentialsException $e) {
                    $this->_aViewData['sErrorMessage'] = oxRegistry::getLang()->translateString("KLARNA_UNAUTHORIZED_REQUEST");
                    $oOrder->oxorder__klsync = new oxField(0);
                    $oOrder->save();

                    return $parent;
                } catch (KlarnaOrderNotFoundException $e) {
                    $this->_aViewData['sErrorMessage'] = oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_FOUND");

                    $oOrder->oxorder__klsync = new oxField(0);
                    $oOrder->save();

                    return $parent;
                } catch (oxException $e) {
                    $this->_aViewData['sErrorMessage'] = $e->getMessage();

                    $oOrder->oxorder__klsync = new oxField(0);
                    $oOrder->save();

                    return $parent;
                }
            }

            if (is_array($this->klarnaOrderData)) {
                $apiDisabled = oxRegistry::getLang()->translateString("KL_NO_REQUESTS_WILL_BE_SENT");
                if ($this->klarnaOrderData['status'] === 'CANCELLED') {
                    $oOrder->oxorder__klsync = new oxField(0);

                    $orderCancelled = oxRegistry::getLang()->translateString("KLARNA_ORDER_IS_CANCELLED");
                    $this->_aViewData['sWarningMessage'] = $orderCancelled . $apiDisabled;

                } else if ($this->klarnaOrderData['order_amount'] != KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100)
                           || !$this->isCaptureInSync($this->klarnaOrderData)) {
                    $oOrder->oxorder__klsync = new oxField(0);

                    $orderNotInSync = oxRegistry::getLang()->translateString("KLARNA_ORDER_NOT_IN_SYNC");
                    $this->_aViewData['sWarningMessage'] = $orderNotInSync . $apiDisabled;

                } else {
                    $oOrder->oxorder__klsync = new oxField(1);
                }
                $oOrder->save();
            }
        }

        return $parent;
    }

    /**
     * Sends order. Captures klarna order.
     * @throws oxSystemComponentException
     * @throws oxException
     */
    public function sendorder()
    {
        $cancelled = $this->getEditObject()->getFieldData('oxstorno') == 1;

        $result = parent::sendorder();

        //force reload
        $oOrder = $this->getEditObject(true);
        $inSync = $oOrder->getFieldData('klsync') == 1;

        if ($this->isKlarnaOrder() && !$cancelled && $inSync && $this->klarnaOrderData['remaining_authorized_amount'] != 0) {
            $orderLang = (int)$oOrder->getFieldData('oxlang');

            $orderLines  = $oOrder->getNewOrderLinesAndTotals($orderLang, true);
            $data        = array(
                'captured_amount' => KlarnaUtils::parseFloatAsInt($oOrder->getTotalOrderSum() * 100),
                'order_lines'     => $orderLines['order_lines'],
            );
            $sCountryISO = KlarnaUtils::getCountryISO($oOrder->getFieldData('oxbillcountryid'));

            try {
                $this->_aViewData['sErrorMessage'] = '';

                $response = $oOrder->captureKlarnaOrder($data, $oOrder->getFieldData('klorderid'), $sCountryISO);
            } catch (oxException $e) {
                $this->_aViewData['sErrorMessage'] = $e->getMessage();

                return $result;
            }
            if ($response === true) {
                $this->_aViewData['sMessage'] =
                    oxRegistry::getLang()->translateString("KLARNA_CAPTURE_SUCCESSFULL");
            }
            $this->klarnaOrderData = $this->retrieveKlarnaOrder($this->_aViewData['sCountryISO']);
        } else {
            if ($cancelled) {
                $this->_aViewData['sErrorMessage'] = oxRegistry::getLang()->translateString("KL_CAPUTRE_FAIL_ORDER_CANCELLED");
            }
        }

        return $result;
    }

    /**
     * Returns editable order object
     *
     * @param bool $reset
     * @return fcPayOneOrder|InvoicepdfOxOrder|Klarna_oxOrder|null|object|oePayPalOxOrder|oxOrder|OxpsPaymorrowOxOrder|tc_cleverreach_oxorder
     * @throws oxSystemComponentException
     */
    public function getEditObject($reset = false)
    {
        $soxId = $this->getEditObjectId();
        if (($this->_oEditObject === null && isset($soxId) && $soxId != '-1') || $reset) {
            $this->_oEditObject = oxNew('oxOrder');
            $this->_oEditObject->load($soxId);
        }

        return $this->_oEditObject;
    }

    /**
     * @param null $sCountryISO
     * @return mixed
     * @throws oxException
     * @throws oxSystemComponentException
     */
    public function retrieveKlarnaOrder($sCountryISO = null)
    {
        if (!$sCountryISO) {
            $sCountryISO = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        }

        return $this->getEditObject()->retrieveKlarnaOrder($this->getEditObject()->getFieldData('klorderid'), $sCountryISO);
    }

    /**
     * @param $klarnaOrderData
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isCaptureInSync($klarnaOrderData)
    {
        if ($klarnaOrderData['status'] === 'PART_CAPTURED') {
            if ($this->getEditObject()->getFieldData('oxsenddate') != "-") {
                return true;
            }

            return false;
        }
        if ($klarnaOrderData['status'] === 'AUTHORIZED') {

            return true;
        }

        return true;
    }

    /**
     * Method checks is order was made with Klarna module
     *
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isKlarnaOrder()
    {
        $blActive = false;

        if ($this->getEditObject() && stripos($this->getEditObject()->getFieldData('oxpaymenttype'), 'klarna_') !== false) {
            $blActive = true;
        }

        return $blActive;
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    public function isCredentialsValid()
    {
        $this->_aViewData['sMid']        = $this->getEditObject()->getFieldData('klmerchantid');
        $this->_aViewData['sCountryISO'] = KlarnaUtils::getCountryISO($this->getEditObject()->getFieldData('oxbillcountryid'));
        $currentMid                      = KlarnaUtils::getAPICredentials($this->_aViewData['sCountryISO']);
        $this->_aViewData['currentMid']  = $currentMid['mid'];

        if (strstr($this->_aViewData['currentMid'], $this->_aViewData['sMid'])) {
            return true;
        }

        return false;
    }
}

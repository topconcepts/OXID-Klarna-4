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
 *
 *
 * Class for Klarna payment history action handling
 *
 * @package Klarna
 */
class Klarna_Payment_History_Full
{
    /**
     * Max length of user ID (_sOXID value)
     *
     * @var int
     */
    const MAX_IDENTIFIER_LENGTH = 24;

    /**
     * Defines how many months in past should orders be taken in payment history
     *
     * @var int
     */
    const DATA_MONTHS_BACK_FULL_HISTORY = 24;

    /**
     * Payment statistics local storage
     *
     * @var array
     */
    protected $paymentStatistics = array();

    /**
     * Gets full payment history
     *
     * @param oxUser $user
     * @return array
     * @throws oxSystemComponentException
     */
    public function getPaymentHistoryFull(oxUser $user)
    {
        $historyRecords = array();
        $paymentList    = $this->getPaymentList();

        if ($this->hasPaymentHistory($user, $paymentList)) {
            $userId = $user->getId();
            foreach ($paymentList as $payment) {
                $historyRecords = $this->addPaymentStatisticsToHistory($historyRecords, $payment, $userId);
            }

            $historyRecords = $this->modifyDateFormats($historyRecords);
            $historyRecords = $this->modifyArrayFormat($historyRecords);
        }

        return array(
            "payment_history_full" => $historyRecords,
        );
    }

    /**
     * Checks if full payment history is posiible
     *
     * @param oxPayment $payment
     * @return bool
     */
    protected function isFullPaymentHistoryPossible(oxPayment $payment, $userId)
    {
        return $this->shouldPaymentBeIgnored($payment)
               && $this->shouldPaymentHistoryBeIgnored($payment)
               && $this->doesPaymentHasAnyOrder($payment, $userId);
    }

    /**
     * Returns all successful order statuses
     *
     * @return array
     */
    protected function getSuccessfulOrderStatuses()
    {
        return array('OK', 'SUCCESS', 'FINISHED', 'COMPLETED', 'PAID');
    }

    /**
     * Gets payment statistics by given payment object and user ID
     *
     * @param oxPayment $payment
     * @param $userId
     * @return int
     */
    protected function getPaymentStatistics(oxPayment $payment, $userId)
    {
        if (!isset($this->paymentStatistics[$payment->getId()])) {
            $this->paymentStatistics[$payment->getId()] = false;

            $orderTable = getViewName('oxorder');

            $query = "
              SELECT
                COUNT({$orderTable}.OXTOTALORDERSUM),
                SUM({$orderTable}.OXTOTALORDERSUM),
                MIN({$orderTable}.OXORDERDATE),
                MAX({$orderTable}.OXORDERDATE)
                FROM {$orderTable}
                WHERE " . $this->getPaymentQueryConditions($payment, $userId);

            $oDb    = oxDb::getDb();
            $result = $oDb->getRow($query);

            if ($result[0]) {

                $pInfo                = new stdClass();
                $pInfo->purchaseCount = (int)$result[0];
                $pInfo->purchaseSum   = (float)$result[1];
                $pInfo->dateFirstPaid = strtotime($result[2]);
                $pInfo->dateLastPaid  = strtotime($result[3]);

                $this->paymentStatistics[$payment->getId()] = $pInfo;
            }
        }

        return $this->paymentStatistics[$payment->getId()];
    }

    /**
     * Gets parameters for where condition in SQL query
     *
     * @param oxPayment $payment
     * @param string $userId
     * @return string
     * @throws oxConnectionException
     */
    protected function getPaymentQueryConditions(oxPayment $payment, $userId)
    {
        $oDb       = oxDb::getDb();
        $data_back = self::DATA_MONTHS_BACK_FULL_HISTORY;
        $dateBack  = new DateTime("-{$data_back}months");

        $orderTable = getViewName('oxorder');

        $whereCondition = " {$orderTable}.OXUSERID = " . $oDb->quote($userId) .
                          " AND {$orderTable}.OXPAYMENTTYPE =" . $oDb->quote($payment->getId()) .
                          " AND {$orderTable}.OXSTORNO != 1" . // cancelled orders
                          " AND {$orderTable}.OXORDERDATE >= '" . $dateBack->format('Y-m-d h:i:s') . "'";

        if (($st = $this->getSuccessfulOrderStatuses()) && is_array($st)) {
            $whereCondition .= " AND {$orderTable}.OXTRANSSTATUS IN ('" . implode("','", $st) . "')";
        }

        if ($this->isPaymentDateRequired($payment)) {
            $whereCondition .= " AND {$orderTable}.OXPAID != '0000-00-00 00:00:00'";
        };

        return $whereCondition;
    }

    /**
     * Checks is given payment has any orders
     *
     * @param oxPayment $payment
     * @param $userId
     * @return bool
     */
    protected function doesPaymentHasAnyOrder(oxPayment $payment, $userId)
    {
        $statistics = $this->getPaymentStatistics($payment, $userId);

        return ($statistics && $statistics->purchaseCount > 0);
    }

    /**
     * Checks if payment date is required
     *
     * @param oxPayment $payment
     * @return bool
     */
    protected function isPaymentDateRequired(oxPayment $payment)
    {
        return $payment->oxpayments__klemdpurchasehistoryfull->value == KlarnaConsts::EMD_ORDER_HISTORY_PAID;
    }

    /**
     * Checks payment history should be ignored
     *
     * @param oxPayment $payment
     * @return bool
     */
    protected function shouldPaymentHistoryBeIgnored(oxPayment $payment)
    {
        return $payment->oxpayments__klemdpurchasehistoryfull->value != KlarnaConsts::EMD_ORDER_HISTORY_NONE;
    }

    /**
     * Checks if payment is ignored
     *
     * @param oxPayment $payment
     * @return bool
     */
    protected function shouldPaymentBeIgnored(oxPayment $payment)
    {
        $ignorablePayments = array("oxempty");

        return !in_array($payment->getId(), $ignorablePayments);
    }

    /**
     * Gets payments list
     *
     * @return oxPaymentList
     * @throws oxSystemComponentException
     */
    protected function getPaymentList()
    {
        $sTable = getViewName('oxpayments');
        $query  = "SELECT {$sTable}.* FROM {$sTable} WHERE {$sTable}.oxactive = 1 ";

        $paymentList = oxNew('oxpaymentlist');
        $paymentList->selectString($query);

        return $paymentList->getArray();
    }

    /**
     * Checks if user is not fake and has a payment history
     *
     * @param oxUser $user
     * @param $paymentList
     * @return bool
     */
    protected function hasPaymentHistory(oxUser $user, $paymentList)
    {
        return !$user->isFake() && is_array($paymentList);
    }

    /**
     * Adds payment statistics to history
     *
     * @param $historyRecords
     * @param $payment
     * @param $userId
     * @return array
     */
    protected function addPaymentStatisticsToHistory($historyRecords, $payment, $userId)
    {
        if ($this->isFullPaymentHistoryPossible($payment, $userId)) {
            $paymentType       = $payment->oxpayments__klpaymentoption->value;
            $paymentStatistics = $this->getPaymentStatistics($payment, $userId);

            if (!isset($historyRecords[$paymentType])) {
                $historyRecords[$paymentType] = array(
                    "unique_account_identifier"   => substr($userId, 0, self::MAX_IDENTIFIER_LENGTH),
                    "payment_option"              => $payment->oxpayments__klpaymentoption->value,
                    "number_paid_purchases"       => 0,
                    "total_amount_paid_purchases" => 0,
                    "date_of_last_paid_purchase"  => $paymentStatistics->dateLastPaid,
                    "date_of_first_paid_purchase" => $paymentStatistics->dateFirstPaid,
                );
            }

            $historyRecords[$paymentType]["number_paid_purchases"]       += $paymentStatistics->purchaseCount;
            $historyRecords[$paymentType]["total_amount_paid_purchases"] += $paymentStatistics->purchaseSum;
            $historyRecords[$paymentType]["date_of_last_paid_purchase"]
                                                                         = max($paymentStatistics->dateLastPaid, $historyRecords[$paymentType]["date_of_last_paid_purchase"]);
            $historyRecords[$paymentType]["date_of_first_paid_purchase"]
                                                                         = min($paymentStatistics->dateFirstPaid, $historyRecords[$paymentType]["date_of_first_paid_purchase"]);
        }

        return $historyRecords;
    }

    /**
     * Modifies date formats of history records
     *
     * @param array $historyRecords
     * @return array
     */
    protected function modifyDateFormats($historyRecords)
    {
        foreach ($historyRecords as &$statistics) {
            // create from timestamp
            $dateLastPaid = new DateTime('@' . $statistics["date_of_last_paid_purchase"]);
            $dateLastPaid->setTimezone(new DateTimeZone('Europe/London'));
            $statistics["date_of_last_paid_purchase"] = $dateLastPaid->format(Klarna_EMD::EMD_FORMAT);

            // create from timestamp
            $dateFirstPaid = new DateTime('@' . $statistics["date_of_first_paid_purchase"]);
            $dateFirstPaid->setTimezone(new DateTimeZone('Europe/London'));
            $statistics["date_of_first_paid_purchase"] = $dateFirstPaid->format(Klarna_EMD::EMD_FORMAT);
        }

        return $historyRecords;
    }

    /**
     * Modifies history records
     *
     * @param array $historyRecords
     * @return array
     */
    protected function modifyArrayFormat($historyRecords)
    {
        $historyRecordsUpdated = array();
        foreach ($historyRecords as $statistics) {
            $historyRecordsUpdated[] = $statistics;
        }

        return $historyRecordsUpdated;
    }
}

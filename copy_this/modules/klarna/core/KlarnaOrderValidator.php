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


class KlarnaOrderValidator extends oxBase
{
    protected $aOrderData;

    /**
     * Reference prefix exclude from array
     *
     * @var string
     */
    protected $_sReferencePrefix = "SRV_";

    /**
     * Errors might occur when validating
     *
     * @var array
     */
    protected $_aResultErrors = array();

    /**
     * @var boolean
     */
    protected $_bResult;

    /**
     * @return array
     */
    public function getResultErrors()
    {
        return $this->_aResultErrors;
    }

    /**
     * KlarnaOrderValidator constructor.
     * @param array $aOrderData from Klarna validation request
     */
    public function __construct($aOrderData)
    {
        parent::__construct();
        $this->aOrderData = $aOrderData;
    }

    /**
     * @return bool
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function validateOrder()
    {
        $aOrderItems = $this->_fetchOrderItems();
        if (empty($aOrderItems)) {
            return $this->_bResult = false;
        }

        $this->_validateItemsBuyable($aOrderItems);

        return count($this->_aResultErrors) === 0 ? $this->_bResult = true : $this->_bResult = false;
    }

    /**
     * Returning order articles list
     *
     * @return int|mixed
     */
    protected function _fetchOrderItems()
    {
        // remove services from articles list
        foreach ($this->aOrderData['order_lines'] as $index => $aItem) {
            if ($this->isService($aItem)) {
                unset($this->aOrderData['order_lines'][$index]);
            }
        }

        return $this->aOrderData['order_lines'];
    }

    /**
     * Validating if product items buyable and with enough stock
     *
     * @param array $aItems
     * @return void
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    protected function _validateItemsBuyable($aItems)
    {
        $mergedProducts = array();
        foreach ($aItems as $item) {
            if (!isset($mergedProducts[$item['reference']])) {
                $mergedProducts[$item['reference']] = 0;
            }
            $mergedProducts[$item['reference']] += $item['quantity'];
        }
        $this->_validateOxidProductsBuyable($mergedProducts);
    }

    /**
     * Check if provided products with requested amount are buyable
     *
     * @param $mergedProducts
     * @throws oxSystemComponentException
     * @throws oxConnectionException
     */
    protected function _validateOxidProductsBuyable($mergedProducts)
    {
        $oArticleObject = oxNew('oxarticle');

        foreach ($mergedProducts as $itemKey => $itemAmount) {
            /** @var oxArticle $oArticleObject */
            $oArticleObject->klarna_loadByArtNum($itemKey);

            if ($oArticleObject->checkForStock($itemAmount) !== true) {
                $this->_aResultErrors['KL_ERROR_NOT_ENOUGH_IN_STOCK'] = $oArticleObject->getFieldData('oxartnum');
                return;
            }

            if (!$oArticleObject->isLoaded()) {
                $this->_aResultErrors['ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST'] = $oArticleObject->getFieldData('oxartnum');

                return;
            }

            if (!$oArticleObject->isBuyable()) {
                $this->_aResultErrors['ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE'] = $oArticleObject->getFieldData('oxartnum');

                return;
            }
        }
    }

    public function isValid()
    {
        return $this->_bResult;
    }

    /** @param $item array
     * @return string
     */
    protected function isService($item)
    {
        return strpos($item['reference'], $this->_sReferencePrefix) === 0;
    }
}
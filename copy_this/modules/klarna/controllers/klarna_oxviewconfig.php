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



class klarna_oxviewconfig extends klarna_oxviewconfig_parent
{

    /**
     * Klarna Express controller class name
     *
     * @const string
     */
    const CONTROLLER_CLASSNAME_KLARNA_EXPRESS = 'klarna_express';

    /**
     * Flow theme ID
     */
    const THEME_ID_FLOW = 'flow';

    /**
     * Check if active controller is Klarna Express
     *
     * @return bool
     */

    const KL_FOOTER_DISPLAY_NONE            = 0;
    const KL_FOOTER_DISPLAY_PAYMENT_METHODS = 1;
    const KL_FOOTER_DISPLAY_LOGO            = 2;

    public function isActiveControllerKlarnaExpress()
    {
        return strcasecmp($this->getActiveClassName(), self::CONTROLLER_CLASSNAME_KLARNA_EXPRESS) === 0;
    }

    /**
     * Check if active theme is Flow
     *
     * @return bool
     */
    public function isActiveThemeFlow()
    {
        return strcasecmp($this->getActiveTheme(), self::THEME_ID_FLOW) === 0;
    }

    /**
     * Get Klarna module url with added path
     *
     * @param string $sPath
     * @return string
     */
    public function getKlarnaModuleUrl($sPath = '')
    {
        $sUrl = str_replace(
            rtrim($this->getConfig()->getConfigParam('sShopDir'), '/'),
            rtrim($this->getConfig()->getSslShopUrl(), '/'),
            $this->getConfig()->getConfigParam('sShopDir') . 'modules/klarna/' . $sPath
        );

        return $sUrl;
    }

    /**
     *
     */
    public function getKlarnaFooterContent()
    {
        $sCountryISO = KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry');
        if (!in_array($sCountryISO, KlarnaConsts::getKlarnaCoreCountries())) {
            return false;
        }

        $klFooter = intval(KlarnaUtils::getShopConfVar('sKlarnaFooterDisplay'));
        if ($klFooter) {

            if ($klFooter === self::KL_FOOTER_DISPLAY_PAYMENT_METHODS && KlarnaUtils::isKlarnaCheckoutEnabled()) {
                $sLocale = strtolower(KlarnaConsts::getLocale(true));
            } else if ($klFooter === self::KL_FOOTER_DISPLAY_LOGO)
                $sLocale = '';
            else
                return false;
            $url  = sprintf(KlarnaConsts::getFooterImgUrls(KlarnaUtils::getShopConfVar('sKlFooterValue')), $sLocale);
            $from = '/' . preg_quote('-', '/') . '/';
            $url  = preg_replace($from, '_', $url, 1);

            return array(
                'url'   => $url,
                'class' => KlarnaUtils::getShopConfVar('sKlFooterValue'),
            );
        }

        return false;
    }

    /**
     *
     */
    public function getKlarnaHomepageBanner()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaDisplayBanner')) {
            $oLang = oxRegistry::getLang();
            $lang  = $oLang->getLanguageAbbr();

            $varName = 'sKlarnaBannerSrc' . '_' . strtoupper($lang);
            if (!$sBannerScript = KlarnaUtils::getShopConfVar($varName)) {
                $aDefaults     = KlarnaConsts::getDefaultBannerSrc();
                $sBannerScript = $aDefaults[$lang];
            }

            return str_replace('{{merchantid}}', KlarnaUtils::getShopConfVar('sKlarnaMerchantId'), $sBannerScript);
        }

        return false;
    }

    /**
     *
     */
    public function addBuyNow()
    {
        return KlarnaUtils::getShopConfVar('blKlarnaDisplayBuyNow');
    }

    /**
     * @param $value
     * @return null|string
     */
    public function resolveFullAssetUrl($value)
    {
        if (!$value) {
            return $value;
        }

        $urlPattern = '/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[\/?#]\S*)?$/u';

        if (preg_match($urlPattern, $value)) {
            return $value;
        }

        return $this->getKlarnaModuleUrl($value);
    }

    /**
     *
     */
    public function getMode()
    {
        return KlarnaUtils::getShopConfVar('sKlarnaActiveMode');
    }

    /**
     *
     * @throws oxSystemComponentException
     */
    public function isKlarnaCheckoutEnabled()
    {
        $sCountryIso = oxRegistry::getSession()->getVariable('sCountryISO');

        return KlarnaUtils::isKlarnaCheckoutEnabled() &&
               KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryIso);
    }

    /**
     *
     */
    public function isKlarnaPaymentsEnabled()
    {
        return KlarnaUtils::isKlarnaPaymentsEnabled();
    }

    /**
     * @param bool $blShipping
     * @return mixed
     * @throws oxSystemComponentException
     */
    public function getCountryList($blShipping = false)
    {
        if ($this->isCheckoutNonKlarnaCountry() && $this->getActiveClassName() !== 'account_user' && !$blShipping) {
            $this->_oCountryList = oxNew('oxcountrylist');
            $this->_oCountryList->loadActiveNonKlarnaCheckoutCountries();

            return $this->_oCountryList;
        } else {
            unset($this->_oCountryList);

            return parent::getCountryList();
        }
    }

    /**
     *
     * @throws oxSystemComponentException
     */
    public function isCheckoutNonKlarnaCountry()
    {
        $sCountryIso = oxRegistry::getSession()->getVariable('sCountryISO');

        return KlarnaUtils::isKlarnaCheckoutEnabled() &&
               !KlarnaUtils::isCountryActiveInKlarnaCheckout($sCountryIso);
    }

    /**
     * @return bool
     */
    public function isUserLoggedIn()
    {
        if ($user = $this->getUser()) {
            return $user->oxuser__oxid->value == oxRegistry::getSession()->getVariable('usr');
        }

        return false;
    }

    /**
     * Confirm present country is Germany
     *
     * @return bool
     */
    public function getIsGermany()
    {
        if ($user = $this->getUser()) {
            $sCountryISO2 = $user->resolveCountry();
        } else {
            $sCountryISO2 = KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry');
        }

        return $sCountryISO2 == 'DE';
    }

    /**
     * Show Checkout terms
     *
     * @return bool true if current country is Austria or Germany
     */
    public function getIsAustria()
    {
        if ($user = $this->getUser()) {
            $sCountryISO2 = $user->resolveCountry();
        } else {
            $sCountryISO2 = KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry');
        }

        return $sCountryISO2 == 'AT';
    }

    /**
     * @return bool
     * @throws oxSystemComponentException
     */
    public function showCheckoutTerms()
    {
        if ($this->isKlarnaCheckoutEnabled() && $this->isShowPrefillNotif()) {
            if ($this->getIsAustria() || $this->getIsGermany())

                return true;
        }

        return false;
    }

    /**
     * Get DE notification link for KCO
     *
     * @return string
     */
    public function getLawNotificationsLinkKco()
    {
        $sCountryISO = oxRegistry::getSession()->getVariable('sCountryISO');

        if(!$sCountryISO)
        {
            $sCountryISO = KlarnaUtils::getShopConfVar('sKlarnaDefaultCountry');
        }

        $mid         = KlarnaUtils::getAPICredentials($sCountryISO);
        preg_match('/^(?P<mid>(.)+)(\_)/', $mid['mid'], $matches);

        return sprintf(KlarnaConsts::KLARNA_PREFILL_NOTIF_URL,
            $matches['mid'], $this->getActLanguageAbbr()
        );
    }

    /**
     *
     */
    public function isShowPrefillNotif()
    {
        return (bool)KlarnaUtils::getShopConfVar('blKlarnaPreFillNotification');
    }

    /**
     *
     */
    public function isPrefillIframe()
    {
        return (bool)KlarnaUtils::getShopConfVar('blKlarnaEnablePreFilling');
    }
}
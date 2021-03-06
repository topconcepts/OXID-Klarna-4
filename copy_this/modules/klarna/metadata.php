<?php
$aModule = array(
    'id'          => 'klarna',
    'title'       => 'Klarna Checkout and Klarna Payments',
    'description' => array(
        'de' => 'Egal was Sie verkaufen, unsere Produkte sind dafür gemacht, Ihren Kunden das beste Erlebnis zu bereiten. Das gefällt nicht nur Ihnen, sondern auch uns! Die Klarna Plugins werden stets auf Herz und Nieren geprüft und können ganz einfach durch Sie oder Ihre technischen Ansprechpartner aktiviert werden. Das nennen wir smoooth. Hier können Sie sowohl Klarna Payments aktivieren und anschließend genau die Zahlarten auswählen, die Sie wünschen oder mit der Komplettlösung, dem Klarna Checkout, Ihre Customer Journey optimieren. Erfahren Sie hier mehr zu Klarna für OXID: <a href="https://www.klarna.com/de/verkaeufer/oxid/" target="_blank">https://www.klarna.com/de/verkaeufer/oxid/</a> Und so einfach ist die Integration: <a href="https://hello.klarna.com/rs/778-XGY-327/images/How_to_OXID.mp4" target="_blank">Zum Video</a>',
        'en' => 'No matter what you sell, our products are made to give your customers the best purchase experience. This is not only smoooth for you - it is smoooth for us, too! Klarna plugins are always tested and can be activated by you or your technical contact with just a few clicks. That is smoooth. Here you can activate Klarna Payments and then select exactly the payment methods you want or optimize your customer journey with the complete Klarna Checkout solution. Find out more about Klarna for OXID: <a href="https://www.klarna.com/de/verkaeufer/oxid/" target="_blank">https://www.klarna.com/de/verkaeufer/oxid/</a> Integrating Klarna at OXID is easy as pie: <a href="https://hello.klarna.com/rs/778-XGY-327/images/How_to_OXID.mp4" target="_blank">to the video (click)</a>'
    ),
    'version'     => '4.6.2',
    'author'      => '<a href="https://www.cgrd.de/oxid-eshop" target="_blank">https://www.cgrd.de/oxid-eshop</a>',
    'thumbnail'   => '/out/admin/img/klarna_lockup_black.jpg',
    'url'         => 'https://www.klarna.com/de/verkaeufer/plattformen-und-partner/oxid/',
    'email'       => 'oxid@klarna.com',
    'files'       => array(
        // core classes
        'KlarnaInstaller'                  => 'klarna/core/KlarnaInstaller.php',
        'KlarnaClientBase'                 => 'klarna/core/KlarnaClientBase.php',
        'KlarnaCheckoutClient'             => 'klarna/core/KlarnaCheckoutClient.php',
        'KlarnaPaymentsClient'             => 'klarna/core/KlarnaPaymentsClient.php',
        'KlarnaOrderManagementClient'      => 'klarna/core/KlarnaOrderManagementClient.php',
        'KlarnaUtils'                      => 'klarna/core/KlarnaUtils.php',
        'KlarnaFormatter'                  => 'klarna/core/KlarnaFormatter.php',
        'KlarnaConsts'                     => 'klarna/core/KlarnaConsts.php',
        'klarna_logs'                      => 'klarna/core/klarna_logs.php',
        'KlarnaPayment'                    => 'klarna/core/KlarnaPayment.php',
        'KlarnaOrder'                      => 'klarna/core/KlarnaOrder.php',
        'KlarnaOrderValidator'             => 'klarna/core/KlarnaOrderValidator.php',
        'KlarnaPaymentHandler'             => 'klarna/core/KlarnaPaymentHandler.php',
        'KlarnaPaymentHandlerInterface'    => 'klarna/core/KlarnaPaymentHandlerInterface.php',

        // controllers
        'klarna_express'                   => 'klarna/controllers/klarna_express.php',
        'klarna_ajax'                      => 'klarna/controllers/klarna_ajax.php',
        'klarna_epm_dispatcher'            => 'klarna/controllers/klarna_epm_dispatcher.php',
        'klarna_acknowledge'               => 'klarna/controllers/klarna_acknowledge.php',
        'klarna_validate'                  => 'klarna/controllers/klarna_validate.php',

        //admin
        'klarna_general'                   => 'klarna/controllers/admin/klarna_general.php',
        'klarna_base_config'               => 'klarna/controllers/admin/klarna_base_config.php',
        'klarna_design'                    => 'klarna/controllers/admin/klarna_design.php',
        'klarna_start'                     => 'klarna/controllers/admin/klarna_start.php',
        'klarna_configuration'             => 'klarna/controllers/admin/klarna_configuration.php',
        'klarna_external_payments'         => 'klarna/controllers/admin/klarna_external_payments.php',
        'klarna_emd_admin'                 => 'klarna/controllers/admin/klarna_emd_admin.php',
        'klarna_orders'                    => 'klarna/controllers/admin/klarna_orders.php',
        'klarna_shipping'                  => 'klarna/controllers/admin/klarna_shipping.php',

        // models
        'klarna_emd'                       => 'klarna/models/klarna_emd.php',
        'klarna_customer_account_info'     => 'klarna/models/emd_payload/klarna_customer_account_info.php',
        'klarna_payment_history_full'      => 'klarna/models/emd_payload/klarna_payment_history_full.php',
        'klarna_pass_through'              => 'klarna/models/emd_payload/klarna_pass_through.php',
        'klarna_invoicepdfarticlesummary'  => 'klarna/models/klarna_invoicepdfarticlesummary.php',

        //Exceptions
        'KlarnaClientException'            => 'klarna/exception/KlarnaClientException.php',
        'KlarnaWrongCredentialsException'  => 'klarna/exception/KlarnaWrongCredentialsException.php',
        'KlarnaOrderNotFoundException'     => 'klarna/exception/KlarnaOrderNotFoundException.php',
        'KlarnaOrderReadOnlyException'     => 'klarna/exception/KlarnaOrderReadOnlyException.php',
        'KlarnaConfigException'            => 'klarna/exception/KlarnaConfigException.php',
        'KlarnaCaptureNotAllowedException' => 'klarna/exception/KlarnaCaptureNotAllowedException.php',
        'KlarnaBasketTooLargeException'    => 'klarna/exception/KlarnaBasketTooLargeException.php',
    ),
    'extend'      => array(
        // models
        'oxbasket'       => 'klarna/models/klarna_oxbasket',
        'oxuser'         => 'klarna/models/klarna_oxuser',
        'oxarticle'      => 'klarna/models/klarna_oxarticle',
        'oxorder'        => 'klarna/models/klarna_oxorder',
        'oxaddress'      => 'klarna/models/klarna_oxaddress',
        'oxpayment'      => 'klarna/models/klarna_oxpayment',
        'oxcountrylist'  => 'klarna/models/klarna_oxcountrylist',
        'oxorderarticle' => 'klarna/models/klarna_oxorderarticle',
        'oxuserpayment'  => 'klarna/models/klarna_oxuserpayment',
        'oxpaymentgateway' => 'klarna/models/KlarnaPaymentGateway',
        'oxshopcontrol' => 'klarna/core/KlarnaOxShopControl',

        // controllers
        'order'          => 'klarna/controllers/klarna_order',
        'thankyou'       => 'klarna/controllers/klarna_thankyou',
        'oxviewconfig'   => 'klarna/controllers/klarna_oxviewconfig',
        'user'           => 'klarna/controllers/klarna_user',
        'payment'        => 'klarna/controllers/klarna_payment',
        'basket'         => 'klarna/controllers/klarna_basket',

        // admin
        'order_address'  => 'klarna/controllers/admin/klarna_order_address',
        'order_list'     => 'klarna/controllers/admin/klarna_order_list',
        'order_article'  => 'klarna/controllers/admin/klarna_order_article',
        'order_main'     => 'klarna/controllers/admin/klarna_order_main',
        'order_overview' => 'klarna/controllers/admin/klarna_order_overview',
        'payment_main'   => 'klarna/controllers/admin/klarna_payment_main',

        //components
        'oxcmp_basket'   => 'klarna/components/klarna_oxcmp_basket',
        'oxcmp_user'     => 'klarna/components/klarna_oxcmp_user',
        'oxwservicemenu' => 'klarna/components/widgets/oxw_klarna_servicemenu',
    ),
    'templates'   => array(
        'kl_klarna_checkout.tpl'                => 'klarna/views/checkout/kl_klarna_checkout.tpl',
        'kl_amazon_login.tpl'                   => 'klarna/views/checkout/kl_amazon_login.tpl',
        'kl_klarna_checkout_voucher_data.tpl'   => 'klarna/views/checkout/inc/kl_klarna_checkout_voucher_data.tpl',
        'kl_klarna_checkout_voucher_box.tpl'    => 'klarna/views/checkout/inc/kl_klarna_checkout_voucher_box.tpl',
        'kl_klarna_checkout_voucher_errors.tpl' => 'klarna/views/checkout/inc/kl_klarna_checkout_voucher_errors.tpl',
        'kl_klarna_json.tpl'                    => 'klarna/views/checkout/inc/kl_klarna_json.tpl',
        'kl_klarna_country_select_popup.tpl'    => 'klarna/views/checkout/inc/kl_klarna_country_select_popup.tpl',
        'kl_klarna_checkout_login_box.tpl'      => 'klarna/views/checkout/inc/kl_klarna_checkout_login_box.tpl',
        'kl_klarna_checkout_address_box.tpl'    => 'klarna/views/checkout/inc/kl_klarna_checkout_address_box.tpl',
        //'kl_klarna_notice.tpl'                  => 'klarna/views/widget/kl_klarna_notice.tpl',

        //admin
        'kl_klarna_general.tpl'                 => 'klarna/views/admin/tpl/kl_klarna_general.tpl',
        'kl_klarna_design.tpl'                  => 'klarna/views/admin/tpl/kl_klarna_design.tpl',
        'kl_klarna_kco_config.tpl'              => 'klarna/views/admin/tpl/kl_klarna_kco_config.tpl',
        'kl_klarna_kp_config.tpl'               => 'klarna/views/admin/tpl/kl_klarna_kp_config.tpl',
        'kl_klarna_start.tpl'                   => 'klarna/views/admin/tpl/kl_klarna_start.tpl',
        'kl_klarna_external_payments.tpl'       => 'klarna/views/admin/tpl/kl_klarna_external_payments.tpl',
        'kl_klarna_emd_admin.tpl'               => 'klarna/views/admin/tpl/kl_klarna_emd_admin.tpl',
        'kl_klarna_orders.tpl'                  => 'klarna/views/admin/tpl/kl_klarna_orders.tpl',
        'tcklarna_shipping.tpl'                 => 'klarna/views/admin/tpl/tcklarna_shipping.tpl',

        //admin partial
        'kl_country_creds.tpl'                  => 'klarna/views/admin/tpl/kl_country_creds.tpl',
        'kl_header.tpl'                         => 'klarna/views/admin/tpl/kl_header.tpl',
        'kl_lang_spec_conf.tpl'                 => 'klarna/views/admin/tpl/kl_lang_spec_conf.tpl',

    ),
    'blocks'      => array(
        array(
            'template' => 'widget/minibasket/minibasket.tpl',
            'block'    => 'widget_minibasket',
            'file'     => '/views/blocks/widget/minibasket/kl_klarna_minibasket.tpl',
        ),
        array(
            'template' => 'layout/footer.tpl',
            'block'    => 'footer_main',
            'file'     => 'kl_klarna_footer_main',
        ),
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'select_payment',
            'file'     => 'kl_payment_select_override',
        ),
        array(
            'template' => 'page/checkout/basket.tpl',
            'block'    => 'checkout_basket_next_step_top',
            'file'     => 'kl_basket_override',
        ),
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'change_payment',
            'file'     => 'kl_kp_widget',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'order_basket',
            'file'     => 'kl_kp_widget',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'shippingAndPayment',
            'file'     => 'kl_order_logo',
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'checkout_order_next_step_bottom',
            'file'     => 'kl_remove_amazon',
        ),
        array(
            'template' => 'page/details/inc/productmain.tpl',
            'block'    => 'details_productmain_tobasket',
            'file'     => '/views/blocks/page/details/inc/kl_klarna_checkout_button.tpl',
        ),
        array(
            'template' => 'page/checkout/thankyou.tpl',
            'block'    => 'checkout_thankyou_info',
            'file'     => '/views/blocks/page/checkout/inc/kl_klarna_checkout_thankyou_info.tpl',
        ),
        array(
            'template' => 'page/checkout/inc/steps.tpl',
            'block'    => 'checkout_steps_main',
            'file'     => '/views/blocks/page/checkout/inc/kl_klarna_checkout_steps_main.tpl',
        ),
        array(
            'template' => 'form/fieldset/user_billing.tpl',
            'block'    => 'form_user_billing_country',
            'file'     => '/views/blocks/form/fieldset/kl_klarna_user_billing.tpl',
        ),
        array(
            'template' => 'layout/footer.tpl',
            'block'    => 'footer_main',
            'file'     => '/views/blocks/widget/header/kl_klarna_law_notice.tpl',
        ),
        array(
            'template' => 'order_main.tpl',
            'block'    => 'admin_order_main_form',
            'file'     => 'kl_admin_order_main_form',
        ),
        array(
            'template' => 'email/html/order_cust.tpl',
            'block'    => 'email_html_order_cust_paymentinfo_top',
            'file'     => '/views/emails/html/kl_klarna_email_payment_badge.tpl',
        ),
        array(
            'template' => 'form/fieldset/user_shipping.tpl',
            'block'    => 'form_user_shipping_country',
            'file'     => '/views/blocks/form/fieldset/kl_klarna_user_shipping.tpl',
        ),
        array(
            'template' => 'order_overview.tpl',
            'block'    => 'admin_order_overview_billingaddress',
            'file'     => 'kl_admin_order_overview_billingaddress',
        ),
        array(
            'template' => 'payment_main.tpl',
            'block'    => 'admin_payment_main_form',
            'file'     => 'kl_admin_payment_main_form',
        ),
    ),
    'settings'    => array(),
    'events'      => array(
        'onActivate'   => 'KlarnaInstaller::onActivate'
    ),
);

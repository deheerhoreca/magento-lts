<?php

class TM_FireCheckout_Model_Observer_Integration
{
    public function addLayoutUpdate($observer)
    {
        $helper  = Mage::helper('core');
        $updates = $observer->getUpdates();
        if (!is_object($updates)) {
            return;
        }

        $mapping = array(
            'Adyen_Payment'              => 'tm/firecheckout/adyen_payment.xml',
            'Aicod_Italy'                => 'tm/firecheckout/aicod_italy.xml',
            'Aitoc_Aitgiftwrap'          => 'tm/firecheckout/aitoc_aitgiftwrap.xml',
            'Amasty_Checkoutfees'        => 'tm/firecheckout/amasty_checkoutfees.xml',
            'Amasty_Deliverydate'        => 'tm/firecheckout/amasty_deliverydate.xml',
            'Amasty_GiftCard'            => 'tm/firecheckout/amasty_giftcard.xml',
            'Amasty_Giftwrap'            => 'tm/firecheckout/amasty_giftwrap.xml',
            'Amasty_Orderattr'           => 'tm/firecheckout/amasty_orderattr.xml',
            'Amasty_StoreCredit'         => 'tm/firecheckout/amasty_storecredit.xml',
            'Amasty_Stripe'              => 'tm/firecheckout/amasty_stripe.xml',
            'AW_Advancednewsletter'      => 'tm/firecheckout/aw_newsletter.xml',
            'AW_Newsletter'              => 'tm/firecheckout/aw_newsletter.xml',
            'AW_Storecredit'             => 'tm/firecheckout/aw_storecredit.xml',
            'Billpay'                    => 'tm/firecheckout/billpay.xml',
            'Bitpay_Bitcoins'            => 'tm/firecheckout/bitpay_bitcoins.xml',
            'Bitpay_Core'                => 'tm/firecheckout/bitpay_core.xml',
            'Bluesnap_Payment'           => 'tm/firecheckout/bluesnap_payment.xml',
            'Bongo_International'        => 'tm/firecheckout/bongo_international.xml',
            'Bpost_ShippingManager'      => 'tm/firecheckout/bpost_shippingmanager.xml',
            'Bpost_ShM'                  => 'tm/firecheckout/bpost_shm.xml',
            'Braintree'                  => 'tm/firecheckout/braintree.xml',
            'Braintree_Payments'         => 'tm/firecheckout/braintree_payments.xml',
            'Bysoft_Relaypoint'          => 'tm/firecheckout/bysoft_relaypoint.xml', // not confirmed module code
            'Ced_CsMultiShipping'        => 'tm/firecheckout/ced_csmultishipping.xml',
            'CheckoutApi_ChargePayment'  => 'tm/firecheckout/checkoutapi_chargepayment.xml',
            'Conekta_Card'               => 'tm/firecheckout/conekta_card.xml',
            'CraftyClicks'               => 'tm/firecheckout/craftyclicks.xml',
            'Craig_Tco'                  => 'tm/firecheckout/craig_tco.xml',
            'Cryozonic_Stripe'           => 'tm/firecheckout/cryozonic_stripe.xml',
            'Customweb_PayUnityCw'       => 'tm/firecheckout/customweb_payunitycw.xml',
            'Dhl_Account'                => 'tm/firecheckout/dhl_account.xml',
            'Dhl_LocationFinder'         => 'tm/firecheckout/dhl_locationfinder.xml',
            'Dhl_Magentolws'             => 'tm/firecheckout/dhl_magentolws.xml',
            'Dhl_Versenden'              => 'tm/firecheckout/dhl_versenden.xml',
            'DPD_Shipping'               => 'tm/firecheckout/dpd_shipping.xml',
            'EasyNolo_BancaSellaPro'     => 'tm/firecheckout/easynolo_bancasellapro.xml',
            'Ebizmarts_MageMonkey'       => 'tm/firecheckout/ebizmarts_magemonkey.xml',
            'Ebizmarts_SagePaymentsPro'  => 'tm/firecheckout/ebizmarts_sagepaymentspro.xml',
            'Ebizmarts_SagePaySuite'     => 'tm/firecheckout/ebizmarts_sagepaysuite.xml',
            'Emja_TaxRelief'             => 'tm/firecheckout/emja_taxrelief.xml',
            'Emjainteractive_ShippingOption' => 'tm/firecheckout/emjainteractive_shippingoption.xml',
            'Enterprise_Enterprise'      => 'tm/firecheckout/mage_enterprise.xml',
            'FireGento_MageSetup'        => 'tm/firecheckout/firegento_magesetup.xml',
            'Fooman_DpsPro'              => 'tm/firecheckout/fooman_dpspro.xml',
            'Fooman_Surcharge'           => 'tm/firecheckout/fooman_surcharge.xml',
            'GCMC_GiveChange'            => 'tm/firecheckout/gcmc_givechange.xml',
            'Geissweb_Euvatgrouper'      => 'tm/firecheckout/geissweb_euvatgrouper.xml',
            'Gene_Braintree'             => 'tm/firecheckout/gene_braintree.xml',
            'Grinet_Payu'                => 'tm/firecheckout/grinet_payu.xml',
            'Hps_Securesubmit'           => 'tm/firecheckout/hps_securesubmit.xml',
            'Inchoo_SocialConnect'       => 'tm/firecheckout/inchoo_socialconnect.xml',
            'Infolution_ILStrongCaptcha' => 'tm/firecheckout/infolution_ilstrongcaptcha.xml',
            'IntellectLabs_Stripe'       => 'tm/firecheckout/intellectlabs_stripe.xml',
            'IrvineSystems_Deliverydate' => 'tm/firecheckout/irvinesystems_deliverydate.xml',
            'IrvineSystems_JapanPost'    => 'tm/firecheckout/irvinesystems_japanpost.xml',
            'IrvineSystems_Sagawa'       => 'tm/firecheckout/irvinesystems_sagawa.xml',
            'IrvineSystems_Seino'        => 'tm/firecheckout/irvinesystems_seino.xml',
            'IrvineSystems_Yamato'       => 'tm/firecheckout/irvinesystems_yamato.xml',
            'Itabs_Debit'                => 'tm/firecheckout/itabs_debit.xml',
            'Iways_PayPalPlus'           => 'tm/firecheckout/iways_paypalplus.xml',
            'IWD_OnepageCheckoutSignature' => 'tm/firecheckout/iwd_opc_signature.xml',
            'J2t_Rewardpoints'           => 'tm/firecheckout/j2t_rewardpoints.xml',
            'JRD_Paczkomaty'             => 'tm/firecheckout/jrd_paczkomaty.xml',
            'Kiala_LocateAndSelect'      => 'tm/firecheckout/kiala_locateandselect.xml',
            'Klarna_KlarnaPaymentModule' => 'tm/firecheckout/klarna_klarnapaymentmodule.xml',
            'Klarna_Payments'            => 'tm/firecheckout/klarna_payments.xml',
            'LaPoste_Colissimo'          => 'tm/firecheckout/laposte_colissimo.xml',
            'Lemonline_SmartPost'        => 'tm/firecheckout/lemonline_smartpost.xml',
            'Mage_Authorizenet'          => 'tm/firecheckout/mage_authorizenet.xml',
            'Mage_Captcha'               => 'tm/firecheckout/mage_captcha.xml',
            'Mage_MOLPaySeamless'        => 'tm/firecheckout/mage_molpayseamless.xml',
            'Magegiant_GiantPoints'      => 'tm/firecheckout/magegiant_giantpoints.xml',
            'Magenio_FiscalData'         => 'tm/firecheckout/magenio_fiscaldata.xml',
            'Magestore_Affiliateplus'    => 'tm/firecheckout/magestore_affiliateplus.xml',
            'Magestore_Storepickup'      => 'tm/firecheckout/magestore_storepickup.xml',
            'MageWorx_MultiFees'         => 'tm/firecheckout/mageworx_multifees.xml',
            'Man4x_MondialRelay'         => 'tm/firecheckout/man4x_mondialrelay.xml',
            'Mico_RushPackage'           => 'tm/firecheckout/mico_rushpackage.xml',
            'MW_RewardPoints'            => 'tm/firecheckout/mw_rewardpoints.xml',

            // this is a temporary solution, until module will fix it's logic
            // The true way is to override Mage::helper('payment')::getStoreMethods method
            // instead of Mage_Checkout_Block_Onepage_Payment_Methods::getMethods
            'Mymonki_Ship2pay'           => 'tm/firecheckout/mymonki_ship2pay.xml',

            'Netresearch_Billsafe'       => 'tm/firecheckout/netresearch_billsafe.xml',
            'Netresearch_OPS'            => 'tm/firecheckout/netresearch_ops.xml',
            'OPG_Square'                 => 'tm/firecheckout/opg_square.xml',
            'Orba_Sheepla'               => 'tm/firecheckout/orba_sheepla.xml',
            'PayEx_Payments'             => 'tm/firecheckout/payex_payments.xml',
            'Payfort_Pay'                => 'tm/firecheckout/payfort_pay.xml',
            'Payone_Core'                => 'tm/firecheckout/payone_core.xml',
            'Phoenix_Ipayment'           => 'tm/firecheckout/phoenix_ipayment.xml',
            'Phoenix_WirecardCheckoutPage' => 'tm/firecheckout/phoenix_wirecardcheckoutpage.xml',
            'Plumtree_Storepickup'       => 'tm/firecheckout/plumtree_storepickup.xml',
            'Pook_CollectInStore'        => 'tm/firecheckout/pook_collectinstore.xml',
            'PostcodeNl_Api'             => 'tm/firecheckout/postcodenl_api.xml',
            'Rack_Getpostcode'           => 'tm/firecheckout/rack_getpostcode.xml',
            'RedPandaPlus_OrderAttachments' => 'tm/firecheckout/redpandaplus_orderattachments.xml',
            'Rewardpoints'               => 'tm/firecheckout/rewardpoints.xml',
            'Rugento_Pickpoint'          => 'tm/firecheckout/rugento_pickpoint.xml',
            'SFC_Autoship'               => 'tm/firecheckout/sfc_autoship.xml',
            'Shipperhq_Pickup'           => 'tm/firecheckout/shipperhq_pickup.xml',
            'Shipperhq_Validation'       => 'tm/firecheckout/shipperhq_validation.xml',
            'Solupay_Networkmerchant'    => 'tm/firecheckout/solupay_networkmerchant.xml',
            'SR_UpsShip'                 => 'tm/firecheckout/sr_upsship.xml',
            'Svea_WebPay'                => 'tm/firecheckout/svea_webpay.xml',
            'Symmetrics_Buyerprotect'    => 'tm/firecheckout/symmetrics_buyerprotect.xml',
            'Tig_MyParcel'               => 'tm/firecheckout/tig_myparcel.xml',
            'TIG_MyParcel2014'           => 'tm/firecheckout/tig_myparcel2014.xml',
            'TIG_Postcode'               => 'tm/firecheckout/tig_postcode.xml',
            'TIG_PostNL'                 => 'tm/firecheckout/tig_postnl.xml',
            'Unirgy_Giftcert'            => 'tm/firecheckout/unirgy_giftcert.xml',
            'Vaimo_Klarna'               => 'tm/firecheckout/vaimo_klarna.xml',
            'Webgriffe_TaxIdPro'         => 'tm/firecheckout/webgriffe_taxidpro.xml',
            'Webgriffe_Tntpro'           => 'tm/firecheckout/webgriffe_tntpro.xml',
            'Webkul_Warehousemanagement' => 'tm/firecheckout/webkul_warehousemanagement.xml',
            'Webkul_Wkbraintree'         => 'tm/firecheckout/webkul_wkbraintree.xml',
            'Webshopapps_Calendarbase'   => 'tm/firecheckout/webshopapps_calendarbase.xml',
            'Webshopapps_Desttype'       => 'tm/firecheckout/webshopapps_desttype.xml',
            'Webshopapps_Premiumrate'    => 'tm/firecheckout/webshopapps_premiumrate.xml',
            'Webshopapps_Wsafreightcommon' => 'tm/firecheckout/webshopapps_wsafreightcommon.xml',
            'Webshopapps_Wsavalidation'  => 'tm/firecheckout/webshopapps_wsavalidation.xml',
            'Webtex_Giftcards'           => 'tm/firecheckout/webtex_gitcards.xml',
            'Wyomind_Pickupatstore'      => 'tm/firecheckout/wyomind_pickupatstore.xml',
            'Ydral_Correos'              => 'tm/firecheckout/ydral_correos.xml',
            'Zipmoney_ZipmoneyPayment'   => 'tm/firecheckout/zipmoney_zipmoneypayment.xml',
        );
        $disabled = array(
            'Mage_Captcha' => array(
                // 'Infolution_ILStrongCaptcha',
                array(Mage::helper('firecheckout'), 'canUseInfolutionILStrongCaptchaModule')
            )
        );
        $requires = array(
            'Amasty_GiftCard' => array(
                'Amasty_Scheckout'
            )
        );
        foreach ($mapping as $module => $layoutXml) {
            if (!$helper->isModuleOutputEnabled($module)) {
                continue;
            }
            if (isset($disabled[$module])) {
                foreach ($disabled[$module] as $_module) {
                    if (is_array($_module)) {
                        if (call_user_func($_module)) {
                            continue 2;
                        }
                    } elseif ($helper->isModuleOutputEnabled($_module)) {
                        continue 2;
                    }
                }
            }
            if (isset($requires[$module])) {
                foreach ($requires[$module] as $_module) {
                    if (!$helper->isModuleOutputEnabled($_module)) {
                        continue 2;
                    }
                }
            }
            $tag = strtolower("firecheckout_{$module}");
            $xml = "<{$tag}><file>{$layoutXml}</file></{$tag}>";
            $node = new Varien_Simplexml_Element($xml);
            $updates->appendChild($node);
        }
    }
}

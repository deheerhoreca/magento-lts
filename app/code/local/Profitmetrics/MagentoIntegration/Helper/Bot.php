<?php

class Profitmetrics_MagentoIntegration_Helper_Bot extends Mage_Core_Helper_Abstract
{
    /**
     * List is taken from https://my.profitmetrics.io/downloads/_for_modules/_botdetection/data.json
     * @var string[]
     */
    private $restrictedUserAgents = array(
        
        // DHH CORE HACK -- Adding bot checks from aoeblackholesession
        "elb-healthchecker",
        "EliasHaeussler-CacheWarmup",
        "meta-externalagent/1.",
        "GPTBot/1.",
        "Elastic-Metricbeat",
        "xCore",
        
        "AdsBot-Google",
        "AdsBot-Google-Mobile",
        "Googlebot",
        "Storebot-Google",
        "APIs-Google",
        "Mediapartners-Google",
        "FeedFetcher-Google",
        "Google-Read-Aloud",
        "DuplexWeb-Google",
        "Google Favicon",
        "googleweblight",
        "Pinterestbot",
        "AhrefsSiteAudit",
        "AhrefsBot",
        "Seekport Crawler",
        "DuckDuckBot",
        "PingdomPageSpeed",
        "pingbot",
        "YandexAccessibilityBot",
        "adidxbot",
        "bingbot",
        "SMTBot",
        "HubSpot Crawler",
        "e.ventures Investment Crawler",
        "Cincraw",
        "Facebot",
        "Twitterbot",
        "Jooblebot",
        "YisouSpider",
        "YandexMetrika",
        "Applebot",
        "PagePeeker",
        "Linespider",
        "proximic",
        "Algolia Crawler Renderscript",
        "PetalBot",
        "SEOFeedback_WebCrawler",
        "oBot",
        "Impact Radius Compliance Bot",
        "Cocolyzebot",
        "nlnbot",
        "SemrushBot-SA",
        "Bytespider",
        "RyteBot",
        "BrandVeritySpider",
        "ethical-bugbot",
        "Screaming Frog SEO Spider",
        "BublupBot",
        "bitlybot",
        "Better Uptime Bot",
        "KargoBot-Artemis-Mobile",
        "YandexVideoParser",
        "DotBot",
        "BLEXBot",
        "CheckMarkNetwork",
        "MegaIndex.ru",
        "Baiduspider",
        "SurdotlyBot",
        "Taboolabot",
        "SiteScoreBot",
        "StatusCake_Pagespeed_Indev",
        "www.facebook.com/externalhit_uatext.php",
        "+http://yandex.com/bots",
        "YandexBot",
        "LinkedInBot",
        "+https://intelx.io",
        "+https://www.seokicks.de/robot.html",
        "SEOkicks;",
        "ZoominfoBot",
        "BingPreview",
        "Morningscore",
        "heritrix",
        "http://tech.quickpay.net/api/callback",
        "CookieInformationScanner",
        "MJ12bot",
        "KlarnaBot-PriceWatcher",
        "HeadlessChrome",
        "Barkrowler",
        "Chrome-Lighthouse",
        "https://vendorcentral.amazon.com/support/amazonproductbot",
    );

    /**
     * @return bool
     */
    public function isBot()
    {
        $userAgent = (string) Mage::helper('core/http')->getHttpUserAgent();

        foreach ($this->restrictedUserAgents as $restrictedUserAgent) {
            if (stripos($userAgent, $restrictedUserAgent) !== false) {
                return true;
            }
        }

        return false;
    }
}

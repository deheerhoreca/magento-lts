<?php

class Profitmetrics_MagentoIntegration_Helper_Bot extends Mage_Core_Helper_Abstract
{
    /**
     * List is taken from m2 ProfitMetrics implementation and merged with the following lists:
     * https://github.com/mitchellkrogza/apache-ultimate-bad-bot-blocker/blob/master/_generator_lists/good-user-agents.list
     * https://www.webnots.com/user-agents-list-for-google-bing-baidu-and-yandex-search-engines/
     * @var string[]
     */
    private $restrictedUserAgents = array(
        'AdIdxBot',
        'AdsBot-Google',
        'AdsBot-Google-Mobile',
        'AdsBot-Google-Mobile-Apps',
        'AhrefsBot',
        'AhrefsSiteAudit',
        'Algolia Crawler Renderscript',
        'Applebot',
        'BLEXBot',
        'Baidu Favorites',
        'Baidu Union',
        'Better Uptime Bot',
        'BingPreview',
        'Bingbot',
        'BrandVeritySpider',
        'BublupBot',
        'Business Search (Advertisements)',
        'Bytespider',
        'CheckMarkNetwork',
        'Cincraw',
        'Cocolyzebot',
        'Desktop',
        'DoCoMo',
        'DotBot',
        'DuckDuckBot',
        'DuckDuckBot',
        'Facebot',
        'Feedfetcher-Google',
        'Google-HTTP-Java-Client',
        'Googlebot',
        'Googlebot-Image',
        'Googlebot-Mobile',
        'Googlebot-News',
        'Googlebot-Video',
        'Googlebot/Test',
        'Gravityscan',
        'HubSpot Crawler',
        'Image Search',
        'Impact Radius Compliance Bot',
        'Jakarta\ Commons',
        'Jooblebot',
        'KargoBot-Artemis-Mobile',
        'Kraken/0.1',
        'Linespider',
        'LinkedInBot',
        'MSNBot',
        'MSNBot-Media',
        'Mediapartners-Google',
        'Mobile',
        'News Search',
        'PagePeeker',
        'PetalBot',
        'PingdomPageSpeed',
        'Pinterestbot',
        'RyteBot',
        'SAMSUNG',
        'SEOFeedback_WebCrawler',
        'SMTBot',
        'Screaming Frog SEO Spider',
        'Seekport Crawler',
        'SemrushBot-SA',
        'Slackbot',
        'Slackbot-LinkExpanding',
        'Slurp',
        'Storebot-Google',
        'Twitterbot',
        'Video Search',
        'Wordpress',
        'YaDirectFetcher',
        'Yandex',
        'YandexAccessibilityBot',
        'YandexAntivirus',
        'YandexBlogs',
        'YandexBot',
        'YandexCalendar',
        'YandexDirect',
        'YandexDirectDyn',
        'YandexFavicons',
        'YandexImageResizer',
        'YandexImages',
        'YandexMedia',
        'YandexMetrika',
        'YandexMobileBot',
        'YandexNews',
        'YandexPagechecker',
        'YandexScreenshotBot',
        'YandexSitelinks',
        'YandexVertis',
        'YandexVideoParser',
        'YandexWebmaster',
        'YisouSpider',
        'adidxbot',
        'aolbuild',
        'bing',
        'bingbot',
        'bingpreview',
        'bitlybot',
        'developers.facebook.com',
        'duckduckgo',
        'e.ventures Investment Crawler',
        'ethical-bugbot',
        'facebookexternalhit',
        'facebookplatform',
        'gsa-crawler',
        'ia_archiver',
        'msnbot',
        'msnbot-media',
        'nlnbot',
        'oBot',
        'pingbot',
        'proximic',
        'slurp',
        'teoma',
        'yahoo',
    );

    /**
     * @return bool
     */
    public function isBot()
    {
        $userAgent = Mage::helper('core/http')->getHttpUserAgent();

        foreach ($this->restrictedUserAgents as $restrictedUserAgent) {
            if (stripos($userAgent, $restrictedUserAgent) !== false) {
                return true;
            }
        }

        return false;
    }
}

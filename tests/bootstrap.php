<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!class_exists('Mage', false)) {
    class Mage
    {
        public static $storeConfig = [];
        public static $helpers = [];
        public static $models = [];
        public static $baseUrl = 'https://example.test/';
        public static $app;

        public static function helper($name)
        {
            return self::$helpers[$name] ?? null;
        }

        public static function getStoreConfig($path)
        {
            return self::$storeConfig[$path] ?? null;
        }

        public static function getBaseUrl($type = null, $secure = false)
        {
            return self::$baseUrl;
        }

        public static function app()
        {
            return self::$app;
        }

        public static function getModel($name)
        {
            return self::$models[$name] ?? null;
        }
    }
}

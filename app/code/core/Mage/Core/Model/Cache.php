<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Core
 */

/**
 * System cache model
 * support id and tags prefix support,
 *
 * @package    Mage_Core
 */
class Mage_Core_Model_Cache
{
    /**
     * Cache settings
     */
    public const DEFAULT_LIFETIME  = 7200;
    public const OPTIONS_CACHE_ID  = 'core_cache_options';
    public const INVALIDATED_TYPES = 'core_cache_invalidate';
    public const XML_PATH_TYPES    = 'global/cache/types';

    /**
     * Id prefix
     *
     * @var string
     */
    protected $_idPrefix    = '';

    /**
     * Cache frontend API
     *
     * @var Varien_Cache_Core|Zend_Cache_Core
     */
    protected $_frontend;

    /**
     * Shared memory backend models list (required TwoLevels backend model)
     *
     * @var array
     */
    protected $_shmBackends = [
        'apc', 'memcached', 'xcache',
        'zendserver_shmem', 'zendserver_disk',
    ];

    /**
     * Default cache backend type
     *
     * @var string
     */
    protected $_defaultBackend = 'File';

    /**
     * Default options for default backend
     *
     * @var array
     */
    protected $_defaultBackendOptions = [
        'hashed_directory_level'    => 1,
        'hashed_directory_perm'    => 0777,
        'file_name_prefix'          => 'mage',
    ];

    /**
     * List of available request processors
     *
     * @var array
     */
    protected $_requestProcessors = [];

    /**
     * Disallow cache saving
     *
     * @var bool
     */
    protected $_disallowSave = false;

    /**
     * List of allowed cache options
     *
     * @var array|null
     */
    protected $_allowedCacheOptions;

    /**
     * DB connection
     *
     * @var string|null
     */
    protected $_dbConnection = 'core_write';

    /**
     * Class constructor. Initialize cache instance based on options
     */
    public function __construct(array $options = [])
    {
        $this->_defaultBackendOptions['cache_dir'] = $options['cache_dir'] ?? Mage::getBaseDir('cache');
        /**
         * Initialize id prefix
         */
        $this->_idPrefix = $options['id_prefix'] ?? '';
        if (!$this->_idPrefix && isset($options['prefix'])) {
            $this->_idPrefix = $options['prefix'];
        }
        if (empty($this->_idPrefix)) {
            $this->_idPrefix = substr(md5(Mage::getConfig()->getOptions()->getEtcDir()), 0, 3) . '_';
        }

        $backend    = $this->_getBackendOptions($options);
        $frontend   = $this->_getFrontendOptions($options);

        $this->_frontend = Zend_Cache::factory(
            'Varien_Cache_Core',
            $backend['type'],
            $frontend,
            $backend['options'],
            true,
            true,
            true,
        );

        if (isset($options['request_processors'])) {
            $this->_requestProcessors = $options['request_processors'];
        }

        if (isset($options['disallow_save'])) {
            $this->_disallowSave = (bool) $options['disallow_save'];
        }
    }

    /**
     * Get cache backend options. Result array contain backend type ('type' key) and backend options ('options')
     *
     * @return  array
     */
    protected function _getBackendOptions(array $cacheOptions)
    {
        $enable2levels = false;
        $type   = $cacheOptions['backend'] ?? $this->_defaultBackend;
        if (isset($cacheOptions['backend_options']) && is_array($cacheOptions['backend_options'])) {
            $options = $cacheOptions['backend_options'];
        } else {
            $options = [];
        }

        $backendType = false;
        switch (strtolower($type)) {
            case 'sqlite':
                if (extension_loaded('sqlite') && isset($options['cache_db_complete_path'])) {
                    $backendType = 'Sqlite';
                }
                break;
            case 'memcached':
                if (extension_loaded('memcached')) {
                    if (isset($cacheOptions['memcached'])) {
                        $options = $cacheOptions['memcached'];
                    }
                    $enable2levels = true;
                    $backendType = 'Libmemcached';
                } elseif (extension_loaded('memcache')) {
                    if (isset($cacheOptions['memcached'])) {
                        $options = $cacheOptions['memcached'];
                    }
                    $enable2levels = true;
                    $backendType = 'Memcached';
                }
                break;
            case 'apc':
                if (extension_loaded('apcu') && ini_get('apc.enabled')) {
                    $enable2levels = true;
                    $backendType = 'Apc';
                }
                break;
            case 'xcache':
                if (extension_loaded('xcache')) {
                    $enable2levels = true;
                    $backendType = 'Xcache';
                }
                break;
            case 'varien_cache_backend_database':
            case 'database':
                $backendType = 'Varien_Cache_Backend_Database';
                $options = $this->getDbAdapterOptions($options);
                break;
            default:
                if ($type != $this->_defaultBackend) {
                    try {
                        if (class_exists($type, true)) {
                            $implements = class_implements($type, true);
                            if (in_array('Zend_Cache_Backend_Interface', $implements)) {
                                $backendType = $type;
                                if (isset($options['enable_two_levels'])) {
                                    $enable2levels = true;
                                }
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
        }

        if (!$backendType) {
            $backendType = $this->_defaultBackend;
            foreach ($this->_defaultBackendOptions as $option => $value) {
                if (!array_key_exists($option, $options)) {
                    $options[$option] = $value;
                }
            }
        }

        $backendOptions = ['type' => $backendType, 'options' => $options];
        if ($enable2levels) {
            $backendOptions = $this->_getTwoLevelsBackendOptions($backendOptions, $cacheOptions);
        }
        return $backendOptions;
    }

    /**
     * Get options for database backend type
     *
     * @return array
     */
    protected function getDbAdapterOptions(array $options = [])
    {
        if (isset($options['connection'])) {
            $this->_dbConnection = $options['connection'];
        }

        $options['adapter_callback'] = [$this, 'getDbAdapter'];
        $options['data_table'] = Mage::getSingleton('core/resource')->getTableName('core/cache');
        $options['tags_table'] = Mage::getSingleton('core/resource')->getTableName('core/cache_tag');
        return $options;
    }

    /**
     * Initialize two levels backend model options
     *
     * @param array $fastOptions fast level backend type and options
     * @param array $cacheOptions all cache options
     * @return array
     */
    protected function _getTwoLevelsBackendOptions($fastOptions, $cacheOptions)
    {
        $options = [];
        $options['fast_backend']                = $fastOptions['type'];
        $options['fast_backend_options']        = $fastOptions['options'];
        $options['fast_backend_custom_naming']  = true;
        $options['fast_backend_autoload']       = true;
        $options['slow_backend_custom_naming']  = true;
        $options['slow_backend_autoload']       = true;

        if (isset($cacheOptions['auto_refresh_fast_cache'])) {
            $options['auto_refresh_fast_cache'] = (bool) $cacheOptions['auto_refresh_fast_cache'];
        } else {
            $options['auto_refresh_fast_cache'] = false;
        }
        if (isset($cacheOptions['slow_backend'])) {
            $options['slow_backend'] = $cacheOptions['slow_backend'];
        } else {
            $options['slow_backend'] = $this->_defaultBackend;
        }
        if (isset($cacheOptions['slow_backend_options'])) {
            $options['slow_backend_options'] = $cacheOptions['slow_backend_options'];
        } else {
            $options['slow_backend_options'] = $this->_defaultBackendOptions;
        }
        if ($options['slow_backend'] == 'database') {
            $options['slow_backend'] = 'Varien_Cache_Backend_Database';
            $options['slow_backend_options'] = $this->getDbAdapterOptions($options['slow_backend_options']);
            if (isset($cacheOptions['slow_backend_store_data'])) {
                $options['slow_backend_options']['store_data'] = (bool) $cacheOptions['slow_backend_store_data'];
            } else {
                $options['slow_backend_options']['store_data'] = false;
            }
        }

        return [
            'type'      => 'TwoLevels',
            'options'   => $options,
        ];
    }

    /**
     * Get options of cache frontend (options of Zend_Cache_Core)
     *
     * @return  array
     */
    protected function _getFrontendOptions(array $cacheOptions)
    {
        $options = $cacheOptions['frontend_options'] ?? [];
        if (!array_key_exists('caching', $options)) {
            $options['caching'] = true;
        }
        if (!array_key_exists('lifetime', $options)) {
            $options['lifetime'] = $cacheOptions['lifetime'] ?? self::DEFAULT_LIFETIME;
        }
        if (!array_key_exists('automatic_cleaning_factor', $options)) {
            $options['automatic_cleaning_factor'] = 0;
        }
        $options['cache_id_prefix'] = $this->_idPrefix;
        return $options;
    }

    /**
     * Prepare unified valid identifier with prefix
     *
     * @param   string $id
     * @return  string
     */
    protected function _id($id)
    {
        if ($id) {
            $id = strtoupper($id);
        }
        return $id;
    }

    /**
     * Prepare cache tags.
     *
     * @param   array $tags
     * @return  array
     */
    protected function _tags($tags = [])
    {
        foreach ($tags as $key => $value) {
            $tags[$key] = $this->_id($value);
        }
        return $tags;
    }

    /**
     * Get cache frontend API object
     *
     * @return Varien_Cache_Core|Zend_Cache_Core
     */
    public function getFrontend()
    {
        return $this->_frontend;
    }

    /**
     * Load data from cache by id
     *
     * @param   string $id
     * @return  string|false
     */
    public function load($id)
    {
        return $this->getFrontend()->load($this->_id($id));
    }

    /**
     * Save data
     *
     * @param string $data
     * @param string $id
     * @param array $tags
     * @param null|false|int $lifeTime
     * @return bool
     */
    public function save($data, $id, $tags = [], $lifeTime = null)
    {
        if ($this->_disallowSave) {
            return true;
        }

        return $this->getFrontend()->save((string) $data, $this->_id($id), $this->_tags($tags), $lifeTime);
    }

    /**
     * Test data
     *
     * @param string $id
     * @return false|int
     */
    public function test($id)
    {
        return $this->getFrontend()->test($this->_id($id));
    }

    /**
     * Remove cached data by identifier
     *
     * @param   string $id
     * @return  bool
     */
    public function remove($id)
    {
        return $this->getFrontend()->remove($this->_id($id));
    }

    /**
     * Clean cached data by specific tag
     *
     * @param   array|string $tags
     * @return  bool
     */
    public function clean($tags = [])
    {
        $mode = Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
        if (!empty($tags)) {
            if (!is_array($tags)) {
                $tags = [$tags];
            }
            return $this->getFrontend()->clean($mode, $this->_tags($tags));
        }

        return $this->flush();
    }

    /**
     * Flush cached data
     *
     * @return  bool
     */
    public function flush()
    {
        return $this->getFrontend()->clean();
    }

    /**
     * Get adapter for database cache backend model
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDbAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection($this->_dbConnection);
    }

    /**
     * Get cache resource model
     *
     * @return Mage_Core_Model_Resource_Cache
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('core/cache');
    }

    /**
     * Initialize cache types options
     *
     * @return $this
     */
    protected function _initOptions()
    {
        $options = $this->load(self::OPTIONS_CACHE_ID);
        if ($options === false) {
            $options = $this->_getResource()->getAllOptions();
            if (is_array($options)) {
                $this->_allowedCacheOptions = $options;
                $this->save(serialize($this->_allowedCacheOptions), self::OPTIONS_CACHE_ID);
            } else {
                $this->_allowedCacheOptions = [];
            }
        } else {
            $this->_allowedCacheOptions = unserialize($options, ['allowed_classes' => false]);
        }

        if (Mage::getConfig()->getOptions()->getData('global_ban_use_cache')) {
            foreach ($this->_allowedCacheOptions as $key => $val) {
                $this->_allowedCacheOptions[$key] = false;
            }
        }

        return $this;
    }

    /**
     * Save cache usage options
     *
     * @param array $options
     * @return $this
     */
    public function saveOptions($options)
    {
        $this->remove(self::OPTIONS_CACHE_ID);
        $this->_getResource()->saveAllOptions($options);
        return $this;
    }

    /**
     * Check if cache can be used for specific data type
     *
     * @param string $typeCode
     * @return bool|array
     */
    public function canUse($typeCode)
    {
        if (is_null($this->_allowedCacheOptions)) {
            $this->_initOptions();
        }

        if (empty($typeCode)) {
            return $this->_allowedCacheOptions;
        }

        if (isset($this->_allowedCacheOptions[$typeCode])) {
            return (bool) $this->_allowedCacheOptions[$typeCode];
        } else {
            return false;
        }
    }

    /**
     * Disable cache usage for specific data type
     *
     * @param string $typeCode
     * @return $this
     */
    public function banUse($typeCode)
    {
        $this->_allowedCacheOptions[$typeCode] = false;
        return $this;
    }

    /**
     * Enable cache usage for specific data type
     *
     * @param string $typeCode
     * @return $this
     */
    public function unbanUse($typeCode)
    {
        $this->_allowedCacheOptions[$typeCode] = true;
        return $this;
    }

    /**
     * Get cache tags by cache type from configuration
     *
     * @param string $type
     * @return array
     */
    public function getTagsByType($type)
    {
        $path = self::XML_PATH_TYPES . '/' . $type . '/tags';
        $tagsConfig = Mage::getConfig()->getNode($path);
        if ($tagsConfig) {
            $tags = (string) $tagsConfig;
            $tags = explode(',', $tags);
        } else {
            $tags = false;
        }
        return $tags;
    }

    /**
     * Get information about all declared cache types
     *
     * @return array
     */
    public function getTypes()
    {
        $types = [];
        $config = Mage::getConfig()->getNode(self::XML_PATH_TYPES);
        if ($config) {
            foreach ($config->children() as $type => $node) {
                $types[$type] = new Varien_Object([
                    'id'            => $type,
                    'cache_type'    => Mage::helper('core')->__((string) $node->label),
                    'description'   => Mage::helper('core')->__((string) $node->description),
                    'tags'          => strtoupper((string) $node->tags),
                    'status'        => (int) $this->canUse($type),
                ]);
            }
        }
        return $types;
    }

    /**
     * Get invalidate types codes
     *
     * @return array
     */
    protected function _getInvalidatedTypes()
    {
        $types = $this->load(self::INVALIDATED_TYPES);
        if ($types) {
            $types = unserialize($types, ['allowed_classes' => false]);
        } else {
            $types = [];
        }
        return $types;
    }

    /**
     * Save invalidated cache types
     *
     * @param array $types
     * @return $this
     */
    protected function _saveInvalidatedTypes($types)
    {
        $this->save(serialize($types), self::INVALIDATED_TYPES);
        return $this;
    }

    /**
     * Get array of all invalidated cache types
     *
     * @return array
     */
    public function getInvalidatedTypes()
    {
        $invalidatedTypes = [];
        $types = $this->_getInvalidatedTypes();
        if ($types) {
            $allTypes = $this->getTypes();
            foreach (array_keys($types) as $type) {
                if (isset($allTypes[$type]) && $this->canUse($type)) {
                    $invalidatedTypes[$type] = $allTypes[$type];
                }
            }
        }
        return $invalidatedTypes;
    }

    /**
     * Mark specific cache type(s) as invalidated
     *
     * @param string|array $typeCode
     * @return $this
     */
    public function invalidateType($typeCode)
    {
        $types = $this->_getInvalidatedTypes();
        if (!is_array($typeCode)) {
            $typeCode = [$typeCode];
        }
        foreach ($typeCode as $code) {
            $types[$code] = 1;
        }
        $this->_saveInvalidatedTypes($types);
        return $this;
    }

    /**
     * Clean cached data for specific cache type
     *
     * @param string $typeCode
     * @return $this
     */
    public function cleanType($typeCode)
    {
        $tags = $this->getTagsByType($typeCode);
        $this->clean($tags);

        $types = $this->_getInvalidatedTypes();
        unset($types[$typeCode]);
        $this->_saveInvalidatedTypes($types);
        return $this;
    }

    /**
     * Try to get response body from cache storage with predefined processors
     *
     * @return bool
     */
    public function processRequest()
    {
        if (empty($this->_requestProcessors)) {
            return false;
        }

        $content = false;
        foreach ($this->_requestProcessors as $processor) {
            $processor = $this->_getProcessor($processor);
            if ($processor) {
                $content = $processor->extractContent($content);
            }
        }

        if ($content) {
            Mage::app()->getResponse()->appendBody($content);
            return true;
        }
        return false;
    }

    /**
     * Get request processor object
     * @param string $processor
     * @return object
     */
    protected function _getProcessor($processor)
    {
        return new $processor();
    }
}

<?php


namespace Core\Helper;

use Illuminate\Support\Arr;

class BundleConfig {

    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Get config ("Dot" notation)
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Check config ("Dot" notation)
     *
     * @param $key
     * @return mixed
     */
    public function has(string $key) {
        return Arr::has($this->config, $key);
    }

	/**
	 * Build bundle config of given bundle name
	 *
	 * @param string $bundleName
	 * @param string $configFile
	 * @param bool $throwException
	 * @return BundleConfig
	 */
    public static function of(string $bundleName, string $configFile = 'config', bool $throwException = false): BundleConfig {
    	$config = (new Bundle())->readBundleFile($bundleName, $configFile, $throwException);
    	return new BundleConfig($config);
	}

}

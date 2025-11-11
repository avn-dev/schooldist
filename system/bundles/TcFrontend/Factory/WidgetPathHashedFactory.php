<?php

namespace TcFrontend\Factory;

use TcFrontend\Dto\WidgetPath;

/**
 * Query-String für Cache Busting an WidgetPath anhängen
 *
 * Das ist der gleiche Ansatz wie die Versionierung von Laravel Mix, allerdings dynamisch gelöst.
 * Webpack selbst hat keine Lösung dafür, den generierten Hash nicht in den Dateinamen zu schreiben.
 */
class WidgetPathHashedFactory {

	const CACHE_KEY = 'tc_frontend_hashed_widget_path_factory'; // Key wird auch hardcoded in Node.js verwendet

	/**
	 * @var string
	 */
	private $bundle;

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var string
	 */
	private $suffix;

	/**
	 * @var string
	 */
	private $app;

	public function __construct(string $prefix, string $suffix, string $app, string $bundle) {
		$this->bundle = $bundle;
		$this->prefix = $prefix;
		$this->suffix = $suffix;
		$this->app = $app;
	}

	public function create(): WidgetPath {

		$path = $this->buildPath();

		$cache = \WDCache::get(self::CACHE_KEY);

		// Achtung: Nicht atomar, spielt aber keine Rolle
		if (isset($cache[$path])) {
			$hash = $cache[$path];
		} else {
			$hash = $this->buildHash($path);
		}

		if (!empty($hash)) {
			$cache[$path] = $hash;
			$hash = '?id='.$hash;
			\WDCache::set(self::CACHE_KEY, (7*24*60*60), $cache);
		}

		return new WidgetPath($this->prefix, $this->suffix.$hash, $this->app);

	}

	private function buildPath(): string {

		[$bundle, $pathSegment] = explode(':', $this->bundle);

		$path = (new \Core\Helper\Bundle())->getBundleResourcesDirectory($bundle).'/'.$pathSegment.$this->suffix;

		return $path;

	}

	private function buildHash(string $path): string {

		if (is_file($path)) {
			$modified = filemtime($path);
			return substr(md5($modified), 0, 8);
		}

		return '';

	}

}
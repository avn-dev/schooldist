<?php

namespace TsStudentApp\Messenger;

use Core\Helper\BundleConfig;
use TsStudentApp\AppInterface;

/**
 * Token-Klasse für den Messenger
 *
 * Anhand der Tokens wird die Kommunikation zu einem bestimmten Objekt definiert.
 * z.b.
 *      Token: 766fdde5708e98f300df98a677bc0443.2468
 *      WDBasic: \Ext_Thebing_Teacher::getInstance(2468)
 *
 * Threads sind definiert in config.php
 *
 * @package TsStudentApp\Messenger
 */
class Token {

	const SEPERATOR = '.';

	private $bundleConfig;

	/**
	 * @param BundleConfig $bundleConfig
	 */
	public function __construct(BundleConfig $bundleConfig) {
		$this->bundleConfig = $bundleConfig;
	}

	/**
	 * Erzeugt den Token für einen Thread
	 *
	 * @param \WDBasic $entity
	 * @return string
	 * @throws \Exception
	 */
	public function encode(\WDBasic $entity): string {
		return implode(self::SEPERATOR, [
			md5(get_class($entity)),
			$entity->getId()
		]);
	}

	/**
	 * Generiert das WDBasic-Objekt anhand des Thread-Tokens
	 *
	 * @param string $token
	 * @return \WDBasic|null
	 */
	public function decode(string $token): ?\WDBasic {

		$parts = explode(self::SEPERATOR, $token);

		if(count($parts) === 2) {

			$entityClass = collect($this->bundleConfig->get('messenger.threads', []))
				->mapWithKeys(function($thread){
					return [md5($thread['entity']) => $thread['entity']];
				})
				->get($parts[0]);

			if(!is_null($entityClass)) {
				return \Factory::getInstance($entityClass, $parts[1]);
			}
		}

		return null;
	}

}

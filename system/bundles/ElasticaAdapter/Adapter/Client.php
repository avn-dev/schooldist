<?php

namespace ElasticaAdapter\Adapter;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class Client extends \Elastica\Client
{
	public function __construct() {
		parent::__construct(self::getConnectionConfig());
	}

	/**
	 * @return array
	 */
	public static function getConnectionConfig(): array
	{

		$host = \System::d('elasticsearch_host') ?? 'localhost';
		$port = \System::d('elasticsearch_port') ?? '9200';
		$transport = \System::d('elasticsearch_transport') ?? 'http';

		// TODO 'headers' => ['Content-Type' => 'application/json'] wg. Deprecation-Log bei jedem einzelnen Request
		return [
			'hosts' => [
				$transport.'://'.$host.':'.$port
			],
			'retry' => 2,
			'connectionParams' => [
				'client' => [
					'timeout' => 3,
					'connect_timeout' => 3
				]
			],
			//'log' => '/tmp/elastica.log'
		];
	}

	/**
	 * Version von Elasticsearch ermitteln
	 *
	 * @return string
	 */
	public function getElasticsearchVersion(): string
	{
		$version = \WDCache::get('elasticsearch_version');

		if($version === null) {
			try {
				$version = $this->getVersion();
			} catch (ServerResponseException|ClientResponseException $e) {
				$version = 'unknown';
			}
			\WDCache::set('elasticsearch_version', 86400, $version);
		}

		return $version;

	}

}

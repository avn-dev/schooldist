<?php

namespace ElasticaAdapter\Adapter;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class Index extends \Elastica\Index
{
	/**
	 * @param string $index
	 */
	public function __construct($index) {
		$client = \ElasticaAdapter\Facade\Elastica::getClient();
		parent::__construct($client, $index);
	}

	public function deleteDocuments(array $docs, string $sType = ''): \Elastica\Bulk\ResponseSet
	{
		$type = $type ?? $this->_name;

		return $this->_client->deleteIds($docs, $this->_name, $type);
	}

	public function create(array $args = array(), array $options = [], int $fields = 1000): \Elastica\Response
	{
		// $args werden aktuell nirgendswo benutzt, daher Default direkt setzen
		$defaultArgs = $this->getParameters($fields);
		$arguments = $defaultArgs;

		return parent::create($arguments, $options);
	}

	public function exists(): bool
	{
		$exist = false;
		try {
			$exist = parent::exists();
		} catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {

		}

		// Zusätzlich prüfen, ob Mapping auch vorhanden ist
		/*
		 * Das ist zwar schön, aber das erzeugt jedes Mal einen zusätzlichen Request!
		 * Wenn der Index erstellt wird, _sollte_ das Mapping immer da sein,
		 * da beides immer zusammen passiert. Ansonsten übernimmt ElasticSearch
		 * das Mapping automatisch.
		 */
		/*if($bExist) {
			$aMappings = $this->getMapping();
			$aMappings = $aMappings[$this->sOriginalIndex];
			if(empty($aMappings)) {
				$bExist = false;
			}
		}*/

		return $exist;
	}

	public function refresh(): \Elastica\Response
	{
		ini_set('memory_limit', '6G');
		set_time_limit(14400);

		return parent::refresh();
	}

	public function delete(bool $ignoreMissingIndex = false): \Elastica\Response
	{
		try {
			return parent::delete();
		} catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {

		} finally {
			return new \Elastica\Response(true);
		}
	}

	protected function getParameters(int $fields = 1000): array
	{
		return [
			'settings' => [
				'number_of_replicas' => 0,
				'index.mapping.total_fields.limit' => $fields,
				'analysis' => [
					'analyzer' => [
						'lowercase_whitespace' => array(
							'type' => 'custom',
							'tokenizer' => 'whitespace',
							'filter' => array('lowercase')
						)
					]
				]
			]
		];
	}

	public function createMapping($mappingData, $source = false): void
	{
		$mapping = new \Elastica\Mapping($mappingData);
		$mapping->setSource(['enabled' => $source]);
		$this->setMapping($mapping);
	}

	public function createDocument(string $id = '', $data = []): \ElasticaAdapter\Adapter\Document
	{
		return new \ElasticaAdapter\Adapter\Document($id, $data);
	}

}

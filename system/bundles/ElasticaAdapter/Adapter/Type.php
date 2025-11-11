<?php

namespace ElasticaAdapter\Adapter;

use \Elastica\Type\Mapping;

class Type extends \Elastica\Type {

	/**
	 * Erzeugt Mapping-Informationen über ein Array
	 *
	 * Format:
	 * $aData = array(
		'office_id' => array('store' => true, 'type' => 'string', 'index' => true, 'analyzer' => 'basic'),
		'school_id' => array('store' => true, 'type' => 'string', 'index' => true, 'analyzer' => 'basic'),
		'location_id' > array('store' => true, 'type' => 'string', 'index' => true, 'analyzer' => 'basic'),
		'area_id' => array('store' => true, 'type' => 'string', 'index' => true, 'analyzer' => 'basic'),
		'productline_id' => array('store' => true, 'type' => 'string', 'index' => true, 'analyzer' => 'basic'),
	   );
	 *
	 * @param array $aMappingData
	 * @param bool $bSource
	 */
	public function createMapping($aMappingData, $bSource = false) {
		$oMapping = new Mapping($this, $aMappingData);
		$oMapping->setSource(['enabled' => $bSource]);
		$this->setMapping($oMapping);
	}

	/**
	 * Erzeugt ein neues Dokument
	 *
	 * @param mixed $mId
	 * @param array $aData
	 * @return \ElasticaAdapter\Adapter\Document
	 */
	public function createDocument($mId = '', $aData = array()) {

		$oDocument = new Document($mId, $aData);

		return $oDocument;
	}

}

<?php

namespace ElasticaAdapter\Iterator;

class Collection implements \Iterator, \Countable {

	/**
	 * @var int
	 */
	private $iKey = 0;

	/**
	 * @var array
	 */
	private $aCurrent = false;

	/**
	 * @var int
	 */
	private $iCount = null;

	/**
	 * @var array
	 */
	private $aOffsetResult = null;

	/**
	 * @var \ElasticaAdapter\Facade\Elastica
	 */
	private $oWDSearch = null;

	public function  __construct($oWDSearch) {
		$this->iKey = 0;
		$this->oWDSearch = $oWDSearch;
	}

	public function rewind() {
		$this->iKey = 0;
		$this->aOffsetResult = null;
		$this->fetch();
	}

	public function reset() {
		$this->rewind();
	}

	public function current() {
		return $this->aCurrent;
	}

	public function key() {
		return $this->iKey;
	}

	public function next() {

		if(($this->iKey % 100) == 0) {
			$this->aOffsetResult = null;
		}

		$this->iKey++;
		$this->fetch();
	}

	public function fetch() {

		if($this->aOffsetResult) {
			$aFinalResult = $this->aOffsetResult[$this->iKey];
		} else {
			$this->oWDSearch->setLimit(100, $this->iKey);
			$aResult = $this->oWDSearch->search();
			$this->iCount = (int)$aResult['total'];
			$aOffsetResult = array();
			$i = $this->iKey;

			if(!empty($aResult['hits'])) {
				foreach($aResult['hits'] as $aHit) {
					$aOffsetResult[$i]['_id'] = $aHit['_id'];
					if(!empty($aHit['fields'])) {
						foreach($aHit['fields'] as $sField => $mValue) {
							$aOffsetResult[$i][$sField] = $mValue;
						}
					} else {
						if(!empty($aHit['_source'])) {
							foreach($aHit['_source'] as $sField => $mValue) {
								$aOffsetResult[$i][$sField] = $mValue;
							}
						}
					}
					$i++;
				}
			}
			$this->aOffsetResult = $aOffsetResult;

			$aFinalResult = $aOffsetResult[$this->iKey];
		}

		$this->aCurrent = $aFinalResult;
	}

	public function valid() {
		return $this->aCurrent;
	}

	public function count() {
		if($this->iCount === null) {
			$this->fetch();
		}

		return $this->iCount;
	}

}
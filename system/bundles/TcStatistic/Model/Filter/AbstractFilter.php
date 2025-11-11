<?php

namespace TcStatistic\Model\Filter;

use TcStatistic\Controller\StatisticController;

abstract class AbstractFilter {

	/**
	 * @see getDefaultValueOrOverwritten()
	 * @var mixed
	 */
	protected $mDefaultValue;

	/**
	 * @return string
	 */
	abstract public function getKey();

	/**
	 * @TODO Das wird bei Feldern, die nicht sichtbar sind, nicht benötigt
	 *
	 * @return string
	 */
	abstract public function getTitle();

	/**
	 * @TODO Das wird bei Feldern, die nicht sichtbar sind, nicht benötigt
	 *
	 * @return string
	 */
	abstract public function getInputType();

	/**
	 * @return string[]
	 */
	public function getSelectOptions() {
		return [];
	}

	/**
	 * Array: Multiselect
	 *
	 * @return string|array
	 */
	public function getDefaultValue() {
		return '0';
	}

	/**
	 * display: none
	 *
	 * @return bool
	 */
	public function isShown() {
		return true;
	}

	/**
	 * Default-Value überschreiben können, damit Statistik-Generator da etwas eigenes setzen kann
	 *
	 * @param string|array $mValue
	 */
	public function setDefaultValue($mValue) {
		$this->mDefaultValue = $mValue;
	}

	/**
	 * @see setDefaultValue()
	 * @return array|string
	 */
	public function getDefaultValueOrOverwritten() {

		if($this->mDefaultValue !== null) {
			return $this->mDefaultValue;
		}

		return $this->getDefaultValue();

	}

	/**
	 * Multiselect: Alle Optionen als Default-Value setzen
	 */
	public function setAllDefaultValues() {

		if($this->getInputType() !== 'multiselect') {
			throw new \BadMethodCallException(__METHOD__.' is only allowed for multiselects');
		}

		$this->setDefaultValue(array_keys($this->getSelectOptions()));

	}

	/**
	 * @param \MVC_Request $oRequest
	 * @return mixed
	 */
	public function getRequestValue(\MVC_Request $oRequest) {

		if(!$oRequest->exists('filter_'.$this->getKey())) {

			// Leere Multiselects schicken nichts
			if($this->getInputType() === 'multiselect') {
				return [];
			} elseif($this->getInputType() === 'checkbox') {
				return '0';
			}

			throw new \RuntimeException('Filter value missing for '.get_class($this));

		}

		return $oRequest->input('filter_'.$this->getKey());

	}

	/**
	 * @param string $sTranslation
	 * @return string
	 */
	protected static function t($sTranslation) {
		return \Factory::executeStatic('\\'.StatisticController::class, 't', [$sTranslation]);
	}

}

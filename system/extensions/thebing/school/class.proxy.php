<?php

use Core\Proxy\WDBasicAbstract;

class Ext_Thebing_School_Proxy extends WDBasicAbstract {

	/**
	 * @var string
	 */
	protected $sEntityClass = 'Ext_Thebing_School';

	/**
	 * Gibt das Schul-Entity zurück.
	 *
	 * Die Basis-Proxy-Klasse stellt sicher das es der richtige Typ ist (Definition in $this->sEntityClass),
	 * diese Methode sorgt nur für korrektes Type-Hinting.
	 *
	 * @return Ext_Thebing_School
	 */
	protected function getEntity() {

		return $this->oEntity;

	}

	/**
	 * Gibt die ID der Schule zurück.
	 *
	 * @return integer
	 */
	public function getId() {

		$oEntity = $this->getEntity();
		return $oEntity->id;

	}

	/**
	 * Gibt das Datumsformat der Schule zurück.
	 *
	 * @param bool $sDatepickerFormat
	 * @return string
	 */
	public function getDateFormat($sDatepickerFormat = null) {

		$oEntity = $this->getEntity();
		$sFormat = \Ext_Thebing_Format::getDateFormat($oEntity->id, 'frontend_date_format');

		if(!empty($sDatepickerFormat)) {
			Util::convertDateFormat($sFormat, $sDatepickerFormat, true);
		}

		return $sFormat;

	}

	public function getName() {
		return $this->oEntity->getName();
	}
	
	public function getCurrency() {
		$oCurrencyProxy = new Ts\Proxy\Currency(Ext_Thebing_Currency::getInstance($this->oEntity->currency));
		return $oCurrencyProxy;
	}
	
}

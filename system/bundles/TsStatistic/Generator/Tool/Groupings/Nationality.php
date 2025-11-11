<?php

namespace TsStatistic\Generator\Tool\Groupings;

class Nationality extends AbstractGrouping {

	private $bLabelsConverted = false;

	public function getTitle() {
		return self::t('Nationalität');
	}

	public function getSelectFieldForId() {
		return "`tc_c`.`nationality`";
	}

	public function getSelectFieldForLabel() {
		return $this->getSelectFieldForId();
	}

	public function isHeadGrouping() {
		return true;
	}

	public function getColumnColor() {
		return 'booking';
	}

	public function getLabels() {

		if(!$this->bLabelsConverted) {
			$aNationalities = \Ext_Thebing_Nationality::getNationalities(true, '', false);
			$this->aLabels = array_map(function($sIso) use ($aNationalities) {
				return $aNationalities[$sIso];
			}, $this->aLabels);
			asort($this->aLabels);
			$this->bLabelsConverted = true;
		}

		return $this->aLabels;

	}

}

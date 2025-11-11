<?php

namespace TsStatistic\Generator\Tool\Groupings;

class InquiryChannel extends AbstractGrouping implements AllLabelsInterface {

	public function getTitle() {
		return self::t('Channel');
	}

	public function getSelectFieldForId() {
		return " IF(`ts_i`.`group_id` > 0, 3, IF(`ts_i`.`agency_id` > 0, 2, 1)) ";
	}

	public function getSelectFieldForLabel() {
		return $this->getSelectFieldForId();
	}

	public function getColumnColor() {
		return 'booking';
	}

	public function getLabels() {

		$this->aLabels = $this->getAllLabels();

		return $this->aLabels;

	}

	public function getAllLabels(): array {
		return  [
			1 => $this->t('Direkt'),
			2 => $this->t('Agentur'),
			3 => $this->t('Gruppe')
		];
	}

}

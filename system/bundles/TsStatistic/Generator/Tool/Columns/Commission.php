<?php

namespace TsStatistic\Generator\Tool\Columns;

/**
 * Spalte für Provision (eigentlich ebenso eine Revenue-Spalte)
 */
class Commission extends Revenue {

	public function getJoinPartsAdditions() {

		$aAdditions = parent::getJoinPartsAdditions();

		if($this->sConfiguration === 'commission_net') {
			$aAdditions['JOIN_DOCUMENTS'] = "
				AND `kid`.`type` IN('netto', 'netto_diff')
			";
		} elseif($this->sConfiguration === 'commission_creditnote') {
			$aAdditions['JOIN_DOCUMENTS'] = "
				AND `kid`.`type` = 'creditnote'
			";
		}

		return $aAdditions;

	}

	public function getConfigurationOptions() {
		return [
			'commission' => self::t('Provision (exkl. Steuern)'),
			//'commission_net' => self::t('Provision (nur Nettorechnungen, exkl. Steuern)'),
			'commission_creditnote' => self::t('Provision (nur Gutschriften, exkl. Steuern)')
		];
	}

}

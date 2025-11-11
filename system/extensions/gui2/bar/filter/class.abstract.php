<?php

/**
 * @property string $id
 * @property string $filter_type
 * @property mixed $initial_value
 * @property string $label
 * @property bool $negateable
 * @property bool $sidebar
 * @property mixed $value
 * @property int $sort_order
 */
abstract class Ext_Gui2_Bar_Filter_Abstract extends Ext_Gui2_Config_Basic {

	const INFO_ICON_KEY = 'FILTER';

	abstract public function setSqlDataByRef($mValue, bool $bNegate, &$aQueryParts, &$aSql, $iKey = 0, &$oGui = null);

	abstract public function setWDSearchQuery($mValue, bool $bNegate, $oWDSearch, $aSearchColumns, $oGui);

	abstract public function hasValue($mValue): bool;

	abstract public function convertSaveValue($value): string;

	abstract public function prepareSaveValue($value): mixed;

	public function buildKeyForNegate(): string {

		return $this->id.'_negate';

	}

}

<?php

namespace TsTuition\Gui2\Data;

class Classroom extends \Ext_Thebing_Gui2_Data
{
	protected function _getJoinedItemsErrorLabel($sLabel)
	{
		return match ($sLabel) {
			'rooms' => $this->t('Klassen'),
			default => parent::_getJoinedItemsErrorLabel($sLabel)
		};
	}
}
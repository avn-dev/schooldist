<?php

namespace TsTuition\Gui2\Format;

class ClassesRooms extends \Ext_Gui2_View_Format_Abstract {

	private array $rooms;

	public function  __construct(
		array $rooms
	) {
		$this->rooms = $rooms;
	}

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$infos = explode(',', $mValue);

		$structure = [];
		foreach ($infos as $info) {
			list($roomId, $blockId) = explode('_', $info);
			$structure[$blockId][$roomId] = $this->rooms[$roomId];
		}

		$roomsRows = [];

		foreach($structure as $blockRooms) {
			$roomsRow = [];
			foreach ($blockRooms as $roomName) {
				$roomsRow[] = $roomName;
			}
			$roomsRows[] = implode('; ', $roomsRow);
		}

		return implode('<br>', $roomsRows);
	}
}
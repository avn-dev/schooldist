<?php

use Core\Helper\Color;

class Ext_Thebing_School_Tuition_Block_Content {

	/**
	 * @param Ext_Thebing_Tuition_Classroom[]|array $aRooms
	 * @param string|bool $sType
	 * @return string
	 */
	public static function getRoomHeadContent(Ext_Thebing_School $oSchool, $aRooms, $sType=false, $bAddMax=true, $bAddLevelRow=false) {
	
		$iColWidth = (int)\System::d('ts_scheduling_block_width', 120);
		$iRooms			= count($aRooms);
		$iTableWidth	= 60 + ($iRooms*$iColWidth);

		$sHtmlRoom = "";
		$sHtmlRoom .= '<table id="tablePlanification'.$sType.'" class="sortable scroll" style="table-layout: fixed; width: '.$iTableWidth.'px;">';
		$sHtmlRoom .= '<colgroup><col style="width: 42px;">';
		for($i=0; $i<$iRooms; $i++) {
			$sHtmlRoom .= '<col style="width: '.$iColWidth.'px;">';
		}
		$sHtmlRoom .= '</colgroup>';

		$sHtmlRoom .= '<thead>';
		
		if($bAddLevelRow) {
			
			$sHtmlRoom .= '<tr>';
			$sHtmlRoom .= '<th>&nbsp;</th>';
			
			$aFloors = [];
			foreach((array)$aRooms as $oRoom) {
				if(!isset($aFloors[$oRoom->floor_id])) {
					$oFloor = Ext_Thebing_Tuition_Floors::getInstance($oRoom->floor_id);
					$aFloors[$oRoom->floor_id] = [
						'name' => $oFloor->title,
						'rooms' => []
					];
				}
				$aFloors[$oRoom->floor_id]['rooms'][] = $oRoom;
			}

			foreach($aFloors as $aFloor) {
				$sHtmlRoom .= '<th colspan="'.count($aFloor['rooms']).'">'.$aFloor['name'].'</th>';
			}
			
			$sHtmlRoom .= '</tr>';
			
		}
		
		// rooms
		$sHtmlRoom .= '<tr>';
		$sHtmlRoom .= '<th>&nbsp;</th>';
		foreach((array)$aRooms as $oRoom) {
			if($sType === 'other') {
				$aItem = reset($oRoom);
				if($aItem['room_type'] === 'virtual') {
					$sRoomName = L10N::t('Virtueller Klassenraum','Thebing » Tuition');
				} else {
					$sRoomName = L10N::t('Raumlos','Thebing » Tuition');
				}
				$sRoomMaxStudents = L10N::t('n/a','Thebing » Tuition');
			} else {
				$sRoomName = $oRoom->name;
				$sRoomMaxStudents = $oRoom->max_students;
			}
			$sHtmlRoom .= '<th title="'.Util::convertHtmlEntities($sRoomName).'">';
			
			if($oSchool->id != $oRoom->idSchool) {
				$sHtmlRoom .= '<i class="pull-right fas fa-share-square fa-xs" title="'. Util::convertHtmlEntities(sprintf(L10N::t('Geteilter Raum der Schule "%s"','Thebing » Tuition'), $oSchool->short)).'"></i>';
			}
			
			$sHtmlRoom .= $sRoomName;
			if($bAddMax) {
				$sHtmlRoom .= '<span class="info">'.L10N::t('max','Thebing » Tuition').'. '.$sRoomMaxStudents.'</span>';
			}
			$sHtmlRoom .= '</th>';
		}

		$sHtmlRoom .= '</tr></thead>';

		$sHtmlRoom .= '<tbody>';

		return $sHtmlRoom;
	}

	public static function getClassTimesContent($iClassesTime, $aTimeRows, $iColspan, $sSuffix) {

		$sHtmlClassTimes = '';
		if($iClassesTime > 0) {
			$sHtmlClassTimes .= '<tr><td class="tdPlanificationSpacer" colspan="'.$iColspan.'"><img src="/admin/media/spacer.gif" /></td>';
			$sHtmlClassTimes .= '</tr>';
		}

		$sHtmlClassTimes .= '<tr>';
		$sHtmlClassTimes .= '<th style="padding: 0;"><div id="divPlanificationLabels'.$sSuffix.'" class="divPlanificationLabels">';
		foreach((array)$aTimeRows as $iTime=>$sTime) {
			$sHtmlClassTimes .= '<div class="thRowLabel">'.$sTime.'</div>';
		}
		$sHtmlClassTimes .= '</div></th>';

		return $sHtmlClassTimes;

	}

	public static function getBlockHtml($aBlock, $sStyleClass, $iStart, $iEnd, $iInterval=15, $iDay=0) {

		$sBlockHtml = '';

		if(
			$aBlock['from'] > $iEnd ||
			$aBlock['until'] < $iStart
		) {
			return $sBlockHtml;
		}

		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$iLength	= $aBlock['until'] - $aBlock['from'];

		$iHours = $iLength / 60 / 60;
		$iHeight = $iLength / 60 * 22 / $iInterval - 1;
		$iHeight -= 2;
		$iTop = -1;

		$sStyleClassColor = '';
		if(!empty($aBlock['class_color'])){
			//$colorPalette = Color::generateColorPalette($aBlock['class_color']);
			//$contrastText = $colorPalette->getContrastShade($colorPalette->getShade(), Color::CONTRAST_RATIO_TEXT);

			$sStyleClassColor = 'background-color: '.$aBlock['class_color'].'!important;';
			//$sStyleClassColor .= 'border-color: '.Color::changeLuminance($aBlock['class_color'], -0.2).'!important;';
			//$sStyleClassColor .= 'color: #'.$contrastText->getColor()->getHex().'!important;';
			$sStyleClassColor .= ' border-color: '.\Core\Helper\Color::changeLuminance($aBlock['class_color'], -0.1).'!important;';
		}

		if ((bool)$aBlock['cancelled']) {
			$sStyleClass .= ' cancelled';
		}

		$iFrom = $aBlock['from'];

		// TODO: Anpassen an dynamische Zeiten
		$iTop += ($iFrom - $iStart) * (22 / ($iInterval * 60));

		if(
			$aBlock['school_id'] == $oSchool->id &&
			$aBlock['readonly'] == 0
		) {
			$sBlockHtml .= '<div class="room_content'.$sStyleClass.'" id="room_content_'.(int)$aBlock['id'].'_'.(int)$aBlock['room_id'].'" style="top: '.$iTop.'px; height: '.$iHeight.'px;'.$sStyleClassColor.'">';
		} else if($aBlock['readonly'] == 1) {
			$sBlockHtml .= '<div class="room_content_readonly" style="top: '.$iTop.'px; height: '.$iHeight.'px;" data-block="'.$aBlock['id'].'" data-room="'.$aBlock['room_id'].'">';
		} else {
			$sBlockHtml .= '<div class="room_content_inactive" style="top: '.$iTop.'px; height: '.$iHeight.'px;'.$sStyleClassColor.'">';
		}		

		$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($aBlock['id'], $aBlock);

		$sBlockHtml .= $oBlock->getBlockContent((int)$aBlock['room_id'], false,$iDay);

		$sBlockHtml .= '</div>';

		return $sBlockHtml;
	}
}

?>

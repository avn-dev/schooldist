<?php

namespace TsScreen\Service\Elements;

use Core\Helper\Color;

class Schedule extends AbstractElement {
	
	public function prepare() {
		
		$school = \Ext_Thebing_School::getInstance($this->schedule->school_id);
		
		$this->templateEngine = new \Core\Service\Templating();
		$this->templateEngine->setLanguage($this->translator);

		$path = explode('\\', get_class($this));
		$type = strtolower(array_pop($path));
		
		$schedule = '<table class="schedule">';
		
		$aClassesTimes = $school->getClassTimes();

		$rooms = $school->getClassRooms(false, null, true, null, $this->schedule->buildings, true);
		
		$weekTimestamps = \Ext_Thebing_Util::getWeekTimestamps(time());

		$blockData = $school->getWeekBlocks($weekTimestamps['start'], $weekTimestamps['end'], $rooms, date('N'));
		
		$interval = 30;
		
		$blocks = [];
		foreach((array)$blockData as $item) {

			$item['from'] = \Ext_Thebing_Util::convertTimeToSeconds($item['from']);
			$item['until'] = \Ext_Thebing_Util::convertTimeToSeconds($item['until']);

			$iKey = $item['from'];
			// change from to full quarter
			if(fmod($iKey / 60 / 15, 1) > 0) {
				$iKey = $iKey - round(fmod($iKey / 60 / 15, 1) * 15 * 60);
			}

			/**
			 * @todo: Klassen auf Räumen effizient verteilen
			 */
			if($item['room_id'] > 0) {

				if(
					!array_key_exists($item['room_id'], $rooms)
				){
					$room = \Ext_Thebing_Tuition_Classroom::getInstance($item['room_id']);
					$rooms[$room->id] = $room;
				}
				$blocks[$item['room_id']][$iKey] = $item;
			}

		}
		
		foreach($rooms as $roomId=>$room) {
			if(empty($blocks[$roomId])) {
				unset($rooms[$roomId]);
			}
		}
		
		$iClassesTime = 0;
		foreach((array)$aClassesTimes as $classesTime) {
			
			$iStart = $classesTime->getFromSeconds();
			$iEnd = $classesTime->getUntilSeconds();

			$schedule .= \Ext_Thebing_School_Tuition_Block_Content::getRoomHeadContent($school, $rooms, false, false, true);
			
			$aTimeRows = \Ext_Thebing_Util::getTimeRows('assoc', $interval, $iStart, $iEnd, false);
			$schedule .= \Ext_Thebing_School_Tuition_Block_Content::getClassTimesContent(0, $aTimeRows, $iColspan, '');//$this->templateEngine->fetch('@TsScreen/'.$type.'.tpl');

			$iTotalHeight = ((count($aTimeRows)) * 22 - 1);
			
			foreach((array)$rooms as $room) {

				$schedule .= '<td style="margin: 0px; padding: 0px;">';
				$schedule .= '<div class="room_container'.$sStyleClass.'" style="height: '.$iTotalHeight.'px;" id="room_container_'.$classesTime->id.'_'.$room->id.'">';

				foreach((array)$blocks[$room->id] as $iTime => $block) {
					
					$iLength	= $block['until'] - $block['from'];

					$iHours = $iLength / 60 / 60;
					$iHeight = $iLength / 60 * 22 / $interval - 1;
					$iHeight -= 2;
					$iTop = -1;

					$sStyleClassColor = '';
					if(!empty($block['class_color'])){
						//$colorPalette = Color::generateColorPalette($block['class_color']);
						//$contrastText = $colorPalette->getContrastShade($colorPalette->getShade(), Color::CONTRAST_RATIO_TEXT);

						$sStyleClassColor = 'background-color: '.$block['class_color'].'!important;';
						//$sStyleClassColor .= 'border-color: '.Color::changeLuminance($block['class_color'], -0.2).'!important;';
						//$sStyleClassColor .= 'color: #'.$contrastText->getColor()->getHex().'!important;';
					}

					$iFrom = $block['from'];

					// TODO: Anpassen an dynamische Zeiten
					$iTop += ($iFrom - $iStart) * (22 / ($interval * 60));

					$schedule .= '<div class="room_content'.$sStyleClass.'" id="room_content_'.(int)$block['id'].'_'.(int)$block['room_id'].'" style="top: '.$iTop.'px; height: '.$iHeight.'px;'.$sStyleClassColor.'">';
					$schedule .= '<div class="room_content_padding">'.$block['class_name'].'</div>';
					$schedule .= '</div>';

				}
			}
			
		}		

		$schedule .= '</table>';
		
		$this->assign('schedule', $schedule);
		
	}

}

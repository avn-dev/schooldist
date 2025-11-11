<?php

use Illuminate\Support\Str;

class Ext_Thebing_Gui2_Format_School_Tuition_Teachers extends Ext_Gui2_View_Format_Abstract {

	public $oLanguageObject;

	public $bLabel = true;

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sTeacherInfos = $aResultData['teachers'];
		$sSubTeacherInfos = $aResultData['sub_teachers'];

		$aTeachers = [];
		$this->getTeacherArray($aTeachers, $sTeacherInfos, false);
		$this->getTeacherArray($aTeachers, $sSubTeacherInfos, true);

		if(empty($aTeachers)) {

			$sTranslation = 'Kein Lehrer zugewiesen';

			if($this->oLanguageObject instanceof \Tc\Service\LanguageAbstract) {
				if($this->oLanguageObject instanceof \Tc\Service\Language\Frontend) {
					$sTranslation = 'No teacher assigned';
				}

				return $this->oLanguageObject->translate($sTranslation);
			} else if($this->oGui) {
				return $this->oGui->t($sTranslation);
			}

			throw new \RuntimeException('Translation object missing');

		} else {

			$aBlockStates = explode(',', $aResultData['block_state']);

			ksort($aTeachers);

			$aLines = [];
			foreach($aTeachers as $blockId=>$blockTeachers) {

				$line = [];
				foreach($blockTeachers as $iTeacherId => $dtoTeacher) {

					if ($iTeacherId == 0) {
						$dtoTeacher->name = $this->oGui->t('Kein Lehrer');
					}

					if ($this->bLabel) {
						$oSpan = new Ext_Gui2_Html_Span();
						$this->getTeacherSpanStyle($oSpan, $dtoTeacher, $aBlockStates);
						$oSpan->setElement((string)$dtoTeacher->name);
						$line[] = $oSpan->generateHtml();
					} else {
						$line[] = $dtoTeacher->name;
					}
				}
				$aLines[] = implode('; ', $line);
			}

			return implode('<br>', $aLines);

		}
		
	}

	protected function getTeacherArray(&$aTeachers, $sTeacherInfos, $isSubstituteTeacher) {

		if(!empty($sTeacherInfos)) {
			$aTeacherInfos = explode(',',$sTeacherInfos);
			foreach($aTeacherInfos as $aTeacherInfo) {
				$aInfos = explode('_', $aTeacherInfo);
				$iTeacherId = (int)$aInfos[0];
				$aName = array(
					'firstname' => $aInfos[1],
					'lastname'	=> $aInfos[2]
				);
				$blockId = (int)$aInfos[3];
				$oFormat = new Ext_Thebing_Gui2_Format_TeacherName();
				$sName = $oFormat->format(null, $oColumn, $aName);

				$dtoTeacher = new stdClass();
				$dtoTeacher->id = $iTeacherId;
				$dtoTeacher->name = $sName;
				$dtoTeacher->isSubstituteTeacher = $isSubstituteTeacher;
				$aTeachers[$blockId][$iTeacherId] = $dtoTeacher;

			}
		}

	}

	protected function getTeacherSpanStyle(Ext_Gui2_Html_Span $span, $dtoTeacher, array $aBlockStates) {

		$title = $style = [];
		$color = '';

		foreach($aBlockStates as $sBlockState) {
			$sKey = $dtoTeacher->id.'_';
			if(Str::startsWith($sBlockState, $sKey)) {
				$iState = substr($sBlockState, strlen($sKey));

				if($iState & Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE) {
					$color = Ext_Thebing_Util::getColor('red_font');
					$title[] = $this->oGui->t('Lehrer ist abwesend');
				}

				if($iState & Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_AVAILABILITY) {
					$color = Ext_Thebing_Util::getColor('soft_purple');
					$title[] = $this->oGui->t('Lehrer ist nicht verfügbar');
				}

				if($iState & Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION) {
					$color = Ext_Thebing_Util::getColor('substitute_part');
					$title[] = $this->oGui->t('Lehrer ist nicht qualifiziert');
				}

			}

		}

		if(!empty($color)) {
			$style[] = 'color: '.$color;
		}

		if($dtoTeacher->isSubstituteTeacher) {
			$style[] = 'font-style: italic';
		}

		$span->style = implode('; ', $style);
		$span->title = implode(', ', $title);
	}

}

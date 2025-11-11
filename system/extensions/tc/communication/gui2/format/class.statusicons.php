<?php

class Ext_TC_Communication_Gui2_Format_StatusIcons extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aIcons = [];
		if($aResultData['direction']) {
			$sIcon = Ext_TC_Util::getIcon($aResultData['direction']);
		
			if($aResultData['direction'] === 'in') {
				$sTitle = $this->oGui->t('Eingehende E-Mail');
			} else {
				$sTitle = $this->oGui->t('Ausgehende E-Mail');
			}

			$aIcons[] = '<i class="fa '.$sIcon.'" title="'.$sTitle.'" />';
		}

		if(isset($aResultData['sent']) && (int)$aResultData['sent'] === 0) {
			$aIcons[] = '<i class="fa fa-times" title="'.$this->oGui->t('Noch nicht versendet').'" />';
		}

		if (!empty($aResultData['relations'])) {
			$relations = array_map(fn ($row) => explode('{|}', $row), explode('{||}', $aResultData['relations']));
			$eventRelation = \Illuminate\Support\Arr::first($relations, fn ($relation) => $relation[0] === \Tc\Entity\EventManagement::class);
			if ($eventRelation) {
				$event = Factory::getInstance($eventRelation[0], $eventRelation[1]);
				$aIcons[] = '<i class="fa fa-link" title="'.$this->oGui->t('Ereignis').': '.$event->name.'" />';
			}
		}

		if($aResultData['has_attachments'] > 0) {
			$sIcon = Ext_TC_Util::getIcon('attachment');
			$aIcons[] = '<i class="fa '.$sIcon.'" title="'.$this->oGui->t('Mit Anhang').'" />';
		}

		if($aResultData['flagged'] > 0) {
			$aIcons[] = '<i class="fa fa-flag" title="'.$this->oGui->t('Gekennzeichnet').'" />';
		}

		return implode(' ', $aIcons);
	}
	
	public function align(&$oColumn = null) {
		return 'left';
	}
	
}

<?php

class Ext_Thebing_Transfer_Package_Gui2_Controller extends MVC_Abstract_Controller {

	public function postDetails() {	
		global $session_data;
		
		\System::setInterface('backend');
		Ext_TC_System::setInterfaceLanguage($_COOKIE['systemlanguage']);
		
		Ext_TC_System::setLocale();
		
		$sDescription = 'Thebing » Transfer';

		$aPackageIds = $this->_oRequest->input('values');
		$iPackageId = (int)reset($aPackageIds);
		$oPackage = Ext_Thebing_Transfer_Package::getInstance($iPackageId);

		$aFields = array();

		$aLocations = \Ext_TS_Transfer_Location::query()->get()
			->mapWithKeys(fn ($oLocation) => [$oLocation->id => $oLocation->getName()]);

		$aFields[] = array(
			'title' => L10N::t('Wochentage', $sDescription),
			'join_table' => 'join_days',
			'separator' => ', '
		);
		$aFields[] = array(
			'title' => L10N::t('Transferanbieter', $sDescription),
			'join_table' => 'join_providers_transfer',
			'separator' => ', '
		);
		$aFields[] = array(
			'title' => L10N::t('Unterkunftsanbieter', $sDescription),
			'join_table' => 'join_providers_accommodation',
			'separator' => ', '
		);
		$aFields[] = array(
			'title' => L10N::t('Abfahrt - Orte', $sDescription),
			'join_table' => 'join_from_locations',
			'separator' => ', ',
			'format' => fn ($iLocationId) => $aLocations[$iLocationId] ?? 'Unknown'
		);
		$aFields[] = array(
			'title' => L10N::t('Abfahrt - Unterkunftskategorien', $sDescription),
			'join_table' => 'join_from_accommodation_categories',
			'separator' => ', '
		);
		$aFields[] = array(
			'title' => L10N::t('Ziel - Orte', $sDescription),
			'join_table' => 'join_to_locations',
			'separator' => ', ',
			'format' => fn ($iLocationId) => $aLocations[$iLocationId] ?? 'Unknown'
		);
		$aFields[] = array(
			'title' => L10N::t('Ziel - Unterkunftskategorien', $sDescription),
			'join_table' => 'join_to_accommodation_categories',
			'separator' => ', '
		);
		$aFields[] = array(
			'title' => L10N::t('Preissaison', $sDescription),
			'join_table' => 'join_saisons_prices',
			'separator' => ', '
		);
		$aFields[] = array(
			'title' => L10N::t('Kostensaison', $sDescription),
			'join_table' => 'join_saisons_costs',
			'separator' => ', '
		);

		$sHtml = '<table class="table" style="width: 100%; border-spacing:0;">';
		$sHtml .= '<colgroup><col style="width:200px;"><col style="width:auto;"></colgroup>';
		
		foreach($aFields as $aField) {
			
			$aBack = array();
			$sJoinTableKey = $aField['join_table'];
			
			$aValues = $oPackage->$sJoinTableKey;

			if (!$aField['format']) {
				$aJoinTableData = $oPackage->getJoinTable($sJoinTableKey);
				$mFormat = $aJoinTableData['format'];
			} else {
				$mFormat = $aField['format'];
			}
			
			if(!empty($mFormat)) {
				foreach((array)$aValues as $iJoinId) {
					if ($mFormat instanceof \Closure) {
						$aBack[] = $mFormat($iJoinId);
					} else {
						/** @var Ext_Gui2_View_Format_Abstract $oFormat */
						$mDummy = null;
						$aBack[] = (new $mFormat())->format($iJoinId, $mDummy, $mDummy);;
					}
				}
			} else {

				$aBack[] = implode(', ', (array)$aValues);
			}

			// Leere Werte entfernen
			$aBack = array_filter($aBack, function($mValue) {
				return !empty($mValue);
			});


			if(!empty($aBack)) {
				$sHtml .= '<tr><th>'.$aField['title'].'</th>';
				$sHtml .= '<td>'.implode(', ', $aBack).'</td></tr>';
			}

		}

		$sHtml .= '</table>';
		
		$this->set('tooltip', $sHtml);
		$this->set('tooltip_id', $this->_oRequest->get('tooltip_id'));
		$this->set('action', 'loadTooltipContent');

	}
	
}
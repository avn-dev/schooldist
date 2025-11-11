<?php

class Ext_Thebing_Access_Html {
	
	public function getSchoolAccessDialog($iParentId, $sHash) {

		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$oAccess = new Ext_Thebing_Access();
		$aList = $oAccess->getAccessSortRightList();

		$oUser = new Ext_Thebing_Access_User($iParentId);

		// Liste noch einmal nach Position sortieren
		$aList = Ext_Thebing_Access::sortRightAccessList($aList);

		foreach($aSchools as $iSchool => $sSchool) {
			$iCount = $oAccess->countSchoolRights($iSchool);
			if($iCount <= 0){
				unset($aSchools[$iSchool]);
			}
		}

		$sLabelYes = L10N::t('Ja', 'Thebing » Admin » Benutzergruppen');
		$sLabelNo = L10N::t('Nein', 'Thebing » Admin » Benutzergruppen');

		$aRightList = $oUser->getAccessList();

		$sHtml = '';
		$sHtml .= '<div style="margin: 0px;" class="infoBox infoBoxTabs">';
			$sHtml .= '<div class="infoBoxTabsHead"> ';
			
				$sHtml .= '<ul class="nav nav-tabs infoBoxTabsNav" id="infoBoxTabCharts" role="tablist" style="display: flex; flex-wrap: wrap">';
				$i = 1;
				foreach($aList as $sTab => $aRights){
					if($i === 1) {
						$sHtml .= '<li id="tab_'.$i.'_btn" role="presentation" class="active access_tab"><a href="#tab_'.$i.'" aria-controls="tab_'.$i.'" role="tab" data-toggle="tab">'.$sTab.'</a></li>';
					}else {
						$sHtml .= '<li id="tab_'.$i.'_btn" role="presentation" class="access_tab"><a href="#tab_'.$i.'" aria-controls="tab_'.$i.'" role="tab" data-toggle="tab">'.$sTab.'</a></li>';
					}
					$i++;
				}
				$sHtml .= '</ul></div>';
				$sHtml .= '<div class="tab-content">';
				$i = 1;
				foreach($aList as $sTab => $aRights) {
					if($i === 1) {
						$sHtml .= '<div role="tabpanel" id="tab_'.$i.'" class="tab-pane fade in active" style="overflow-x: auto;">';
					} else {
						$sHtml .= '<div role="tabpanel" id="tab_'.$i.'" class="tab-pane fade" style="overflow-x: auto;">';
					}
						$sHtml .= '<br/><table class="table" cellspacing="0" cellpadding="4" style="table-layout: fixed; width: 100%;">';
						$sHtml .= '<tr>';
								$sHtml .= '<td style="width: 300px;">&nbsp;</td>';
								foreach($aSchools as $iSchool => $sSchool){
									if($bGroup){
										$sHtml .= '<th>';
									} else {
										$sHtml .= '<th style="width: 150px;">';
									}
										$sHtml .= $sSchool;
									$sHtml .= '</th>';
								}
						$sHtml .= '</tr>';
						
						foreach($aRights as $aRight) {
							
							$aRight['description'] = $aRight['section'];
							
							if($aRight['name'] !== 'Dummy') {
								$aRight['description'] .= ' &raquo; '.$aRight['name'];
							}
							
							if($aRight['description'] == ""){
								$aRight['description'] = $aRight['access'];
							}
							
							list($sSection, $sRight) = explode('-', $aRight['access'], 2);

							if(empty($sRight)) {
								$sRight = 'dummy';
							}
							
							$sHtml .= '<tr>';
									$sHtml .= '<th>';
									$sHtml .= $aRight['description'];
								$sHtml .= '</th>';
								foreach($aSchools as $iSchool => $sSchool){

									$sColor = '';
									if($oUser->checkAccess($aRight['access'], $iSchool)) {
										$sColor = Ext_Thebing_Util::getColor('green');
									} else {
										$sColor = Ext_Thebing_Util::getColor('red');
									}

									$sYes = '';
									$sNo = '';
									if(array_key_exists($sSection, (array)$aRightList[$iSchool])) {
										if(
											(int)$aRightList[$iSchool][$sSection][$sRight] == 1
										) {
											$sYes = 'selected="selected"';
										} else {
											$sNo = 'selected="selected"';
										}
									}

									$sHtml .= '<td style="text-align: center; background-color: '.$sColor.';">';

										$sHtml .= '<select class="txt form-control accessSelect" title="'.$aRight['description'].'" alt="'.$aRight['description'].'" name="access['.$iSchool.']['.$aRight['access'].']">';
										$sHtml .= '<option value="-1"></option>';
										$sHtml .= '<option value="1" '.$sYes.'>'.$sLabelYes.'</option>';
										$sHtml .= '<option value="0" '.$sNo.'>'.$sLabelNo.'</option>';
										$sHtml .= '</select>';
	
									$sHtml .= '</td>';
								}
							$sHtml .= '</tr>';
						}
						$sHtml .= '</table>';
					$sHtml .= '</div>';
					$sHtml .= '<script>//window.setTimeout(function() {var iHeight = $(\'tab_'.$i.'\').up(4).getHeight(); $(\'tab_'.$i.'\').style.height = (iHeight-40)+\'px\';}, 200);</script>';
					$i++;
				}
				//$sHtml .= '</ul>';
		$sHtml .= '</div></div>';

		return $sHtml;
	
	}
	
	public function getUserGroupDialog($iUserId){
		global $user_data;

		$sHtml = '';
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$oAccess = new Ext_Thebing_Access();

		foreach($aSchools as $iSchool => $sSchool){
			
			$oSchool = Ext_Thebing_School::getInstance((int)$iSchool);
			
			$iCount = $oAccess->countSchoolRights($iSchool);
			if(
				$iCount <= 0 && // Kein Recht für diese Schule
				$oSchool->creator_id != $user_data['id'] // Schule wurde nicht von User selber erstellt
			){
				unset($aSchools[$iSchool]);
			}
		}
	
		$aGroups = $oAccess->getAccessGroups();
		$oUser = new Ext_Thebing_Access_User($iUserId);
		$sHtml .= '';

		
		foreach($aSchools as $iSchool => $sSchool) {
			$iSchoolGroupId = $oUser->getGroupIdOfSchool($iSchool);
			
			$sHtml .= '<div class="GUIDialogRow form-group" id="">';
			$sHtml .= '<label class="GUIDialogRowLabelDiv control-label col-sm-3">'.$sSchool.'</label>';
			$sHtml .= '<div class="GUIDialogRowInputDiv col-sm-9">';
		
			$sHtml .= '<select class="txt form-control" name="group['.$iSchool.']">';
			$sHtml .= '<option value="0">';
			$sHtml .= Ext_Thebing_L10N::t('keine Benutzergruppe','','Thebing » Admin » Users');
			$sHtml .= '</option>';
			foreach($aGroups as $iGroup => $sGroup){
				$sHtml .= '<option value="'.$iGroup.'"';
				if($iGroup == $iSchoolGroupId) {
					$sHtml .= ' selected="selected" ';
				}
				$sHtml .= '>';
				$sHtml .= $sGroup;	
				$sHtml .= '</option>';
			}

			$sHtml .= '</select>';
				
			$sHtml .= '</div>';
			$sHtml .= '</div>';

		}
		$sHtml .= '';
		
		return $sHtml;
	}

}
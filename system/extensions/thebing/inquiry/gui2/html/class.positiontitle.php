<?php

class Ext_Thebing_Inquiry_Gui2_Html_PositionTitle {

	/** @var Ext_Gui2_Dialog */
	public $oDialogData			= null;
	public $sTitle				= '';
	public $sTab				= '';
	public $bReadOnly			= false;
	public $iInqiryId			= 0;
	public $iDialogRowId		= 0;
	public $iVisible			= 1;
	public $sL10NDescription	= '';
	public $sDeleteButtonId = '';
	public $bDeleteButtonReadOnly = false;

	public function getTitelRowHtml(){
		
//		<div class="box-header with-border">
//              <h3 class="box-title">Monthly Recap Report</h3>
//
//              <div class="box-tools pull-right">
//                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
//                </button>
//                <div class="btn-group">
//                  <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
//                    <i class="fa fa-wrench"></i></button>
//                  <ul class="dropdown-menu" role="menu">
//                    <li><a href="#">Action</a></li>
//                    <li><a href="#">Another action</a></li>
//                    <li><a href="#">Something else here</a></li>
//                    <li class="divider"></li>
//                    <li><a href="#">Separated link</a></li>
//                  </ul>
//                </div>
//                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
//              </div>
//            </div>
		
		// Prüft ob Werte vorhanden sind
		$bCheck = $this->checkRequiredData();
	
		if($bCheck){
			
			$oDiv = $this->oDialogData->create('div');
			$oDiv->class = 'box-header with-border GUIDialogRow block-headline';
			
			$iInquiryId			= (int)$this->iInqiryId;
			$iDialogRowId		= (int)$this->iDialogRowId;

			if(
				$this->sTab == 'course' ||
				$this->sTab == 'accommodation' ||
				$this->sTab == 'course_guide' ||
				$this->sTab == 'accommodation_guide' ||
				$this->sTab == 'activity'
			) {
				$sId	= $this->sTab . '['.$iInquiryId.']['.$iDialogRowId.'][visible]';
				$sName	= $this->sTab . '['.$iDialogRowId.'][visible]';
			} else {
				$sId	= $this->sTab . '['.$iInquiryId.'][visible][0]';
				$sName	= $this->sTab . '['.$iInquiryId.'][visible][]';
			}

//			$oDiv2 = $this->oDialogData->create('div');
//			$oDiv2->style = "line-height: 20px;";//padding:3px;";
			$oDivTitel = $this->oDialogData->create('h3');
			$oDivTitel->class = 'box-title';
			//$oDivTitel->setElement($this->sTitle);
			//$oDivTitel->style = 'float:left;';// margin-top: 5px;';

			$oSpanTitle = $this->oDialogData->create('span');
			$oSpanTitle->class = $this->sTab . '_block_title';
			$oSpanTitle->setElement($this->sTitle);
			$oDivTitel->setElement($oSpanTitle);

			## START 'active' Select
			$oDivAction = $this->oDialogData->create('div');
			$oDivAction->class = 'box-tools pull-right block-headline-actions';

			if(
				$this->sTab !== 'transfer' &&
				$this->sTab !== 'holiday' &&
				$this->sTab !== 'sponsoring_gurantee'
			) {

				$oSelect = $this->oDialogData->create('select');
				$oSelect->name	= $sName;
				$oSelect->class	= $this->sTab . '_block_visibility txt form-control input-sm activ_select';
				$oSelect->style	= 'display: inline-block; width: auto;';//margin-top: 5px;';
				$oSelect->id	= $sId;
				$oOption = $this->oDialogData->create('option');
				$oOption->value = 0;
				if($this->iVisible != 1) {
					$oOption->selected = 'selected';
				}
				$oOption->setElement(L10N::t('inaktiv', $this->sL10NDescription));
				$oSelect->setElement($oOption);
				$oOption = $this->oDialogData->create('option');
				$oOption->value = 1;
				if($this->iVisible == 1) {
					$oOption->selected = 'selected';
				}
				$oOption->setElement(L10N::t('aktiv', $this->sL10NDescription));
				$oSelect->setElement($oOption);
				if(
					$this->sTab == 'course' ||
					$this->sTab == 'course_guide'
				){
					$oDivAction->setElement(L10N::t('Kurs ist', $this->sL10NDescription).' ');
				} elseif(
					$this->sTab == 'accommodation' ||
					$this->sTab == 'accommodation_guide'
				){
					$oDivAction->setElement(L10N::t('Unterkunft ist', $this->sL10NDescription).' ');
				} elseif($this->sTab == 'insurance'){
					$oDivAction->setElement(L10N::t('Versicherung ist', $this->sL10NDescription).' ');
				} elseif($this->sTab == 'activity'){
					$oDivAction->setElement(L10N::t('Aktivität ist', $this->sL10NDescription).' ');
				}
				$oDivAction->setElement($oSelect);
			}
			## ENDE

			$oButton = new Ext_Gui2_Html_Button();
			$oButton->id = $this->sDeleteButtonId;
			$oButton->class = 'btn btn-xs btn-default '.$this->sTab.'_block_remover pull-right';
			$oButton->setElement('<i class="fa fa-minus-circle"></i> '.L10N::t('Löschen', $this->sL10NDescription));
			$oButton->bReadOnly = $this->bReadOnly || $this->bDeleteButtonReadOnly;

			$oDivAction->setElement($oButton);

			if(count($oDivAction->getElements()) > 1) {
				$oDivAction->setElement('&nbsp;');
			}

			$oDiv->setElement($oDivTitel);
			$oDiv->setElement($oDivAction);
//			$oDiv2->setElement($oDivTitel);

		}else{
			$oDiv = new Ext_Gui2_Html_Div();
		}
		
		return $oDiv;
	}
	
	private function checkRequiredData(){
		
		$bCheck = false;
		
		if(
			is_object($this->oDialogData) &&
			!empty($this->sTitle) &&
			!empty($this->sTab) &&
			!empty($this->sL10NDescription)
		){
			$bCheck = true;
		}

		return $bCheck;
	}
}

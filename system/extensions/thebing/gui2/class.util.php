<?php


class Ext_Thebing_Gui2_Util extends Ext_TC_Gui2_Util {
	
	
	static public function getCurrencyAmountRow($oDialog, $aData, $sLabel = '', $bReturnOnlyInputDiv = false, $bDisableCurrencySelect = false) {

		$oDialog->bBigLabels = true;
		
		$sDBColumn1			= $aData['db_column_1'];
		$sDBColumn2			= $aData['db_column_2'];
		$sDBColumnCurrency	= $aData['db_column_currency'];
		$sAlias				= $aData['db_alias'];
		$iSchoolId			= (int)$aData['school_id'];
		$oFormat			= $aData['format'];
		$fAmount			= (float)$aData['amount'];
		$fMaxAmount			= (float)$aData['amount_max'];
		$iCurrencyId		= (int)$aData['currency_id'];
		$iSchoolCurrencyId	= (int)$aData['school_currency_id'];
		$iDisableAll		= (int)$aData['disable_all'];
		$bShowRightDiv		= $aData['show_right_div']; // Standardmäßig deaktiviert, siehe unten
		$sClassNameFrom		= $aData['class_name_from'];
		$sClassNameTo		= $aData['class_name_to'];

		if(empty($iSchoolId)) {
			$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$oCurrentCurrency = Ext_Thebing_Currency::getInstance($iCurrencyId);

		if($iDisableAll == 1){
			$bDisableCurrencySelect = true;
		}
		
		$sAliasPart = '';
		if(!empty($sAlias)) {
			$sAliasPart = '['.(string)$sAlias.']';
		}

		if($sDBColumn1 != ""){
			$sName 	= 'save['.(string)$sDBColumn1.']'.$sAliasPart;
			$sId 	= 'saveid['.(string)$sDBColumn1.']'.$sAliasPart;
			$aOptions = array('db_column' => $sDBColumn1, 'db_alias' => $sAlias, 'format' => $oFormat, 'label'=>$sLabel);

			if(!array_key_exists($sId, $oDialog->aUniqueFields)) {
				// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
				$oDialog->aSaveData[] = $aOptions;
				$oDialog->aUniqueFields[$sId] = 1;
			}
		}

		if($sDBColumn2 != ""){
			$sName2 	= 'save['.(string)$sDBColumn2.']'.$sAliasPart;
			$sId2 	= 'saveid['.(string)$sDBColumn2.']'.$sAliasPart;
			$aOptions = array('db_column' => $sDBColumn2, 'db_alias' => $sAlias, 'format' => $oFormat, 'label'=>$sLabel);

			if(!array_key_exists($sId2, $oDialog->aUniqueFields)) {
				// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
				$oDialog->aSaveData[] = $aOptions;
				$oDialog->aUniqueFields[$sId2] = 1;
			}
		}

		if(empty($sLabel)){
			$sLabel = L10N::t('Betrag / Schulbetrag', Ext_Gui2::$sAllGuiListL10N);
		}

		$oSchool		= Ext_Thebing_School::getInstance((int)$iSchoolId);
		
		$aCurrencies	= $oSchool->getCurrencies(true);

		
		if($iSchoolCurrencyId <= 0){
			$iSchoolCurrencyId	= $oSchool->getCurrency();
		}
		$oCurrency			= Ext_Thebing_Currency::getInstance($iSchoolCurrencyId);
		$sSchoolCurrency	= $oCurrency->getSign();

		$oDiv				= new Ext_Gui2_Html_Div();
		$oDiv->class		= 'GUIDialogRow form-group form-group-sm';

		$oDivInput			= new Ext_Gui2_Html_Div();
		$oDivInput->class	= 'GUIDialogRowInputDiv col-sm-8';
		$oDivCleaner		= new Ext_Gui2_Html_Div();
		$oDivCleaner->class = 'clearfix';

		$oLabel = new Ext_Gui2_Html_Label();

		if ($oDialog->bBigLabels) {
			$oLabel->class = 'GUIDialogRowLabelDivBig control-label col-sm-4';
		} else if ($oDialog->bSmallLabels) {
			$oLabel->class = 'GUIDialogRowLabelDivSmall control-label col-sm-2';
		} else {
			$oLabel->class = 'GUIDialogRowLabelDiv control-label col-sm-3';
		}

		if((int)$aOptions['required'] == 1){
			$sLabel .= ' *';
		}

		$oLabel->setElement($sLabel);
//		$oDivLabel->setElement($oLabel);

		$oDivLeftContainer = new Ext_Gui2_Html_Div();
		$oDivLeftContainer->class = "currency_amount_row_leftdiv";

		if($sDBColumn1 != ""){

			//hidden with max amount if defined
			if(isset($aData['amount_max'])){
				$oInput	= new Ext_Gui2_Html_Input();
				$oInput->class	= 'currency_amount_row_input_max';
				$oInput->type	= "hidden";
				$oInput->value  = $fMaxAmount;
				$oDivLeftContainer->setElement($oInput);
			}

			$oInput	= new Ext_Gui2_Html_Input();
			$oInput->class = 'currency_amount_row_input_from';
			$oInput->class = 'txt form-control';

			if(!empty ($sClassNameFrom)){
				$oInput->class = $sClassNameFrom;
			}
			$fTempAmount = Ext_Thebing_Format::Number($fAmount, null, $iSchoolId);

			$oInput->name	= $sName;
			$oInput->id		= $sId;
			$oInput->value  = $fTempAmount;
			if($iDisableAll == 1){
				$oInput->disabled = 'disabled';
			}

			$oDivLeftContainer->setElement($oInput);

			$oSelect = new Ext_Gui2_Html_Select();
			$oSelect->class = 'txt form-control currency_amount_row_select';
			if(!empty($sDBColumnCurrency)){

				if(!empty($aData['db_alias_currency'])){
					$sAliasPart = $aData['db_alias_currency'];
				}

				$sSelectName 	= 'save['.(string)$sDBColumnCurrency.']'.$sAliasPart;
				$sSelectId		= 'saveid['.(string)$sDBColumnCurrency.']'.$sAliasPart;
				$oSelect->name = $sSelectName;
				$oSelect->id	= $sSelectId;

				$aOptions = array('db_column' => $sDBColumnCurrency, 'db_alias' => $sAlias);

				if(!array_key_exists($sSelectId, $oDialog->aUniqueFields)) {
					// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
					$oDialog->aSaveData[] = $aOptions;
					$oDialog->aUniqueFields[$sSelectId] = 1;
			}
				
			}
			foreach((array)$aCurrencies as $oCurrency){

				// Wenn kein Default -> erste Währung
				if($iCurrencyId <= 0){
					$iCurrencyId = $oCurrency->id;
				}
				
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = $oCurrency->id;

				if($iCurrencyId > 0 && $iCurrencyId == $oCurrency->id){
					$oOption->selected = "selected";
				}

				$oOption->setElement($oCurrency->getSign());
				$oSelect->setElement($oOption);
			}
			if($bDisableCurrencySelect){
				$oSelect->disabled = "disabled";
			}

			$oDivLeftContainer->setElement($oSelect);
			
			if($bDisableCurrencySelect){
				$oHiddenSelect = new Ext_Gui2_Html_Input();
				$oHiddenSelect->name = $oSelect->name;
				$oHiddenSelect->type = "hidden";
				$oHiddenSelect->value = $iCurrencyId;
				$oDivLeftContainer->setElement($oHiddenSelect);
			}
			
		} else {
			$oDivLeftContainer->setElement('&nbsp;');
		}

		$oDivInput->setElement($oDivLeftContainer);

		$oDivRightContainer = new Ext_Gui2_Html_Div();
		$oDivRightContainer->class = 'currency_amount_row_rightdiv';

		// Zweites Betragsfeld ist standardmäßig nicht mehr sichtbar.
		// Das ganz zu entfernen würde vermutlich massig Probleme verursachen. #5170
		if(empty($bShowRightDiv)) {
			$oDivRightContainer->style = 'display: none';
		}

		if($sDBColumn2 != ""){

			$oDivRightContainer->setElement('<span class="amount-sep">/</span>');

			$oInput	= new Ext_Gui2_Html_Input();
			$oInput->class = 'txt form-control currency_amount_row_input_to';

			if(!empty ($sClassNameTo)){
				$oInput->class = $sClassNameTo;
			}

			$oInput->name	= $sName2;
			$oInput->id		= $sId2;

			$fTempAmount = Ext_Thebing_Format::ConvertAmount($fAmount, (int)$oCurrentCurrency->id, (int)$iSchoolCurrencyId);
			$fTempAmount = Ext_Thebing_Format::Number($fTempAmount, null, $iSchoolId);

			$oInput->value  = $fTempAmount;

			if($iDisableAll == 1){
				$oInput->disabled = 'disabled';
			}

			$oDivRightContainer->setElement($oInput);

			$oInput	= new Ext_Gui2_Html_Input();
			$oInput->type = 'hidden';
			$oInput->value = $iSchoolCurrencyId;
			$oInput->class = 'currency_amount_row_input_hidden';
			$oDivRightContainer->setElement($oInput);

			$oInput	= new Ext_Gui2_Html_Input();
			$oInput->type = 'hidden';
			$oInput->value = $oSchool->id;
			$oInput->class = 'currency_amount_row_input_hidden2';
			$oDivRightContainer->setElement($oInput);

			$oDivRightContainer->setElement($sSchoolCurrency);

		}

		$oDivInput->setElement($oDivRightContainer);

		if($bReturnOnlyInputDiv){
			return $oDivInput;
		}

		$oDiv->setElement($oLabel);
		$oDiv->setElement($oDivInput);

		//$oDiv->setElement($oDivCleaner);

		return $oDiv;
	}

}
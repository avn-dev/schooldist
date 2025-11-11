<?php


class Ext_TC_Gui2_Util {

	
	/**
	 * DIESE METHODE IST VERALTET!
	 * Siehe Dialog createMultiRow
	 *
	 * @deprecated
	 *
	 * Div generieren mit mehreren Inputs
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aData
	 * @param string $sLabel
	 * @param string $sTextBetween
	 * @param string $sTextBetween2
	 * @param string $sTextBetween3
	 * @return Ext_Gui2_Html_Div
	 */
	public static function getInputSelectRow($oDialog, $aData, $sLabel, $sTextBetween='', $sTextBetween2='', $sTextBetween3='') {

		$aNewData = array();

		/**
		 * neues schema
		 * array(
		 *		'db_alias'		=> 'xxx',
		 *		'store_fields'	=> false,
		 *		'items' => array(
		 *			0 => array(
		 *				'default_class'		=> 'cssClass',
		 *				'class'				=> 'cssClass',
		 *				'db_column'			=> 'xxx',
		 *				'input'				=> 'select',
		 *				'select_options'	=> array(),
		 *				'text_after'		=> 'text',
		 *				'value'				=> 'xxx',
		 *				'format'			=> Ext_Gui2_View_Format_Abstract,
		 *				'name'				=> 'save[xxx]',
		 *				'joined_object_key' => ''
		 *              'style'             => 'xxx'
		 *			),
		 *		)
		 * )
		 */
		if(
			isset($aData['db_column_1']) ||
			isset($aData['db_column_2']) ||
			isset($aData['db_column_3'])
		) {
			//altes schema umwandeln
			$aTemp = $aData;
			$aItems = array();

			foreach($aTemp as $sOption => $mValue)
			{
				$bMatch = preg_match('/(.*)_([0-9])/',$sOption,$aMatch);
				if($bMatch)
				{
					$aItems[$aMatch[2]][$aMatch[1]] = $mValue;
				}
				elseif(mb_strpos($sOption,'select_options')===false)
				{
					$aNewData[$sOption] = $mValue;
				}
			}

			if(isset($aItems[1]))
			{
				$aItems[1]['input']			= 'input';
				$aItems[1]['text_after']	= $sTextBetween;
				$aItems[1]['default_class']	= 'txt form-control w100';
			}

			if(isset($aItems[2]))
			{
				$aItems[2]['input']			= 'select';
				$aItems[2]['text_after']	= $sTextBetween2;
				$aItems[2]['default_class']	= 'txt form-control auto_width';

				if(isset($aData['select_options']))
				{
					$aItems[2]['select_options'] = $aData['select_options'];
				}
			}
			if(isset($aItems[3]))
			{
				$aItems[3]['input']			= 'select';
				$aItems[3]['text_after']	= $sTextBetween3;
				$aItems[3]['default_class']	= 'txt form-control auto_width';

				if(isset($aData['select_options2']))
				{
					$aItems[3]['select_options'] = $aData['select_options2'];
				}
			}

			$aNewData['items'] = $aItems;
		}
		else
		{
			$aNewData = $aData;
		}

		$sAlias = $aNewData['db_alias'] ?? null;
		$bStoreFields = true;

		// ob felder gespeichert werden wenn nicht muss die save abgel. werden
		if(isset($aNewData['store_fields'])){
			$bStoreFields = (bool)$aNewData['store_fields'];
		}

		$sAliasPart = '';
		if(!empty($sAlias)) {
			$sAliasPart = '['.(string)$sAlias.']';
		}

		if(!isset($aNewData['items']))
		{
			$aNewData['items'] = array();
		}

		$aItems = $aNewData['items'];

		$oDiv				= new Ext_Gui2_Html_Div();
		$oDiv->class		= 'GUIDialogRow GUIDialogMultiRow form-group form-group-sm form-inline';

		$oDivLabel			= new Ext_Gui2_Html_Div();
		$oDivLabel->class	= 'GUIDialogRowLabelDiv control-label col-sm-3';
		$oDivInput			= new Ext_Gui2_Html_Div();
		$oDivInput->class	= 'GUIDialogRowInputDiv col-sm-9';

		$sClass = '';
		foreach($aItems as $aItem){
			if(
				isset($aItem['required']) &&
				$aItem['required']
			) {
				$sClass = 'required';
			}
		}
		
		$oLabel				= new Ext_Gui2_Html_Label();
		$oLabel->class      = $sClass;

		$oLabel->setElement($sLabel); 
		$oDivLabel->setElement($oLabel);

		$oDivLeftContainer = new Ext_Gui2_Html_Div();
		$oDivLeftContainer->class = "currency_amount_row_leftdiv";

		if(!empty($aItems))
		{
			foreach($aItems as $aItem)
			{
				$aItem['db_alias'] = $sAlias;
				$sSuffix = Ext_Gui2_Dialog::getSaveFieldSuffix($aItem);
				
				if(isset($aItem['name']))
				{
					$sName 	= $aItem['name'];
				}
				else
				{
					$sName 	= 'save'.$sSuffix;
				}

				$sId = 'saveid'.$sSuffix;

				$aSaveDataOptions = $aItem;
				
				$aSaveDataOptions['db_alias']	= $sAlias;
				$aSaveDataOptions['label']		= $sLabel;

				if(!empty($aItem['joined_object_key'])) {
					$aSaveDataOptions['joined_object_key'] = $aItem['joined_object_key'];
				}
				
				$sInput			= ucfirst($aItem['input']);
				$sInputClass	= 'Ext_Gui2_Html_'.$sInput;

				if(class_exists($sInputClass))
				{
					$sClass = $aItem['default_class'];					
					
					if(isset($aItem['class']))
					{
						$sClass .= ' '.$aItem['class'];
					} else {
						
						// Standardklassen setzen
						switch($aItem['input']) {
							case 'input':
							case 'select':
								$sClass .= ' txt form-control';
								break;
						}
						
					}
					
					if(
						isset($aItem['required']) &&
						$aItem['required']
					) {
						$sClass .= ' required';
					}

					$oInput			= new $sInputClass;
					$oInput->class	= $sClass;
					$oInput->name	= $sName;
					$oInput->id		= $sId;

					//CSS-Style
					
					if(!empty($aItem['style'])){
						$oInput->style = $aItem['style'];
					}
					
					if($oInput instanceof Ext_Gui2_Html_Input)
					{
						if(isset($aItem['value']))
						{
							$oInput->value = $aItem['value'];
						}
						
						if(isset($aItem['type']))
						{
							$oInput->type = $aItem['type'];
						}
						
					}
					elseif($oInput instanceof Ext_Gui2_Html_Select)
					{
						if(isset($aItem['select_options']))
						{
							$aOptions	= (array)$aItem['select_options'];
							$mValue		= 0;
							if(isset($aItem['value']))
							{
								$mValue = $aItem['value'];
							}

							foreach($aOptions as $iOption => $sOption)
							{
								$oOption		= new Ext_Gui2_Html_Option();
								$oOption->value = $iOption;

								if(
									$mValue == $iOption
								){
									$oOption->selected = 'selected';
								}

								$oOption->setElement((string)$sOption);
								$oInput->setElement($oOption);
							}
						}
					}

					$oDivInput->setElement($oInput);

					if(!empty($aItem['text_after']))
					{
						$sTextBetween = '&nbsp;'.$aItem['text_after'].'&nbsp;';
						$oDivInput->setElement($sTextBetween);
					}

					if(
						!array_key_exists($sId, $oDialog->aUniqueFields) &&
						$bStoreFields
					) {
						// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
						$oDialog->aSaveData[] = $aSaveDataOptions;
						$oDialog->aUniqueFields[$sId] = 1;
					}

				} else {
					throw new Exception('Invalid input (or input not set)!');
				}
			}
		}

		$oDiv->setElement($oDivLabel);
		$oDiv->setElement($oDivInput);

		return $oDiv;
	}

	/*
	 * Spalte mit Kalender + Input(zeit)
	 */
	public static function getDateTimeRow($oDialog, $aData, $sLabel, $sLabelBetween = '') {

		$sDBColumn1			= $aData['db_column_1'];
		$sDBAlias1			= $aData['db_alias_1'];

		$sDBColumn2			= $aData['db_column_2'];
		$sDBAlias2			= $aData['db_alias_2'];

		$sValue_1			= $aData['value_1'];
		$sName_1			= $aData['name_1'];
		$sId_1				= $aData['id_1'];
		$sClass_1			= $aData['class_1'];
		$bDisabled_1		= $aData['disabled_1'];
		$sCalendar_id		= $aData['calendar_id'];

		$sValue_2			= $aData['value_2'];
		$sName_2			= $aData['name_2'];
		$sId_2				= $aData['id_2'];
		$sClass_2			= $aData['class_2'];
		$bDisabled_2		= $aData['disabled_2'];

		$sAliasPart = '';
		if(!empty($sDBAlias1)) {
			$sAliasPart = '['.(string)$sDBAlias1.']';
		}

		if($sDBColumn1 != ''){
			$sName_1		= 'save['.(string)$sDBColumn1.']'.$sAliasPart;
			$sId_1			= 'saveid['.(string)$sDBColumn1.']'.$sAliasPart;
			$sCalendar_id 	= 'saveid[calendar]['.(string)$sDBColumn1.']'.$sAliasPart;
			$aOptions = array('db_column' => $sDBColumn1, 'db_alias' => $sDBAlias1, 'format' => $aData['format_1'], 'label'=>$sLabel);
			
			if($bDisabled_1 === true){
				$aOptions['disabled'] = true;
			}
			
			if(!array_key_exists($sId_1, $oDialog->aUniqueFields)) {
				// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
				$oDialog->aSaveData[] = $aOptions;
				$oDialog->aUniqueFields[$sId_1] = 1;
			}
			
		}

		if($sDBColumn2 != ''){
			$sName_2		= 'save['.(string)$sDBColumn2.']'.$sAliasPart;
			$sId_2			= 'saveid['.(string)$sDBColumn2.']'.$sAliasPart;
			$aOptions = array('db_column' => $sDBColumn2, 'db_alias' => $sDBAlias2, 'format' => $aData['format_2'], 'label'=>$sLabel);

			
			if($bDisabled_2 === true){
				$aOptions['disabled'] = true;
			}
			
			if(!array_key_exists($sId_2, $oDialog->aUniqueFields)) {
				// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
				$oDialog->aSaveData[] = $aOptions;
				$oDialog->aUniqueFields[$sId_2] = 1;
			}

		}

		$oDiv				= new Ext_Gui2_Html_Div();
		$oDiv->class		= 'GUIDialogRow form-group form-group-sm';

		$oDivLabel			= new Ext_Gui2_Html_Label();
		$oDivLabel->class	= 'GUIDialogRowLabelDiv control-label col-sm-3';

		$oFieldsDiv = new \Ext_Gui2_Html_Div();
		$oFieldsDiv->class	= 'GUIDialogRowInputDiv GUIDialogRowInputGridDiv';

		$oDivInput			= new Ext_Gui2_Html_Div();
		$oDivInput->class	= 'GUIDialogRowInputDiv col-sm-5';

		$oDivInputTime = new Ext_Gui2_Html_Div();
		$oDivInputTime->class = 'GUIDialogRowInputDiv col-sm-4';

		$oLabel = new Ext_Gui2_Html_Label();

		if((int)$aData['required'] == 1) {
			$sLabel .= ' *';
		}

		$oLabel	->setElement($sLabel);
		$oDivLabel->setElement($oLabel);

		// Calendar
			$oDivDay			= new Ext_Gui2_Html_DIV();
			$oDivDay->class		= 'GUIDialogRowWeekdayDiv';

			$oInput				= new Ext_Gui2_Html_Input();
			$oInput->type		= 'text';
			$oInput->class		= 'txt form-control calendar_input';
			$oInput->name		= $sName_1;
			$oInput->value		= $sValue_1;
			$oInput->id			= $sId_1;
			
			if($bDisabled_1 === true){
				$oInput->readonly = true;
				$oInput->bReadOnly = true;
				$sClass_1 .= ' readonly';
			}
			
			$oInput->class		= $sClass_1;

			$oCalendarDiv = new Ext_Gui2_Html_DIV();
			$oCalendarDiv->class = 'GUIDialogRowCalendarDiv input-group';

			$sAddon = '<span class="input-group-addon  calendar_img"><i class="fa fa-calendar" aria-hidden="true"></i></span>';

			$sWeekday = '<div class="GUIDialogRowWeekdayDiv input-group-addon"></div>';

		$oCalendarDiv->setElement($sAddon);
		$oCalendarDiv->setElement($oInput);
		$oCalendarDiv->setElement($sWeekday);

		$oDivInput->setElement($oCalendarDiv);

		// Zeit
//		if(
//			$sLabelBetween != '' &&
//			$aData['show_time'] !== false
//		) {
//			$oDivLabel2			= new Ext_Gui2_Html_DIV();
//			$oDivLabel2->class	= 'GUIDialogRowLabelDiv';
//			$oDivLabel2->style	= 'width: auto; float: left; margin-right: 5px;';
//
//			$oLabel				= new Ext_Gui2_Html_Label();
//			$oLabel->setElement($sLabelBetween);
//			$oDivLabel2->setElement($oLabel);
//			$oDivInputTime->setElement($oDivLabel2);
//		}

		if($aData['show_time'] !== false) {

			$oInputTime				= new Ext_Gui2_Html_Input();
			$oInputTime->type		= 'text';
			$oInputTime->value		= $sValue_2;
			$oInputTime->class		= 'txt form-control';
			$oInputTime->name		= $sName_2;
			$oInputTime->id			= $sId_2;
			if(!empty($sLabelBetween)) {
				$oInputTime->placeholder = $sLabelBetween;
			}
			
			if($bDisabled_2 === true){
				$oInputTime->readonly = true;
				$oInputTime->bReadOnly = true;
				$sClass_2 .= ' readonly';
			}
			
			$oInputTime->class		= $sClass_2;
			
			$oDivInputTime->setElement($oInputTime);

		}
		//

		$oFieldsDiv->setElement($oDivInput);
		$oFieldsDiv->setElement($oDivInputTime);

		$oDiv->setElement($oDivLabel);
		$oDiv->setElement($oFieldsDiv);

		return $oDiv;

	}

	// Spalte die Informationen enthält
	public static function getInfoRow($oDialog,  $sLabel = '', $sType = 'input', $aData = array(), $sValue = ''){

		$oDivInfo					= $oDialog->create('div');

		if(isset($aData['id'])){
			$oDivInfo->id			= 'row_' . $aData['id'];
		}

		$oDivInfo->style			= $aData['style'];

		$oDivInfo->class			= 'GUIDialogRow';
		$oDivLabel					= $oDialog->create('div');
		$oDivLabel->class			= 'GUIDialogRowLabelDiv';
		$oDivInput					= $oDialog->create('div');
		$oDivInput->class			= 'GUIDialogRowInputDiv';
		$oDivCleaner				= $oDialog->create('div');
		$oDivCleaner->class			= 'divCleaner';
		$oLabel						= $oDialog->create('label');
		$oLabel->setElement($sLabel);
		$oDivLabel->setElement($oLabel);

		// Fake Input/Textarea der NICHT mit geschickt wird
		if($sType == 'textarea'){
			$oFakeInput				= $oDialog->create('textarea');
			$oFakeInput->setElement($sValue);
			$oFakeInput->class		= 'textarea';
		}else{
			$oFakeInput				= $oDialog->create('input');
			$oFakeInput->value		= $sValue;
		}
		$oFakeInput->class			= 'txt form-control';
		$oFakeInput->id				= $aData['id'];
		$oFakeInput->class			= $aData['class'];
		if($aData['editable'] == false){
			$oFakeInput->style			= 'border: none; background-color: transparent; color: black; ';
			$oFakeInput->disabled		= 'disabled';
			$oFakeInput->readonly		= 'readonly';
		}
		#print_r($aData['editable']);
		#print_r($oFakeInput);

		$oDivInput->setElement($oFakeInput);

		$oDivInfo->setElement($oDivLabel);
		$oDivInfo->setElement($oDivInput);
		$oDivInfo->setElement($oDivCleaner);

		return $oDivInfo;
	}
	

	/*
	 * Spalte mit einem Icon z.B. "Plus"
	 */
	public static function getIconRow($oDialog,  $sLabel = '', $sIcon = '', $aData = array()) {

		$oDivInfo = $oDialog->create('div');

		if (isset($aData['id'])){
			$oDivInfo->id = 'row_' . $aData['id'];
		}

		if (isset($aData['row_class'])) {
			$oDivInfo->class = 'row_' . $aData['row_class'];
		}

		if (isset($aData['class'])) {
			$oDivInfo->class .= ' ' . $aData['class'];
		}

		$oDivInfo->style = 'display: flex; align-items: center;';

		$oDivInfo->class = 'GUIDialogRow';
		$oDivLabel = $oDialog->create('div');
		$oDivLabel->class = 'GUIDialogRowLabelDiv';
		$oDivLabel->style = 'padding-top:0px';
		$oDivInput = $oDialog->create('div');
		$oDivInput->class = 'GUIDialogRowInputDiv';
		$oDivCleaner = $oDialog->create('div');
		$oDivCleaner->class = 'divCleaner';
		$oLabel = $oDialog->create('label');
		$oLabel->setElement($sLabel);
		$oDivLabel->setElement($oLabel);

		$oImage = $oDialog->create('i');
		$oImage->class = $aData['class'].' fa '.$sIcon;

		if ($aData['onclick']){
			$oImage->onclick = $aData['onclick'];
			$oImage->style = "cursor: pointer; ";
		}

		$oDivInput->setElement($oImage);

		$oDivInfo->setElement($oDivLabel);
		$oDivInfo->setElement($oDivInput);
		$oDivInfo->setElement($oDivCleaner);

		return $oDivInfo;
	}

}
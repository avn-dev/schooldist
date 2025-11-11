<?php

class Ext_TC_Exchangerate_Table_Source_Gui2_Data extends Ext_TC_Gui2_Data {
	
	/**
	 * liest den Inhalt einer XML-Datei aus und gibt diesen aus
	 * @param array $_VARS 
	 */	
	public function switchAjaxRequest($_VARS) {
		
		if($_VARS['task'] == 'openXmlFile') {

			$sXmlUrl = $_VARS['XmlUrl'];

			$aTransfer = array();

			$sXml = @file_get_contents($sXmlUrl);

			if(!$sXml){
				$sXml = $this->_oGui->t('XML konnte nicht geladen werden!');
			}

			/**
			 * Absichtlich kein Charset angegeben, damit eventuell 
			 * Kodierungsfehler nicht zu einer leeren Zeichenkette führen
			 */
			$sXml = htmlentities($sXml);

			$aTransfer['action'] = 'openXmlFile';
			$aTransfer['xml'] = $sXml;

			$aTransfer = json_encode($aTransfer);

			echo $aTransfer;

		} else {
			parent::switchAjaxRequest($_VARS);
		}
		
	}
	
	/**
	 * baut den Dialog für Wechselkurs-Quellen auf
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDialog($oGui)
	{
		
		$oDialog = $oGui->createDialog($oGui->t('Quelle "{name}" editieren'), $oGui->t('Quelle anlegen'));

		// Name
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_ets',
			'db_column' => 'name',
			'required' => true
		)));

		// Erhöhung

		$oDialog->setElement($oDialog->createRow($oGui->t('Erhöhung'), 'input', array(
			'db_column'			=> 'factor',
			'db_alias'			=> 'tc_ets',
			'required'			=> true,
			'style'             => 'width:70px;'
		)));

		// URL

		$oDialog->setElement($oDialog->createRow($oGui->t('URL der XML'), 'input', array(
			'db_column'			=> 'url',
			'db_alias'			=> 'tc_ets',
			'required'			=> true,
			'events' => array(
				array(
					'event' 		=> 'keyup',
					'function' 		=> 'loadXMLFile'
				)
			)
		)));

		// XMLOutput

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->id = 'XmlOutput';
		$oDiv->class = 'designDiv';
		$oDiv->style = 'height: 200px; overflow: auto; font-family: monospace; white-space: pre;';
		$oDialog->setElement($oDiv);

		// Kursdatum

		$aInputOptions = array(
				'db_alias' => 'tc_ets',
				'items'    => array(

							//Kursdatum
							0 => array(
								'db_column'			=> 'date_position',		 
								'input'				=> 'input',
								'required'			=> true,
								'style'             => 'margin-right:10px;',
								'text_after'        => $oGui->t('Format:')
							),

							//Format
							1 => array(
								'db_column'			=> 'date_format',	
								'input'				=> 'input',
								'required'			=> true,
								'style'             => 'width:100px;'
							)
					)
				);	

		$oRow = $oDialog->createMultiRow($oGui->t('Kursdatum'), $aInputOptions);
		$oDialog->setElement($oRow);

		// Container

		$sLabel = $oGui->t('Container');
		$aInputOptions = array(
				'db_alias' => 'tc_ets',
				'items'    => array(

							//Container
							0 => array(
								'db_column'			=> 'container',		 
								'input'				=> 'input',
								'required'			=> true,
								'style'             => 'margin-right:10px;',
								'text_after'        => $oGui->t('Kindelement:')
							),

							//Kindelement
							1 => array(
								'db_column'			=> 'child_element',	
								'input'				=> 'checkbox',
								'value'				=> 1
							)
					)
				);	

		$oRow = $oDialog->createMultiRow($sLabel, $aInputOptions);
		$oDialog->setElement($oRow);

		// Quellwährung

		$sLabel = $oGui->t('Quellwährung (oder fester Währungsschlüssel)');
		$aInputOptions = array(
				'db_alias' => 'tc_ets',
				'items'    => array(

							//Quellwährung
							0 => array(
								'db_column'			=> 'source_currency',		 
								'input'				=> 'input',
								'required'			=> true,
								'style'             => 'margin-right:10px;',
								'text_after'        => $oGui->t('Suchausdruck:')
							),

							//Suchausdruck
							1 => array(
								'db_column'			=> 'source_currency_searchterm',	
								'input'				=> 'input',
								'style'             => 'width:140px;'
							)
					)
				);

		$oRow = $oDialog->createMultiRow($sLabel, $aInputOptions);
		$oDialog->setElement($oRow);

		// Zielwährung

		$sLabel = $oGui->t('Zielwährung (oder fester Währungsschlüssel)');
		$aInputOptions = array(
				'db_alias' => 'tc_ets',
				'items'    => array(

							//Zielwährung
							0 => array(
								'db_column'			=> 'target_currency',		 
								'input'				=> 'input',
								'required'			=> true,
								'style'             => 'margin-right:10px;',
								'text_after'        => $oGui->t('Suchausdruck:')
							),

							//Suchausdruck
							1 => array(
								'db_column'			=> 'target_currency_searchterm',	
								'input'				=> 'input',
								'style'             => 'width:140px;'
							)
					)
				);

		$oRow = $oDialog->createMultiRow($sLabel, $aInputOptions);
		$oDialog->setElement($oRow);

		// Kurs

		$sLabel = $oGui->t('Kurs');
		$aInputOptions = array(
				'db_alias' => 'tc_ets',
				'items'    => array(

							//Kurs
							0 => array(
								'db_column'			=> 'rate',		 
								'input'				=> 'input',
								'required'			=> true,
								'style'             => 'margin-right:10px;',
								'text_after'        => $oGui->t('Trennzeichen:')
							),

							// Trennzeichen
							1 => array(
								'db_column'			=> 'separator',	
								'input'				=> 'input',
								'style'				=> 'width:30px;margin-right:10px;',
								'text_after'        => $oGui->t('Umkehren:'),
								'required'			=> 'true'
							),

							// Umkehren
							2 => array(
								'db_column'			=> 'reverse',	
								'input'				=> 'checkbox',
								'value'				=> 1						
							)
					)
				);
		$oRow = $oDialog->createMultiRow($sLabel, $aInputOptions);
		//$oRow = Ext_TC_Gui2_Util::getInputSelectRow($oDialog, $aInputOptions, $sLabel);
		$oDialog->setElement($oRow);

		// Divisor

		$sLabel = $oGui->t('Divisor');
		$aInputOptions = array(
				'db_alias' => 'tc_ets',
				'items'    => array(

							//Divisor
							0 => array(
								'db_column'			=> 'divisor',		 
								'input'				=> 'input',
								'style'             => 'margin-right:10px;',
								'text_after'        => $oGui->t('Suchausdruck:')
							),

							//Suchausdruck
							1 => array(
								'db_column'			=> 'divisor_searchterm',	
								'input'				=> 'input',
								'style'             => 'width:140px;'
							)
					)
				);

		$oRow = $oDialog->createMultiRow($sLabel, $aInputOptions);
		$oDialog->setElement($oRow);

		$oDialog->save_as_new_button = true;
		
		return $oDialog;
	}
	
}
<?php

/**
 * erzeugt den HTML-Code in der Platzhalterübersicht, wenn in der Übersicht die
 * Funktionen (Suche, etc.) zur Verfügung stehen sollen
 */
class Ext_TC_Placeholder_Html {

	/**
	 * baut den Html-Code für die Platzhalterübersicht auf (aktuell mit Suchfeld). Über $sContent muss
	 * bereits generierter HTML-Code der Platzhalterübersicht übergeben werden
	 * @param string $sContent
	 * @return string 
	 */
	public function createPlaceholderContent($sContent){

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'placeholderContent';

		// Optionen: Search, ...
		$oPlaceholderTools = $this->_getPlaceholderTools();
		$oDiv->setElement($oPlaceholderTools);

		// Scrollbarer Bereich in dem die Platzhalter dargestellt werden
		$oScrollDiv = new Ext_Gui2_Html_Div();
		$oScrollDiv->class = 'placeholderContentScroll';
		$oScrollDiv->setElement($sContent);

		$oDiv->setElement($oScrollDiv);

		// HTML-Code erzeugen
		$sReturn = $oDiv->generateHTML();

		return $sReturn;
	}

	/**
	 * baut den Header für die Übersicht auf mit allen Funktionen (aktuell nur 
	 * Suche nach Platzhaltern)
	 * @return Ext_Gui2_Html_Div 
	 */
	protected function _getPlaceholderTools(){

		// Div
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'placeholderTools';

		// Input
		$oSearch = new Ext_Gui2_Html_Input();
		$oSearch->class = 'txt form-control input-sm placeholderSearchInput';
		$oSearch->placeholder = L10N::t('Suche').'…';
		//$oSearch->id = 'searchPlaceholder';
		$oDiv->setElement($oSearch);

		// Loading		
		$oLoading = new Ext_Gui2_Html_I();
		$oLoading->class = 'placeholderLoadingIndicator fa fa-spinner fa-pulse';
		//$oLoading->id = 'placeholderLoadingIndicator';
		$oLoading->style = 'display: none;';
		$oDiv->setElement($oLoading);

		return $oDiv;
	}

	/**
	 * erzeugt den Tab für Platzhalter-Beispiele
	 * @param array $aApplications
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2 $oGui
	 * @param string $sType pdf/communication
	 * @return Ext_Gui2_Dialog_Tab 
	 */
	public static function getPlaceholderExampleTab($aApplications, $oDialog, $oGui, $sType = 'pdf') {

		//@todo Caching einbauen

		//Tab erzeugen
		$oTab = $oDialog->createTab($oGui->t('Beispiele'));
		// Platzhalterbeipiele holen
		$aPlaceholderExamples = (array) Ext_TC_Placeholder_Example::getSelectOptions();
		
		$aStaticApplications = array();

		// Applications holen
		if($sType == 'pdf'){
			$aStaticApplications = (array) Ext_TC_Factory::executeStatic('Ext_TC_Placeholder_Example_Entry', 'getPdfApplications');
		} elseif($sType == 'communication') {
			$aStaticApplications = (array) Ext_TC_Factory::executeStatic('Ext_TC_Placeholder_Example_Entry', 'getEmailApplications');
		}
		
		// Applications durchlaufen
		foreach((array)$aApplications as $sApplication) {
		
			// Überschrift
			$oH2 = new Ext_Gui2_Html_H2();
			$oH2->style = 'margin:10px 0 10px 0;';
			if(!empty($aStaticApplications[$sApplication])) {
				$oH2->setElement($aStaticApplications[$sApplication]);
			}
			$oTab->setElement($oH2);

			
			// Platzhalterbeipiele durchlaufen
			foreach($aPlaceholderExamples as $iId => $sI18NName) {

				// Objekt holen
				$oExample = Ext_TC_Placeholder_Example::getInstance($iId);
				// Childs holen
				$aChilds = $oExample->getJoinedObjectChilds('entries');

				if(!empty($aChilds)){
				
					$sId = $sApplication.'_'.$iId;

					$oAccordion = new Ext_Gui2_Dialog_Accordion($sId);

					$oTab->setElement((new Ext_Gui2_Html_H2())->setElement($sI18NName));

//					$oElement = $oAccordion->createElement($sI18NName, [
//						'close' => false
//					]);
					
					foreach($aChilds as $oExampleEntry){
						
						$aChildApplications = array();					

						// Applications des Beipiels holen
						if($sType == 'pdf'){
							$aChildApplications = $oExampleEntry->pdf_applications;
						} elseif($sType == 'communication') {
							$aChildApplications = $oExampleEntry->email_applications;
						}

						// prüfen, ob Beispiel für diesen Bereich angelegt wurde
						if(in_array($sApplication, $aChildApplications)){

							$oSubElement = $oAccordion->createElement($oExampleEntry->getName(), [
								'id' => $sApplication.'_'.$iId.'_'.$oExampleEntry->id,
								'style' => 'padding: 10px;'
							]);
							
							$sContent = 
									'<span>'.$oExampleEntry->getDescription().'</span>'.
									'<div class="placeholderContainerValue">'.$oExampleEntry->value.'</div>';
							
							$oSubElement->setContent($sContent);

							$oAccordion->addElement($oSubElement);
							
						}
						
					}
					
//					$oAccordion->addElement($oElement);
					
					$oTab->setElement($oAccordion);

				}
			}
		
		}
		
		return $oTab;
		
	}
		
	/**
	 * baut ein Accordion-Header auf 
	 * @param string $sValue
	 * @param string $sId
	 * @param bool $bCategory
	 * @return Ext_Gui2_Html_Div 
	 */
	protected static function _getAccordionEntry($sValue, $sId, $bCategory = false){
				
		$oEntry = new Ext_Gui2_Html_Div();
		$oEntry->class = 'accordionEntry box box-primary';
		$oEntry->id = $sId.'_switch';
			
		$oIcon = new Ext_Gui2_Html_Div();
		$oIcon->class = 'icon';
		$oIcon->setElement('&nbsp;');
		$oEntry->setElement($oIcon);		
		
		$oValue = new Ext_Gui2_Html_Div();
		$oValue->class = 'value';
		$oValue->setElement($sValue);
		$oEntry->setElement($oValue);
		
		if($bCategory){
			$oEntry->class .= ' category';
			$oIcon->class .= ' iconClose';	// Kategorien sind beim Öffnen des Dialogs geschlossen
		} else {
			$oEntry->class .= ' sc_gradient';
			$oIcon->class .= ' iconOpen';	// Einträge sind beim Öffnen des Dialogs offen
		}
		
		return $oEntry;
		
	}
	
}

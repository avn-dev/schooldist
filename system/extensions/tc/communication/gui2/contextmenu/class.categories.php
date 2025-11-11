<?php

/**
 * Kontextmenü der Kategorien
 */
class Ext_TC_Communication_Gui2_ContextMenu_Categories extends Ext_Gui2_View_ContextMenu_Abstract {

	public function getOptions($aResultData) {
		
		$oMessage = Ext_TC_Factory::getInstance('Ext_TC_Communication_Message', (int)$aResultData['id']);
		$iCategoryId = $oMessage->category_id;
		
		$aMenu = array(
			array(
				'name' => Ext_TC_Communication::t('keine Kategorie'),
				'key' => 0
			),
			array(
				'type' => 'separator'
			)
		);

		$oCategory = new Ext_TC_Communication_Category(0);
		$aCategories = $oCategory->getArrayList();

		foreach($aCategories as $aCategory) {
					
			$aMenu[] = array(
				'key' => $aCategory['id'],
				'name' => $aCategory['name'],
				'color_icon' => $aCategory['code']
			);
			
			$iIndex = -1;
			if($iCategoryId == 0) {
				$iIndex = 0;
			} elseif($iCategoryId == $aCategory['id']) {
				$iIndex = count($aMenu) - 1;
			}
			
			if($iIndex !== -1) {
				$aMenu[$iIndex]['class'] = 'active';
			}

		}

		return $aMenu;
		
	}
	
}

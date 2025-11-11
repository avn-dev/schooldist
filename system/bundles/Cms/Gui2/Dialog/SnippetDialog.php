<?php

namespace Cms\Gui2\Dialog;

use \Cms\Gui2\Format\SnippetFormat;

class SnippetDialog extends \Ext_Gui2_Data {
	
	public static function getEditDialog(\Ext_Gui2 $oGui) {
		$oDialog = self::getDialog($oGui , true);	
		
		return $oDialog;
	}
	
	public static function getDialog(\Ext_Gui2 $oGui , $bReadonly = false) {

		$oDialog = $oGui->createDialog('Schnipsel "{title}" bearbeiten', 'Neues Schnipsel anlegen');
		
		$oDialog->height = 500;
		$oDialog->width = 900;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Titel'), 
				'input', 
				array(
					'db_column' => 'title',
					'db_alias' => 'cms_s',
					'required' => true
				)
			)
		);

		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Platzhalter'), 
				'input', 
				array(
					'db_column' => 'placeholder',
					'db_alias' => 'cms_s',
					'required' => true,
					'readonly' => $bReadonly,
					'format' => new \Cms\Gui2\Format\SnippetFormat,
				)
			)
		);
		
		$aLanguages  = \Factory::executeStatic('Util', 'getLanguages', 'frontend');
		foreach($aLanguages as $sKey=>$sLanguage) {
			
			if(strlen($sKey) < 2) {
				continue;
			}
			
			$oDialog->setElement(
				$oDialog->createRow(
					$oGui->t($sLanguage), 
					'html', 
					array(
						'db_column' => $sKey,
						'db_alias' => 'cms_s',
						'required' => true,
					)
				)
			);

			\DB::addField('cms_snippets', $sKey, 'TEXT NOT NULL');
			
		}
		
		\Cms\Entity\Snippet::deleteTableCache();
		
		return $oDialog;
	}
	
    public static function getOrderBy(){
        return array('title' => 'ASC');
    }	

	public static function getWhere() {
		return array();
	}
	
}

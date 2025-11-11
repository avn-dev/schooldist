<?php

namespace Cms\Gui2\Dialog;

class DynamicRoutingDialog extends \Ext_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Dynamisches Routing "{name}" bearbeiten', 'Neues dynamisches Routing anlegen');

		$oDialog->save_as_new_button = true;
		
		$oDialog->height = 500;
		$oDialog->width = 900;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Name'), 
				'input', 
				array(
					'db_column' => 'name',
					'db_alias' => 'cms_dr',
					'required' => true
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Internetauftritt'), 
				'select', 
				array(
					'db_column' => 'site_id',
					'db_alias' => 'cms_dr',
					'required' => true,
					'selection' => new \Cms\Gui2\Selection\SiteSelection(),
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Sprache'), 
				'select', 
				array(
					'db_column' => 'language_iso',
					'db_alias' => 'cms_dr',
					'required' => true,
					'selection' => new \Cms\Gui2\Selection\LanguageSelection(),
					'dependency' => array(
						array(
							'db_column' => 'site_id',
							'db_alias' => 'cms_dr'
						)
					)
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Zielseite'), 
				'select', 
				array(
					'db_column' => 'page_id',
					'db_alias' => 'cms_dr',
					'required' => true,
					'selection' => new \Cms\Gui2\Selection\PageSelection(),
					'dependency' => array(
						array(
							'db_column' => 'language_iso',
							'db_alias' => 'cms_dr'
						)
					)
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Permalink-Vorlage'), 
				'input', 
				array(
					'db_column' => 'permalink_template',
					'db_alias' => 'cms_dr',
					'required' => true,
					'style' => 'width: 600px;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Titel-Vorlage'), 
				'input', 
				array(
					'db_column' => 'title_template',
					'db_alias' => 'cms_dr',
					'required' => true,
					'style' => 'width: 600px;'
				)
			)
		);

		$sUtilClass = \Factory::getClassName('\Cms\Service\DynamicRouting');
		
		if(method_exists($sUtilClass, 'getDynamicRoutingPlaceholders')) {
			$aPlaceholder = $sUtilClass::getDynamicRoutingPlaceholders();

			$sPlaceholder = '<p>'.$oGui->t('Die Platzhalter beziehen sich auf die Ressourcennamen der entsprechenden Einträge.').'</p>'.self::generatePlaceholderList($aPlaceholder);
			
			$oNotification = $oDialog->createNotification($oGui->t('Verfügbare Platzhalter'), $sPlaceholder, 'info');
			$oDialog->setElement($oNotification);
		}
		
		return $oDialog;
	}

	private static function generatePlaceholderList($aPlaceholder) {
		
		$sList = '<dl>';
		foreach($aPlaceholder as $sPlaceholder=>$sDescription) {
			$sList .= '<dt><b>{'.$sPlaceholder.'}</b></dt><dd>'.$sDescription.'</dd>';
		}
		$sList .= '</dl>';
		
		return $sList;
	}
	
    public static function getOrderBy(){
        return array('name' => 'ASC');
    }	

	public static function getWhere() {
		return array();
	}
	
	static public function getContentDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Texte von "{name}" bearbeiten');

		return $oDialog;
	}
	
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = [], $sAdditional = false) {
		
		if($sIconAction == 'content') {
			
			$oDialog->aElements = [];
			
			$dynamicRouting = $this->_getWDBasicObject((array)$aSelectedIds);
			
			$site = \Cms\Entity\Site::getInstance($dynamicRouting->site_id);
			$languages = $site->getLanguages();
			
			$languages = array_map(function($language) {
				return [
					'name' => $language['name'],
					'iso' => $language['code']
				];
			}, $languages);
			
			$dynamicRoutingService = \Factory::getObject(\Cms\Service\DynamicRouting::class);
			
			$links = $dynamicRoutingService->getLinks();

			$fields = $dynamicRoutingService->getContentFields($dynamicRouting);

			if(!empty($fields)) {

				$data = \DB::getJoinData('cms_dynamic_routings_contents', ['dynamic_route_id'=>$dynamicRouting->id]);
				
				$data = array_reduce($data, function ($carry, $item) {
					$carry[$item['key']][$item['field']][$item['language']] = $item['value'];
					return $carry;
				}, []);
				
				$links = array_filter($links, function($link, $key) use($dynamicRouting) {
					return $link['dynamic_routing'] == $dynamicRouting->id && preg_match('/^[0-9_]+$/',$key);
				}, ARRAY_FILTER_USE_BOTH);

				foreach($links as $link) {
					$oDialog->setElement($oDialog->create('h4')->setElement($link['title']));
					foreach($fields as $field) {
						$oDialog->setElement($oDialog->createI18NRow($field['label'], [
							'type' => $field['type'], 
							'db_column' => $link['key'],
							'db_alias' => $field['key'],
							'value'=>$data[$link['key']][$field['key']]??[]
						], $languages));
					}
				}
				
			} else {
				$oDialog->setElement($oDialog->createNotification($this->t('Für diese Route sind keine Felder definiert!')));
			}
			
		}
		
		return parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);		
	}
	
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		
		if($sAction == 'content') {

			if($bSave) {
				
				$dynamicRouting = $this->_getWDBasicObject((array)$aSelectedIds);
				
				\DB::begin(__METHOD__);

				$joinData = [];
				foreach($aData as $key=>$fields) {
					foreach($fields as $field=>$languages) {
						foreach($languages as $language=>$value) {
							if(empty($value)) {
								continue;
							}
							$joinData[] = [
								'key'=>$key,
								'field'=>$field,
								'language'=>$language,
								'value'=>$value
							];
						}
					}
					
				}

				\DB::updateJoinData('cms_dynamic_routings_contents', ['dynamic_route_id'=>$dynamicRouting->id], $joinData);
				
				\DB::commit(__METHOD__);
			}

			$transfer = [
				'action' => 'closeDialogAndReloadTable',
				'data' => ['id' => 'ID_'.implode('_', (!empty($aSelectedIds)) ? $aSelectedIds : [0])],
				'error' => []
			];
			
		} else {
			
			$transfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
			
		}
		
		return $transfer;
	}
	
}

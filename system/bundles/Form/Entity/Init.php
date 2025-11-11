<?php

namespace Form\Entity;

class Init extends \WDBasic {
	
	protected $_sTable = 'form_init';
	protected $_sTableAlias = 'f_i';

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent',
	 *			'check_active'=>true,
	 *			'orderby'=>position,
	 *			'orderby_type'=>ASC,
	 *			'orderby_set'=>false
	 *			'query' => false,
     *          'readonly' => false,
	 *			'cloneable' => true,
	 *			'static_key_fields' = array('field' => 'value'),
	 *			'on_delete' => 'cascade' / '' / 'detach' ( nur bei "childs" möglich ),
	 *			'bidirectional' => false // legt fest, ob eine Verknüpfung in beide Richtungen besteht
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = [
		'options' => [
			'class' => '\Form\Entity\Option',
			'key' => 'form_id',
			'type' => 'child',
			'orderby' => 'position',
			'check_active'=>true
		],
		'pages' => [
			'class' => '\Form\Entity\Page',
			'key' => 'form_id',
			'type' => 'child',
			'orderby' => 'position',
			'check_active'=>true
		]
	];

	public function updateStructure() {
		
		$sSql = "
			CREATE TABLE IF NOT EXISTS
				#table
			(  
				`id` int(11) NOT NULL auto_increment,  
				`date` DATETIME NOT NULL,
				`ip` tinytext NOT NULL,  
				`data` text NOT NULL, 
				`done` TINYINT NOT NULL,  
				PRIMARY KEY  (`id`)
			) ENGINE=InnoDB
		";
		$aSql = [
			'table' => 'form_data_'.$this->id
		];
		\DB::executePreparedQuery($sSql, $aSql);

		$aOptions = $this->getJoinedObjectChilds('options');

		// Nicht vorhandene Felder anlegen
		$aTable = \DB::describeTable('form_data_'.$this->id);
		
		foreach($aOptions as $oOption) {
			if(!isset($aTable["field_".$oOption->id])) {
				\DB::addField('form_data_'.$this->id, "field_".$oOption->id, 'TEXT NOT NULL');
			}
		}

	}
	
	public function copy() {
		
		\DB::begin(__METHOD__);
		
		$aFormInit = $this->_aData;
		
		unset($aFormInit['id']);

		$aFormInit['subject'] .= ' Copy';

		$iNewFormId = \DB::insertData('form_init', $aFormInit);

		$aMapping = [
			'init' => [
				$this->id => $iNewFormId
			]
		];

		$aNewConditions = [];
		
		// Seiten
		$aPages = (array)\DB::getQueryRows("SELECT * FROM `form_pages` WHERE `form_id` = ".(int)$this->id." ORDER BY `position`");
		foreach($aPages as $aPage) {
			
			$iOldPageId = $aPage['id'];
			unset($aPage['id']);
			$aPage['form_id'] = $aMapping['init'][$this->id];
			$aPage['changed'] = date('Y-m-d H:i:s');
			$aPage['created'] = date('Y-m-d H:i:s');

			$aMapping['pages'][$iOldPageId] = \DB::insertData('form_pages', $aPage);

			// Felder
			$aFields = (array)\DB::getQueryRows("SELECT * FROM `form_options` WHERE `page_id` = ".(int)$iOldPageId." ORDER BY `position`");
			foreach($aFields as $aField) {

				$iOldFieldId = $aField['id'];
				unset($aField['id']);
				$aField['form_id'] = $aMapping['init'][$this->id];
				$aField['page_id'] = $aMapping['pages'][$iOldPageId];
				$aField['changed'] = date('Y-m-d H:i:s');
				$aField['created'] = date('Y-m-d H:i:s');

				$aMapping['options'][$iOldFieldId] = \DB::insertData('form_options', $aField);

				// Bedingungen
				$aConditions = (array)\DB::getQueryRows("SELECT * FROM `form_options_conditions` WHERE `option_id` = ".(int)$iOldFieldId." ORDER BY `position`");
				foreach($aConditions as $aCondition) {

					$iOldConditionId = $aCondition['id'];
					unset($aCondition['id']);
					$aCondition['option_id'] = $aMapping['options'][$iOldFieldId];

					$aNewConditions[] = $aCondition;
					
				}

			}
			
		}

		foreach($aNewConditions as $aNewCondition) {

			$aNewCondition['field'] = $aMapping['options'][$aNewCondition['field']];
			\DB::insertData('form_options_conditions', $aNewCondition);
		
		}

		\DB::commit(__METHOD__);
		
		$this->updateStructure();
		
		return $iNewFormId;
	}
	
}
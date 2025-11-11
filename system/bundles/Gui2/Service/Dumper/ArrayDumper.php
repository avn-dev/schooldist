<?php

namespace Gui2\Service\Dumper;

class ArrayDumper {
	
	/**
	 * List of attributes which can be listed with the same key
	 * 
	 * @var array
	 */
	private $aSameAttributes = [
		'multiple_selection',
		'foreign_key',
		'foreign_key_alias',
		'parent_primary_key',
		'rows_clickable'
	];
	
	/**
	 * dump Gui2 object to array
	 * 
	 * @see default.yml
	 * 
	 * @param \Ext_Gui2 $oGui
	 * @return array
	 */
	public function dumpGuiObject(\Ext_Gui2 $oGui) {
		
		$aData = [];
		
		$aData['class'] = $this->dumpClassAttributes($oGui);
		
		$aData['title'] = $oGui->gui_title;
		$aData['description'] = $oGui->gui_description;
		$aData['sortable'] = $oGui->row_sortable;
		
		$aData = array_merge(
			$aData, 
			array_intersect_key($oGui->getConfigArray(), array_flip($this->aSameAttributes))
		);

		$aArrayBars = [];
		$oLastBar = null;
		
		$aTopBars = $oGui->getBarList(true);
		
		$aBars = $oGui->getBar();
		foreach($aBars as $iKey => $oBar) {
			if(($iKey + 1) === count($aTopBars)) {
				$oLastBar = $oBar;
				break;
			}
			
			$aArrayBars[] = $this->dumpGuiBar($oBar);
		}
		
		if($oLastBar) {
			$aLastBarElements = $oLastBar->getElements();
			$bStandardLastBar = false;

			foreach($aLastBarElements as $oElement) {
				if($oElement instanceof \Ext_Gui2_Bar_Pagination) {
					$aData['pagination'] = true;
					if($oElement->only_pagecount) {
						$aData['only_pagecount'] = true;
					}
					if($oElement->limit_selection) {
						$aData['limit_selection'] = true;
					}
					$bStandardLastBar = true;				
				} else if($oElement instanceof \Ext_Gui2_Bar_Icon) {
					if(
						$oElement->task === 'export_csv' ||
						$oElement->task === 'export_excel'
					) {
						$aData['export'] = true;
						$bStandardLastBar = true;
					}
				} else if(
					$oElement instanceof \Ext_Gui2_Bar_LoadingIndicator
				) {
					// nothing
					$bStandardLastBar = true;
				}				
			}
			
			if(!$bStandardLastBar) {
				$aArrayBars[] = $this->dumpGuiBar($oLastBar);
			}
		}
		
		$aData['bars'] = $aArrayBars;
		$aData['columns'] = [];
		
		$aColumns = $oGui->getColumnList();
		$aI18nCache = [];
		foreach ($aColumns as $oColumn) {
			
			if($oColumn->i18n && isset($aI18nCache[$oColumn->select_column])) {
				continue;
			} else if($oColumn->i18n) {
				$aI18nCache[$oColumn->select_column] = true;
			}
			
			$aData['columns'][] = $this->dumpGuiColumn($oColumn);
		}

		$aData += $oGui->getJSandCSSFiles();
		
		return $aData;
	}
	
	public function dumpClassAttributes(\Ext_Gui2 $oGui) {
		
		$aClassData = [
			'wdbasic' => $oGui->class_wdbasic,
			'class' => get_class($oGui),
			'data' => $oGui->class_data,
			'date_format' => get_class($oGui->calendar_format),
			'js' => $oGui->class_js
		];
		
		if($oGui->row_icon_status_active instanceof \Ext_Gui2_View_Icon_Interface) {
			$aClassData['icon_status'] = get_class($oGui->row_icon_status_active);
		}
		
		if($oGui->row_icon_status_visible instanceof \Ext_Gui2_View_Icon_Interface) {
			$aClassData['icon_visible'] = get_class($oGui->row_icon_status_visible);
		}
		
		return $aClassData;
	}
	
	public function dumpGuiBar(\Ext_Gui2_Bar $oBar) {
		
		$aBar = [
			'position' => $oBar->position,
			'elements' => []
		];
		
		if($oBar instanceof \Ext_Gui2_Bar_Legend) {
			$aBar['type'] = 'legend';
		}
		
		$aElements = $oBar->getElements();
		
		foreach($aElements as $oElement) {
			
			if($oElement instanceof \Ext_Gui2_Bar_Labelgroup) {
				$aBar['elements'][] = $this->dumpGuiBarLabelGroup($oElement);
			} else if($oElement instanceof \Ext_Gui2_Bar_Seperator) {
				$aBar['elements'][] = [ 'element' => 'separator' ];
			} else if($oElement instanceof \Ext_Gui2_Bar_Icon) {
				$aBar['elements'][] = $this->dumpGuiBarIcon($oElement);
			} else if($oElement instanceof \Ext_Gui2_Bar_Filter) {
				$aBar['elements'][] = $this->dumpGuiBarFilter($oElement);
			}
			
		}
		
		return $aBar;
	}
	
	public function dumpGuiBarLabelGroup(\Ext_Gui2_Bar_Labelgroup $oLabelGroup) {
		$aLabelGroup = [
			'element' => 'labelgroup',
			'label' => $oLabelGroup->label
		];
		return $aLabelGroup;
	}
	
	public function dumpGuiBarIcon(\Ext_Gui2_Bar_Icon $oIcon) {		
		$aIcon = [];
		
		if($oIcon->action === 'new') {
			$aIcon['element'] = 'icon_new';
		} else if($oIcon->action === 'edit') {
			$aIcon['element'] = 'icon_edit';
		} else if($oIcon->task === 'deleteRow') {
			$aIcon['element'] = 'icon_delete';
		} else {
			$aIcon['element'] = 'icon';
			$aIcon['label'] = $oIcon->label;
			$aIcon['task'] = $oIcon->task;
			$aIcon['action'] = $oIcon->action;
			$aIcon['info_text'] = (bool) $oIcon->info_text;
			$aIcon['request_data'] = $oIcon->additional;
			$aIcon['active'] = (int) $oIcon->active;
			$aIcon['img'] = [ 'Ext_Gui2_Util', 'getIcon', '@todo' ];
			$aIcon['access'] = $oIcon->access;
			
			if($oIcon->dialog_data instanceof \Ext_Gui2_Dialog) {
				$aIcon['dialog'] = [ '@todo' ];
			}

		}
		
		return $aIcon;
	}
	
	public function dumpGuiBarFilter(\Ext_Gui2_Config_Basic $oFilter) {
		$aFilter = [];

		if($oFilter instanceof \Ext_Gui2_Bar_Timefilter) {
			
			$aFilter['element'] = 'timefilter';
			$aFilter['label'] = $oFilter->label;
			$aFilter['from'] = [ 
				'alias' => $oFilter->db_from_alias,
				'column' => $oFilter->db_from_column,
				'default' => [ 'Ext_Gui2_Factory_Default', 'getDefaultFilterFrom' ]
			];
			$aFilter['until'] = [ 
				'alias' => $oFilter->db_until_alias,
				'column' => $oFilter->db_until_column,
				'default' => [ 'Ext_Gui2_Factory_Default', 'getDefaultFilterUntil' ]
			];
			$aFilter['searchtype'] = $oFilter->search_type;
			$aFilter['access'] = $oFilter->access;
			$aFilter['text_after'] = $oFilter->text_after;
			
		} else if($oFilter instanceof \Ext_Gui2_Bar_Filter) {
			
			if($oFilter->filter_type === 'select') {
				$aFilter['element'] = 'selectfilter';
				$aFilter['label'] = $oFilter->label;
				$aFilter['searchtype'] = $oFilter->search_type;
				$aFilter['id'] = $oFilter->id;
				$aFilter['name'] = $oFilter->name;
				$aFilter['searchempty'] = (bool) $oFilter->db_emptysearch;
				$aFilter['skip_query'] = $oFilter->skip_query;
				$aFilter['filter_part'] = $oFilter->filter_part;				
				$aFilter['visibility'] = $oFilter->visibility;
				$aFilter['multiple'] = $oFilter->multiple;
				
				if(!is_bool($oFilter->dependency)) {
					$aFilter['dependency'] = $oFilter->dependency;
				}
				
				if(!is_null($oFilter->size)) {
					$aFilter['size'] = $oFilter->size;
				}
				
				if($oFilter->selection instanceof \Ext_Gui2_View_Selection_Filter_Abstract) {
					$aFilter['selection'] = get_class($oFilter->selection);
				} else {
					$aFilter['entries'] = [ '@todo' ];
				}

			} else {
				$aFilter['element'] = 'inputfilter';
				$aFilter['label'] = $oFilter->label;
				$aFilter['searchtype'] = $oFilter->search_type;
				$aFilter['column'] = $oFilter->db_column;
				$aFilter['alias'] = $oFilter->db_alias;
			}

			if(!empty($oFilter->access)) {
				$aFilter['access'] = $oFilter->access;
			}
		}
		
		return $aFilter;
	}
	
//	public function dumpGuiBarLegend(\Ext_Gui2_Bar_Legend $oLegend) {
//		$aLegend = [
//			'element'
//		];
//		return $aLegend;
//	}
	
	public function dumpGuiColumn(\Ext_Gui2_Head $oColumn) {

		$oPurifier = new \Ext_TC_Purifier([]);
		
		$aColumn = [
			'title' => trim($oPurifier->purify($oColumn->title)), // ist übersetzt
			'alias' => $oColumn->db_alias,
			'column' => $oColumn->db_column,
			'data' => $oColumn->select_column,
			'width' => $oColumn->width,		
			'sortable' => (bool) $oColumn->sortable,
			'resize' => (bool) $oColumn->width_resize
		];
		
		if($oColumn->i18n) {
			unset($aColumn['alias']);
			$aColumn['column'] = substr($aColumn['column'], 0, (strlen($aColumn['column']) - 3));
		}
		
		if(!is_null($oColumn->sortable_column)) {
			$aColumn['sortable_column'] = $oColumn->sortable_column;
		}
		
		if(!empty($oColumn->mouseover_title)) {
			$aColumn['description'] = $oColumn->mouseover_title;
		}
		
		if($oColumn->format instanceof \Ext_Gui2_View_Format_Interface) {
			$aColumn['format'] = get_class($oColumn->format);
		}
		
		if($oColumn->post_format instanceof \Ext_Gui2_View_Format_Interface) {
			$aColumn['post_format'] = get_class($oColumn->post_format);
		}
		
		if(!empty($oColumn->i18n)) {
			$aColumn['i18n'] = $oColumn->i18n;
			unset($aColumn['i18n']['language']);
			unset($aColumn['i18n']['original_title']);
		}
		
		return $aColumn;
	}
}


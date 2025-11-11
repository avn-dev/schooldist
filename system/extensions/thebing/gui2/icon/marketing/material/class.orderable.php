<?php
 
class Ext_Thebing_Gui2_Icon_Marketing_Material_Orderable extends Ext_Gui2_View_Icon_Active {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Löschen nur erlauben, wenn es keine entsprechenden Zusatzvertrag gibt
		if(
			$oElement->action == 'active' ||
			$oElement->action == 'inactive'
		) {
		
			if(
				$oElement->action == 'active' &&
				$aRowData[0]['orderable'] == 1
			){
				return 0;
			}else if(
				$oElement->action == 'inactive' &&
				$aRowData[0]['orderable'] == 0
			){
				return 0;
			}else{
				return 1;
			}

		} else {
			return 1;
		}

	}

}
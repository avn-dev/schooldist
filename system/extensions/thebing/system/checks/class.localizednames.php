<?php

class Ext_Thebing_System_Checks_LocalizedNames extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Repair localized labels';
		return $sTitle;
	}

	public function executeCheck(){

		global $system_data;

		Ext_Thebing_Util::updateLanguageFields();

		Ext_Thebing_Util::backupTable('customer_db_3');
		Ext_Thebing_Util::backupTable('customer_db_8');
		Ext_Thebing_Util::backupTable('customer_db_10');
		Ext_Thebing_Util::backupTable('customer_db_11');
		Ext_Thebing_Util::backupTable('customer_db_24');

		$aTables = array(
			'customer_db_3'=>array(
				'name'=>'ext_33',
				'changed'=>'last_changed'
			),
			'customer_db_8'=>array(
				'short'=>'ext_4',
				'name'=>'ext_1',
				'changed'=>'changed'
			),
			'customer_db_10'=>array(
				'short'=>'ext_1',
				'name'=>'ext_4',
				'changed'=>'changed'
			),
			'customer_db_11'=>array(
				'short'=>'ext_1',
				'name'=>'ext_4',
				'changed'=>'changed'
			),
			'customer_db_24'=>array(
				'name'=>'ext_1',
				'changed'=>'changed'
			),
		);

		try {

			foreach((array)$system_data['allowed_languages'] as $sLang => $sName){

				foreach((array)$aTables as $sTable=>$aTable) {

					if($aTable['name']) {
						$sSql = " UPDATE
										#table
									SET
										#field = #source,
										#changed = #changed
									WHERE
										#field IS NULL OR
										#field = ''
									";
						$aSql = array('table'=>$sTable, 'changed'=>$aTable['changed'], 'source'=>$aTable['name'], 'field' => 'name_'.$sLang);
						DB::executePreparedQuery($sSql, $aSql);
					}

					if($aTable['short']) {
						$sSql = " UPDATE
										#table
									SET
										#field = #source,
										#changed = #changed
									WHERE
										#field IS NULL OR
										#field = ''
									";
						$aSql = array('table'=>$sTable, 'changed'=>$aTable['changed'], 'source'=>$aTable['short'], 'field' => 'short_'.$sLang);
						DB::executePreparedQuery($sSql, $aSql);
					}

				}

			}

			// Alte Spalten entfernen
			foreach((array)$aTables as $sTable=>$aTable) {
				if($aTable['short']) {
					$sSql = "ALTER TABLE #table DROP #field";
					$aSql = array('table'=>$sTable, 'field'=>$aTable['short']);
					DB::executePreparedQuery($sSql, $aSql);
				}
				if($aTable['name']) {
					$sSql = "ALTER TABLE #table DROP #field";
					$aSql = array('table'=>$sTable, 'field'=>$aTable['name']);
					DB::executePreparedQuery($sSql, $aSql);
				}
			}

		} catch(Pdf_Exception $e) {
		} catch(Exception $e) {
		}

		return true;

	}

}
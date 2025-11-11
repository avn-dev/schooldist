<?php

class Ext_Thebing_Db_Clean {

	/*
	 * array(
	 *	'kolumbus_inquiries' => array(
	 *		'primary_key'=>'id',
	 *		'foreign_key'=>'crs_partnerschool'
	 *		'childs'=>array(
	 *			'kolumbus_inquiries_courses'=>array(
	 *				'primary_key'=>'id',
	 *				'foreign_key'=>'inquiry_id',
	 *				'childs'=>array(
	 *
	 *				)
	 *			)
	 *		)
	 *	)
	 * )
	 *
	 *
	 */
	public function execute($aStructure, $aParentIds=null) {

		foreach((array)$aStructure as $sTable=>$aTable) {

			Ext_Thebing_Util::backupTable($sTable, true);

			/*
			 * Datensätze löschen
			 */
			if(
				$aParentIds !== null
			) {

				$aSql = array();
				$aSql['table'] = $sTable;

				$sSql = "
					DELETE FROM
						#table
					WHERE
						#foreign_key NOT IN (:parent_id)
				";
				$aSql['parent_id'] = $aParentIds;
				$aSql['foreign_key'] = $aTable['foreign_key'];

				DB::executePreparedQuery($sSql, $aSql);

				$sSql = "OPTIMIZE TABLE #table";
				$aSql = array('table'=>$sTable);
				DB::executePreparedQuery($sSql, $aSql);

			}

			/**
			 * Die IDs aller restlichen Datensätze auslesen und Rekursion aufrufen
			 */
			$aSql = array();
			$aSql['table'] = $sTable;
			$aSql['primary_key'] = $aTable['primary_key'];

			$sSql = "
				SELECT
					#primary_key
				FROM
					#table
					";
			$aIds = DB::getQueryCol($sSql, $aSql);

			if(!empty($aTable['childs'])) {
				$this->execute($aTable['childs'], $aIds);
			}

		}

	}

}
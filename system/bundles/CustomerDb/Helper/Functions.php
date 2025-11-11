<?php

namespace CustomerDb\Helper;

class Functions {
	
	static public function getCustomerFields($sCustomerDb, $sOrderBy="name", $bCompleteFieldNames=false) {

		$aStandard = array("id"=>"ID", "email"=>"E-Mail", "nickname"=>"Nickname", "password"=>"Passwort", "changed"=>"letzte &Auml;nderung", "last_login"=>"letzter Login", "changed_by"=>"ge&auml;ndert von", "created"=>"Erstellungszeitpunkt", "views"=>"Aufrufe", "groups"=>"Gruppen", "access_code"=>"Zugriffscode");

		$arrCustomerFields = $aStandard;

		$res = (array)\DB::getQueryRows("SELECT field_nr,name FROM customer_db_definition WHERE db_nr = '".$sCustomerDb."' AND field_nr != '0' AND active = 1 ORDER BY ".$sOrderBy."");
		foreach($res as $my) {
			if($bCompleteFieldNames) {
				$arrCustomerFields["ext_".$my['field_nr']] = $my['name'];
			} else {
				$arrCustomerFields[$my['field_nr']] = $my['name'];
			}
		}

		return $arrCustomerFields;
	}

	static public function getCustomerTables() {

		$arrCustomerTables = \DB::getQueryPairs("SELECT `id`, `db_name` FROM customer_db_config WHERE active = 1 ORDER BY db_name ");

		return $arrCustomerTables;
	}

	static public function getCustomerGroups($sCustomerDb=0) {

		$arrCustomerGroups = array();

		if($sCustomerDb > 0) {
			$res_groups = (array)\DB::getQueryRows("SELECT * FROM customer_groups WHERE db_nr = '".$sCustomerDb."' AND active = 1 ORDER BY name");
			foreach($res_groups as $my_groups) {
				$arrCustomerGroups[$my_groups['id']] = $my_groups['name'];
			}
		} else {
			
			$sSql = "
				SELECT 
					c.*,
					g.id group_id,
					g.name group_name
				FROM 
					customer_db_config c LEFT JOIN
					customer_groups g ON
						c.id = g.db_nr AND
						g.active = 1
				WHERE 
					c.active = 1 
				ORDER BY 
					c.db_name,
					g.name
			";
			$res = (array)\DB::getQueryRows($sSql);
			foreach($res as $my) {
				if(empty($arrCustomerGroups[''.$my['id'].'|0'])) {
					$arrCustomerGroups[''.$my['id'].'|0'] = $my['db_name']." &raquo; ".\L10N::t('Alle Eintr&auml;ge', 'CMS');
				}
				$arrCustomerGroups[''.$my['id'].'|'.$my['group_id'].''] = $my['db_name']." &raquo; ".$my['group_name'];
			}
		}

		return $arrCustomerGroups;
	}
	
}

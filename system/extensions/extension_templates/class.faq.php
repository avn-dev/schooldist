<?php
/*
 * Created on 07.03.2008
 * 
 * forms for editing, deleting an to create extension templates categories
 * 
 * @author Carsten Fritsch
 * 
 */

 Access_Backend::checkAccess("modules_admin");
 
 
 class Ext_ExtensionTemplates_FAQ
 {
 	private $_objDB;	
 	private $_arrData = array();
 	private $_intId = 0;
 
 	public function __construct($intId, &$objDB)
 	{
 		$this->_objDB = $objDB;
 		$this->_intId = $intId;
 		if (!isset($intId) || $intId == 0) {
 			$this->_add();
 		}

	 	$this->_fetchData(); 

 	}
 	
 	private function _fetchData()
 	{	
 		$strSql = "
 					SELECT 
						* 
					FROM 
						`faq_entries` 
					WHERE 
						`id` = :intId";
		$arrSql = array("intId"=>$this->_intId);
		$arrData = $this->_objDB->preparedQueryData($strSql, $arrSql);
		$this->_arrData = $arrData[0];
 	}
 	
 	public function __get($strField) {
 		
 		if(isset($this->_arrData[$strField])) {
 			return $this->_arrData[$strField];
 		}
 		
 	}
 	
 	public function __set($strField, $mixValue) {
 		
 		if(isset($this->_arrData[$strField])) {
 			$this->_arrData[$strField] = $mixValue;
 		}
 		
 	}

 	public function save() {
 		
 		$strSql = "
 					UPDATE 
						`faq_entries` 
					SET
						`frage` = :strFrage,
						`antwort` = :strAntwort,
						`idCategory` = :intCategory
					WHERE 
						`id` = :intId";
		$arrSql = array();
		$arrSql['intId'] = $this->_arrData['id'];
		$arrSql['strFrage'] = $this->_arrData['frage'];
		$arrSql['strAntwort'] = $this->_arrData['antwort'];
		$arrSql['intCategory'] = $this->_arrData['idCategory'];
		$arrData = $this->_objDB->preparedQueryData($strSql, $arrSql);
 		
 	}
 	
 	private function _add() {
 		
 		$strSql = "
 					INSERT INTO 
						`faq_entries`
					SET
						`id` = ''
				";
		$this->_objDB->query($strSql);
 		$this->_intId = $this->_objDB->getInsertId();

 	}
 	
 	public function delete() {
 		
 		$strSql = "
 					DELETE FROM 
						`faq_entries` 
					WHERE 
						`id` = :intId";
		$arrSql = array();
		$arrSql['intId'] = $this->_arrData['id'];
		$this->_objDB->preparedQuery($strSql, $arrSql);

 	}
 }
 
?>

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
 
 
 class Ext_License
 {
 	private $_objDB;	
 	private $_arrData = array();
 	private $_intId = 0;
 	private $_strLicenseTable = 'license';
 
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
						#strTableName 
					WHERE 
						`id` = :intId";
		$arrSql = array("intId"=>$this->_intId, 'strTableName'=>$this->_strLicenseTable);
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
						#strTableName 
					SET
						`name` = :strName,
						`domain` = :strDomain,
						`domain_check` = :intCheck,
						`license_key` = :intKey,
						`version` = :intVersion,
						`validfrom` = :strStart,
						`validuntil` = :strEnde,
						`encode` = :encode
						
					WHERE 
						`id` = :intId";
		$arrSql = array('strTableName'=>$this->_strLicenseTable);
		$arrSql['intId'] = $this->_arrData['id'];
		$arrSql['strName'] = $this->_arrData['name'];
		$arrSql['strDomain'] = $this->_arrData['domain'];
		if($this->_arrData['domain_check']==true){ $checked=1; } else{ $checked=0;}
		$arrSql['intCheck'] = $checked;
		$arrSql['intKey'] = $this->_arrData['license_key'];
		$arrSql['strStart'] = $this->_arrData['validfrom'];
		$arrSql['strEnde'] = $this->_arrData['validuntil'];
		$arrSql['intVersion'] = $this->_arrData['version'];
		$arrSql['encode'] = (int)$this->_arrData['encode'];
		$arrData = $this->_objDB->preparedQueryData($strSql, $arrSql);
 		
 	}
 	
 	private function _add() {
 		
 		$strSql = "
 					INSERT INTO 
						#strTableName 
					SET
						`id` = ''
				";
		$arrSql = array('strTableName'=>$this->_strLicenseTable);
		$this->_objDB->preparedQuery($strSql, $arrSql);
 		$this->_intId = $this->_objDB->getInsertId();

 	}
 	
 	public function delete() {
 		
 		$strSql = "
 					DELETE FROM 
						#strTableName 
					WHERE 
						`id` = :intId";
		$arrSql = array('strTableName'=>$this->_strLicenseTable);
		$arrSql['intId'] = $this->_arrData['id'];
		$this->_objDB->preparedQuery($strSql, $arrSql);

 	}
 }
 
?>
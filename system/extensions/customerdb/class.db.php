<?php

class Ext_CustomerDB_DB {
	
	protected $_aConfig = array();
		
	function __construct($iDbId) {
		
		$this->_aConfig['id'] = (int)$iDbId;
		$this->_getConfig();
		
	}
	
	protected function _getConfig() {
		
		$this->_aConfig = DB::getRowData('customer_db_config', $this->_aConfig['id']);

		if(empty($this->_aConfig)) {
			return false;
		}
		
		if($this->_aConfig['external_table']) {
			$this->_aConfig['db_id'] = $this->_aConfig['external_table_pk'];
			$this->_aConfig['db_active'] = $this->_aConfig['external_table_active'];
			$this->_aConfig['db_user'] = $this->_aConfig['external_table_user'];
			$this->_aConfig['db_email'] = $this->_aConfig['external_table_email'];
			$this->_aConfig['db_pass'] = $this->_aConfig['external_table_pass'];
			$this->_aConfig['db_table'] = $this->_aConfig['external_table'];
			$this->_aConfig['db_accesscode'] = $this->_aConfig['external_table_accesscode'];
			$this->_aConfig['db_groups'] = $this->_aConfig['external_table_groups'];
		} else {
			$this->_aConfig['db_id'] = "id";
			$this->_aConfig['db_active'] = "active";
			$this->_aConfig['db_email'] = "email";
			$this->_aConfig['db_user'] = "nickname";
			$this->_aConfig['db_pass'] = "password";
			$this->_aConfig['db_table'] = "customer_db_".intval($this->_aConfig['id']);
			$this->_aConfig['db_accesscode'] = "access_code";
			$this->_aConfig['db_groups'] = "groups";
		}
		
		return true;

	}
	
	public function __get($sField) {
		if(isset($this->_aConfig[$sField])) {
			return $this->_aConfig[$sField];
		}
	}
	
	public function __set($sField, $mValue) {
		if(isset($this->_aConfig[$sField])) {
			$this->_aConfig[$sField] = $mValue;
		}
	}
	
	public function getCustomer($iCustomerId) {
		
		$aCustomer = $this->getCustomerByUniqueField('id', (int)$iCustomerId);
		return $aCustomer;
		
	}

	public function getQuerySelectPart() {

	    $sQueryPart = "
            #field_id `id`,						
            #field_user `nickname`,
            #field_password `password`,
            #field_active `active`
        ";

        if(!empty($this->_aConfig['db_accesscode'])) {
            $sQueryPart .= ", #field_accesscode `accesscode`";
        }

        if(!empty($this->_aConfig['db_email'])) {
            $sQueryPart .= ", #field_email `email`";
        }

        return $sQueryPart;
    }

	public function getCustomerByUniqueField($sField, $mValue) {
		
		$sSql = "
					SELECT 
						*,
		";

		$sSql .= $this->getQuerySelectPart();

        $sSql .= "
					FROM
						#table 
					WHERE 
						#field_where = :value_where AND 
						#field_active = 1";
		$aSql = array();
		$aSql['table'] = $this->_aConfig['db_table'];
		$aSql['field_id'] = $this->_aConfig['db_id'];
		$aSql['field_email'] = $this->_aConfig['db_email'];
		$aSql['field_user'] = $this->_aConfig['db_user'];
		$aSql['field_password'] = $this->_aConfig['db_pass'];
		$aSql['field_active'] = $this->_aConfig['db_active'];
		$aSql['field_accesscode'] = $this->_aConfig['db_accesscode'];
		$aSql['field_where'] = $this->_aConfig['db_'.$sField];
		$aSql['value_where'] = $mValue;

		$aCustomer = DB::getPreparedQueryData($sSql, $aSql);
		if(!empty($aCustomer)) {
			$aCustomer = reset($aCustomer);
		}
		
		return $aCustomer;
		
	}	

	public function searchCustomers($aSearch=array(), $sOrderBy="") {
		
		// fulltext search
		if(!is_array($aSearch)) {
			$sSearch = $aSearch;
			$aFields = DB::getQueryData("DESCRIBE `".$this->_aConfig['db_table']."`");

			$aSearch = array();
			foreach((array)$aFields as $aField) {
				if($aField['Type'] != 'timestamp') {
					$aSearch[$aField['Field']] = $sSearch;
				}
			}
		}
		
		$aSql = array();
		
		$sSql = "
					SELECT 
						*,
						".$this->getQuerySelectPart()."
					FROM
						#table 
					WHERE 
						#field_active = 1 AND
						(
				";
		
		$bWhere = 0;
		foreach((array)$aSearch as $sField=>$mValue) {
			if(!empty($mValue)) {
				$sSql .= " #field_".$sField." LIKE :value_".$sField." OR ";
				$aSql["field_".$sField] = $sField;
				$aSql["value_".$sField] = '%'.$mValue.'%';
				$bWhere = 1;
			}
		}

		if($bWhere) {
			$sSql .= " 0) ";
		} else {
			$sSql .= " 1) ";
		}

		if($sOrderBy) {
			$sSql .= "ORDER BY #order_by";
			$aSql['order_by'] = $sOrderBy;
		}
		
		$aSql['table'] = $this->_aConfig['db_table'];
		$aSql['field_id'] = $this->_aConfig['db_id'];
		$aSql['field_email'] = $this->_aConfig['db_email'];
		$aSql['field_user'] = $this->_aConfig['db_user'];
		$aSql['field_password'] = $this->_aConfig['db_pass'];
		$aSql['field_active'] = $this->_aConfig['db_active'];
		$aSql['field_accesscode'] = $this->_aConfig['db_accesscode'];
		//$aSql['field_where'] = $this->_aConfig['db_'.$sField];
		//$aSql['value_where'] = $mValue;

		$aCustomers = DB::getPreparedQueryData($sSql, $aSql);
		
		return $aCustomers;
		
	}	

	public function getFieldValues($sField) {
		
		$sSql = "
					SELECT 
						#field `field`
					FROM
						#table 
					WHERE 
						#field_active = 1
					GROUP BY
						#field
					ORDER BY
						#field
						";
		$aSql = array();
		$aSql['table'] = $this->_aConfig['db_table'];
		$aSql['field_active'] = $this->_aConfig['db_active'];
		$aSql['field'] = $sField;
		$aValues = DB::getPreparedQueryData($sSql, $aSql);
		
		return $aValues;
		
	}	

	public function updateCustomerField($iCustomerId, $sField, $mValue) {
		
		if(
			$sField == 'password' &&
			$this->_aConfig['db_encode_pw'] == 1
		) {
			$mValue = password_hash($mValue, PASSWORD_DEFAULT);
		}
		
		if(isset($this->_aConfig['db_'.$sField])) {
			$sField = $this->_aConfig['db_'.$sField];
		}
		
		$sSql = "
					UPDATE
						#table 
					SET
						#field_update = :value_update 
					WHERE
						#field_id = :id AND
						#field_active = 1
					LIMIT 1
						";
		$aSql = array();
		$aSql['table'] = $this->_aConfig['db_table'];
		$aSql['field_id'] = $this->_aConfig['db_id'];
		$aSql['field_active'] = $this->_aConfig['db_active'];
		$aSql['field_update'] = $sField;
		$aSql['value_update'] = $mValue;
		$aSql['id'] = (int)$iCustomerId;
		
		DB::executePreparedQuery($sSql, $aSql);
		
	}

	public function deleteCustomer($iCustomerId) {
		
		$sSql = "
					UPDATE
						#table 
					SET
						#field_active = 0
					WHERE
						#field_id = :id						
					LIMIT 1
						";
		$aSql = array();
		$aSql['table'] = $this->_aConfig['db_table'];
		$aSql['field_id'] = $this->_aConfig['db_id'];
		$aSql['field_active'] = $this->_aConfig['db_active'];
		$aSql['id'] = (int)$iCustomerId;
		
		DB::executePreparedQuery($sSql, $aSql);

	}

	public function insertCustomer($aValues) {

		if($this->_aConfig['db_encode_pw'] == 1) {
			$aValues['password'] = md5($aValues['password']);
		}

		$sSql = "
					INSERT INTO
						#table 
					SET
						#field_id = :id,
						#field_email = :email,
						#field_user = :nickname,
						#field_password = :password,
						#field_active = :active,
						#field_accesscode = :accesscode
					";
		$aSql = array();
		$aSql['table'] = (string)$this->_aConfig['db_table'];
		$aSql['field_id'] = (string)$this->_aConfig['db_id'];
		$aSql['field_email'] = (string)$this->_aConfig['db_email'];
		$aSql['field_user'] = (string)$this->_aConfig['db_user'];
		$aSql['field_password'] = (string)$this->_aConfig['db_pass'];
		$aSql['field_active'] = (string)$this->_aConfig['db_active'];
		$aSql['field_accesscode'] = (string)$this->_aConfig['db_accesscode'];

		$aSql['id'] = (string)$aValues['id'];
		$aSql['email'] = (string)$aValues['email'];
		$aSql['nickname'] = (string)$aValues['nickname'];
		$aSql['password'] = (string)$aValues['password'];
		$aSql['active'] = (string)$aValues['active'];
		$aSql['accesscode'] = (string)$aValues['accesscode'];

		DB::executePreparedQuery($sSql, $aSql);

		$iCustomerId = DB::fetchInsertId();

		return $iCustomerId;

	}

	public function createActivationCode($iCustomerId) {
		
		$aInsert = [
			'id_user' => $iCustomerId,
			'id_table' => $this->_aConfig['id']
		];
		
		$aCheck = DB::getJoinData('customer_db_activation', $aInsert);		

		if(!empty($aCheck)) {
			$aCheck = reset($aCheck);
			$sActivationCode = $aCheck['activation_key'];
		} else {
			$sActivationCode = Util::generateRandomString(16);
			$aInsert['activation_key'] = $sActivationCode;
			DB::insertData('customer_db_activation', $aInsert);
		}
		
		return $sActivationCode;
	}

	public function verifyPassword($iUserId, $sPassword) {

		$aUser = $this->getCustomer($iUserId);

		if(
			(
				$this->_aConfig['db_encode_pw'] == 0 &&
				$aUser[$this->_aConfig['db_pass']] === $sPassword
			) ||
			(
				$this->_aConfig['db_encode_pw'] == 1 &&
				password_verify($sPassword, $aUser[$this->_aConfig['db_pass']])
			)
		) {
			return true;
		}

		return false;
	}

}

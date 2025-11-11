<?php

namespace TcApi\Entity;

class ApiToken extends \Ext_TC_Basic {

	protected $_sTable = 'tc_wdmvc_tokens';

	protected $_sTableAlias = 'tc_wt';

	protected $_aJoinTables = array(
		'ips' => array(
			'table' => 'tc_wdmvc_tokens_ips',
			'primary_key_field' => 'token_id',
			'foreign_key_field' => 'ip'
		),
		'applications' => array(
			'table' => 'tc_wdmvc_tokens_applications',
			'primary_key_field' => 'token_id',
			'foreign_key_field' => 'application'
		)
	);

	public function getIPs(): array {
		return array_map(fn ($ip) => trim($ip), $this->ips);
	}

	/**
	 * ips exploden für die speicherung
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue){
		if($sName == 'ips_concat'){
			$mValue = explode(',', $mValue);
			foreach($mValue as $iKey => $sValue){
				$sValue = trim($sValue);
				if(!empty($sValue)) {
					$mValue[$iKey] = $sValue;
				} else {
					unset($mValue[$iKey]);
				}
			}
			$this->ips = $mValue;
		} else {
			parent::__set($sName, $mValue);
		}
	}

	/**
	 * ips und token entsprechend formatieren
	 * @param string $sName
	 * @return mixed
	 */
	public function __get($sName){
		if($sName == 'ips_concat'){
			$aIps = $this->ips;
			return implode(', ', $aIps);
		} else if($sName == 'token'){
			$sToken = parent::__get($sName);
			if(empty($sToken)){
				$sToken = $this->_generateToken();
				$this->token = $sToken;
			}
			return $sToken;
		}  else {
			return parent::__get($sName);
		}
	}

	/**
	 * generiert einen token
	 * @return string
	 */
	protected function _generateToken() {
		// TODO vllt. \Illuminate\Support\Str::random(30); statt md5?
		$sToken = md5(uniqid());
		return $sToken;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ", 
			GROUP_CONCAT(DISTINCT `applications`.`application` SEPARATOR ', ') `application`, 
			GROUP_CONCAT(DISTINCT `ips`.`ip` SEPARATOR ', ') `ip`";

	}

}

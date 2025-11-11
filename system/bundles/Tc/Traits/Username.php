<?php

namespace Tc\Traits;

trait Username {

 	public function generateUsername($sName) {
		// Whitespaces, Punctuation, Sonderzeichen usw. entfernen [^a-zA-Z0-9_]
		$sName = preg_replace('/[\W_]+/u', '', $sName);
		$sName = iconv('UTF-8', 'ASCII//TRANSLIT', $sName);

		if(empty($sName)) {
			//$sName = Ext_TC_Util::generateRandomString(16);
			throw new \RuntimeException('Empty name for generateUsername'); // #12543
		}

		do {
			if($iCount == 3000) {
				throw new \RuntimeException('Maximum count reached');
			}

			if($iCount > 0) {
				$sNameCheck = $sName.$iCount;
			} else {
				$sNameCheck = $sName;
			}

			$iCount++;
		} while(!$this->checkUniqueUsername($sNameCheck));

		return $sNameCheck;
	}

	/**
	 * Überprüfen ob Username schon existiert, siehe auch function _getUserNameColumn()
	 *
	 * @param string $sNameCheck
	 * @return bool
	 */
	public function checkUniqueUsername($sNameCheck) {

		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`".$this->usernameColumn."` = :username AND
				`id` != :self_id
		";

		$aSql = array();
		$aSql['table'] = $this->_sTable;
		$aSql['username'] = $sNameCheck;
		$aSql['self_id'] = (int)$this->id;

		$aResult = \DB::getPreparedQueryData($sSql, $aSql);

		return empty($aResult);
	}

}

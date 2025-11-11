<?php

namespace Cms\Entity;

class Content extends \WDBasic {
	
	protected $_sTable = 'cms_content';
	protected $_sTableAlias = 'cms_c';

	/**
	 * Abgeleitet weil active hier eine andere Funktion hat
	 * 
	 * @return boolean
	 */
	public function delete() {

		$oDB = $this->getDbConnection();

		// if no active field and no errors
		// delete the entry and remove the object reference
		$sSql = "DELETE FROM #table WHERE `id` = :id LIMIT 1";
		$aSql = array(
			'table' => $this->_sTable,
			'id' => $this->_aData[$this->_sPrimaryColumn]
		);
		$oDB->preparedQuery($sSql, $aSql);

		return true;
	}

}
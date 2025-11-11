<?php

namespace TsCompany\Entity;

class Upload extends \Ext_Thebing_Basic {

	protected $_sTable = 'ts_companies_uploads';

	public static function getList($iCompanyId, $sType = 'pdf'){

		return self::getRepository()->findBy(['company_id' => $iCompanyId, 'type' => $sType]);

	}

}

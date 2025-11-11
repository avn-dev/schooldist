<?php

class Ext_TS_Accounting_Bookingstack_Numberrange_Contact extends Ext_TS_NumberRange {

	protected $_sNumberTable = 'ts_accounting_company_contact_numberranges';
	protected $_sNumberField = 'number';

    /**
     * @var \TsAccounting\Entity\Company
     */
    protected $_oCompany;

    /**
     * @var Ext_TC_Contact
     */
    protected $_oContact;

    protected $_sType = 'customer';

    public function __construct($iDataID, \TsAccounting\Entity\Company $oCompany, Ext_TC_Contact $oContact, $sType = 'customer') {
        $this->_oCompany    = $oCompany;
        $this->_oContact    = $oContact;
        $this->_sType       = $sType;
        parent::__construct($iDataID);
    }

    /**
     * generiert und speichert eine neue nummer falls noch keine vorhanden ist
     *
     * @return mixed
     */
    public function createNumber() {

        if(
            $this->id <= 0 ||
            $this->_oContact->getId() <= 0 ||
            $this->_oCompany->getId() <= 0
        ) {
            return '';
        }

        if(!$this->hasNumber()) {

			if(!$this->acquireLock()) {
				return false;
			}

            $this->removeOldNumbers();
            $sNumber = $this->generateNumber();

            $sSql = "
                INSERT INTO #table SET
                    `numberrange_id`    = :number_id,
                    `company_id`        = :company_id,
                    `contact_id`        = :contact_id,
                    `type`              = :type,
                    #numberfield        = :numnber
            ";

            $aSql = array();
            $aSql['number_id']      = $this->id;
            $aSql['company_id']     = $this->_oCompany->getId();
            $aSql['contact_id']     = $this->_oContact->getId();
            $aSql['type']           = $this->_sType;
            $aSql['numnber']        = $sNumber;
            $aSql['numberfield']    = $this->_sNumberField;
            $aSql['table']          = $this->_sNumberTable;

            DB::executePreparedQuery($sSql, $aSql);

            $this->removeLock();

        } else {

            $sNumber = $this->getLastNumber();

        }

        return $sNumber;
    }

    public function removeOldNumbers() {

        $sSql = "
			DELETE
			FROM
				#table
			WHERE
				`contact_id`    = :contact_id AND
                `company_id`    = :conpany_id AND
                `type`          = :type
				";

        $aSql = array(
            'conpany_id'        => $this->_oCompany->getId(),
            'contact_id'        => $this->_oContact->getId(),
            'type'              => $this->_sType,
			'table'             => $this->_sNumberTable
        );

        DB::executePreparedQuery($sSql, $aSql);
    }

    /**
     * search the contact number fot the given Company
     * @return string
     */
    public function getLastNumber() {

        $sSql = "
			SELECT
				`number`
			FROM
				#table
			WHERE
				`contact_id`    = :contact_id AND
                `company_id`    = :conpany_id AND
                `type`          = :type
			LIMIT 1
				";

        $aSql = array(
            'conpany_id'    => $this->_oCompany->getId(),
            'contact_id'    => $this->_oContact->getId(),
            'type'          => $this->_sType,
			'table'         => $this->_sNumberTable
        );

        $aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
        $aResult = reset($aResult);

        if($aResult){
            return $aResult['number'];
        }

        return null;
    }

    /**
     * check if the contact has a number fot the given Company
     * @return boolean
     */
    public function hasNumber() {

        $sLastNumber = $this->getLastNumber();

        if($sLastNumber){
            return true;
        }

        return false;
    }

	protected function executeSearchLatestNumber($sSql, $aSql) {

		$sSql = "
			SELECT
				{$this->buildLatestNumberQuerySelect()}
			FROM
				#table
			WHERE
				`numberrange_id` = :numberrange_id AND
				#number_field LIKE :pattern AND
                `company_id` = :company_id AND
                `type` = :type
			ORDER BY
				`last_number` DESC
			LIMIT 1
		";

		$aSql['company_id'] = $this->_oCompany->getId();
		$aSql['contact_id'] = $this->_oContact->getId();
		$aSql['type'] = $this->_sType;

		return parent::executeSearchLatestNumber($sSql, $aSql);

	}

}
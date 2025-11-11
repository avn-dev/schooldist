<?php

namespace TsAccounting\Entity\Company;

use DB;
use Ext_Thebing_Client_Inbox;
use Ext_Thebing_Inquiry_Document_Version_Item;
use Ext_Thebing_School;

class TemplateReceiptText extends \TsAccounting\Entity\Company\CombinationAbstract
{
	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_accounting_company_template_receipt_text';

	/**
	 * Table alias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'ts_ctrt';

	/**
	 *
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';


	/**
	 * Format
	 *
	 * @var array
	 */
	protected $_aFormat = array(

		'name' => array(
			'required' => true
		),
		'type' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		),
		'based_on' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		),

	);

	/**
	 * JoinTables
	 *
	 * @var array
	 */
	protected $_aJoinTables = array(
		'companies' => array(
			'table' => 'ts_accounting_company_template_receipt_text_to_companies',
			'foreign_key_field' => 'company_id',
			'primary_key_field' => 'receipt_text_id',
			'autoload' => true,
		),
		'schools' => array(
			'table' => 'ts_accounting_company_template_receipt_text_to_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'receipt_text_id',
			'autoload' => true,
		),
		'inboxes' => array(
			'table' => 'ts_accounting_company_template_receipt_text_to_inboxes',
			'foreign_key_field' => 'inbox_id',
			'primary_key_field' => 'receipt_text_id',
			'autoload' => true,
		),
	);

	/**
	 *
	 * @param int $iDataID
	 * @param string $sTable
	 */
	public function __construct($iDataID = 0, $sTable = null)
	{
		// Dynamisch mit allen Verfügbaren Elementen generieren
		$aBasedOn = \TsAccounting\Helper\Company\ReceiptTextBasedOn::getAttributes();

		foreach ($aBasedOn as $sAttribute) {
			$this->_aAttributes[$sAttribute] = array(
				'class' => 'WDBasic_Attribute_Type_Varchar'
			);
		}

		parent::__construct($iDataID, $sTable);
	}

	/**
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param string $sType position|vat|claim_debt
	 */
	public function findText(Ext_Thebing_Inquiry_Document_Version_Item $oItem = null, $sType = 'position')
	{

		if ($oItem === null) {
			return $this->$sType;
		}

		$iBasedOn = $this->based_on;
		$oDocument = $oItem->getDocument(true);
		$sDocumentType = $oDocument->type;
		$sItemType = $oItem->type;
		$sAttributeKey = '';

		if ($sDocumentType == 'manual_creditnote') {
			$sItemType = 'commission';
		} else if (
			$sItemType == 'extraPosition' ||
			$sItemType == 'extra_position'
		) {
			$sItemType = 'extra';
		} else if ($sItemType == 'extra_nights') {
			$sItemType = 'extra_night';
		} else if ($sItemType == 'extra_weeks') {
			$sItemType = 'extra_week';
		}

		switch ($iBasedOn) {
			// Rechnungstyp basierend
			case 1:
				$sAttributeKey = $sDocumentType . '_' . $sType;
				break;
			// Item Typ bassieren
			case 2:
				if ($sType == 'position') {
					$sAttributeKey = $sItemType;
				} else {
					$sAttributeKey = $sType;
				}
				break;
			// Rechnung und Item Typ bassieren
			case 3:
				// nur bei normalen pos. ist das pro leistung
				if ($sType == 'position') {
					$sAttributeKey = $sDocumentType . '_' . $sItemType;
				} else {
					$sAttributeKey = $sDocumentType . '_' . $sType;
				}
				break;
		}

		$sText = $this->$sAttributeKey;

		return $sText;
	}

	static public function searchByCombination(\TsAccounting\Entity\Company $oCompany, Ext_Thebing_School $oSchool, Ext_Thebing_Client_Inbox $oInbox = null)
	{
		$sSql = " 
            SELECT 
                `ts_ctrt`.`id` 
            FROM 
                `ts_accounting_company_template_receipt_text` `ts_ctrt` INNER JOIN
                `ts_accounting_company_template_receipt_text_to_companies` `ts_ctrt_to_c` ON
                    `ts_ctrt_to_c`.`receipt_text_id` = `ts_ctrt`.`id` INNER JOIN
                `ts_accounting_company_template_receipt_text_to_schools` `ts_ctrt_to_s` ON
                    `ts_ctrt_to_s`.`receipt_text_id` = `ts_ctrt`.`id` LEFT JOIN
                `ts_accounting_company_template_receipt_text_to_inboxes` `ts_ctrt_to_i` ON
                    `ts_ctrt_to_i`.`receipt_text_id` = `ts_ctrt`.`id`
            WHERE
				`ts_ctrt`.`active` = 1 AND
                `ts_ctrt_to_c`.`company_id` = :company_id AND
                `ts_ctrt_to_s`.`school_id` = :school_id
            ";

		$aSql = array(
			'school_id' => $oSchool->getId(),
			'company_id' => $oCompany->getId()
		);

		if ($oInbox) {
			$sSql .= ' AND (
					`ts_ctrt_to_i`.`inbox_id` = :inbox_id OR 
					`ts_ctrt_to_i`.`inbox_id` IS NULL
				) ';
			$aSql['inbox_id'] = $oInbox->getId();
		}

		$sSql .= '
            GROUP BY 
                `ts_ctrt`.`id`
            LIMIT 1';

		$iReceiptText = DB::getQueryOne($sSql, $aSql);

		$oReceipText = null;

		if ($iReceiptText > 0) {
			$oReceipText = self::getInstance($iReceiptText);
		}

		return $oReceipText;
	}

	/**
	 *
	 * @param bool $bLog
	 * @return TemplateReceiptText
	 */
	public function save($bLog = true)
	{
		$iBasedOn = (int)$this->based_on;

		$aAttributeListByType = \TsAccounting\Helper\Company\ReceiptTextBasedOn::getAttributes($iBasedOn);

		$aAttributeConfig = (array)$this->_aAttributes;

		// Attribute die nicht vom Typ her passen entfernen, falls davor schon irgendwas abgespeichert wurde
		$aWrongTypeAttribute = array_diff_key($aAttributeConfig, $aAttributeListByType);


		foreach ($aWrongTypeAttribute as $sAttributeName => $aConfig) {
			if (
				isset($this->_aAttributesTypesData[$sAttributeName]) &&
				$this->_aAttributesTypesData[$sAttributeName] instanceof \WDBasic_Attribute_Type_Abstract
			) {
				$this->_aAttributesTypesData[$sAttributeName]->value = null;
			}
		}

		$mReturn = parent::save($bLog);

		return $mReturn;
	}

	/**
	 * siehe abstract
	 *
	 * @return string
	 */
	protected function _getCompaniesJoinKey()
	{
		return 'companies';
	}

	/**
	 * siehe abstract
	 *
	 * @return string
	 */
	protected function _getSchoolJoinKey()
	{
		return 'schools';
	}

	/**
	 * siehe abstract
	 *
	 * @return string
	 */
	protected function _getInboxJoinKey()
	{
		return 'inboxes';
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null)
	{
		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= "
			, GROUP_CONCAT(
				DISTINCT `ts_c`.`name` SEPARATOR ', '
			) `company_names`
			, GROUP_CONCAT(
				DISTINCT `cdb2`.`ext_1` SEPARATOR ', '
			) `school_names`
			, GROUP_CONCAT(
				DISTINCT `k_inb`.`short` SEPARATOR ', '
			) `inbox_names`
		";

		$aSqlParts['from'] .= "
			LEFT JOIN `ts_accounting_companies` `ts_c` ON
				`ts_c`.`id` = `companies`.`company_id` AND
				`ts_c`.`active` = 1 LEFT JOIN 
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `schools`.`school_id` AND
					`cdb2`.`active` = 1 LEFT JOIN
				`kolumbus_inboxlist` `k_inb` ON
					`k_inb`.`id` = `inboxes`.`inbox_id` AND
					`k_inb`.`active` = 1
		";

	}

}

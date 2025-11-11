<?php

namespace TsCompany\Entity;

use Ts\Traits\Numberrange;
use Core\Traits\BitwiseFlag;

// TODO hier kann man noch sehr viel refaktorisieren, übernommen aus Ext_Thebing_Agency
abstract class AbstractCompany extends \Ext_Thebing_Basic {
	use Numberrange, BitwiseFlag;

	const TYPE_COMPANY = 1;

	const TYPE_AGENCY = 2;

	protected $_sTable = 'ts_companies';

	protected $_sTableAlias = 'ka';

	protected $sNumberrangeClass = \TsCompany\Service\NumberRange::class;

	/**
	 * @var null|int
	 */
	protected $_iMasterContact = null;

	protected $_aFormat = [
		'ext_1' => [
			'validate' => 'UNIQUE',
			'required' => true
		],
		'ext_4' => [
			'validate' => 'ZIP',
			'parameter_settings' => [
				'type' => 'field',
				'source' => 'ext_6'
			]
		],
		'ext_10' => [
			'validate' => 'URL'
		],
		'ext_17' => [
			'validate' => 'IBAN'
		]
	];

	protected $_aJoinTables = [
		'comments' => [
			'table' => 'ts_companies_comments',
			'foreign_key_field' => 'id',
			'primary_key_field' => 'company_id',
			'class' => \TsCompany\Entity\Comment::class,
			'autoload' => false
		],
		// Da es keine Verknüpfungstabelle ist, muss das als Joined-Object angelegt werden -> HIER READONLY
		'contacts' => [
			'table' => 'ts_companies_contacts',
			'foreign_key_field' => '',
			'primary_key_field' => 'company_id',
			'readonly' => true
		],
		'numbers' => [
			'table' => 'ts_companies_numbers',
			'foreign_key_field' => ['number', 'numberrange_id'],
			'primary_key_field' => 'company_id',
			'autoload' => true
		]
	];

	protected $_aJoinedObjects = array(
		'contacts' => array(
			'class' => Contact::class,
			'key' => 'company_id',
			'check_active' => true,
			'type' => 'child'
		)
	);

	/**
	 * @param string $sName
	 * @return array|int|mixed
	 * @throws \ErrorException
	 */
	public function __get($sName) {

		\Ext_Gui2_Index_Registry::set($this);

		switch($sName) {
			case 'number':
				$sRetVal = $this->getNumber();
				break;
			case 'ext_34':
				$aValues = $this->_aData[$sName];
				$sRetVal = explode(',', $aValues);
				break;
			case 'ext_27':
			case 'ext_28':
				$sValue = $this->invoice;
				$sRetVal = 0;
				if(
					(
						$sValue == 1 &&
						$sName == 'ext_28'
					) ||
					(
						$sValue == 2 &&
						$sName == 'ext_27'
					)
				) {
					$sRetVal = 1;
				}
				break;
			default:
				$sRetVal = parent::__get($sName);
				break;
		}

		return $sRetVal;
	}

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function  __set($sName, $mValue) {

		if(
			$sName == 'ext_34' &&
			is_array($mValue)
		) {

			$this->_aData[$sName] = implode(',', $mValue);

		} elseif($sName == 'number') {

			if(
				!empty($this->numbers) &&
				$this->numbers[0]['numberrange_id'] != 0
			) {
				throw new \InvalidArgumentException('Trying to overwrite company number with existing numberrange id!');
			}

			$this->numbers = array(array(
				'company_id' => $this->getId(),
				'number' => $mValue,
				'numberrange_id' => 0
			));

		} else {
			parent::__set($sName, $mValue);
		}

	}

	/**
	 * @return Contact|false
	 */
	public function getMasterContact() {

		if($this->id == 0) {
			return false;
		}

		$oMasterContact = false;

		if(is_null($this->_iMasterContact)) {

			$sSql = "
				SELECT
					`id`
				FROM
					#table
				WHERE
					`company_id` = :company_id AND
					`master_contact` = 1 AND
					`active`		 = 1
				LIMIT 1
			";

			$aSql = array(
				'company_id' => (int)$this->id,
				'table'		=> 'ts_companies_contacts',
			);

			$this->_iMasterContact = \DB::getQueryOne($sSql, $aSql);

		}

		if($this->_iMasterContact > 0) {
			$sClass = $this->_aJoinedObjects['contacts']['class'];
			$oMasterContact = call_user_func_array([$sClass, 'getInstance'], [$this->_iMasterContact]);
		}

		return $oMasterContact;

	}

	public function getLanguage() {

		$sLanguage = $this->ext_33;

		if(empty($sLanguage)) {
			$sLanguage = parent::getLanguage();
		}

		return $sLanguage;

	}

	/**
	 * @param bool $bLongName
	 * @return string|null
	 */
	public function getName($bLongName = false) {
		if($this->_helperCheckData()) {
			if(
				strlen($this->ext_2) > 0 &&
				$bLongName === false
			) {
				return $this->ext_2;
			} elseif (strlen($this->ext_1) > 0) {
				return $this->ext_1;
			}
		}
		// null damit Elasticsearch leere Spalten nach unten sortiert
		return null;
	}

	public function getDeliveryAddresses($bPrepareSelect=0) {

		$sSql = "
				SELECT 
					*
				FROM
					`ts_companies_addresses` kaa
				WHERE 
					`active` = 1 AND
					`company_id` = :company_id
				ORDER BY
					`shortcut` ASC
				";
		$aSql = array('company_id'=>(int)$this->id);
		$aAddresses = \DB::getPreparedQueryData($sSql, $aSql);

		if($bPrepareSelect) {
			$aItems = array();
			foreach((array)$aAddresses as $aAddress) {
				$aItems[$aAddress['id']] = $aAddress['shortcut'];
			}
			return $aItems;
		} else {
			return $aAddresses;
		}

	}

	public function saveComment($aComment){
		if($aComment['text'] == ""){
			return false;
		}
		$sSQL = "INSERT INTO
				`ts_companies_comments` 
			SET
				`created` = NOW(),
				`company_id` = :company_id,
				`date` 	= FROM_UNIXTIME(:date),
				`text` = :text
			";
		$aSQL = array(
			'company_id'	=> $this->id,
			'date'	=> \Ext_Thebing_Format::ConvertDate($aComment['date']),
			'text'	=> $aComment['text']
		);
		\DB::executePreparedQuery($sSQL, $aSQL);
	}

	/**
	 * @param bool $bLog
	 * @return type
	 */
	public function save($bLog = true) {

		// Nummernkreis erzeugen
		$mNumber = $this->getNumber();
		if(empty($mNumber)) {
			$this->generateNumber();
		}

		return parent::save($bLog);
	}

	public function delete() {

		//Feld ist UNIQUE
		$this->ext_1 = $this->ext_1.'_'.\Ext_TC_Util::generateRandomString(8);
		$this->tracking_key = $this->tracking_key.'_'.\Ext_TC_Util::generateRandomString(8);

		return parent::delete();

	}

	/*
	 * Liefert die letzten Kommentare zu dieser Agentur
	 */
	public function getPlaceholderComments(){

		$aComments = $this->getJoinTableObjects('comments');

		$oDivContent = new \Ext_Gui2_Html_Div();
		$oDivContent->setElement('<h4>'.\L10N::t('Kommentare', 'Thebing » Placeholder').'</h4>');

		// Tabelle aufbauen
		$oTable = new \Ext_Gui2_Html_Table();
		$oTable->cellpadding = '2';
		$oTable->class = 'table tblDocumentTable ';

		$oTr = new \Ext_Gui2_Html_Table_tr();
		$oTh = new \Ext_Gui2_Html_Table_Tr_Th();
		$oTh->setElement((string)\L10N::t('Titel', 'Thebing » Placeholder'));
		$oTh->style = 'width: 30%; border-bottom: 1px solid black';
		$oTr->setElement($oTh);
		/*
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement((string)L10N::t('Betreff', 'Thebing » Placeholder'));
			$oTh->style = 'border-left: 1px solid black; border-bottom: 1px solid black';
		$oTr->setElement($oTh);
			$oTh = new Ext_Gui2_Html_Table_Tr_Th();
			$oTh->setElement((string)L10N::t('Aktivität', 'Thebing » Placeholder'));
			$oTh->style = 'border-left: 1px solid black; border-bottom: 1px solid black';
		$oTr->setElement($oTh);
		 */
		$oTh = new \Ext_Gui2_Html_Table_Tr_Th();
		$oTh->setElement((string)\L10N::t('Text', 'Thebing » Placeholder'));
		$oTh->style = 'width: 70%; border-left: 1px solid black; border-bottom: 1px solid black';
		$oTr->setElement($oTh);
		/*$oTh = new Ext_Gui2_Html_Table_Tr_Th();
		$oTh->setElement((string)L10N::t('Kontakt', 'Thebing » Placeholder'));
		$oTh->style = 'border-left: 1px solid black; border-bottom: 1px solid black';
	$oTr->setElement($oTh);*/
		$oTable->setElement($oTr);

		foreach((array)$aComments as $oComment){
			$oTr = new \Ext_Gui2_Html_Table_tr();
			$oTd = new \Ext_Gui2_Html_Table_Tr_Td();
			$oTd->setElement((string)$oComment->title);
			$oTr->setElement($oTd);
			/*
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->setElement((string)$aSubjects[$oComment->subject_id]);
				$oTd->style = 'border-left: 1px solid black;';
			$oTr->setElement($oTd);
				$oTd = new Ext_Gui2_Html_Table_Tr_Td();
				$oTd->setElement((string)$aActivities[$oComment->activity_id]);
				$oTd->style = 'border-left: 1px solid black;';
			$oTr->setElement($oTd);
			 */
			$oTd = new \Ext_Gui2_Html_Table_Tr_Td();
			$oTd->setElement((string)$oComment->text);
			$oTd->style = 'border-left: 1px solid black;';
			$oTr->setElement($oTd);
			/*$oTd = new Ext_Gui2_Html_Table_Tr_Td();
			$oTd->setElement((string)$aContacts[$oComment->agency_contact_id]);
			$oTd->style = 'border-left: 1px solid black;';
		$oTr->setElement($oTd);*/

			$oTable->setElement($oTr);
		}

		$oDivContent->setElement($oTable);

		$sHTML = $oDivContent->generateHTML();
		return $sHTML;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ",
					`numbers`.`number` `company_number`,
					`ts_ac`.`email` `contact_email`,
					`ts_ac`.`firstname` `contact_firstname`,
					`ts_ac`.`lastname` `contact_lastname`,
					`data_c`.`cn_short_".\System::getInterfaceLanguage()."` `country` 
					";


		$aSqlParts['from'] .= " LEFT JOIN
					`ts_companies_contacts` `ts_ac` ON
						`ka`.`id` = `ts_ac`.`company_id` AND
						`ts_ac`.`master_contact` = 1 AND
						`ts_ac`.`active` = 1 LEFT JOIN
					`data_countries` `data_c` ON 
						`data_c`.`cn_iso_2` = `ka`.`ext_6` LEFT JOIN
					`ts_companies_uploads` `kau` ON
						`kau`.`company_id` = `ka`.`id` AND
						`kau`.`active` = 1  LEFT JOIN
					`ts_companies_comments` `comments` ON
						`comments`.`company_id` = `ka`.`id` AND
						`comments`.`active` = 1
					";

	}
	
}

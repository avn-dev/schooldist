<?php

use Communication\Interfaces\Model\CommunicationSubObject;
use Tc\Traits\Filename;

/**
 * Beschreibung der Klasse
 */
class Ext_Thebing_Contract_Version extends Ext_Thebing_Basic implements \Communication\Interfaces\Model\HasCommunication {

	use Filename;

	// Tabellenname
	protected $_sTable = 'kolumbus_contracts_versions';
	protected $_sTableAlias = 'kcontv';
	
	protected $_sEditorIdColumn = 'editor_id';
	

	protected $_aJoinedObjects = array(
		'kcont'=>array(
			'class'=>'Ext_Thebing_Contract',
			'key'=>'contract_id',
			'type' => 'parent'
		)
	);
	
	protected $_aFormat = array(
		'contract_id' => array(
			'required'	=> true
		)
	);
	
	public function  __get($sName) {

		if($sName == 'type_name') {
			if($this->_aData['id'] > 0) {
				$oContract = $this->getContract();
				$oTemplate = $oContract->getContractTemplate();
				$sValue = $oTemplate->type_name;
			} else {
				$sValue = '';
			}
		} elseif($sName == 'name') {
			if($this->_aData['id'] > 0) {
				$oContract = $this->getContract();
				$oFormat = new Ext_Thebing_Gui2_Format_Contract_ItemName();
				$sValue = $oFormat->format('', $oDummy, $oContract->aData);
			} else {
				$sValue = '';
			}
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {
		global $user_data;

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		if($oGui instanceof Ext_Gui2) {
			$sItem = $oGui->_aTableData['where']['kcont.item'];
		}

		if($sItem == 'teacher') {
			$sJoinAppend = " JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = `kcont`.`item_id` AND
					`kt`.`active` = 1
			";
		} else {
			$sJoinAppend = " JOIN							
				`customer_db_4` `c4` ON
					`c4`.`id` = `kcont`.`item_id` AND
					`c4`.`active` = 1
			";
		}
		
		$aQueryData['sql'] = "
			SELECT
				`kcont`.*,						
				`kcontv`.*,
				`kcontv`.`id`,
				`kcontt`.`name` `template_name`
				{FORMAT},
				`kcont`.`creator_id` `creator_id`,
				`kcont`.`created` `created`,
				`kcont`.`editor_id` `editor_id`,
				`kcont`.`changed` `changed`
			FROM
				`kolumbus_contracts` `kcont` INNER JOIN
				`{TABLE}` `kcontv` ON
					`kcontv`.`id` = (SELECT `id` FROM {TABLE} WHERE contract_id = `kcont`.`id` ORDER BY created DESC LIMIT 1) AND
					`kcontv`.`active` = 1 LEFT JOIN
				`kolumbus_contract_templates` `kcontt` ON
					`kcontt`.`id` = `kcont`.`contract_template_id`
				" . $sJoinAppend . "
			WHERE
				`kcont`.`active` = 1					
			GROUP BY
				`kcont`.`id`
		";
		
		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}
	
	
	/**
	 * Speichert eine Vertragsversion und setzt die Vertragsnummer
	 * 
	 * @TODO $bLog sollte der erste Parameter sein, da das in der Parent-Methode der Fall ist - insgesamt aufräumen!
	 * @return Ext_Thebing_Contract_Version
	 */
	public function save($bForceNew=true, $bLog=true, $bCreateFile=true) {

		if(
			$this->id > 0 &&
			$bForceNew
		) {
			$this->_aData['id'] = 0;
		}

		// Joined Object speichern
		$oContract = $this->getJoinedObject('kcont');
		$oContract->updateChanged();
		$oContract->save();

		$this->contract_id = (int)$oContract->id;
		
		parent::save($bLog);

		$sNumber = $oContract->number;
		
		// Wenn noch keine Nummer vorhanden, neue Nummer generieren
		if(empty($sNumber)) {
			$oContract->generateNumber();
		}

		if($bCreateFile) {
		
			$sFilePath = $this->createPdf();

			$sFilePath = str_replace(\Util::getDocumentRoot().'storage', '', $sFilePath);

			$this->file = $sFilePath;
			$this->save(false, false, false);
			
		}

		return $this;
	}

	/**
	 * Gibt das Objekt des Items zurück
	 * @return Ext_Thebing_Contract
	 */
	public function getContract() {
		$oContract = $this->getJoinedObject('kcont');
			return $oContract;

	}

	/**
	 * Sucht in einem Zeitraum einen Rahmenvertrag
	 * 
	 * @param <type> $dValidFrom
	 * @param <type> $dValidUntil
	 * @param <type> $bFullmatch
	 * @return <int>
	 */
	public function searchMainContract($bFullmatch=false) {

		$dValidFrom = $this->valid_from;
		$dValidUntil = $this->valid_until;

		if($dValidUntil == '') {
			$dValidUntil = '0000-00-00';
		}

		$sItem = $this->getJoinedObject('kcont')->item;
		$iItemId = (int)$this->getJoinedObject('kcont')->item_id;

		$sWhere = "";
		$aSql = array();

		$aSql['item'] = $sItem;
		$aSql['item_id'] = $iItemId;

		// Wenn der übergebene Zeitraum komplett von einem Rahmenvertrag abgedeckt werden soll
		if($bFullmatch) {
			$sWhere .= " AND `kcontv`.`valid_from` <= :valid_from AND (`kcontv`.`valid_until` >= :valid_until OR `kcontv`.`valid_until` = '0000-00-00') ";
		} else {
			$sWhere .= " AND (`kcontv`.`valid_from` <= :valid_until OR :valid_until = '0000-00-00') AND (`kcontv`.`valid_until` >= :valid_from OR `kcontv`.`valid_until` = '0000-00-00') ";
		}

		$aSql['valid_until'] = $dValidUntil;
		$aSql['valid_from'] = $dValidFrom;

		$sSql = "
				SELECT
					`kcont`.`id`
				FROM
					`kolumbus_contracts` `kcont` JOIN
					`kolumbus_contracts_versions` `kcontv` ON
						`kcontv`.`id` = (SELECT `id` FROM `kolumbus_contracts_versions` WHERE contract_id = `kcont`.`id` AND `active` = 1 ORDER BY created DESC LIMIT 1) JOIN
					`kolumbus_contract_templates` `kcontt` ON
						`kcontt`.`id` = `kcont`.`contract_template_id` AND
						`kcontt`.`type` = 1
				WHERE
					`kcont`.`item` = :item AND
					`kcont`.`item_id` = :item_id AND
					`kcont`.`active` = 1
					".$sWhere."
				GROUP BY
					`kcont`.`id`
			";

		$iContractId = DB::getQueryOne($sSql, $aSql);

		return (int)$iContractId;

	}

	public function getAdditionalContracts() {

		$oTemplate = $this->getContract()->getContractTemplate();

		if($oTemplate->type == 1) {

			$dValidFrom = $this->valid_from;
			$dValidUntil = $this->valid_until;

			$sItem = $this->getJoinedObject('kcont')->item;
			$iItemId = (int)$this->getJoinedObject('kcont')->item_id;

			$sWhere = "";
			$aSql = array();

			$aSql['item'] = $sItem;
			$aSql['item_id'] = $iItemId;

			$sWhere .= " AND `kcontv`.`valid_until` >= :valid_from AND (`kcontv`.`valid_from` <= :valid_until OR :valid_until = '0000-00-00') ";

			$aSql['valid_until'] = $dValidUntil;
			$aSql['valid_from'] = $dValidFrom;

			$sSql = "
					SELECT
						`kcont`.`id`
					FROM
						`kolumbus_contracts` `kcont` JOIN
						`kolumbus_contracts_versions` `kcontv` ON
							`kcontv`.`id` = (SELECT `id` FROM `kolumbus_contracts_versions` WHERE contract_id = `kcont`.`id` AND `active` = 1 ORDER BY created DESC LIMIT 1) JOIN
						`kolumbus_contract_templates` `kcontt` ON
							`kcontt`.`id` = `kcont`.`contract_template_id` AND
							`kcontt`.`type` = 2
					WHERE
						`kcont`.`item` = :item AND
						`kcont`.`item_id` = :item_id AND
						`kcont`.`active` = 1
						".$sWhere."
					GROUP BY
						`kcont`.`id`
				";

			$aContracts = DB::getQueryRows($sSql, $aSql);

			return (array)$aContracts;


		} else {

			return false;

		}

	}

	/**
	 * Pro Item darf es keine überschneidenden Rahmenverträge (type=1) geben
	 *
	 * @param <bool> $bThrowExceptions
	 * @return string
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if($aErrors === true) {

			$oItem = $this->getJoinedObject('kcont')->getItemObject();

			$aErrors = array();

			$oTemplate = $this->getJoinedObject('kcont')->getContractTemplate();

			if($oTemplate->type == 2) {
				if(empty($this->_aData['valid_until'])) {
					$aErrors['kcontv.valid_until'][] = 'EMPTY';
				}
			}

			if(!empty($aErrors)) {
				return $aErrors;
			}

			// Prüfen, ob das Startdatum vor dem Enddatum liegt
			if(
				!empty($this->_aData['valid_until']) &&
				$this->_aData['valid_until'] != '0000-00-00'
			) {
				$oFrom = new WDDate($this->_aData['valid_from'], WDDate::DB_DATE);
				$iCompare = $oFrom->compare($this->_aData['valid_until'], WDDate::DB_DATE);
				if($iCompare > -1) {
					$aErrors['kcontv.valid_until'][] = array('message'=>'UNTIL_BEFORE_FROM', 'item'=>$oItem->name);
				}
			}

			if(!empty($aErrors)) {
				return $aErrors;
			}

			// Wenn ein Rahmenvertrag gespeichert wird, checken ob für den Zeitraum nicht schon ein anderer existiert
			if($oTemplate->type == 1) {

				$iMainContractId = $this->searchMainContract();

				if(
					$iMainContractId > 0 &&
					$iMainContractId != $this->getJoinedObject('kcont')->id
				) {
					$aErrors['kcontv.valid_from'][] = array('message'=>'OTHER_BASIC_CONTRACT_IN_PERIOD', 'item'=>$oItem->name);
				}

				// Bestehender Eintrag
				if($this->id > 0) {
					if($this->_aOriginalData['valid_from'] != $this->_aData['valid_from']) {
						$oDate = new WDDate($this->_aOriginalData['valid_from'], WDDate::DB_DATE);
						$iCompare = $oDate->compare($this->_aData['valid_from'], WDDate::DB_DATE);
						if($iCompare < 0) {
							$aErrors['kcontv.valid_from'][] = array('message'=>'NO_VALID_FROM_INCREASE');
						}
					}
					if(
						$this->_aOriginalData['valid_until'] != $this->_aData['valid_until'] &&
						!empty($this->_aData['valid_until'])
					) {
						$oDate = new WDDate($this->_aOriginalData['valid_until'], WDDate::DB_DATE);
						$iCompare = $oDate->compare($this->_aData['valid_until'], WDDate::DB_DATE);
						if($iCompare > 0) {
							$aErrors['kcontv.valid_until'][] = array('message'=>'NO_VALID_UNTIL_DECREASE');
						}
					}
				}

			// Wenn ein Zusatzvertrag gespeichert wird, checken ob es für den Zeitraum einen Rahmenvertrag gibt
			} else {

				$iMainContractId = $this->searchMainContract(true);

				if(
					$iMainContractId == 0
				) {
					$aErrors['kcontv.valid_from'][] = array('message'=>'NO_BASIC_CONTRACT_IN_PERIOD', 'item'=>$oItem->name);
				}

			}

		}

		if(empty($aErrors)) {
			return true;
		}

		return $aErrors;

	}

	public function confirm() {
		global $user_data;

		$this->confirmed = time();
		$this->confirmed_by = (int)$user_data['id'];

		$this->save(false);

		$oContract = $this->getContract();
		$oContract->last_confirmed_version_id = $this->id;
		$oContract->save();
	}
	
	public function deleteConfirmation() {
		$this->confirmed = 0;
		$this->confirmed_by = 0;
		$this->save(false);

		$oContract = $this->getContract();
		$oContract->last_confirmed_version_id = 0;
		$oContract->save();
	}

	public function isConfirmed(){
		$bConfirmed = false;
		
		if($this->confirmed){ 
			$bConfirmed = true;
		}
		
		return $bConfirmed;
	}
	
	public function getVersion() {

		$sSql = "
				SELECT
					COUNT(*)
				FROM
					kolumbus_contracts_versions
				WHERE
					`contract_id` = :contract_id AND
					`created` <= :created AND
					`active` = 1 ";
		$aSql = array('contract_id'=>$this->contract_id, 'created'=>date('Y-m-d H:i:s', $this->created));
		$iVersion = DB::getQueryOne($sSql, $aSql);

		return $iVersion;

	}

	public function createPdf() {

		$oPdf = new Ext_Thebing_Pdf_Basic($this->pdf_template_id, $this->getJoinedObject('kcont')->school_id);

		$oPdf->sDocumentType = 'contract';

		// Vorbereiten der Daten für PDF
		$this->createDocument($oPdf);

		## Dateinamen + Pfad bauen ##

		$aTemp		= $this->buildFileNameAndPath();
		$sPath		= $aTemp['path'];
		$sFileName	= $aTemp['filename'];

		## ENDE ##

		$sFilePath = $oPdf->createPdf($sPath, $sFileName);

		$this->log(Ext_Thebing_Log::PDF_CREATED);

		return $sFilePath;

	}

	public function createDocument(&$oPdf) {

		// Vorbereiten der Daten für PDF
		$aData = array();
		$aData['txt_intro']			= $this->txt_intro;
		$aData['date']				= $this->getJoinedObject('kcont')->date;
		$aData['document_number']	= $this->getJoinedObject('kcont')->number;
		/////////////////////////////

		$aAdditional = array();
		$aAdditional['contract_id'] = $this->contract_id;
		$aAdditional['version_id'] = $this->id;

		$oPdf->createDummyDocument($aData, array(), array(), $aAdditional);

	}

	public static function generatePdf(array $aVersionIds) {

		$iVersionId = reset($aVersionIds);
		$oVersion = self::getInstance((int)$iVersionId);

		$oPdfMerge = new Ext_Gui2_Pdf_Merge();
		foreach((array)$aVersionIds as $iVersionId) {
			$oVersion = self::getInstance($iVersionId);
			$oPdfMerge->addPdf($oVersion->file);
		}

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oVersion->pdf_template_id);

		$sNumber = $oTemplate->name;
		$sNumber .= '_'.date('YmdHis');
		$sFileName = \Util::getCleanFileName($sNumber).'.pdf';

		$oSchool = $oVersion->getContract()->getSchool();
		$sPath = $oSchool->getSchoolFileDir()."/contracts/".$sFileName;

		$oPdfMerge->save($sPath);

		return $sPath;

	}

	public function buildFileNameAndPath() {

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($this->pdf_template_id);
		$contract = $this->getContract();
		$oSchool = $contract->getSchool();

		$sFilenameTemplate = $oTemplate->getOptionValue($this->getLanguage(), $oSchool, 'filename');

		if(!empty($sFilenameTemplate)) {

			switch ($contract->item) {
				case 'teacher':
					$item = Ext_Thebing_Teacher::getInstance($contract->item_id);
					$aReplace = [
						$item->firstname,
						$item->lastname,
					];
					break;
				case 'accommodation':
					$item = Ext_Thebing_Accommodation::getInstance($contract->item_id);
					$aReplace = [
						$item->ext_103,
						$item->ext_104,
					];
					break;
			}


			$aPattern = [
				'{firstname}',
				'{surname}',
				'{document_number}',
				'{id}',
				'{version}',
				'{date}'
			];

			$aReplace = array_merge($aReplace, [
				$contract->number,
				$contract->id,
				$this->getVersion(),
				(new DateTime($contract->date))->format('Ymd')
			]);

			$sFileName = str_replace($aPattern, $aReplace, $sFilenameTemplate);
			$sFileName = Util::getCleanFilename($sFileName);
		} else {

			// Name der PDF Vorlage
			$sNumber = $oTemplate->name;

			// Nummer des Vertrages
			$sNumber .= '_' . $contract->number;

			$sFileName = \Util::getCleanFileName($sNumber);

			// version anhängen
			$sFileName .= '_v' . (int)$this->getVersion();

			// ID anhängen, sonst nicht eindeutig
			$sFileName .= '_' . $this->id;
		}

		$sPath = $oSchool->getSchoolFileDir()."/contracts/";

		$newName = $this->addCounter($sFileName, $sPath);

		$aBack = array('path' => $sPath, 'filename' => $newName);

		return $aBack;
	}

	/**
	 * Versionen können nicht gelöscht werden
	 * Es wird immer der komplette Vertrag gelöscht
	 * 
	 * @param <type> $bLog
	 * @return <type>
	 */
	public function delete($bLog = true, $bDeleteVersion=false) {

		if($bDeleteVersion) {
			$bSuccess = parent::delete();

			if($this->bPurgeDelete) {
				$sPath = $this->getPath(true);
				if(is_file($sPath)) {
					unlink($sPath);
				}
			}

		} else {
			// Vertrag löschen
			$oContract = $this->getContract();
			$bSuccess = $oContract->delete();
		}

		return $bSuccess;

	}

	/**
	 * Gibt die Kommunikationssprache der zugehörigen Schule zurück
	 * @return <string>
	 */
	public function getLanguage() {
		$oSchool = Ext_Thebing_School::getInstance($this->getContract()->school_id);
		$sLanguage = $oSchool->getLanguage();
		return $sLanguage;
	}
	
	/**
	 * Erzeugt einen Titel für diese Version
	 * @return string
	 */
	public function getLabel() {

		$sLabel = '';

		$oContract = $this->getContract();
		$oTemplate = $oContract->getContractTemplate();

		$sLabel .= $oTemplate->name;

		if($oContract->number) {
			$sLabel .= ' - '.$oContract->number;
		}

//		$oFormat = new Ext_Thebing_Gui2_Format_Date();
//		$mDate = $oFormat->format($oContract->date, $oDummy, $aDummy);
//
//		$sLabel .= ': '.$mDate;

		return $sLabel;

	}

	public function getPath($bFullPath=false) {

		$sPath = '';
		
		$sFile = $this->file;

		$sFile = str_replace('/storage', '', $sFile);
		$sFile = str_replace('storage', '', $sFile);

		if($bFullPath) {
			$sPath .= Util::getDocumentRoot().'storage';
		}

		$sPath .= $sFile;

		return $sPath;
	}

	/**
	 * Geht nur für Unterkunftsverträge
	 */
	public function getPayedStudents() {

		$oContract = $this->getContract();

		// Nur für Unterkünfte
		if($oContract->item != 'accommodation') {
			return false;
		}

		$sSql = "
			SELECT
				`kap`.*,
				`cdb1`.`lastname` `lastname`,
				`cdb1`.`firstname` `firstname`,
				(
					".Ext_Thebing_Accounting_Accommodation_Payment_List::getNightsQueryPart('`kap`.`accommodation_id`', '`kap`.`inquiry_accommodation_id`', '`kap`.`timepoint`')."
				)
				`nights`
			FROM
				`kolumbus_accommodations_payments` `kap` JOIN
				`ts_inquiries_journeys_accommodations` `kia` ON
					`kap`.`inquiry_accommodation_id` = `kia`.`id` AND
					`kia`.`active` = 1 JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kia`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ki` ON
					`ts_i_j`.`inquiry_id` = `ki`.`id` AND
					`ki`.`active` = 1  JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `cdb1` ON
					`ts_i_to_c`.`contact_id` = `cdb1`.`id` AND
					`cdb1`.`active` = 1
			WHERE
				`kap`.`accommodation_id` = :accommodation_id AND
				`kap`.`date` BETWEEN :from AND :until AND
				`kap`.`active` = 1
		";
		$aSql = array();
		$aSql['accommodation_id'] = (int)$oContract->item_id;
		$aSql['from'] = $this->valid_from;
		$aSql['until'] = $this->valid_until;

		$aStudents = DB::getQueryRows($sSql, $aSql);

		return $aStudents;

	}

	public function getCommunicationDefaultApplication(): string
	{
		$contract =  $this->getContract();

		if ($contract->item === 'teacher') {
			return \TsTuition\Communication\Application\TeacherContract::class;
		} else if ($contract->item === 'accommodation') {
			return \TsAccommodation\Communication\Application\AccommodationContract::class;
		}

		return '';
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return '';
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getContract()->getSchool();
	}
}
<?php

/**
 * Class Ext_Thebing_Examination_Version
 */
class Ext_Thebing_Examination_Version extends Ext_Thebing_Basic
{
	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'kolumbus_examination_version';

	/**
	 * Tabellen Alias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'kexv';

	/**
	 * @var array
	 */
	protected $_aSections = array();

	/**
	 * @var array
	 */
	protected $_aFormat = array(

		'examination_date'	=> array(
			'required' => true
		),

	);

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'kex'=>array(
			'class'=>'Ext_Thebing_Examination',
			'key'=>'examination_id',
			'type' => 'parent'
		)
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'teachers' => array(
			'table' => 'kolumbus_examination_version_teachers',
			'foreign_key_field' => 'teacher_id',
			'primary_key_field' => 'examination_version_id'
	));

	protected $_oExamination;

	protected $_oExaminationTemplate;

	protected $_oInquiryCourse;

	protected $_iInquiryId = null;

	protected $bParentSaved = false;

	public function getListQueryData($oGui = null) {

		$aQueryData					= array();
		$oSchool					= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolID					= $oSchool->id;
		$sWhereShowWithoutInvoice	= Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');

		// Tuition-Index direkt holen, anstatt über Lazy-Load bei jeder Kursbuchung
		$sCommonSelect = "
			(
				SELECT
					GROUP_CONCAT(CONCAT_WS(',', `week`, `current_week`) SEPARATOR ';')
				FROM
					`ts_inquiries_journeys_courses_tuition_index` `ts_ijcti_sub`
				WHERE
					`ts_ijcti_sub`.`journey_course_id` = `kic`.`id`
			) `tuition_index_entries`
		";

		$sSql = "
			SELECT	
				*
			FROM (
			(
				SELECT
					`ki`.`id`,
					`ki`.`group_id`,
					`ki`.`checkin`,
					`cdb1`.`lastname`,
					`cdb1`.`firstname`,
					`tc_c_n`.`number` as `customerNumber`,
					`kg`.`short` as `group_short`,
					`ktc`.`name_short` `course_name`,
					IFNULL(`ts_tcps`.`from`, `kic`.`from`) `from`,
					IFNULL(`ts_tcps`.`until`, `kic`.`until`) `until`,
				    `ts_tcps`.`id` `program_service_id`,   
					`ktc`.`id` `course_id`,
					`ki`.`id` `inquiry_id`,
					`kic`.`id` `inquiry_course_id`,
					`kic`.`weeks`,
					`kex`.`id` `examination_id`,
					`kex`.`examination_term_id` `examination_term_id`,
					`kex`.`term_possible_date`,
					`kex`.`examination_template_id` `template_id`,
					`kid`.`released_student_login` `released_student_login`,
					`kexv`.`id` `version_id`,
					`kexv`.`examination_date`,
					`kexv`.`score` `score`,
					`kexv`.`passed` `passed`,
					`kexv`.`creator_id` `creator_id`,
					`kexv`.`user_id` `editor_id`,
					UNIX_TIMESTAMP(`kexv`.`created`) `created`,
					UNIX_TIMESTAMP(`kexv`.`changed`) `changed`,
					`kexv`.`user_id` `user_id`,
					NULL AS `possible_date`,
					{$sCommonSelect}
				FROM
					`kolumbus_examination` `kex` INNER JOIN
					`kolumbus_examination_version` `kexv` ON
						`kex`.`id` = `kexv`.`examination_id` AND
						`kexv`.`active` = 1 AND
						`kexv`.`id` = (
							SELECT
								`id`
							FROM
								`kolumbus_examination_version`
							WHERE
								`examination_id` = `kexv`.`examination_id`
							ORDER BY
								`created` DESC
							LIMIT 1
						) INNER JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON 
					    `ts_tcps`.`id` = `kex`.`program_service_id` AND 
					    `ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' INNER JOIN
					`kolumbus_tuition_courses` `ktc` ON
						`ktc`.`id` = `ts_tcps`.`type_id` INNER JOIN
					/* Keine Abfrage nach course_id, da sich diese auch ändern kann */
					`ts_inquiries_journeys_courses` `kic` ON
						`kic`.`id` = `kex`.`inquiry_course_id` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kic`.`journey_id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` `ki` ON
						`ts_i_j`.`inquiry_id` = `ki`.`id` INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `cdb1` ON
						`ts_i_to_c`.`contact_id` = `cdb1`.`id` AND
						`cdb1`.`active` = 1 LEFT JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ki`.`group_id` LEFT JOIN
					`kolumbus_inquiries_documents` `kid` ON
						`kid`.`id` = `kex`.`document_id` AND
						`kid`.`active` = 1 AND
						`kid`.`type` = 'examination'
				WHERE
					`kex`.`active` = 1 AND
					`ki`.`active` = 1 AND
					`ts_i_j`.`school_id` = {$iSchoolID}
			) UNION ALL
			(
				SELECT
					`ki`.`id`,
					`ki`.`group_id`,
					`ki`.`checkin`,
					`cdb1`.`lastname`,
					`cdb1`.`firstname`,
					`tc_c_n`.`number` as `customerNumber`,
					`kg`.`short` as group_short,
					`ktc`.`name_short` `course_name`,
					IFNULL(`ts_tcps`.`from`, `kic`.`from`) `from`,
					IFNULL(`ts_tcps`.`until`, `kic`.`until`) `until`,
					`ts_tcps`.`id` `program_service_id`,
					`ktc`.`id` `course_id`,
					`ki`.`id` `inquiry_id`,
					`kic`.`id` `inquiry_course_id`,
					`kic`.`weeks`,
					NULL as `examination_id`,
					NULL AS `examination_term_id`,
					NULL as `term_possible_date`,
					`kext`.`id` `template_id`,
					NULL AS `released_student_login`,
					NULL as `version_id`,
					NULL as `examination_date`,
					NULL AS `score`,
					NULL AS `passed`,
					NULL as `creator_id`,
					NULL as `editor_id`,
					NULL as `created`,
					NULL as `changed`,
					NULL as `user_id`,
					NULL as `possible_date`,
					{$sCommonSelect}
				FROM
					`ts_inquiries_journeys_courses` `kic` INNER JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON
						`ts_tcps`.`program_id` = `kic`.`program_id` AND
						`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
						`ts_tcps`.`active` = 1 INNER JOIN
					`kolumbus_tuition_courses` `ktc` ON
						`ktc`.`id` = `ts_tcps`.`type_id` AND
						`ktc`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kic`.`journey_id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` `ki` ON
						`ts_i_j`.`inquiry_id` = `ki`.`id` INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `cdb1` ON
						`ts_i_to_c`.`contact_id` = `cdb1`.`id` AND
						`cdb1`.`active` = 1 LEFT JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ki`.`group_id` INNER JOIN
					`kolumbus_examination_templates_courses` `kextc` ON
						`kextc`.`course_id` = `ktc`.`id` INNER JOIN
					`kolumbus_examination_templates` `kext` ON
						`kextc`.`examination_template_id` = `kext`.`id` AND
						`kext`.`active` = 1
				WHERE
					`ki`.`active` = 1 AND
					`ts_i_j`.`school_id` = {$iSchoolID} AND
					`kic`.`active` = 1 AND
					`kic`.`visible` = 1 AND
					`ktc`.`active` = 1 AND
					`ki`.`canceled` <= 0
					{$sWhereShowWithoutInvoice}
			)
		) as `result`

		";

		$aQueryData['sql'] = $sSql;

		return $aQueryData;

	}

	/**
	 *
	 * @param <int> $iTemplateID
	 * @return <array>
	 */
	public function getSections($iTemplateID, $iSchoolId=false, $bGroupByCategory=true)
	{
		if(!$iSchoolId)
		{
			$oSchool	= Ext_Thebing_School::getSchoolFromSession();
			$iSchoolId	= $oSchool->id;
		}

		$aSql = array(
			'examination_version_id'	=> $this->id,
			'template_id'				=> $iTemplateID,
			'school_id'					=> $iSchoolId,
		);

		if( 0 >= $this->id )
		{
			$sSelectAddon = '
				NULL as `entity_decimal`,
				NULL as `entity_varchar`,
				NULL as `entity_text`,
				NULL as `entity_int`
			';

			$sJoinLeftAddon = '';
		}
		else
		{
			$sSelectAddon = '
				`decimal_table`.`value` `entity_decimal`,
				`varchar_table`.`value` `entity_varchar`,
				`text_table`.`value` `entity_text`,
				`int_table`.`value` `entity_int`
			';

			$sJoinLeftAddon = '
				LEFT JOIN
					`kolumbus_examination_sections_entity_decimal` `decimal_table`
					ON `kexs`.`id` = `decimal_table`.`section_id` AND
					`decimal_table`.`examination_version_id` = :examination_version_id
				LEFT JOIN
					`kolumbus_examination_sections_entity_varchar` `varchar_table`
					ON `kexs`.`id` = `varchar_table`.`section_id` AND
					`varchar_table`.`examination_version_id` = :examination_version_id
				LEFT JOIN
					`kolumbus_examination_sections_entity_text` `text_table`
					ON `kexs`.`id` = `text_table`.`section_id` AND
					`text_table`.`examination_version_id` = :examination_version_id
				LEFT JOIN
					`kolumbus_examination_sections_entity_int` `int_table`
					ON `kexs`.`id` = `int_table`.`section_id` AND
					`int_table`.`examination_version_id` = :examination_version_id
			';
		}

		$sSql = "
			SELECT
				`kexs`.`id`,
				`kexs`.`title`,
				`kexset`.`model_class`,
				`kexsc`.`id` AS `category_id`,
				`kexsc`.`name` AS `category`,
				".$sSelectAddon."
			FROM
				`ts_examination_templates_sectioncategories` `kextsc` INNER JOIN
				`kolumbus_examination_sections_categories` `kexsc` ON
					`kexsc`.`id` = `kextsc`.`examination_sectioncategory_id` AND
					`kexsc`.`active` = 1 JOIN
				`kolumbus_examination_sections_categories_to_schools` `kexscs` ON
					`kexsc`.`id` = `kexscs`.`category_id` INNER JOIN
				`kolumbus_examination_sections` `kexs` ON
					`kexsc`.`id` = `kexs`.`section_category_id` AND
					`kexs`.`active` = 1 INNER JOIN
				`kolumbus_examination_sections_entity_type` `kexset`
					ON `kexs`.`entity_type_id` = `kexset`.`id`

				".$sJoinLeftAddon."
			WHERE
				`kextsc`.`examination_template_id` = :template_id AND
				`kexscs`.`school_id` = :school_id
			ORDER BY
				`kextsc`.`sort_order` ASC,
				`kexs`.`position` ASC
		";
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aSections = array();

		if($bGroupByCategory)
		{
			foreach($aResult as $aRowData)
			{
				$sCategoryName					= $aRowData['category'];
				$aSections[$sCategoryName][]	= $aRowData;
			}
		}
		else
		{
			$aSections = $aResult;
		}

		return $aSections;
	}

	public function  __set($sName, $mValue)
	{
		if( 'sections' == $sName )
		{
			$this->_aSections = (array)$mValue;
		}
		elseif( 'inquiry_id' == $sName )
		{
			$this->_iInquiryId = $mValue;
		}
		else
		{
			parent::__set($sName, $mValue);
		}
	}

	public function  __get($sName)
	{
		if( 'possible_date' == $sName )
		{
			return $this->getJoinedObject('kex')->term_possible_date;
		}
		else if( 'template_id' == $sName )
		{
			return $this->getJoinedObject('kex')->examination_template_id;
		}
		elseif($sName === 'examination_term_id') {
			// Ohne das hier gibt es bei Encode-Data in saveEdit() eine Exception
			return $this->getJoinedObject('kex')->examination_term_id;
		}
		else if( 'inquiry_course_id' == $sName )
		{
			return $this->getJoinedObject('kex')->inquiry_course_id;
		}
		else if( 'program_service_id' == $sName )
		{
			return $this->getJoinedObject('kex')->program_service_id;
		}
		else if( 'course_id' == $sName )
		{
			return $this->getJoinedObject('kex')->course_id;
		}
		else if( 'inquiry_id' == $sName )
		{
			return $this->getInquiryId();
		}
		else if( 'version_id' == $sName )
		{
			return $this->id;
		}
		else if( 'sections' == $sName )
		{
			return $this->_aSections;
		}
		else
		{
			return parent::__get($sName);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @TODO $bLog sollte der erste Parameter sein, da das in der Parent-Methode der Fall ist.
	 * Das muss komplett umgeschrieben werden, weil es so nicht zu verwenden ist an anderen Stellen.
	 *
	 * @return Ext_Thebing_Examination_Version
	 */
	public function save($bForceNew=true, $bLog=true, $bCreateFile=true) {

		// Wurde gelöscht, also einfach gar nichts machen, außer löschen…
		if($this->active == 0) {
			parent::save($bLog);
			return $this;
		}

		if(
			$this->id > 0 &&
			$bForceNew
		) {
			$this->_aData['id'] = 0;
		}

		//Parent speichern, diese Zeilen wurden in der WDBasic auskommentiert...

		if($bCreateFile === true) {

			$aObject = $this->_aJoinedObjects['kex'];

			if(
				isset($aObject['object']) &&
				is_object($aObject['object']) &&
				$aObject['type'] != 'child'
			) {
				$this->bParentSaved = true;
				$aObject['object']->save();
				$sKeyField = $aObject['key'];
				$this->$sKeyField = $aObject['object']->id;
			}

		}

		parent::save($bLog);

		if(
			$this->id > 0 &&
			$bForceNew
		) {
			$this->saveEntitys();
		}

		if($bCreateFile)
		{
			$sFilePath = $this->_createPdf();

			if($sFilePath===false)
			{
				if(1==$this->version_nr)
				{
					$oExamination	= $this->getExamination();
					$this->delete();
					$iDocumentNr	= $oExamination->document_id;
					$oDocument		= Ext_Thebing_Inquiry_Document::getInstance($iDocumentNr);
					$oDocument->delete();
					$oExamination->delete();
				}
				else
				{
					$oExamination	= $this->getExamination();
					$this->delete();
					$iDocumentNr	= $oExamination->document_id;
					$oDocument		= Ext_Thebing_Inquiry_Document::getInstance($iDocumentNr);
					$oLastVersion	= $oDocument->getLastVersion();
					if($oLastVersion){
						$oLastVersion->delete();
					}
				}

				$this->_aData = $this->_aOriginalData;
				return array(L10N::t('Bitte überprüfen Sie die PDF-Vorlage',"Thebing » Tuition » Examination"));
			}
		}

		return $this;

	}

//	public function delete()
//	{
//		$sSql = "
//			DELETE FROM
//				#table
//			WHERE
//				id = :id
//		";
//		$aSql = array(
//			'id'		=> $this->id,
//			'table'		=> $this->_sTable,
//		);
//
//		DB::executePreparedQuery($sSql, $aSql);
//	}

	public function saveEntitys()
	{

		$aEntitys = $this->_aSections;

		$aSaveData	= array();
		$oSections	= Ext_Thebing_Examination_Sections::getInstance();
		$aSections	= $oSections->getSchoolSections();
		$aObjects	= array();
		$aErrors	= array();

		foreach((array)$aSections as $aData)
		{
			$sClass = $aData['model_class'];
			$oModel = new $sClass();
			$aObjects[$aData['id']] = $oModel;
		}

		foreach((array)$aEntitys as $iSectionID => $mValue)
		{
			if(array_key_exists($iSectionID, $aObjects) && (!empty($mValue) || 0 === $mValue))
			{
				$oModel = $aObjects[$iSectionID];
				$oModel->section_id				= $iSectionID;
				$oModel->examination_version_id = $this->id;
				$oModel->value					= $mValue;

				try
				{
					$oModel->save();
				}
				catch(Exception $e)
				{
					#$this->_aEntityErrors[] = $e->getMessage();
					#$aErrors[$aTitles[$iSectionID]][] = $e->getMessage();
				}
			}
		}
	}

	/**
	 *
	 * @param <int> $iExaminationId
	 * @return <int>
	 */
	public function getLastVersion($iExaminationId)
	{
		$iVersionNr = 0;

		$sSql = "
			SELECT
				`version_nr`
			FROM
				#table
			WHERE
				`examination_id` = :examination_id
			ORDER BY
				`version_nr` DESC
			LIMIT 1
		";

		$aSql = array(
			'table' => $this->_sTable,
			'examination_id' => $iExaminationId,
		);

		$aData = DB::getQueryRow($sSql, $aSql);
		if(is_array($aData))
		{
			$iVersionNr = (int)reset($aData);
		}

		return $iVersionNr;
	}

	protected function _createPdf() {

		$oExaminationTemplate	= $this->getTemplate();
		
		if($oExaminationTemplate) {
			
			$iPdfTemplateId = $oExaminationTemplate->pdf_template_id;

			if(0 < $iPdfTemplateId) {

				$oPdf = new Ext_Thebing_Pdf_Basic($oExaminationTemplate->pdf_template_id, $oExaminationTemplate->school_id);

				$oPdf->sDocumentType = 'examination';

				// Vorbereiten der Daten für PDF
				$bCreate = $this->_createDocument($oPdf);

				if($bCreate)
				{
					## Dateinamen + Pfad bauen ##
					$aTemp		= $this->_buildFileNameAndPath();
					if(is_array($aTemp))
					{
						$sPath		= $aTemp['path'];
						$sFileName	= $aTemp['filename'];

						## ENDE ##

						try {
							$sFilePath = $oPdf->createPdf($sPath, $sFileName);
						} catch(PDF_Exception $e) {
							return false;
						} catch(RuntimeException $e) {
							// Fehler aus Ext_Thebing_Pdf_Basic::createPDF abfangen, wenn PDF-Background nicht existiert
							if(strpos($e->getMessage(), 'Background PDF File') !== false) {
								return false;
							}

							throw $e;
						}

						$this->log(Ext_Thebing_Log::PDF_CREATED);

						return $sFilePath;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param Ext_Thebing_Pdf_Basic $oPdf
	 * @return bool
	 * @throws Exception
	 */
	protected function _createDocument(&$oPdf)
	{
		global $user_data;

		$iInquiryId	= $this->getInquiryId();

		if( 0 < $iInquiryId )
		{
			if($this->_oExaminationTemplate)
			{
				$oExaminationTemplate = $this->_oExaminationTemplate;
			}
			else
			{
				$oExaminationTemplate = $this->getTemplate();
			}

			if(is_object($oExaminationTemplate) && $oExaminationTemplate instanceof Ext_Thebing_Examination_Templates)
			{
				$oPdfT			= Ext_Thebing_Pdf_Template::getInstance($oExaminationTemplate->pdf_template_id);
				$oTemplateType	= $oPdfT->getTemplateType();
				$oSchool		= Ext_Thebing_School::getInstance($oExaminationTemplate->school_id);
				$iUserId		= (int)$user_data['id'];

				if( 0 < $oPdfT->id && 0 < $oSchool->id )
				{
					$oInquiry		= Ext_TS_Inquiry::getInstance($this->getInquiryId());
					$oCustomer		= $oInquiry->getCustomer();
					$oSchool		= $oInquiry->getSchool();
					$iSchoolId		= (int)$oSchool->id;
					
					#$sLanguage		= $oInquiry->getLanguage();
					$sLanguage		= $oSchool->getLanguage();
					$oPdf->setLanguage($sLanguage);

					$sText1			= $oPdfT->getStaticElementValue($sLanguage, 'text1');
					$sText2			= $oPdfT->getStaticElementValue($sLanguage, 'text2');
					$sSubject		= $oPdfT->getStaticElementValue($sLanguage, 'subject');
					$sDate			= $oPdfT->getStaticElementValue($sLanguage, 'date');
					$sAddress		= $oPdfT->getStaticElementValue($sLanguage, 'address');

					$iUserSig		= $oPdfT->user_signature;
					if($iUserSig == 1)
					{
						$sSignature		= Ext_Thebing_User_Data::getData($iUserId, 'signature_pdf_'.$sLanguage);
						$iSignatureImg	= Ext_Thebing_User_Data::getData($iUserId, 'signature_img_'.$iSchoolId);
					}
					else
					{
						$sSignature		= $oPdfT->getOptionValue($sLanguage,$iSchoolId,'signatur_text');
						$iSignatureImg	= $oPdfT->getOptionValue($sLanguage, $iSchoolId, 'signatur_img');
					}

					$oPlaceholder = new Ext_Thebing_Examination_Placeholder($oInquiry->id, $oCustomer->id, $this->id);
					$sText1			= $oPlaceholder->replace($sText1);
					$sText2			= $oPlaceholder->replace($sText2);
					$sSubject		= $oPlaceholder->replace($sSubject);
					$sDate			= $oPlaceholder->replace($sDate);
					$sAddress		= $oPlaceholder->replace($sAddress);

					$oExamination = $this->getExamination();
					if($oExamination->document_id == 0)
					{
						$oDocument = $oInquiry->newDocument('examination');
						try {
							$oDocument->save();
						} catch(Exception $e) {
							return false;
						}

						$oExamination->document_id = $oDocument->id;

						try {

							// Manuell speichern, damit save Gedöns nicht ausgeführt wird
							$this->_oDb->update('kolumbus_examination', ['document_id'=>$oDocument->id], ['id'=>$oExamination->id]);

						} catch(Exception $e) {
							return false;
						}

					} else {
						$oDocument = Ext_Thebing_Inquiry_Document::getInstance($oExamination->document_id);
					}

					$iSchoolId						= $oSchool->id; 

					$oDocumentVersion				= $oDocument->newVersion();

					if($oTemplateType->element_text1){
						$oDocumentVersion->txt_intro	= $sText1;
					}

					if($oTemplateType->element_text2){
						$oDocumentVersion->txt_outro	= $sText2;
					}

					if($oTemplateType->element_subject){
						$oDocumentVersion->txt_subject	= $sSubject;
					}
					
					$oDocumentVersion->template_id	= $oPdfT->id;

					if($oTemplateType->element_address){
						$oDocumentVersion->txt_address	= $sAddress;
					}

					if($oTemplateType->element_date){
						$oDocumentVersion->date			= Ext_Thebing_Format::ConvertDate($sDate, $iSchoolId,1);
					}

					if(empty($oDocumentVersion->date)){
						$oDocumentVersion->date		= date('Y-m-d');
					}
					if($oTemplateType->element_signature_text == 1){
						$oDocumentVersion->txt_signature	= $sSignature;
					}
					if($oTemplateType->element_signature_img == 1){
						$oDocumentVersion->signature		= $iSignatureImg;
					}

					try {
						$oDocumentVersion->save();

						// Manuell speichern, damit save Gedöns nicht ausgeführt wird
						$this->_oDb->update('kolumbus_examination_version', ['version_nr'=>$oDocumentVersion->version], ['id'=>$this->id]);

						$this->version_nr = $oDocumentVersion->version;

					} catch(Exception $e) {
						return false;
					}

					try {
						$oPdf->createDocument($oDocument, $oDocumentVersion, [], array('version_id' => $this->id));
					} catch(Exception $e) {
						return false;
					}

					return true;
				}
			}
		}

		return false;
	}

	protected function _buildFileNameAndPath()
	{
		$oExaminationTemplate   = $this->getTemplate();
		if($oExaminationTemplate)
		{
			$oExamination			= $this->getExamination();
			$iPdfTemplateId			= $oExaminationTemplate->pdf_template_id;

			$oTemplatePdf = Ext_Thebing_Pdf_Template::getInstance($iPdfTemplateId);

			if( 0 < $oTemplatePdf->id )
			{
				// Name der PDF Vorlage
				$sNumber = $oTemplatePdf->name;

				// Nummer des Vertrages
				$sNumber .= '_'.$oExamination->id;

				$sFileName = \Util::getCleanFileName($sNumber);

				// version anhängen
				$sFileName .= '_v'.(int)$this->version_nr;

				#$oSchool = $this->getContract()->getSchool();
				$oSchool = Ext_Thebing_School::getInstance($oExaminationTemplate->school_id);
				$sPath = $oSchool->getSchoolFileDir()."/examination/";

				$aBack = array('path' => $sPath, 'filename' => $sFileName);

				return $aBack;
			}
		}

		return false;
	}

	/**
	 *
	 * @return Ext_Thebing_Examination
	 */
	public function getExamination()
	{
		if(is_object($this->_oExamination))
		{
			$oExamination = $this->_oExamination;
		}
		else
		{
			$oExamination = $this->getJoinedObject('kex');
			$this->_oExamination = $oExamination;
		}

		return $oExamination;
	}

	/**
	 *
	 * @return Ext_Thebing_Examination_Templates
	 */
	public function getTemplate()
	{
		$oExaminationTemplate = false;

		if(is_object($this->_oExaminationTemplate))
		{
			$oExaminationTemplate = $this->_oExaminationTemplate;
		}
		else
		{
			$oExamination = $this->getExamination();

			if( $oExamination instanceof Ext_Thebing_Examination )
			{
				$iTemplateId			= $oExamination->examination_template_id;
				$oExaminationTemplate	= Ext_Thebing_Examination_Templates::getInstance($iTemplateId);
				$this->_oExaminationTemplate = $oExaminationTemplate;
			}
		}

		if(is_object($oExaminationTemplate) && $oExaminationTemplate instanceof Ext_Thebing_Examination_Templates)
		{
			return $oExaminationTemplate;
		}
		else
		{
			return false;
		}

	}

	/**
	 *
	 * @return <int>
	 */
	public function getInquiryId()
	{
		$oInquiryCourse = false;

		if($this->_iInquiryId !== null)
		{
			return $this->_iInquiryId;
		}
		else
		{
			if(is_object($this->_oInquiryCourse))
			{
				$oInquiryCourse = $this->_oInquiryCourse;
			}
			else
			{
				$oExamination = $this->getExamination();
				if( $oExamination instanceof Ext_Thebing_Examination )
				{
					$iInquiryCourseId	= $oExamination->inquiry_course_id;
					$oInquiryCourse		= Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
					$this->_oInquiryCourse = $oInquiryCourse;
				}
			}
			
			if( is_object($oInquiryCourse) && $oInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course )
			{
				return $oInquiryCourse->inquiry_id;
			}
			else
			{
				return 0;
			}
		}
	}

	public function  validate($bThrowExceptions = false)
	{
		$mErrors = parent::validate($bThrowExceptions);
		if( true === $mErrors )
		{
			$aEntitys = $this->_aSections;

			$aSaveData	= array();
			$oSections	= Ext_Thebing_Examination_Sections::getInstance();
			$aSections	= $oSections->getSchoolSections();
			$aObjects	= array();$aTitles = array();
			$aErrors	= array();

			foreach((array)$aSections as $aData)
			{
				$sClass = $aData['model_class'];
				$oModel = new $sClass();
				$aObjects[$aData['id']] = $oModel;
				$aTitles[$aData['id']] = $aData['title'];
			}

			foreach((array)$aEntitys as $iSectionID => $mValue)
			{
				if(array_key_exists($iSectionID, $aObjects) && !empty($mValue))
				{
					$oModel = $aObjects[$iSectionID];
					$oModel->section_id				= $iSectionID;
					$oModel->examination_version_id = $this->id;
					$oModel->setValue($mValue);

					$mValidate = $oModel->validate();

					if( true !== $mValidate )
					{
						foreach($mValidate as $sField => $aErrorData)
						{
							foreach($aErrorData as $sErrorType)
							{
								#$aErrors[$aTitles[$iSectionID]][] = $sErrorType;
								$sKey = '[sections_' . $iSectionID . ']';
								$aErrors[$sKey][] = $sErrorType;
							}
						}
					}
				}
			}
			if( !empty($aErrors) )
			{
				return $aErrors;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return $mErrors;
		}
	}

	public function getEntityValue($iSectionId)
	{
		$oExaminationSection		= Ext_Thebing_Examination_Sections::getInstance($iSectionId);

		if(!$oExaminationSection->exist()) {
			// Wenn Objekt nicht existiert, gibt es auch kein Model und dann stürzen die Platzhalter ab
			return null;
		}

		// TODO: Tabelle entfernen (macht in Datenbank überhaupt keinen Sinn)
		$oEntityModel				= $oExaminationSection->getEntityModel();
		$oEntityModel->section_id	= $iSectionId;
		$mValue						= $oEntityModel->getValueByVersion($this->id);

		return $mValue;
	}

	/**
	 * Namen aller Klassen (des Prüfungskurses), die in den Zeitraum dieser Version fallen
	 *
	 * @return array
	 */
	public function getClassNames() {

		$aClasses = [];

		if(
			!empty($this->from) &&
			!empty($this->until)
		) {
			$oSearch = new Ext_Thebing_School_Tuition_Allocation_Result();
			$oSearch->setInquiry(Ext_TS_Inquiry::getInstance($this->getInquiryId()));
			$oSearch->setInquiryCourse(Ext_TS_Inquiry_Journey_Course::getInstance($this->inquiry_course_id));
			$oSearch->setTimePeriod('block_day', new DateTime($this->from), new DateTime($this->until));
			$aBlockDays = $oSearch->fetch();

			foreach($aBlockDays as $aBlockDay) {
				$aClasses[$aBlockDay['class_id']] = $aBlockDay['class_name'];
			}
		}

		return $aClasses;

	}

}

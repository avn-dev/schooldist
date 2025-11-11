<?php

use TsTuition\Entity\Placementtest;

/**
 * @TODO Diese Tabelle sollte einen UNIQUE auf inquiry_id + placementtest_id haben; active 0 kann komplett gelöscht werden
 *
 * @property string id
 * @property string changed
 * @property string created
 * @property string user_id
 * @property string placementtest_date
 * @property string palcementtest_result_date
 * @property string active
 * @property string creator_id
 * @property string mark
 * @property string level_id
 * @property string key
 * @property string inquiry_id
 * @property string placementtest_id
 * @property string score
 * @property string comment
 * @property string examiner_name
 * @property string started
 * @property string invited
 * @property string answered
 * @property array result_summary
 * @property int questions_answered
 * @property int questions_answered_correct
 * @property int courselanguage_id
 */
class Ext_Thebing_Placementtests_Results extends Ext_Thebing_Basic {

	use \Core\Traits\UniqueKeyTrait;

	// Tabellenname
	protected $_sTable = 'ts_placementtests_results';

	// Tablealias
	protected $_sTableAlias = 'ts_ptr';

	protected $_sPlaceholderClass = Ts\Service\Placeholder\PlacementTests::class;

	protected $_aFlexibleFieldsConfig = [
		'placementtests_results' => []
	];

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'level_id' => array(
			'required'	=> true,
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'placementtest_date' => array(
			'validate' => 'DATE'
		),
		'placementtest_result_date' => array(
			'validate' => 'DATE'
		),
		'result_summary' => [
			'format' => 'JSON'
		]
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'teachers' => array(
			'table' => 'ts_placementtests_results_teachers',
			'foreign_key_field' => 'teacher_id',
			'primary_key_field' => 'placementtest_result_id',
		),
	);

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = [
		'notices' => [
			'class' => 'Ext_Thebing_Placementtests_Notices',
			'type' => 'child',
			'key' => 'result_id',
			'check_active' => true,
			'orderby' => false,
			'on_delete' => 'cascade'
		],
		'details' => [
			'class' => 'Ext_Thebing_Placementtests_Results_Details',
			'type' => 'child',
			'key' => 'result_id',
			'check_active' => true,
			'orderby' => false,
			'on_delete' => 'cascade'
		],
		'placementtest' => [
			'class' => \TsTuition\Entity\Placementtest::class,
			'type' => 'parent',
			'key' => 'placementtest_id',
			'check_active' => true
		],
	];

	/**
	 * @param Ext_Gui2|null $oGui
	 * @return array
	 */
	public function getListQueryData($oGui = null) {

		$aQueryData = array();
		$oSchool	 = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolID	 = $oSchool->id;
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$aDocumentTypesInvoice = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');
		$sDocumentTypesInvoice = "'".implode("','", $aDocumentTypesInvoice)."'";

		$aWhere = array(
			'`ki`.`active` = 1',
			'`ki`.`canceled` <= 0',
			'`ts_i_j`.`school_id` = '.$iSchoolID,
			'`kic`.`active` = 1',
			'`kic`.`visible` = 1',
			'`ktc`.`active` = 1',
			'IF(
						`ptr`.`id` IS NOT NULL,
						`kic`.`courselanguage_id` = `ptr`.`courselanguage_id` OR `ptr`.`courselanguage_id` = 0,
						`kic`.`active` = 1
						)',
		);

		// Es dürfen Nur Schüler angezeigt werden die keine Rechnung haben, wenn Client erlaubt
		//$iCustomerSetting = (int)Ext_Thebing_System::getConfig('show_customer_without_invoice');
		//if($iCustomerSetting != 1) {
		//	$aWhere[] =  '`ki`.`confirmed` > 0';
		//}

		$sWhere = '';
		if(!empty($aWhere)) {
			$sWhere .= 'WHERE ';
		}
		$sWhere .= implode(' AND ', $aWhere);

		$aQueryData['sql'] = "
				SELECT
					`kic`.`id` `id`,
					GROUP_CONCAT(`kic`.`comment` SEPARATOR '{|}') `comments`,
					(
						SELECT
							GROUP_CONCAT(`kid`.`document_number` SEPARATOR ', ')
						FROM
							`kolumbus_inquiries_documents` `kid`
						WHERE
							`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
							`kid`.`entity_id` = `ts_i_j`.`inquiry_id` AND
							`kid`.`active` = 1 AND
							`kid`.`type` IN($sDocumentTypesInvoice)
					)`documents`,
					`tc_c_n`.`number` `customerNumber`,
					`cdb1`.`lastname`,
					`cdb1`.`firstname`,
					`cdb1`.`nationality`,
					`tc_e`.`email`,
					GROUP_CONCAT(`ktc`.`id`) `courses`,
					GROUP_CONCAT(`ktc`.`category_id`) `category_id`,
					`kic`.`courselanguage_id` `courselanguage_id`,
					/*GROUP_CONCAT(CONCAT('ID_',`kic`.`id`,'_',`kic`.`from`)) `course_start`,
					GROUP_CONCAT(CONCAT('ID_',`kic`.`id`,'_',`kic`.`until`)) `course_end`,*/
					GROUP_CONCAT(`kic`.`from`) `course_start`,
					GROUP_CONCAT(`kic`.`until`) `course_end`,
					`ktul`.`name_".$sInterfaceLanguage."` `normal_level`,
					`ktul_ptr`.`name_".$sInterfaceLanguage."` `internal_level`,
					`ptr`.`mark`,
					`ptr`.`comment` `placementtest_result_comment`,
					`ptr`.`placementtest_date`,
					`ptr`.`placementtest_result_date`,
					`ptr`.`invited`,
					`ptr`.`started`,
					`ptr`.`answered`,
					`ptr`.`score`,
					`ptr`.`changed`,
					`ptr`.`editor_id`,
					`kic`.`id` `inquiry_course_id`,
					`ki`.`id` `inquiry_id`,
                    `ki`.`inbox` `inbox`,
					getAge(
						`cdb1`.`birthday`
					) `customer_age`,
					`kg`.`short` `group_short`,
					`kage`.`ext_2` `agency_name`,
					`ptr`.`id` `placementtest_result_id`,
					`d_l_cl`.`name_".$sInterfaceLanguage."` `corresponding_language`,
					MIN(`kic`.`from`) `first_course_start`,
					(
						SELECT
							CONCAT (
								`ktc_sub`.`name_short`, '{|}',
								`ktc_sub`.`name_".$sInterfaceLanguage."`, '{|}',
								`ts_ijc_sub`.`from`
							)
						FROM
							`ts_inquiries_journeys_courses` `ts_ijc_sub` INNER JOIN
							`kolumbus_tuition_courses` `ktc_sub` ON
								`ktc_sub`.`id` = `ts_ijc_sub`.`course_id`
						WHERE
							`ktc_sub`.`active` = 1 AND
							`ts_ijc_sub`.`journey_id` = `ts_i_j`.`id`
						ORDER BY
							`ts_ijc_sub`.`from`
						LIMIT
							1
					) `first_course`
				FROM
					`ts_inquiries_journeys_courses` `kic` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kic`.`journey_id` AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` `ki` ON
						`ki`.`id` = `ts_i_j`.`inquiry_id` INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `cdb1`
						ON `cdb1`.`id` = `ts_i_to_c`.`contact_id` INNER JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id` INNER JOIN
					`kolumbus_tuition_courses` `ktc`
						ON `ktc`.`id` = `kic`.`course_id` LEFT JOIN
					`kolumbus_groups` `kg`
						ON `kg`.`id` = `ki`.`group_id` LEFT JOIN
					`ts_companies` `kage`
						ON `kage`.`id` = `ki`.`agency_id` LEFT JOIN
					`ts_tuition_levels` `ktul`
						ON `ktul`.`id` = `kic`.`level_id` LEFT JOIN
					`ts_placementtests_results` `ptr` ON
						`ptr`.`inquiry_id` = `ki`.`id` AND
						`ptr`.`courselanguage_id` = `kic`.`courselanguage_id` AND
						`ptr`.`active` = 1 LEFT JOIN
					`ts_tuition_levels` `ktul_ptr` ON
						`ktul_ptr`.`id` = `ptr`.`level_id` LEFT JOIN
					`tc_contacts_to_emailaddresses` `tc_c_to_e` ON
						`tc_c_to_e`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`tc_emailaddresses` `tc_e` ON
						`tc_e`.`id` = `tc_c_to_e`.`emailaddress_id` AND
						`tc_e`.`active` = 1 AND
						`tc_e`.`master` = 1 LEFT JOIN
					`data_languages` `d_l_cl` ON
						`d_l_cl`.`iso_639_1` = `cdb1`.`corresponding_language`
				".$sWhere."
				GROUP BY
					IF(
						`ptr`.`id` IS NOT NULL,
						CONCAT(`kic`.`courselanguage_id`,'_',`ptr`.`id`),
						CONCAT(
							`ki`.`id`,
							'_',
							IF(
								`kic`.`courselanguage_id` > 0,
								CONCAT('lg_',`kic`.`courselanguage_id`),
								CONCAT('kic_',`kic`.`id`)
							)
						)
					)
			";

		return $aQueryData;
	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($name) {

		if($name == 'placementtest_result_id') {
			return $this->id;
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * Internen Fortschritt für alle Kurse dieses Einstufungstest speichern (nur erste Kurswoche, ggf. überschreiben)
	 */

	public function updateInternalProgress()
	{

		$aJourneyCourses = Ext_TS_Inquiry::getInstance($this->inquiry_id)->getCourses();

		foreach ($aJourneyCourses as $oJourneyCourse) {
			if ($oJourneyCourse->courselanguage_id == $this->courselanguage_id) {

				$dStartWeek = Ext_Thebing_Util::getWeekFromCourseStartDate(new DateTime($oJourneyCourse->from), true);
				$oProgram = $oJourneyCourse->getProgram();
				$aProgramServices = $oProgram->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

				foreach ($aProgramServices as $oProgramService) {

					/* @var Ext_Thebing_Tuition_Course $oCourse */
					$oCourse = $oProgramService->getService();

					if (!$oCourse->isEmployment()) {
						$oJourneyCourse->saveProgress($dStartWeek, $this->getLevel(), $oCourse->getLevelgroup(), $oProgramService, null);
					}
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function save($bLog = true) {

		if('' == $this->_aData['mark']) {
			$this->_aData['mark'] = null;
		}

		$mSuccess = parent::save($bLog);

		$this->updateInternalProgress();

		return $mSuccess;

	}

	/**
	 * See parent
	 * @param type $bThrowExceptions
	 * @return type
	 */
	public function validate($bThrowExceptions = false) {
		$mValidate = parent::validate($bThrowExceptions);

		// Prüfen ob die Ergebnisse, NACH dem eigentlichen Test eingetragen wurden
		if(
			WDDate::isDate($this->placementtest_date, WDDate::DB_DATE) &&
			WDDate::isDate($this->placementtest_result_date, WDDate::DB_DATE) &&
			$this->placementtest_date != '0000-00-00' &&
			$this->placementtest_result_date != '0000-00-00'
		){
			$oDate = new WDDate($this->placementtest_date, WDDate::DB_DATE);
			$iCheck = $oDate->compare($this->placementtest_result_date, WDDate::DB_DATE);

			if($iCheck > 0){
				// Fehler werfen
				$aError = array($this->_sTableAlias . '.placementtest_result_date' => 'WRONG_PLACEMENTTEST_RESULT_DATE');
				if(is_array($mValidate)){
					$mValidate = array_merge($mValidate, $aError);
				}else{
					$mValidate = $aError;
				}
			}
		}

		return $mValidate;

	}

	/**
	 * @return array
	 */
	public function getReviewData() {

		$id = $this->id;
		$aTransfare = array();
		
		// Get complete Test-questions
		$sSql = "
				SELECT
					kpa.`id`,
					kpa.`id` a_id,
					kpq.`id` AS q_id, 
					kpq.`idCategory`, 
					kpq.`text` AS q_text, 
					kpq.`type` AS q_typ,
					kpq.`optional` AS q_optional, 
					kpc.`category`, 
					kpa.`text` AS a_text,
					kpa.`right_answer`
				FROM
					ts_placementtests_questions AS kpq LEFT JOIN 
					ts_placementtests_categories AS kpc ON
						kpc.`id` = kpq.`idCategory` AND
						kpc.`active` = 1 LEFT JOIN 
					ts_placementtests_questions_answers AS kpa ON
						kpa.`idQuestion` = kpq.`id`
				WHERE
					kpq.`placementtest_id` = :placmenttestId AND
					kpq.`active` = 1 AND
					kpa.`active` = 1
				ORDER BY
					kpc.`position`,
					kpc.`id`, 
					kpq.`position`, 
					kpq.`id`, 
					kpa.`position`, 
					kpa.`id`
				";

		$aSql = array();
		$aSql['placmenttestId'] = $this->placementtest_id;
		$aAnswers = (array)DB::getQueryRowsAssoc($sSql,$aSql);

		// prepare
		$aQuestion = array();
		foreach($aAnswers as $iKey => &$aAnswer) {
			$aAnswer['id'] = $iKey;
			// Questions //////////////////////////////////////////////////////////////////////////////
			// 0 = Catetory; 1 = Question; 2 = ID; 3 = Num Questions; 4 = Num Right Questions, 5 = Optional Question; 6 = Question Typ; 7 = Category ID
			$aTemp =  array( $aAnswer['category'], $aAnswer['q_text'], $aAnswer['q_id'], 0, 0, $aAnswer['q_optional'], $aAnswer['q_typ'], $aAnswer['idCategory']);
			if(empty($aTemp)) {
				$aQuestion[] = $aTemp;
			}
			$iFound = false;
			foreach($aQuestion as $aData) {
				$adiv = array_diff($aData, $aTemp);
				if(empty($adiv)) {
					$iFound = true;
				}
			}
			if(!$iFound) {
				$aQuestion[$aAnswer['q_id']] = $aTemp;
			}

		}
		unset($aAnswer);

		// Find number of Answers per Question AND number of korrect Questions
		foreach($aQuestion as $iKey => $aQues) {
			foreach($aAnswers as $jKey => $aAnswer) {
				if($aAnswer['q_id'] == $aQues[2]) {
					$aQuestion[$iKey][3]++;
					if($aAnswer['right_answer'] == 1) {
						$aQuestion[$iKey][4]++;
					}
				}
			}
		}

		// Categories /////////////////////////////
//		$sSql = "
//				SELECT
//					kpq.`idCategory`, 
//					kpc.`category`, 
//					kpq.`id` AS q_id, 
//					kpq.`optional` AS q_optional
//				FROM
//					ts_placementtests_questions AS kpq LEFT JOIN 
//					ts_placementtests_categories AS kpc ON
//						kpc.`id` = kpq.`idCategory` AND
//						kpc.`active` = 1
//				WHERE
//					kpq.`school_id` = :school AND
//					kpq.`active` = 1
//				ORDER BY 
//					kpc.`position`, 
//					kpc.`id`, 
//					kpq.position, 
//					kpq.id
//				";
//
//		$aSql = array();
//		$aSql['school'] = \Core\Handler\SessionHandler::getInstance()->get('sid');;
//		$aResultCat = DB::getPreparedQueryData($sSql,$aSql);
//
//		foreach($aResultCat as $aCat) {
//			
//			if(empty($aCategory)) {
//				// 0 = text; 1 = Num Quest; 2 = Num Opt Quest;
//				$aCategory[$aCat['idCategory']] = array($aCat['category'], 0, 0);
//			}
//
//			$iFound = false;
//
//			foreach($aCategory as $jKey => $aCatResp) {
//				if($aCatResp[0] == $aCat['category']) {
////					$aCategory[$jKey][1]++;
////					if($aCat['q_optional'] == 1){
////						$aCategory[$jKey][2]++;
////					}
//					$iFound = true;
//					break;
//				}
//			}
//
////			$iOpt = ($aCat['q_optional'] == 1) ? 1 : 0 ;
////			if(!$iFound){
////				$aCategory[$aCat['idCategory']] = array($aCat['category'], 1, $iOpt);
////			}
//		}


		////////////////////////////////////////////////////

		$aOrderedResults = array();
		if(!empty($id)) {

			//get data for questions and answers
			$aSql = array();
			$aSql['id'] = $id;
			$aSql['placementtestId'] = $this->placementtest_id;

			$sSql = "
				SELECT
					`kprd`.*,
					`q`.`id` as `q_id`,
					`q`.`text` as `q_text`,
					`q`.`type` as `q_type`,
					`a`.`id` as `a_id`,
					`a`.`text` as `a_text`,
					`a`.`right_answer` as `a_right`
				FROM
					ts_placementtests_results_details kprd INNER JOIN
					ts_placementtests_questions as `q` ON
						`kprd`.`question_id` = `q`.`id` INNER JOIN
					ts_placementtests_questions_answers as `a` ON
						`q`.`id` = `a`.`idQuestion`
				WHERE
				    kprd.active = 1 AND
					kprd.result_id = :id AND
					`q`.`placementtest_id` = :placementtestId
				ORDER BY
					`kprd`.`question_id` ASC, 
					`a`.`id` ASC, 
					`kprd`.`changed` DESC
				";

			//get Test Id from DB
			$aResults = DB::getPreparedQueryData($sSql,$aSql);

			//order results
			$i = 0;
			foreach($aResults as $key=>$aAnswer) {
				// q_type: 1 = Radio; 2 = Checkbox; 3 = text; 4 = Textarea; 5 = Auswählen; 6 = Multiselect

				$result = Ext_Thebing_Placementtests_Results_Details::getInstance($aAnswer['id']);
				$question = Ext_Thebing_Placementtests_Question::getInstance($aAnswer['q_id']);
				$category = Ext_Thebing_Placementtests_Question_Category::getInstance($question->idCategory);
					
				if(!isset($aQuestion[$aAnswer['q_id']])) {
					
					$aQuestion[$aAnswer['q_id']] = [$category->category, $question->text, $question->id, 0, 0, $question->optional, $question->type, $category->id];

					// Get complete Test-questions
					$sSql = "
							SELECT
								kpa.`id`,
								kpa.`id` a_id,
								kpq.`id` AS q_id, 
								kpq.`idCategory`, 
								kpq.`text` AS q_text, 
								kpq.`type` AS q_typ,
								kpq.`optional` AS q_optional, 
								kpc.`category`, 
								kpa.`text` AS a_text,
								kpa.`right_answer`
							FROM
								ts_placementtests_questions AS kpq LEFT JOIN 
								ts_placementtests_categories AS kpc ON
									kpc.`id` = kpq.`idCategory` AND
									kpc.`active` = 1 LEFT JOIN 
								ts_placementtests_questions_answers AS kpa ON
									kpa.`idQuestion` = kpq.`id`
							WHERE
								kpq.`placementtest_id` = :placementtestId AND
								kpq.`id` = :question_id AND
								(
									kpa.`id` = :answer_id OR
									kpa.`right_answer`
								)
							ORDER BY
								kpc.`position`,
								kpc.`id`, 
								kpq.`position`, 
								kpq.`id`, 
								kpa.`position`, 
								kpa.`id`
							";

					$aSql = array();
					$aSql['placementtestId'] = $this->placementtest_id;
					$aSql['question_id'] = $aAnswer['q_id'];
					$aSql['answer_id'] = $aAnswer['a_id'];
					$aQuestionAnswers = DB::getQueryRowsAssoc($sSql,$aSql);
					
					foreach($aQuestionAnswers as $aQuestionAnswer) {
						if(!isset($aAnswers[$aQuestionAnswer['id']])) {
							$aAnswers[$aQuestionAnswer['id']] = $aQuestionAnswer;
						}
					}
					
				}
				
				$aOrderedResults[$i]['answer_id'] = $aAnswer['a_id'];
				$aOrderedResults[$i]['answer_value'] = $aAnswer['value'];
				$aOrderedResults[$i]['answer_is_right'] = $aAnswer['answer_is_right'];
				$i++;
			}
		}

		$aCategory = [];
		foreach($aQuestion as $aSingleQuestion) {
			if(!isset($aCategory[$aSingleQuestion[7]])) {
				$aCategory[$aSingleQuestion[7]] = [$aSingleQuestion[0], 0, 0];
			}
			$aCategory[$aSingleQuestion[7]][1]++;
		}

		$aTransfare['categories'] = array_values($aCategory);
		$aTransfare['questions'] = array_values($aQuestion);
		$aTransfare['answers'] = array_values($aAnswers);
		
		$aTransfare['user_result'] = $this->_aData;
		$aTransfare['user_answers'] = $aOrderedResults;

		return $aTransfare;

	}

	/**
	 * @return int
	 */
	public static function getLevelForInquiryAndLanguage($inquiryId, $languageId) {

		return self::query()
			->join('ts_placementtests as ts_pt', 'ts_pt.id', '=', 'ts_ptr.placementtest_id')
			->where('inquiry_id', $inquiryId)
			->where('ts_pt.courselanguage_id', $languageId)
			->pluck('level_id')
			->first();
	}

	/**
	 * Gibt ein Child-Array der AnswerId und dem Kommentar zurück
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getChildAsArray() {

		$aNotices = $this->getJoinedObjectChilds('notices');
		$aReturn = [];

		if(!empty($aNotices)) {
			foreach($aNotices as $oNotice) {
				$aReturn[(int)$oNotice->question_id] = [
					'question_id' => (int)$oNotice->question_id,
					'comment' => $oNotice->comment
				];
			}
		}

		return $aReturn;

	}

	/**
	 * @return Ext_Thebing_Tuition_Level
	 */
	public function getLevel() {
		return Ext_Thebing_Tuition_Level::getInstance($this->level_id);
	}

	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return Ext_TS_Inquiry::getInstance($this->inquiry_id);
	}

	public function evaluateResult() {

		// result_summary ist mit den Fragen, die auf Optional und "Immer bewertet" sind, auch wenn diese nicht beantwortet wurden
		$this->result_summary = $this->getResultSummary();
//
		// Die beiden Werte sind nur die tatsächlich beantworteten Fragen (ohne Textfelder)
		$this->questions_answered = count($this->getAnswers(false));
		$this->questions_answered_correct = $this->getAmountQuestionsAnsweredCorrect(false);

		// Ticket #20021
		$this->setInquiryCourseLevel();
	}

	public function getResultSummary() {

		$questions = $this->getPlacementtest()->getQuestions();

		$resultSummary = [];
		foreach ($questions as $question) {
			$category = $question->getCategory();
			if (empty($resultSummary['amount'][$category->id])) {
				$arrayForResult = [];

				$amountQuestionsAnsweredBasedOnCategory = count($this->getAnswers(true, $question->idCategory));
				$amountQuestionsAnsweredCorrectBasedOnCategory = $this->getAmountQuestionsAnsweredCorrect(true, $question->idCategory);

				$totalAmountQuestionsAnsweredCorrect += $amountQuestionsAnsweredCorrectBasedOnCategory;
				$totalAmountQuestionsAnsweredBasedOnCategory += $amountQuestionsAnsweredBasedOnCategory;

				$arrayForResult['amountQuestionsAnsweredCorrectBasedOnCategory'] = $amountQuestionsAnsweredCorrectBasedOnCategory;
				$arrayForResult['amountQuestionsAnsweredBasedOnCategory'] = $amountQuestionsAnsweredBasedOnCategory;
				$resultSummary['amount'][$category->id] = $arrayForResult;
			}
			if (empty($resultSummary['percentage'][$category->id])) {

				if ($amountQuestionsAnsweredBasedOnCategory != 0) {
					$percentRight = $amountQuestionsAnsweredCorrectBasedOnCategory / $amountQuestionsAnsweredBasedOnCategory * 100;
				} else {
					$percentRight = 0;
				}

				$resultSummary['percentage'][$category->id] = $percentRight;
			}
		}

		$resultSummary['amount']['total']['totalAmountQuestionsAnsweredCorrect'] = $totalAmountQuestionsAnsweredCorrect;
		$resultSummary['amount']['total']['totalAmountQuestionsAnsweredBasedOnCategory'] = $totalAmountQuestionsAnsweredBasedOnCategory;


		if ($totalAmountQuestionsAnsweredCorrect != 0) {
			$percentRight = $totalAmountQuestionsAnsweredCorrect / $totalAmountQuestionsAnsweredBasedOnCategory * 100;
		} else {
			$percentRight = 0;
		}

		$resultSummary['percentage']['total'] = $percentRight;

		return $resultSummary;
	}

	/**
	 * Liefert alle Antworten (, die bewertet werden müssen), basierend auf 1. der Fragenkategorie oder 2. alle Antworten
	 */
	public function getAnswers($forEvaluation = true, $questionCategoryId = null): array {

		// Nicht beantwortete, optionale Fragen (ohne "Immer bewerten" angetickt) sind nicht in dem Array drinne
		$answers = $this->getJoinedObjectChilds('details');

		// Textareas werden nicht in der Datenbank berücksichtigt, weil man diese nicht bewerten kann
		$answersWithoutTextAreaAnswers = [];
		foreach ($answers as $answer) {
			$question = Ext_Thebing_Placementtests_Question::getInstance($answer->question_id);
			$questionType = (int)$question->type;
			if ($questionType != $question::TYPE_TEXTAREA) {
				$answersWithoutTextAreaAnswers[$question->id] = $answer;
			}
		}

		$answers = $answersWithoutTextAreaAnswers;

		// Nur die Antworten, bei denen die Frage auch beantwortet wurde
		// (-> also die Fragen, die nicht beantwortet wurden, aber auf "Immer bewerten" sind, werden aus dem Array geschmissen)
		// (->deswegen !forEvaluation)
		if (!$forEvaluation) {
			$answersGiven = [];
			foreach ($answers as $answer) {
				if (!empty($answer->value)) {
					$answersGiven[$answer->id] = $answer;
				}
			}
			$answers = $answersGiven;
		}

		if ($questionCategoryId == null) {
			return $answers;
		}

		$answersBasedOnCategory = [];
		foreach ($answers as $answer) {
			$question = Ext_Thebing_Placementtests_Question::getInstance($answer->question_id);
			if ($question->idCategory == $questionCategoryId) {
				$answersBasedOnCategory[$answer->id] = $answer;
			}
		}

		return $answersBasedOnCategory;
	}

	/**
	 * Liefert die Anzahl der richtigen Antworten basierend auf 1. der Fragenkategorie oder 2. insgesamt
 	 */
	public function getAmountQuestionsAnsweredCorrect($forEvaluation = true, $questionCategoryId = null): int {

		$answers = $this->getAnswers($forEvaluation, $questionCategoryId);
		$correctAnswers = 0;

		foreach ($answers as $answer) {

			// answer_correctness wurde, wenn man hier landet, schon abgeprüft
			if ($answer->answer_is_right == 1) {
				$correctAnswers++;
			}
		}

		return $correctAnswers;
	}

	public function getFormattedTotalCorrectAnswers() {

		$resultSummary = $this->result_summary;

		$totalAmountQuestionsAnsweredCorrect = $resultSummary['amount']['total']['totalAmountQuestionsAnsweredCorrect'];

		$totalAmountQuestionsAnsweredBasedOnCategory = $resultSummary['amount']['total']['totalAmountQuestionsAnsweredBasedOnCategory'];

		$resultString = $totalAmountQuestionsAnsweredCorrect.'/'.$totalAmountQuestionsAnsweredBasedOnCategory;

		if ($resultString == '/') {
			return '';
		}

		return $resultString;
	}

	public function setInquiryCourseLevel() {

		$inquiry = Ext_TS_Inquiry::getInstance($this->inquiry_id);

		$totalQuestionsCorrectPercent = round($this->result_summary['percentage']['total'], 2);

		$levelsOfSchool = Ext_Thebing_Tuition_Level::getLevelsBySchoolId($inquiry->getSchool()->id);

		foreach ($levelsOfSchool as $level) {

			// Wenn es ein Prozentbereich für das Level gibt
			// und der Prozentwert vom Test in dem Bereich liegt
			if (
				$level->automatic_assignment_from !== null &&
				$totalQuestionsCorrectPercent >= $level->automatic_assignment_from &&
				$totalQuestionsCorrectPercent <= $level->automatic_assignment_until
			) {
				$this->level_id = $level->id;
				break;
			}
		}
	}

	public static function getResultByInquiryAndCourseLanguage($inquiryId, $courseLanguageId) {
		return self::query()
			->where('inquiry_id', $inquiryId)
			->where('courselanguage_id', $courseLanguageId)
			->get()
			->first();
	}

	public function getPlacementtest() {
		return Placementtest::getInstance($this->placementtest_id);
	}

	public function isAnswered(): bool {
		// TODO @Marlon WDBasic::__get() liefert hier false
		return is_string($this->answered) && $this->answered !== '0000-00-00 00:00:00';
	}

}

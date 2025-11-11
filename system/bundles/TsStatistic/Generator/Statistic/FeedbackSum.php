<?php

namespace TsStatistic\Generator\Statistic;

use \TcStatistic\Exception\NoResultsException;
use \TcStatistic\Model\Statistic\Column;
use \TcStatistic\Model\Table;
use \TsStatistic\Model\Filter;

class FeedbackSum extends AbstractGenerator {

	protected $aAvailableFilters = [
		Filter\Schools::class,
		Filter\Feedback\Questionnaire::class,
		Filter\Feedback\Dependency::class,
		Filter\Feedback\Topic::class,
	];

	/**
	 * @var array
	 */
	private $aQuestions = null;

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return self::t('Feedback-Summen');
	}

	/**
	 * Alle Fragen des Fragebogens (Fragen, die diese Statistik anzeigen kann)
	 *
	 * @return array
	 */
	protected function getQuestions() {

		if($this->aQuestions !== null) {
			return $this->aQuestions;
		}

		$sWhere = "";
		if(!empty($this->aFilters['question_topic'])) {
			$sWhere .= " AND `tc_fqu`.`topic_id` = :topic_id ";
		}

		$sLanguage = \Ext_TC_System::getInterfaceLanguage();

		$sSql = "
			SELECT
				`tc_fqu`.`id` `question_id`,
				`tc_fqu`.`question_type`,
				`tc_fqu`.`quantity_stars`,
				`tc_fqu_i18n`.`question` `title`,
				GROUP_CONCAT(CONCAT(`tc_frc`.`id`, ',', `tc_frc`.`rating`) ORDER BY `tc_frc`.`rating` SEPARATOR ';') `ratings`
			FROM
				`tc_feedback_questions` `tc_fqu` INNER JOIN
				`tc_feedback_questionaries_childs_questions_groups_questions` `tc_fqcqgq` ON
					`tc_fqcqgq`.`question_id` = `tc_fqu`.`id` AND
					`tc_fqcqgq`.`active` = 1 INNER JOIN
				`tc_feedback_questionaries_childs_questions_groups` `tc_fqcqg` ON
					`tc_fqcqg`.`id` = `tc_fqcqgq`.`questionary_question_group_id` INNER JOIN
				`tc_feedback_questionaries_childs` `tc_fqc` ON
					`tc_fqc`.`id` = `tc_fqcqg`.`child_id` AND
					`tc_fqc`.`active` = 1 INNER JOIN
				`tc_feedback_questionaries` `tc_fq` ON
					`tc_fq`.`id` = `tc_fqc`.`questionnaire_id` AND
					`tc_fq`.`active` = 1 LEFT JOIN
				`tc_feedback_questions_i18n` `tc_fqu_i18n` ON
					`tc_fqu_i18n`.`question_id` = `tc_fqu`.`id` AND
					`tc_fqu_i18n`.`language_iso` = '{$sLanguage}' LEFT JOIN
				`tc_feedback_ratings_childs` `tc_frc` ON
					`tc_frc`.`rating_id` = `tc_fqu`.`rating_id` AND
					`tc_frc`.`active` = 1
			WHERE
				`tc_fqu`.`active` = 1 AND
				`tc_fq`.`id` = :questionnaire_id AND
				`tc_fqu`.`dependency_on` = :dependency_on AND
				`tc_fqu`.`question_type` IN ('stars', 'rating', 'yes_no')
				{$sWhere}
			GROUP BY
				`tc_fqu`.`id`
			ORDER BY
				`tc_fqc`.`position`,
				`tc_fqcqgq`.`position`
		";

		$aSql = [
			'questionnaire_id' => (int)$this->aFilters['questionnaire'],
			'dependency_on' => $this->aFilters['question_dependency'],
			'topic_id' => $this->aFilters['question_topic']
		];

		$aResult = (array)\DB::getQueryRows($sSql, $aSql);

		$this->aQuestions = [];
		foreach($aResult as $aQuestion) {
			switch($aQuestion['question_type']) {
				case 'rating':
					$aQuestion['answers'] = [];
					$aRatingChilds = explode(';', $aQuestion['ratings']);
					foreach($aRatingChilds as $sRatingChild) {
						$aRatingChild = explode(',', $sRatingChild);
						// Die Rating-Antworten werden nicht mit ID gespeichert, sondern mit dem Skalen-Wert
						$aQuestion['answers'][$aRatingChild[1]] = $aRatingChild[1];
					}
					break;
				case 'stars':
					$aQuestion['answers'] = [];
					for($i = 1; $i < $aQuestion['quantity_stars'] + 1; $i++) {
						$aQuestion['answers'][$i] = $i;
					}
					break;
				case 'yes_no':
					$aQuestion['answers'] = \Ext_TC_Util::getYesNoArray();
					break;
			}

			$this->aQuestions[$aQuestion['question_id']] = $aQuestion;
		}

		return $this->aQuestions;

	}

	/**
	 * Eigentliche Daten, aus dem Query
	 *
	 * @return array
	 */
	protected function getQueryData() {

		if($this->aFilters['based_on'] === 'service_period') {
			$sWhere = "
				AND (
					`ts_i`.`service_from` <= :until AND
					`ts_i`.`service_until` >= :from
				)
			";
		} else {
			$sWhere = " AND `tc_fqp`.`answered` BETWEEN :from AND :until ";
		}

		$sSql = "
			SELECT
				`tc_fqp`.`id` `process_id`,
				`tc_fqpr`.`id` `result_id`,
				`tc_fqpr`.`answer`,
				`tc_fqpr`.`dependency_id`,
				`tc_fqu`.`id` `question_id`,
				`tc_fqu`.`dependency_on`,
				`tc_fqu`.`question_type`
			FROM
				`tc_feedback_questionaries_processes` `tc_fqp` INNER JOIN
				`tc_feedback_questionaries_processes_results` `tc_fqpr` ON
					`tc_fqpr`.`questionary_process_id` = `tc_fqp`.`id` AND
					`tc_fqpr`.`active` = 1 INNER JOIN
				`tc_feedback_questionaries_childs_questions_groups_questions` `tc_fqcqgq` ON
					`tc_fqcqgq`.`id` = `tc_fqpr`.`questionary_question_group_question_id` AND
					`tc_fqcqgq`.`active` = 1 INNER JOIN
				`tc_feedback_questions` `tc_fqu` ON
					`tc_fqu`.`id` = `tc_fqcqgq`.`question_id` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `tc_fqp`.`journey_id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id`
			WHERE
				`tc_fqp`.`active` = 1 AND
				`tc_fqu`.`id` IN (:question_ids) AND
				`cdb2`.`id` IN (:schools)
				{$sWhere}
			GROUP BY
				`tc_fqpr`.`id`
		";

		$aSql = [
			'from' => $this->aFilters['from']->format('Y-m-d H:i:s'),
			'until' => $this->aFilters['until']->format('Y-m-d H:i:s'),
			'question_ids' => array_column($this->getQuestions(), 'question_id'),
			'schools' => $this->aFilters['schools']
		];

		$aResult = (array)\DB::getQueryRows($sSql, $aSql);

		if(empty($aResult)) {
			throw new NoResultsException();
		}

		return $aResult;

	}

	/**
	 * @inheritdoc
	 */
	public function generateDataTable() {

		$aResult = $this->getQueryData();
		$aQuestions = $this->getQuestions();

		$aProviderResults = [];
		foreach($aResult as $aFeedbackResult) {
			if($aFeedbackResult['dependency_on'] !== $this->aFilters['question_dependency']) {
				throw new \RuntimeException('Feedback result has wrong dependency_on (provider) type?');
			}

			$iDependencyId = $aFeedbackResult['dependency_id'];
			$iQuestionId = $aFeedbackResult['question_id'];
			$iAnswer = $aFeedbackResult['answer'];

			// Keine Ahnung, ob das verbuggte Einträge sind, aber damit kann man hier nichts anfangen
			if(
				!empty($aFeedbackResult['dependency_on']) &&
				empty($iDependencyId)
			) {
				continue;
			}

			if(!isset($aProviderResults[$iDependencyId][$iQuestionId][$iAnswer])) {
				$aProviderResults[$iDependencyId][$iQuestionId][$iAnswer] = 0;
			}
			$aProviderResults[$iDependencyId][$iQuestionId][$iAnswer]++;
		}

		$aRows = [];
		foreach($aProviderResults as $iDependencyId => $aQuestionAnswers) {
			$oRow = new Table\Row();

			$sName = \Ext_TS_Marketing_Feedback_Question::getDependencyLabel($this->aFilters['question_dependency'], $iDependencyId);
			if(\System::d('debugmode') == 2) {
				$sName .= ' ('.$iDependencyId.')';
			}
			$oRow[] = new Table\Cell($sName);

			foreach($aQuestions as $aQuestion) {

				$fFeedbackSum = 0;
				$fFeedbackAvg = 0;
				if(isset($aQuestionAnswers[$aQuestion['question_id']])) {
					// Summe aller Feedbacks dieser Frage (für diese Abhängigkeit)
					$fFeedbackSum = array_sum($aQuestionAnswers[$aQuestion['question_id']]);

					// Durchschnitt der Antworten
					$aAvg = [];
					foreach($aQuestionAnswers[$aQuestion['question_id']] as $iAnswer => $iCount) {
						$aAvg[] = $iAnswer * $iCount;
					}
					if(!empty($aAvg)) {
						$fFeedbackAvg = array_sum($aAvg) / $fFeedbackSum;
					}
				}

				$oRow[] = new Table\Cell($fFeedbackSum, false, 'number_int');
				$oRow[] = new Table\Cell($fFeedbackAvg, false, 'number_float');

				foreach(array_keys($aQuestion['answers']) as $iAnswer) {
					$fValue = null;
					if(isset($aQuestionAnswers[$aQuestion['question_id']][$iAnswer])) {
						$fValue = $aQuestionAnswers[$aQuestion['question_id']][$iAnswer];
					}

					$oRow[] = new Table\Cell($fValue, false, 'number_int');
				}

			}

			$aRows[] = $oRow;
			$oTable[] = $oRow;
		}

		// Nach erster Zelle (Name des Providers) sortieren
		uasort($aRows, function($oRow1, $oRow2) {
			return strcmp($oRow1[0]->getValue(), $oRow2[0]->getValue());
		});

		$aTable = array_merge($this->generateHeaderRow(), $aRows);
		$oTable = new Table\Table();
		$oTable->exchangeArray($aTable);

		return $oTable;

	}

	/**
	 * @inheritDoc
	 */
	protected function getColumns() {
		$aColumns = [];

		$oColumn = new Column('question', null);
		$aColumns['question'] = $oColumn;

		$oColumn = new Column('feedback_count', self::t('Anzahl der Feedbacks'));
		$aColumns['feedback_count'] = $oColumn;

		$oColumn = new Column('feedback_average', self::t('Durchschnittliches Feedback'));
		$aColumns['feedback_average'] = $oColumn;

		$oColumn = new Column('answer', null);
		$aColumns['answer'] = $oColumn;

		return $aColumns;
	}

	/**
	 * @inheritdoc
	 */
	protected function generateHeaderRow() {

		$aQuestions = $this->getQuestions();
		$aColumns = $this->getColumns();

		$oRow1 = new Table\Row();
		$oRow1->setRowSet('head');
		$oRow2 = new Table\Row();
		$oRow2->setRowSet('head');

		$oCell = new Table\Cell('', true);
		$oCell->setRowspan(2);
		$oRow1[] = $oCell;

		foreach($aQuestions as $aQuestion) {

			$iColumnCount = 2 + count($aQuestion['answers']);
			$oRow2[] = $aColumns['feedback_count']->createCell(true);
			$oRow2[] = $aColumns['feedback_average']->createCell(true);

			foreach($aQuestion['answers'] as $iAnswer) {
				$oCell = $aColumns['answer']->createCell(true);
				$oCell->setValue($iAnswer);
				$oRow2[] = $oCell;
			}

			$oCell = $aColumns['question']->createCell(true);
			$oCell->setValue($aQuestion['title']);
			$oCell->setColspan($iColumnCount);

			if(\System::d('debugmode') == 2) {
				$oCell->setValue($oCell->getValue().' ('.$aQuestion['question_id'].')');
			}

			$oRow1[] = $oCell;

		}

		return [$oRow1, $oRow2];

	}

	/**
	 * @inheritdoc
	 */
	public function getBasedOnOptionsForDateFilter() {
		return [
			'answered' => self::t('Beantwortet'),
			'service_period' => self::t('Absoluter Leistungszeitraum')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getInfoTextListItems() {
		return [
			//self::t('Diese Statistik berücksichtigt nur Fragen mit Sternen und Skalen.')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function isShowingFiltersInitially() {
		return true;
	}

}
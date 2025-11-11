<?php

/**
 * @property string id
 * @property string changed
 * @property string created
 * @property string active
 * @property string result_id
 * @property string question_id
 * @property mixed value
 * @property string answer_is_right
 */
class Ext_Thebing_Placementtests_Results_Details extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_placementtests_results_details';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_ptrd';

	protected $_aJoinedObjects = [
		'result' => [
			'class' => 'Ext_Thebing_Placementtests_Results',
			'key' => 'result_id',
			'type' => 'parent',
			'check_active' => true,
		],
		'question' => [
			'class' => 'Ext_Thebing_Placementtests_Question',
			'key' => 'question_id',
			'type' => 'parent',
			'check_active' => true,
		],
	];

	protected $_aFormat = [
		'value' => [
			// TODO Warum wird jeder Wert als JSON gespeichert?
			'format' => 'JSON'
		]
	];

	public function evaluateAnswer() {

		$question = $this->getJoinedObject('question');
		$correctAnswers = $question->getCorrectAnswers();
		// Wenn etwas eingegeben wurde
		// Und eine korrekte Antwort existiert
		// Und es auch keine Textarea ist (kann nicht automatisch bewertet werden)
		if (
			!empty($this->value) &&
			!empty($correctAnswers) &&
			$question->type != Ext_Thebing_Placementtests_Question::TYPE_TEXTAREA
		) {

			$percent = Ext_TS_Placementtest::compareUserAnswerWithCorrectAnswer($question->type, $this->value, $correctAnswers);

			$accuracyInPercent = $this->getJoinedObject('result')->getJoinedObject('placementtest')->getAccuracyInPercent();

			// Wenn das Feld nicht gesetzt ist, wird mit 100% gerechnet.
			if ($accuracyInPercent === null) {
				$accuracyInPercent = 100;
			}

			if ($accuracyInPercent <= $percent) {
				// Die Antwort darf als richtig gewertet werden.
				$this->answer_is_right = 1;
			} else {
				$this->answer_is_right = 0;
			}

			$this->answer_correctness = $percent;
		} elseif (empty($this->value) && $question->always_evaluate == 1) {
			// Wenn die Frage nicht beantwortet wurde, aber "Immer bewerten" angetickt wurde, wird die Frage dann
			// natürlich auch bewertet mit "falsch" ( Ticket #20018 )

			$this->answer_is_right = 0;
			$this->answer_correctness = 0;
		}
	}

}
<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $version_id
 * @property int $setting_id
 * @property string $type (ENUM)
 * @property string $date (DATE)
 * @property string|float $amount
 */
class Ext_TS_Document_Version_PaymentTerm extends Ext_TC_Basic {

	protected $_sTable = 'ts_documents_versions_paymentterms';

	protected $_sPlaceholderClass = \Ts\Service\Placeholder\Booking\Document\PaymentTerm::class;

	protected $_aFormat = [
		'type' => [
			'required' => true
		],
		'date' => [
			'format' => 'DATE'
		],
//		'amount' => [
//			'format' => 'FLOAT_NOTNEGATIVE'
//		]
	];

	protected $_aJoinedObjects = [
		'version' => [
			'class' => Ext_Thebing_Inquiry_Document_Version::class,
			'key' => 'version_id',
			'type' => 'parent',
			'bidirectional' => true
		]
	];

	private ?float $amountPayed = null;

	/**
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function getVersion() {
		return $this->getJoinedObject('version');
	}

	public function getAmount(): \Ts\Dto\Amount
	{
		$document = $this->getVersion()?->getDocument();
		return new \Ts\Dto\Amount((float)$this->amount, ($document->exist()) ? $document->getCurrency() : null);
	}

	public static function fromPaymentConditionRow(\Ext_TS_Document_PaymentCondition_Row $row): self {

		$term = new self();
		$term->setting_id = $row->iSettingId;
		$term->type = $row->sType;
		$term->date = $row->dDate->format('Y-m-d');
		$term->amount = $row->fAmount;

		return $term;

	}

	/**
	 * Interner State: Auf Basis von Version Zahlungsinformation für alle Payment Terms berechnen
	 *
	 * @return void
	 */
	private function calculatePayedAmounts() {

		$version = $this->getVersion();
		$payed = round($version->getDocument()->getPayedAmount(), 2);

		foreach ($version->getPaymentTerms() as $term) {
			$amount = (float)$term->amount;
			if ($payed >= $amount) {
				// Term ist voll bezahlt
				$term->amountPayed = $amount;
				$payed -= $amount;
			} elseif ($payed > 0) {
				// Term ist teilweise bezahlt
				$term->amountPayed = $payed;
				$payed = 0;
			} else {
				// Term ist nicht bezahlt
				$term->amountPayed = 0;
			}
		}

	}

	public function getPayedAmount(): float {

		if ($this->amountPayed === null) {
			$this->calculatePayedAmounts();
		}

		return $this->amountPayed;

	}

	public function getOpenAmount(): float {

		return (float)$this->amount - $this->getPayedAmount();

	}

	/**
	 * Fällige Fälligkeiten ermitteln oder nächste Fälligkeit (oder keine, wenn es keine mehr gibt)
	 *
	 * @param Collection<Ext_TS_Document_Version_PaymentTerm> $terms
	 * @return Collection<Ext_TS_Document_Version_PaymentTerm>
	 */
	public static function calculateDueTerms(Collection $terms) {

		$openTerms = $terms
			->filter(fn(self $term) => $term->getOpenAmount() > 0)
			->sort(fn(self $term1, self $term2) => Carbon::make($term1->date) > Carbon::make($term2->date));

		// Alle fälligen Terms
		$dueTerms = $openTerms->filter(fn(self $term) => Carbon::make($term->date)->lte(Carbon::now()));

		// Wenn es keinen fälligen Term gibt: Nächstbeste Fälligkeit
		if ($dueTerms->isEmpty()) {
			$dueTerms = $openTerms->take(1);
		}

		return $dueTerms->values();

	}

}

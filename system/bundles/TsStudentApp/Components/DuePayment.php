<?php

namespace TsStudentApp\Components;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use TsStudentApp\AppInterface;

class DuePayment implements Component
{
	private ?string $paymentLink = null;

	/**
	 * @var Collection<\Ext_TS_Document_Version_PaymentTerm>
	 */
	private Collection $terms;

	public function __construct(private readonly AppInterface $appInterface)
	{
	}

	public function getKey(): string
	{
		return 'due-payment';
	}

	public function terms(Collection $terms): static
	{
		$this->terms = $terms;
		return $this;
	}

	public function paymentLink(string $paymentLink): static
	{
		$this->paymentLink = $paymentLink;
		return $this;
	}

	public function toArray(): array
	{
		$data = [
			'title' => $this->terms->count() === 1 ?
				$this->appInterface->t('Due payment') :
				$this->appInterface->t('Due payments'),
			'lines' => $this->terms->map(function (\Ext_TS_Document_Version_PaymentTerm $term) {
				return sprintf('%s → %s',
					$this->appInterface->formatDate2(Carbon::parse($term->date), 'L'),
					\Ext_Thebing_Format::Number($term->amount, $this->appInterface->getInquiry()->getCurrency(), $this->appInterface->getSchool())
				);
			})
		];

		if (version_compare($this->appInterface->getAppVersion(), '3.0', '<')) {
			$data = [
				'icon' => 'alert-circle-outline',
				'due' => $data
			];
		}

		$data['payment_link'] = $this->paymentLink;

		return $data;
	}
}
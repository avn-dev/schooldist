<?php

namespace TsStudentApp\Pages\Home\Boxes;

use Carbon\Carbon;
use TsStudentApp\AppInterface;
use TsStudentApp\Components\Component;
use TsStudentApp\Components\Container;

class DuePaymentBox implements Box
{
	const KEY = 'due-payment';

	public function __construct(private readonly AppInterface $appInterface)
	{
	}

	public function generate(): ?Component
	{
		$inquiry = $this->appInterface->getInquiry();

		$dueTerms = $inquiry->getDueTerms()
			->reject(fn(\Ext_TS_Document_Version_PaymentTerm $term) => Carbon::make($term->date)->gt(Carbon::now()));

		if ($dueTerms->isEmpty()) {
			return null;
		}

		$container = new Container();

		$container->add(
			\TsStudentApp\Facades\Component::DuePayment($dueTerms)
//				->paymentLink('https://fidelo.com')
		);

		return $container;
	}
}
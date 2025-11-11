<?php

namespace Ts\Http\Resources\Admin\Inquiry;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Ext_Thebing_Inquiry_Document
 */
class InvoiceResource extends JsonResource
{
	public function toArray($request)
	{
		$amount = new \Ts\Dto\Amount($this->getAmount(), $this->getCurrency());

		$array = [
			'label' => $this->getLabel(),
			'number' => $this->document_number,
			'amount' => $amount->toString($this->getSchool())
		];

		if (!empty($path = $this->getLastVersion()->getPath())) {
			$array['file'] = [
				'name' => basename($path),
				'path' => '/storage'.$path,
			];
		}

		return $array;
	}
}
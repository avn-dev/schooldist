<?php

namespace Ts\Gui2\Format\Accounting;

/**
 * @internal
 */
class PartialInvoice extends \Ext_Gui2_View_Format_Abstract
{
	public function format($value, &$column = null, &$resultData = null): string
	{
		$formatedValue = $value;
		[$partialInvoicesInfo, $documentIds] = explode('|', $resultData['partial_invoices_info']);
		if ($column->select_column == 'number_partial_invoices_payed') {
			$formatedValue = 0;
			$documentIds = array_filter(explode(',', trim($documentIds)));

			foreach ($documentIds as $documentId) {
				$document = \Ext_Thebing_Inquiry_Document::getInstance($documentId);

				if (
					$document->exist() &&
					$document->getAmount() <= $document->getPayedAmount()
				) {
					$formatedValue++;
				}
			}
		}

		if ($column->select_column == 'partial_invoices_info') {
			$formatedValue = $partialInvoicesInfo;
		}

		return strval($formatedValue);
	}
}
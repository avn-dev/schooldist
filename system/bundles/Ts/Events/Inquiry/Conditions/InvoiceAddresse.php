<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Dto\ExpectedPayment;
use Ts\Events\Inquiry\InquiryDayEvent;
use Ts\Interfaces\Events\InquiryEvent;

class InvoiceAddresse implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Rechnungsadressat');
	}

	public static function toReadable(Settings $settings): string
	{
		$types = array_intersect_key(
			\Ext_Thebing_Document_Address::getLabels(),
			array_flip($settings->getSetting('addressees'))
		);

		return sprintf(
			EventManager::l10n()->translate('Wenn Rechnungsadressat "%s"'),
			implode(', ', $types)
		);
	}

	public function passes(InquiryDayEvent $event) {

		$inquiry = $event->getInquiry();

		$addAddressee = function (\Ext_Thebing_Inquiry_Document $document, &$addressees) {
			if (null !== $addressee = $document->getLastVersion()?->getAddressee()) {
				$addressees[] = $addressee[1];
			}
		};

		$addressees = [];
		if ($event->getManagedObject()?->getSetting('event_type') === 'reminder_date') {
			// Fälligkeitsdatum
			$nextPayment = $inquiry->getIndexPaymentTermData('paymentterms_next_payment_object');
			if ($nextPayment instanceof ExpectedPayment && $nextPayment->isDue()) {
				$addAddressee($nextPayment->document, $addressees);
			}
		} else {
			$invoices = $inquiry->getDocuments('invoice_brutto_without_proforma', true, true);
			foreach ($invoices as $invoice) {
				$addAddressee($invoice, $addressees);
			}
		}

		$intersect = array_intersect($addressees, $this->managedObject->getSetting('addressees', []));

		return !empty($intersect);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Adressat'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_addressees',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_Thebing_Document_Address::getLabels()
		]));
	}

}
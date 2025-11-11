<?php

namespace Ts\Handler\Communication\Allocation;

use Communication\Interfaces\MessageAllocationAction;
use Illuminate\Http\Request;
use Tc\Traits\Communication\Allocation\WithDialog;

/**
 * @deprecated
 */
class ExistingInquiry implements MessageAllocationAction {
	use WithDialog;

	public function isValid(\Ext_TC_Communication_Message $message): bool {

		// Nur eingehende Nachrichten können zu Anfragen umgewandelt werden
		if ($message->direction === 'in') {
			$relationsEntities = array_column($message->relations, 'relation');

			// E-Mail darf noch mit keiner Buchung/Anfrage verknüpft sein
			if (!in_array(\Ext_TS_Inquiry::class, $relationsEntities)) {
				return true;
			}
		}

		return false;
	}

	public function prepareDialog(\Ext_Gui2 $gui2, \Ext_Gui2_Dialog $dialog, \Ext_TC_Communication_Message $message): void {

		$dialog->height = 300;
		$dialog->width = 700;

		$dialog->setElement($dialog->createRow($gui2->t('Mit Buchung verknüpfen'), 'autocomplete', array(
			'db_column' => 'autocomplete_inquiry_id',
			'autocomplete' => new \Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry(\Ext_TS_Inquiry::TYPE_BOOKING_STRING),
			'required' => true,
			'skip_value_handling' => true
		)));

	}

	public function save(\Ext_Gui2 $gui2, \Ext_TC_Communication_Message $message, Request $request): bool|array {

		$inquiryId = (int)$request->input('save.autocomplete_inquiry_id', 0);

		$inquiry = \Ext_TS_Inquiry::query()
			->where('type', '&', \Ext_TS_Inquiry::TYPE_BOOKING)
			->findOrFail($inquiryId);

		// Nachricht mit der Abfrage verknüpfen

		$relations = $message->relations;
		$relations[] = [
			'relation' => \Ext_TS_Inquiry::class,
			'relation_id' => $inquiry->getId()
		];
		$message->relations = $relations;
		$message->save();

		\Ext_Gui2_Index_Registry::insertRegistryTask($inquiry);

		return true;
	}

}

<?php

namespace Ts\Traits\Events\Testable;

use Tc\Gui2\Data\EventManagementData;
use Tc\Interfaces\Events\Settings;

trait WithInquiryTesting
{
	public static function buildTestEvent(Settings $settings): static
	{
		$inquiry = \Ext_TS_Inquiry::query()->findOrFail($settings->getSetting('inquiry_id'));
		return new self($inquiry);
	}

	public static function prepareTestingGui2Dialog(\Ext_Gui2_Dialog $dialog, EventManagementData $data): void
	{
		self::addInquirySelectionField($dialog, $data);
	}

	private static function addInquirySelectionField(\Ext_Gui2_Dialog $dialog, EventManagementData $data): void
	{
		$dialog->setElement($dialog->createRow($data->t('Buchung'), 'autocomplete', array(
			'db_alias' => '',
			'db_column'=>'inquiry_id',
			'required'=>true,
			'autocomplete'=>new \Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry(\Ext_TS_Inquiry::TYPE_BOOKING_STRING)
		)));
	}

}
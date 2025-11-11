<?php

namespace Ts\Observers;

use Ts\Events\Inquiry;

class InquiryObserver
{
	public function created(\Ext_TS_Inquiry $inquiry) {
		Inquiry\CreatedEvent::dispatch($inquiry);
	}

	public function updated(\Ext_TS_Inquiry $inquiry) {
		Inquiry\UpdatedEvent::dispatch($inquiry);
	}

	public function saved(\Ext_TS_Inquiry $inquiry) {
		Inquiry\SavedEvent::dispatch($inquiry);
	}
}
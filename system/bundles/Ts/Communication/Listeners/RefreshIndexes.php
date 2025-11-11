<?php

namespace Ts\Communication\Listeners;

use Communication\Events\MessagesSent;
use Core\Notifications\Channels\MessageTransport;

class RefreshIndexes
{
	public function handle(MessagesSent $event)
	{
		$logs = $event->getTransportCollection()
			->map(fn (MessageTransport $transport) => $transport->getLog())
			->filter(fn ($log) => $log instanceof \Ext_TC_Communication_Message && $log->exist());

		foreach ($logs as $log) {
			/* @var \Ext_TC_Communication_Message $log */
			$inquiries = $log->searchRelations(\Ext_TS_Inquiry::class);
			foreach ($inquiries as $inquiry) {
				\Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->id, 0);
			}
		}
	}
}
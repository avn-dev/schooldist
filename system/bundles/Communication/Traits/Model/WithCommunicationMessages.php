<?php

namespace Communication\Traits\Model;

use Communication\Facades\Communication;

trait WithCommunicationMessages
{
	/**
	 * Model der letzten Nachricht
	 *
	 * @return \Ext_TC_Communication_Message
	 */
	public function getLastMailMessageLog() {

		$message = Communication::basedOn($this)->messages()
			->orderBy('created', 'DESC')
			->where('tc_cm.type', '!=', 'notice')
			->first();

		return $message;
	}
}
<?php

namespace Ts\Notifications\Channels\Messages;

use Admin\Notifications\Channels\Messages\AdminMailMessage as BaseMessage;

class AdminMailMessage extends BaseMessage
{
	/**
	 * Methode dient nur dazu den E-Mail-Account der Schule zu nutzen
	 *
	 * @param \Ext_Thebing_School $school
	 * @return $this
	 */
	public function school(\Ext_Thebing_School $school): static
	{
		\Ext_Thebing_Mail::$oSchool = $school;
		return $this;
	}
}
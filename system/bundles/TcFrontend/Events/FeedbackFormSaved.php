<?php

namespace TcFrontend\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FeedbackFormSaved
{
	use Dispatchable;

	public function __construct(protected \Ext_TC_Marketing_Feedback_Questionary_Process $process) {}

	public function getProcess(): \Ext_TC_Marketing_Feedback_Questionary_Process
	{
		return $this->process;
	}
}

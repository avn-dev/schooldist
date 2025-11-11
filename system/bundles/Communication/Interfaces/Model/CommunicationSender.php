<?php

namespace Communication\Interfaces\Model;

interface CommunicationSender
{
	public function getCommunicationSenderName(string $channel, CommunicationSubObject $subObject = null): string;

	public function getCommunicationEmailAccount(CommunicationSubObject $subObject = null): ?\Ext_TC_Communication_EmailAccount;
}
<?php

namespace Communication\Interfaces\Model;

interface CommunicationSubObject
{
	public function getCommunicationDefaultLayout(): ?\Ext_TC_Communication_Template_Email_Layout;
}
<?php

namespace Ts\Interfaces\Events;

interface InquiryEvent extends SchoolEvent {

	public function getInquiry(): \Ext_TS_Inquiry;

}
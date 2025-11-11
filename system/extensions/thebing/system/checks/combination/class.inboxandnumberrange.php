<?php

class Ext_Thebing_System_Checks_Combination_InboxAndNumberRange extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Inbox, number range and register forms update';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Inbox, number range and register forms update and allocation';
		return $sDescription;
	}

	public function executeCheck(){

		// Default Inbox anlegen
		$oCheck1 = new Ext_Thebing_System_Checks_Inquiry_EmptyInbox();
		$oCheck1->executeCheck();

		// Default customer number ranges anlegen
		$oCheck2 = new Ext_Thebing_System_Checks_Customer_Numberrange();
		$oCheck2->executeCheck();

		// Nummernkreiszuweisungen alle Inboxen zuweisen, wenn keine eingetragen ist (Pflichtfeld)
		// Dieser Check ist von den anderen beiden abhängig!
		$oCheck3 = new Ext_Thebing_System_Checks_Numberranges2();
		$oCheck3->executeCheck();

		return true;

	}

}

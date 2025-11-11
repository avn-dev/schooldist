<?php

// Neues Smarty-Objekt erstellen
$oSmarty = new \Cms\Service\Smarty();

/// Neuen Kreditkartenservice erstellen
$oCreditCardPaymentService = new \Office\Service\CreditCardPaymentService();

// Auslesen, was als nächstes passieren soll
$sNext = $_VARS['next'];

switch ($sNext) {
	// Wenn erster aufruf (initial request)
	case NULL:
		$sHash = $_VARS['hash'];
		$oCreditCardPaymentService->preparePayment($oSmarty, $sHash);
		break;
	// Rechnung anzeigen
	case 'show_invoice':
		$sHash = $_VARS['hash'];
		$oCreditCardPaymentService->showInvoice($oSmarty, $sHash);
		break;
	// Wenn eine Zahlung getätigt werden soll
	case 'confirm':
		$oCreditCardPaymentService->confirmPayment($oSmarty, $_VARS);
		break;
	// Sonst
	default:
		$oSmarty->assign('errors', 'not_found');
		break;
}

$oSmarty->displayExtension($element_data);
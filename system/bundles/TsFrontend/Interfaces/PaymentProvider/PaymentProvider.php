<?php

namespace TsFrontend\Interfaces\PaymentProvider;

use Illuminate\Support\Collection;
use Tc\Service\Language\Frontend;
use TsFrontend\Exceptions\PaymentError;

interface PaymentProvider {

	public function setKey(string $key);

	/**
	 * @param \Ext_Thebing_School $school
	 */
	public function setSchool(\Ext_Thebing_School $school);

	/**
	 * URL, die im Frontend asynchron geladen wird (Widget, SDK, etc.)
	 *
	 * @return string
	 */
	public function getScriptUrl(): string;

	/**
	 * Vorausgehendes Payment: Falls Zahlungsanbieter bereits vorab Daten braucht, um verfügbare Zahlungsmethoden zu ermitteln (z.B. Klarna oder TransferMate)
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param Collection $items
	 * @param string $description
	 * @return mixed
	 */
	public function createPreliminaryPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description);

	/**
	 * @TODO Daten sollten in die Instanz gesetzt werden, da das ggf. auch andere Methoden brauchen (createPreliminaryPayment, capturePayment)
	 *
	 * Klick auf Zahlungsbutton: Zahlung erzeugen
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param Collection $items
	 * @param string $description
	 * @param Collection|null $invoices
	 * @return array
	 */
	public function createPayment(\Ext_TS_Inquiry $inquiry, Collection $items, string $description, Collection $invoices = null): array;

	/**
	 * Beim Submit: Zahlung capturen und damit endgültig abschließen
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param Collection $data
	 * @return ?\Ext_TS_Inquiry_Payment_Unallocated
	 * @throws PaymentError
	 */
	public function capturePayment(\Ext_TS_Inquiry $inquiry, Collection $data): ?\Ext_TS_Inquiry_Payment_Unallocated;

	/**
	 * @param Frontend $languageFrontend
	 * @return array
	 */
	public function getTranslations(Frontend $languageFrontend): array;

	/**
	 * Zahlungsanbieter auf Methoden aufteilen
	 *
	 * @param Frontend $languageFrontend
	 * @return array
	 */
	public function getPaymentMethods(Frontend $languageFrontend): array;

	/**
	 * @param array $data
	 * @return mixed
	 */
	public function setPaymentMethod(array $data);

	/**
	 * Vue Component
	 *
	 * @return string
	 */
	public function getComponentName(): string;

	/**
	 * @return \Ext_Thebing_Admin_Payment
	 */
	public function getAccountingPaymentMethod(): \Ext_Thebing_Admin_Payment;

	/**
	 * Sortierung der Zahlungsanbieter
	 *
	 * @return int
	 */
	public function getSortOrder(): int;

}

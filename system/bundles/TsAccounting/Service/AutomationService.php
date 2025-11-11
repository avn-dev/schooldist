<?php

namespace TsAccounting\Service;

use Core\Entity\ParallelProcessing\ErrorStack;
use Core\Entity\ParallelProcessing\Stack;

class AutomationService {

	const SYSTEM_CREATOR_ID = 0;

	/**
	 * Führt die automatische Rechnungsfreigabe aus
	 *
	 * TODO - das mit dem Errorstack ist nicht optimal, sauberer wäre es die Einträge komplett im PP abzuarbeiten aber dadurch
	 * das die automatische Weiterverarbeitung (je nach Einstellung) im Anschluss passieren kann müssen die Einträge schon
	 * freigegeben sein
	 *
	 * @return false
	 * @throws \Exception
	 */
	public static function startDocumentRelease(bool $ignoreErrors = false) {

		$automaticCompanies = self::getCompaniesWithReleaseAutomation();
		if(empty($automaticCompanies)) {
			return false;
		}

		$unrealeasedDocuments = \Ext_Thebing_Inquiry_Document::getRepository()
			->getUnreleasedDocuments();

		$ids = array_column($unrealeasedDocuments, 'id');

		$now = new \DateTime();
		foreach($unrealeasedDocuments as $document) {
			/* @var \Ext_Thebing_Inquiry_Document $document */

			if(
				!$document->exist() ||
				!$document->isActive() ||
				!$document->getLastVersion()
			) {
				continue;
			}

			$company = $document->getCompany();

			if(
				$company &&
				self::checkCompanyReleaseExecution($company)
			) {

				$created = (new \DateTime())->setTimestamp($document->created);
				$created->modify(sprintf('+%shours', $company->automatic_document_release_after));

				// Nur freigegeben wenn die Einstellungen "nach x Stunden" passt
				if($created <= $now) {
					try {

						self::getLogger()->info('Release document', ['id' => $document->getId()]);

						$document->releaseDocument(($ignoreErrors) ? ['*'] : [], $ids, self::SYSTEM_CREATOR_ID);

						if($document->hasError()) {
							throw new \RuntimeException($document->getError());
 						}

					} catch(\Exception $e) {

						self::getLogger()->error('Document release failed', ['company' => $company->getId(), 'document' => $document->getId(), 'exception' => $e->getMessage()]);

						// Wenn die Freigabe nicht erfolgreich war den Eintrag in den Error-Stack schreiben.
						self::writeToErrorStack('document', (int) $document->getId(), [
							'document' => $document->getId(),
							'document_number' => $document->document_number,
							'exception' => $e->getMessage(),
						]);				
					}
				}
			}
		}

		\Ext_Gui2_Index_Stack::executeCache();

		return true;
	}

	/**
	 * Führt die automatische Zahlungsfreigabe aus
	 *
	 * TODO - das mit dem Errorstack ist nicht optimal, sauberer wäre es die Einträge komplett im PP abzuarbeiten aber dadurch
	 * das die automatische Weiterverarbeitung (je nach Einstellung) im Anschluss passieren kann müssen die Einträge schon
	 * freigegeben sein
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function startPaymentRelease() {

		$automaticCompanies = self::getCompaniesWithReleaseAutomation();

		if(empty($automaticCompanies)) {
			return false;
		}

		$unrealeasedPayments = \Ext_Thebing_Inquiry_Payment::getRepository()
			->getUnreleasedPayments();

		$now = new \DateTime();

		foreach($unrealeasedPayments as $payment) {
			/* @var \Ext_Thebing_Inquiry_Payment $payment */

			$company = $payment->getCompany();

			if(
				$company &&
				self::checkCompanyReleaseExecution($company)
			) {

				$created = (new \DateTime())->setTimestamp($payment->created);
				$created->modify(sprintf('+%shours', $company->automatic_payment_release_after));

				// Nur freigegeben wenn die Einstellungen "nach x Stunden" passt
				if($created <= $now) {
					try {

						self::getLogger()->info('Release payment', ['id' => $payment->getId()]);

						$payment->releasePayment(self::SYSTEM_CREATOR_ID);

						if($payment->hasError()) {
							throw new \RuntimeException($payment->getError());
						}

					} catch(\Exception $e) {

						self::getLogger()->error('Payment release failed', ['company' => $company->getId(), 'payment' => $payment->getId(), 'exception' => $e->getMessage()]);

						// Wenn die Freigabe nicht erfolgreich war den Eintrag in den Error-Stack schreiben.
						self::writeToErrorStack('payment', (int) $payment->getId(), [
							'payment' => $payment->getId(),
							'exception' => $e->getMessage(),
						]);
					}
				}

			}

		}

		return true;
	}

	/**
	 * Führt die automatische Weiterverarbeitung aus
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function startBookingstackExport() {

		$automaticCompanies = \TsAccounting\Entity\Company::getRepository()->findBy(['automatic_stack_export' => 1]);

		$files = [];

		$currentHour = (int)date('G');

		foreach($automaticCompanies as $company) {

			if($currentHour != $company->automatic_stack_export_time) {
				continue;
			}

			$notExported = \Ext_TS_Accounting_BookingStack::getRepository()
				->findBy(['company_id' => $company->getId()]);

			$ids = array_column($notExported, 'id');

			if(!empty($ids)) {
				$file = BookingStackService::saveHistory($ids, $company, 'export');

				self::getLogger()->info('Generate export file', ['company' => $company->getId(), 'file' => $file]);

				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Schreibt eine Entität in der Error-Stack des Parallel-Processings damit dieser erneut abgearbeitet werden kann
	 *
	 * @param string $type
	 * @param int $id
	 * @param array $errorData
	 */
	protected static function writeToErrorStack(string $type, int $id, array $errorData= []): void {

		$repository = Stack::getRepository();
		$stackEntry = $repository->generateEntry('ts-accounting/entity-release', [
			'type' => $type,
			'entity_id' => $id,
			'creator_id' => self::SYSTEM_CREATOR_ID,
			'automatic' => 1 // nur für den hash
		], 100, false);

		// Nur hinzufügen wenn noch nicht vorhanden
		if(null === ErrorStack::getRepository()->findOneBy(['hash' => $stackEntry['hash']])) {
			$repository->writeTaskToErrorStack($stackEntry, $errorData);
		}

	}

	/**
	 * Prüft ob die automatische Freigabe ausgeführt werden soll
	 * 
	 * @param \TsAccounting\Entity\Company $company
	 * @return bool
	 */
	protected static function checkCompanyReleaseExecution(\TsAccounting\Entity\Company $company): bool {

		$now = new \DateTime();

		if(
			$company->hasAutomaticRelease() &&
			$now->format('G') == $company->automatic_release_time
		) {
			return true;
		}

		return false;
	}

	/**
	 * Liefert alle Firmen mit automatischer Freigabe
	 *
	 * @return array
	 */
	protected static function getCompaniesWithReleaseAutomation(): array {
		return \TsAccounting\Entity\Company::getRepository()->findBy(['automatic_release' => 1]);
	}

	protected static function getLogger() {
		return \Log::getLogger('ts_accounting_automation');
	}
}

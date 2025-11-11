<?php

namespace TsAccounting\Handler\ParallelProcessing;

use Core\Exception\ParallelProcessing\RewriteException;
use Core\Handler\ParallelProcessing\TypeHandler;
use Core\Interfaces\ParallelProcessing\TaskAware;
use TsAccounting\Service\eInvoice\Spain\Verifactu;

final class RegisterInvoice extends TypeHandler implements TaskAware
{

	const TASK_NAME = 'register-invoice';

	private array $task;

	public function execute(array $data, $debug = false)
	{
		$document = \Ext_Thebing_Inquiry_Document::getInstance($data['document_id']);
		if (!$document->exist()) {
			throw new \RuntimeException('Document with ID: '.$data['document_id'].' not found.');
		}

		if ($data['type'] === 'verifactu') {
			if ($document->tax_registered) {
				throw new \RuntimeException('Document already registered.');
			}
			if(\System::wd()->hasHook('ts_register_invoice')) {
				\System::wd()->executeHook('ts_register_invoice', $document);
			}
		} elseif ($data['type'] === 'office') {
			if ($document->office_registered) {
				throw new \RuntimeException('Document already registered.');
			}
			if (!$document->getLastVersion()->path) {
				throw new \RuntimeException('Document has no path.');
			}
			$hash = $document->getLastVersion()->getFileHash();
			if (!$hash) {
				throw new \RuntimeException('PDF Hash creation failed.');
			}
			$resultOfficeRegister = (new \Licence\Service\Office\Api())->registerInvoice(\Carbon\Carbon::createFromTimestamp($document->created), $hash, $document->document_number);
			if (
				!$resultOfficeRegister->isSuccessful() ||
				!$resultOfficeRegister->get('success')
			) {
				// Nochmal versuchen
				throw new RewriteException('Register in Office failed.');
			} else {
				$document->office_registered = 1;
				$document->save();
			}
		} else {
			throw new \RuntimeException('Unknown type: '.$data['type']);
		}

		\Ext_Gui2_Index_Stack::save();
		\Ext_Gui2_Index_Stack::executeCache();

		return true;
	}

	public function getLabel()
	{
		return \L10N::t('Registrierung einer Rechnung', 'TsAccounting');
	}

	public function getRewriteAttempts()
	{
		if ((int)$this->task['execution_count'] >= 10) {
			return (int)$this->task['execution_count'];
		}

		return (int)$this->task['execution_count'] + 1;
	}

	public function setTask(array $task): void
	{
		$this->task = $task;
	}

	public function getErrorDescription(array $data, array $errorData = []): string
	{
		return sprintf(\L10N::t('Registrierung des Dokuments “%s” bei “%s” fehlgeschlagen.'), $data['document_id'] ?? 'Unbekannte ID', $data['type'] ?? 'Unbekannter Typ');
	}
}
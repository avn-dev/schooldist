<?php

namespace TsFlywire\Service;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use Ts\Events\Inquiry\NewPayment;
use Ts\Events\Inquiry\PaymentAllocationFailed;

class SyncFiles {

	// CSV-Spalten: Transfer Reference, Student ID, Student First Name, Student Last Name, Transfer Amount, Transfer Finished Date
	private $csvColumns = [
		'transaction' => 'Transfer Reference',
		'customer_number' => 'Student ID',
		'customer_firstname' => 'Student First Name',
		'customer_lastname' => 'Student Last Name',
		'amount' => 'Transfer Amount',
		'date' => 'Transfer Finished Date',
		'comment' => 'Transfer Reference'
	];

	private array $config;

	private SFTP $sftp;

	private \Monolog\Logger $log;

	/**
	 * @param array $aConfig
	 */
	public function __construct(array $aConfig) {

		$this->config = $aConfig;
		$this->log = self::logger();

		$this->config['columns'] = $this->csvColumns;

		if (empty($this->config['prefix'])) {
			throw new \RuntimeException('Prefix is not set!');
		}

	}

	public static function default(): self {

		$aConfig = [
			'ssh_host' => 'sftp.flywire.com',
			'ssh_port' => 22,
			'ssh_user' => \System::d('flywire_ssh_user'),
			'ssh_key' => \Util::getDocumentRoot().'../.ssh/flywire-production.key',
			'prefix' => \System::d('flywire_prefix'),
			'currency' => \System::d('flywire_currency'),
			'method_id' => \System::d('flywire_method_id')
		];

		return new self($aConfig);
	}

	public static function logger() {
		return \Log::getLogger('flywire');
	}

	/**
	 * Synchronisieren
	 */
	public function sync() {

		try {

			$this->log->info('Sync started');
			\DB::begin(__CLASS__);

			$this->connect();
			$this->syncFiles();

			\DB::commit(__CLASS__);
			$this->log->info('Sync ended');

			$this->sftp->disconnect();

			// Manuell ausführen, da die Cronjobs da nichts machen
			\Ext_Gui2_Index_Stack::save();

		} catch (\Exception $e) {
			$this->log->error('Exception: '.$e->getMessage(), [$e->getTraceAsString()]);
			\Ext_Thebing_Util::reportError('TsFlywire-SyncFiles Exception', $e);
			throw $e;
		}

	}

	/**
	 * SFTP-Verbindung aufbauen
	 */
	private function connect() {

		if (!is_file($this->config['ssh_key'])) {
			throw new \RuntimeException('SSH key is missing!');
		}

		$key = PublicKeyLoader::load(file_get_contents($this->config['ssh_key']));

		$this->sftp = new SFTP($this->config['ssh_host'], $this->config['ssh_port']);
		$this->sftp->login($this->config['ssh_user'], $key);

	}

	/**
	 * Dateien synchronisieren
	 */
	private function syncFiles() {

		$files = $this->sftp->nlist('/incoming');
		
		$this->log->info('Files', (array)$files);

		foreach ($files as $sFile) {

			if (
				$sFile === '.' ||
				$sFile === '..'
			) {
				continue;
			}

			// Präfix überprüfen, alle anderen Dateien ignorieren
			if (strpos($sFile, $this->config['prefix']) !== 0) {
				continue;
			}

			// Erste Datei löschen, da hier ein Satz ohne CSV-Format drin steht
			if (strpos($sFile, $this->config['prefix'].'1970-01-01') === 0) {
				$this->sftp->delete('/incoming/'.$sFile, false);
				continue;
			}

			if ($this->isFileSynced($sFile)) {
				continue;
			}

			$sPath = '/incoming/'.$sFile;

			$rTmp = tmpfile();
			$this->sftp->get($sPath, $rTmp);
			rewind($rTmp);

			$iLine = 0;
			$aColumnsIndex = [];
			while (($aLine = fgetcsv($rTmp)) !== false) {

				// Flywire stellt die Columns pro Kunde ein, daher müssen diese gemappt werden
				if ($iLine === 0) {

					foreach ($this->config['columns'] as $sKeyConfig => $sLabelConfig) {
						$bFound = false;
						foreach ($aLine as $iKey => $sLabel) {
							if ($sLabel == $sLabelConfig) {
								$aColumnsIndex[$sKeyConfig] = $iKey;
								$bFound = true;
							}
						}

						if (!$bFound) {

							throw new \RuntimeException('Flywire CSV column missing: '.$sKeyConfig.' ('.$sLabelConfig.'; '.implode(', ', $aLine).')');
						}
					}

				} else {

					if (empty($aColumnsIndex)) {
						throw new \RuntimeException('$aColumnsIndex is empty');
					}

					$aData = [];
					foreach (array_keys($this->config['columns']) as $sKeyConfig) {
						$aData[$sKeyConfig] = $aLine[$aColumnsIndex[$sKeyConfig]];
					}

					try {
						$this->createPayment($aData, $sFile);
					} catch (\Exception $e) {
						$this->log->error('Parsing of CSV line failed for payment! ', [
							'message' => $e->getMessage(),
							'trace' => $e->getTraceAsString(),
							'data' => $aData,
							'file' => $sFile
						]);
					}

				}

				$iLine++;

			}

			$this->log->info($sFile.' synced: '.($iLine - 1).' new payments');

			$this->markFileAsSynced($sFile, $iLine - 1);

			// Achtung: fgetcsv() setzt Pointer von $rTmp ans Ende, daher muss rewind benutzt werden
			\System::wd()->executeHook('ts_flywire_sync_file', $sFile, $rTmp);

		}

	}

	/**
	 * @param array $aData
	 * @param string $sFile
	 */
	private function createPayment(array $aData, $sFile) {

		$aData['amount'] = (float)$aData['amount'];
		$aData['date'] = (new \DateTime($aData['date']))->format('Y-m-d');

		// Automatisches Zuweisen: Kundennummer vorhanden und Kunde hat nur eine Buchung
		// Außerdem Nachnamen überprüfen, da eine falsche Nummer zur Zuweisung eines falschen Kunden sorgen würde
		$oInquiry = null;
		if (
			!empty($aData['customer_lastname']) &&
			!empty($aData['customer_number'])
		) {
			$oRepo = \Ext_TS_Inquiry_Contact_Traveller::getRepository();
			$oContact = $oRepo->findOneByLastnameAndNumber($aData['customer_lastname'], $aData['customer_number']);

			if ($oContact !== null) {
				$aInquiries = $oContact->getInquiries(false, true);
				if (count($aInquiries) === 1) {
					$oInquiry = reset($aInquiries);
				}
			}
		}

		$oUnallocatedPayment = $this->createUnallocatedPayment($aData, $sFile);

		$this->log->info('Created flywire payment', $aData);

		if ($oInquiry instanceof \Ext_TS_Inquiry) {
			$this->log->info('Matched flywire payment to inquiry '.$oInquiry->id, $aData);

			// Wenn das fehlschlägt, besteht die Zahlung ohnehin als unzugewiesene Zahlung
			try {
				$oUnallocatedPayment->createInquiryPayment($oInquiry, $oUnallocatedPayment->payment_method_id, 'flywire');
			} catch (\Exception $e) {
				$this->log->error('Payment matching failed: '.$e->getMessage(), [$e->getTrace()]);
				PaymentAllocationFailed::dispatch($oUnallocatedPayment);
			}

			\Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 10);
		}
	}

	/**
	 * Payment anlegen
	 *
	 * @param array $aData
	 * @param string $sFile
	 * @return \Ext_TS_Inquiry_Payment_Unallocated
	 */
	private function createUnallocatedPayment(array $aData, $sFile) {

		$oPayment = new \Ext_TS_Inquiry_Payment_Unallocated();

		if (!empty($aData['customer_number'])) {
			$aData['comment'] .= ' / '.$aData['customer_number'];
		}

		$oPayment->transaction_code = $aData['transaction'];
		$oPayment->comment = $aData['comment'];
		$oPayment->firstname = $aData['customer_firstname'];
		$oPayment->lastname = $aData['customer_lastname'];
		$oPayment->amount = (float)$aData['amount'];
		$oPayment->amount_currency = $this->config['currency'];
		$oPayment->payment_date = (new \DateTime($aData['date']))->format('Y-m-d');
		$oPayment->payment_method_id = $this->config['method_id'];
		$oPayment->additional_info = json_encode(['type' => 'flywire_sync', 'file' => $sFile]);

		$oPayment->validate(true);
		$oPayment->save();

		return $oPayment;

	}

	/**
	 * Wurde Datei bereits synchronisiert?
	 *
	 * @param string $sFile
	 * @return bool
	 */
	private function isFileSynced($sFile) {

		$sSql = "
			SELECT
				`file`
			FROM
				`ts_payments_flywire_filesync`
			WHERE
				`file` = :file
		";

		$sResult = \DB::getQueryOne($sSql, ['file' => $sFile]);

		return $sResult !== null;

	}

	/**
	 * Datei als synchronisiert markieren
	 *
	 * @param string $sFile
	 * @param int $iPaymentCount
	 */
	private function markFileAsSynced($sFile, $iPaymentCount) {

		$sSql = "
			INSERT INTO
				`ts_payments_flywire_filesync`
			SET
				`file` = :file,
				`payments` = :payment_count,
				`created` = NOW()
		";

		\DB::executePreparedQuery($sSql, [
			'file' => $sFile,
			'payment_count' => $iPaymentCount
		]);

	}

}

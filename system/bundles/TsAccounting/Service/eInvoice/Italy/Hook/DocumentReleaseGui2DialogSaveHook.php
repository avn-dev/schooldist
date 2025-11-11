<?php

namespace TsAccounting\Service\eInvoice\Italy\Hook;

use TcAccounting\Service\eInvoice\Entity\File as EntityFile;
use TsAccounting\Service\eInvoice\Italy\ExternalApp\XmlIt as XmlItApp;

class DocumentReleaseGui2DialogSaveHook extends \Core\Service\Hook\AbstractHook {
	
	/**
	 * @param \Ext_Gui2 $oGui
	 * @param array $aTransfer
	 * @param array $_VARS
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param string $sAdditional
	 * @param bool $bSave
	 */
	public function run(\Ext_Gui2 $oGui, array &$aTransfer, array $_VARS, $sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		if(
			\TcExternalApps\Service\AppService::hasApp(XmlItApp::APP_NAME) &&
			in_array($sAction, ['xml_export_it', 'xml_export_it_final'])
		) {

			$this->save($oGui, $aTransfer, $_VARS, ($sAction === 'xml_export_it_final'));

			if(!isset($aTransfer['data'])) {
				$aTransfer['data'] = array();
			}

			$aTransfer['data']['id'] = $_VARS['dialog_id'];
			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['data']['show_skip_errors_checkbox'] = 1;

			if(
				!empty($aTransfer['error']) &&
				(
					// Keine Anzeige von Fehler beim Speichern, wenn es der Hinweis wegen group documents ist
					is_array($aTransfer['error'][0]) &&
					$aTransfer['error'][0]['code'] != 'group_documents'
				)
			) {
				array_unshift($aTransfer['error'], \L10N::t('Fehler beim Speichern'));
			}
		}

	}
	
	/**
	 * Generiert ein Zip welches die XML-Dateien aller ausgewählten Dokumente enthält
	 * 
	 * @param \Ext_Gui2 $oGui
	 * @param array $aTransfer
	 * @param array $_VARS
	 * @param bool $bFinal
	 */
	private function save(\Ext_Gui2 $oGui, array &$aTransfer, $_VARS, $bFinal = false) {

		$ignoreErrorCodes = (array)$_VARS['ignore_errors_codes'] ?? [];

		$aDocumentIds = (array)$_VARS['document_ids'];

		$groupedDocumentIds = [];
			
		$aErrors = array();
		
		if (!empty($aDocumentIds)) {
			
			$oGenerator = new \TsAccounting\Service\eInvoice\Italy\FileBuilder($bFinal);
			$documentNumbersWithHint = [];
			foreach ($aDocumentIds as $iDocumentId) {
				$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
				$oInquiry = $oDocument->getInquiry();
				if (
					$oInquiry instanceof \Ext_TS_Inquiry_Abstract &&
					$oInquiry->hasGroup()
				) {
					if (
						$bFinal &&
						!in_array('group_documents', $ignoreErrorCodes)
					) {
						$aErrors[] = [
							'message' => sprintf($oGui->t('Die Rechnung "%s" ist eine Gruppenrechnung. Bei der Freigabe einer Gruppenrechnung werden alle Positionen der Rechnung aller Gruppenmitglieder automatisch mit freigegeben. Wollen Sie das Dokument "%s" trotzdem freigeben?'), $oDocument->document_number, $oDocument->document_number),
							'type' => 'hint',
							'code' => 'group_documents'
						];
						continue;
					}

					if (in_array($oDocument->document_number, $documentNumbersWithHint)) {
						// Sollten mehrere Dokumente aus der gleichen Gruppe ausgewählt sein, ignorieren.
						// Es wird nur eine Gruppenrechnung erstellt.
						// Für die Freigabe werden alle zugehörigen
						// Dokumente aus der Gruppe werden mit getDocumentsOfSameNumber() zusammengeführt.
						continue;
					}
					$documentNumbersWithHint[] = $oDocument->document_number;

					/** @var \Ext_Thebing_Inquiry_Document[] $aGroupDocuments */
					$aGroupDocuments = $oDocument->getDocumentsOfSameNumber();
					$groupedDocumentIds[$oDocument->getId()] = [];
					foreach ($aGroupDocuments as $oGroupDocument) {
						$groupedDocumentIds[$oDocument->getId()][] = $oGroupDocument->getId();
					}

					$oGenerator->addDocument($oDocument);

				} else {
					$oGenerator->addDocument($oDocument);
				}
			}
			if ($aErrors) {
				$aTransfer['error'] = $aErrors;
				return;
			}

			$oBuilderResponse = $oGenerator->generate();

			if($oBuilderResponse->hasErrors()) {
				$aErrors = $oBuilderResponse->getErrors();	
			} else {
				if ($bFinal) {
					foreach ($oBuilderResponse->getFiles() as $file) {
						// Alle restlichen Dokumente aus der Gruppe bekommen das gleiche File zugewiesen
						foreach ($groupedDocumentIds[$file->getDocumentId()] ?? [] as $groupedDocumentId) {
							$entityFile = new EntityFile();
							$entityFile->document_id = $groupedDocumentId;
							$entityFile->type = $file->getType();
							$entityFile->file = basename($file->getBackupFile());
							if (!$entityFile->save()) {
								$aErrors[] = sprintf($oGui->t('Speichern der Dateizuweisung für Dokument "%s" fehlgeschlagen!'), $groupedDocumentId);
							}
						}
					}
				}
				$sZipArchive = $oBuilderResponse->buildZip();

				$sLink = str_replace(\Util::getDocumentRoot(false), '', $sZipArchive);

				$sMessage = $oGui->t('Export wurde erfolgreich erstellt: {OPEN_LINK_TAG}Download{CLOSE_LINK_TAG}');

				$sMessage = str_replace(
					['{OPEN_LINK_TAG}', '{CLOSE_LINK_TAG}'],
					['<a href="' . $sLink.'?t='.time() . '" onclick="window.open(this.href); return false;">', '</a>'],
					$sMessage
				);

				$aTransfer['success_message'] = $sMessage;

			} 
			
		} else {
			$aErrors[] = $oGui->t('Keine Dokumente ausgewählt!');
		}
		
		$aTransfer['error'] = $aErrors;
				
	}

}


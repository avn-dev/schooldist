<?php

namespace TsAccounting\Service\eInvoice\Italy\Hook;

use TsAccounting\Service\eInvoice\Italy\ExternalApp\XmlIt as XmlItApp;

/**
 * Hook für die Liste der Dokumentenfreigabe
 * 
 * Hier werden die Buttons für den italienischen XML-Export eingeblendet
 * sobald man die App installiert hat
 */
class DocumentReleaseGui2ListHook extends \Core\Service\Hook\AbstractHook {
	
	/**
	 * @param array $mixInput
	 */
	public function run(array &$mixInput) {

		if(\TcExternalApps\Service\AppService::hasApp(XmlItApp::APP_NAME)) {
			
			$aGuiData = &$mixInput['config'];

			$aGuiData['bars'][0]['elements'][] = array(
				'element' => 'labelgroup',
				'label' => 'eInvoicing'
			);
			
			$aGuiData['bars'][0]['elements'][] = array(
				'element' => 'icon',
				'label' => 'XML Export',
				'task' => 'openDialog',
				'action' => 'xml_export_it',
				'active' => 0,
				'img' => \Ext_Thebing_Util::getIcon('export'),
				'access' => [
					'thebing_accounting_release_documents_list',
					'it_xml_export'
				]
			);

			$aGuiData['bars'][0]['elements'][] = array(
				'element' => 'icon',
				'label' => 'XML Export (Final)',
				'task' => 'openDialog',
				'action' => 'xml_export_it_final',
				'active' => 0,
				'img' => \Ext_Thebing_Util::getIcon('export'),
				'access' => [
					'thebing_accounting_release_documents_list',
					'it_xml_export_final'
				]
			);
			
//			$aGuiData['bars'][0]['elements'][] = array(
//				'element' => 'icon',
//				'label' => 'Historie',
//				'task' => 'openDialog',
//				'action' => 'einvoice_history',
//				'active' => 0,
//				'img' => \Ext_Thebing_Util::getIcon('history_detail'),
//				'access' => [
//					'thebing_accounting_release_documents_list',
//					'it_xml_export_final'
//				]
//			);
			
		}

	}
	
}


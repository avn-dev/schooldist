<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class Accommodation extends AbstractPage {
	
	protected $_aProviders = array();
	
	public function render(array $aData = array()) {
		$sTemplate = $this->generatePageHeading($this->oApp->t('Accommodation'));

		return $sTemplate;
	}
	
	public function getStorageData() {
		$aTemp = array();

		$aProviders = $this->getProviders();

		foreach($aProviders as $oProvider) {
			/* $oProvider \Ext_Thebing_Accommodation */
			$aTemp[$oProvider->id] = array(
				'title' => $oProvider->ext_33,
				'html' => '<h3>'.$oProvider->ext_33.'</h3><p>'.$oProvider->ext_66.'</p>',
				'items' => array(
					array(
						'html' => $this->generateProviderHtml($oProvider)
					)
				)
			);
		}
			
		$aList = array();
		$aList['items'] = array_values($aTemp);
		
		return $aList;
	}

	/**
	 * Liefert alle Unterkunftsanbieter der aktuellen Buchung.
	 * 
	 * @return Ext_Thebing_Accommodation[]
	 */
	protected function getProviders() {
		if(empty($this->_aProviders)) {
			$oInquiry = $this->oApp->getInquiry();
			$this->_aProviders = $oInquiry->getAccommodationProvider();
		}

		return $this->_aProviders;
	}
	
	/**
	 * Generiert das HTML für einen Unterkunftsanbieter
	 * 
	 * @param \Ext_Thebing_Accommodation $oProvider
	 * @return string
	 */
	protected function generateProviderHtml(\Ext_Thebing_Accommodation $oProvider) {
		
		$sHtml = '<h3>'.$oProvider->ext_33.'</h3>';
		
		$sHtml .= '<p>'.$oProvider->ext_63 . '<br>';
		$sHtml .= $oProvider->ext_64 . ' ' . $oProvider->ext_65 . '<br>';
		$sHtml .= $oProvider->ext_66 . '</p>';
		
		$sHtml .= '<p>'.$this->oApp->t('Phone'). ': '.$oProvider->ext_67 . '<br>';
		$sHtml .= $this->oApp->t('Mobil'). ': '.$oProvider->ext_77 . '<br>';
		$sHtml .= $this->oApp->t('E-Mail'). ': '.$oProvider->email.'</p>';
		
		$sHtml .= '<br><p>'.$oProvider->getFamilyDescription($this->_sInterfaceLanguage).'</p>';
		
		$sHtml .= '<br><p>'.$oProvider->getWayDescription($this->_sInterfaceLanguage).'</p>';

		$aUploads = $this->getAccommodationImages($oProvider);
		foreach($aUploads as $oUpload) {
			// div: jQuery Mobile Workaround
			// http://forum.jquery.com/topic/adding-images-to-list-that-are-not-thumbnails-or-icons
			$sHtml .= '<div><img style="width: 100%" src="storage://image/'.$oUpload->id.'/?additional=accommodation"></div>';
		}

		return $sHtml;
	}

	/**
	 * Liefert alle freigegebenen Bilder des Unterkunftsanbieters
	 *
	 * @param \Ext_Thebing_Accommodation $oProvider
	 * @return \Ext_Thebing_Accommodation_Upload[]
	 */
	protected function getAccommodationImages(\Ext_Thebing_Accommodation $oProvider) {

		$aUploads = \Ext_Thebing_Accommodation_Upload::getList($oProvider->id, 'picture', 'released_student_login');
		foreach($aUploads as $iIndex => $oUpload) {
			if(!$oUpload->isFileExisting()) {
				unset($aUploads[$iIndex]);
			}
		}

		return $aUploads;
	}

	public function getFileData() {
		$aReturn = array();

		$aProviders = $this->getProviders();
		foreach($aProviders as $oProvider) {

			$aUploads = $this->getAccommodationImages($oProvider);
			foreach($aUploads as $oUpload) {
				$aReturn[] = array(
					'type' => 'image', // Controller
					'additional' => 'accommodation', // Sub-Typ
					'id' => $oUpload->id // Eindeutige ID unter Controller und Sub-Typ
				);
			}
		}

		return $aReturn;
	}
	
}
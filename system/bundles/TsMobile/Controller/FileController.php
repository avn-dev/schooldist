<?php
namespace TsMobile\Controller;

class FileController extends AbstractController {

	/**
	 * Andere View-Klasse
	 * @var string
	 */
	protected $_sViewClass = '\MVC_View_File';

	/**
	 * Datei-Route
	 */
	public function getAction() {

		$sId = $this->_oRequest->get('id');
		$sType = $this->_oRequest->get('type');

		if(
			empty($sId) ||
			empty($sType)
		) {
			$this->_oView->setHTTPCode(404);
			return;
		}

		// Autorisierung prüfen
		if(!$this->checkAuth()) {
			$this->_oView->setHTTPCode(401);
			return;
		}

		// Datei-Typen umschalten
		if($sType === 'document') {
			$this->getDocument();
		} else if($sType === 'image') {
			$this->_oView->bForceDownload = false;
			$this->getImage();
		} elseif($sType === 'inquiry_upload') {
			$this->getInquiryUpload();
		} else {
			$this->_oView->setHTTPCode(400);
			return;
		}
	}

	/**
	 * HEAD-Request ermöglichen
	 */
	public function headAction() {
		$this->getAction();
	}

	/**
	 * Dokument holen
	 */
	protected function getDocument() {
		$iId = (int)$this->_oRequest->get('id');

		$oDocument = \Ext_Thebing_Inquiry_Document::getInstance($iId);
		$oInquiry = $this->_oApp->getInquiry();

		// Dokument muss Buchung gehören
		// @TODO Auf alle Buchungen des Schülers umstellen
		if($oInquiry->id != $oDocument->getInquiry()->id) {
			$this->_oView->setHTTPCode(401);
			return;
		}

		$oVersion = $oDocument->getLastVersion();
		$sPath = $oVersion->getPath(true);

		$this->_oView->set('file_path', $sPath);
		$this->_oView->set('mime_type', 'application/pdf; charset=binary');
	}

	/**
	 * Bild holen
	 */
	protected function getImage() {
		
		$iImageId = $this->_oRequest->get('id');
		$sAdditional = $this->_oRequest->get('additional');

		$sFile = null;
		$oAppSchool = $this->_oApp->getSchool();

		if(empty($sAdditional)) {
			$this->_oView->setHTTPCode(404);
			return;
		}		

		$oImageSchool = null;
		
		// Bild-Typ durchgehen
		if($sAdditional == 'school_image') {
			$oImageSchool = \Ext_Thebing_School::getInstance($iImageId);
//			$sFile = $oImageSchool->getSchoolFileDir(). '/app/' . $oImageSchool->app_image;
			$sFile = $oImageSchool->getFirstFile(\TsActivities\Entity\Activity::APP_IMAGE_TAG)?->getPathname();
		} elseif($sAdditional == 'accommodation') {
			$oUpload = \Ext_Thebing_Accommodation_Upload::getInstance($iImageId);
			$oProvider = $oUpload->getAccommodationProvider();
			$sFile = $oUpload->getPath();
		} else {
			$this->_oView->setHTTPCode(404);
			return;
		}

		// Sicherheits-Prüfung, ob Schule des Benutzers mit angefragten Daten aus Schule übereinstimmt
		if(
			$oImageSchool instanceof \Ext_Thebing_School &&
			$oAppSchool->id != $oImageSchool->id
		) {
			$this->_oView->setHTTPCode(403);
			$sFile = null;
		}

		if(
			!empty($sFile) &&
			file_exists($sFile)
		) {
			$aImageSizeInfo = getimagesize($sFile);
			$this->_oView->set('file_path', $sFile);
			$this->_oView->set('mime_type', $aImageSizeInfo['mime']);
		} else {
			$this->_oView->setHTTPCode(404);
			return;
		}

	}

	/**
	 * Upload im SR: Eines der beiden statischen Felder oder flexibles Feld
	 */
	protected function getInquiryUpload() {

		$oInquiry = $this->_oApp->getInquiry(); /** @var \Ext_TS_Inquiry $oInquiry */
		$sImageId = $this->_oRequest->get('id');
		list($sType, $iTypeId) = explode('_', $sImageId, 2);

		if($oInquiry->isUploadReleasedForStudentApp($sType, $iTypeId))  {

			// Inquiry muss gesetzt werden, da das als interne Property ansonsten leer ist und es dann keine Schule gibt…
			$oTraveller = $oInquiry->getFirstTraveller();
			$oTraveller->setInquiry($oInquiry);

			$sFile = null;

			// Benötigtes Umschalten wegen bescheuerter Trennung bei eigentlich gleicher Struktur
			if($sType === 'static') {
				if($iTypeId == 1) {
					$sFile = $oTraveller->getPhoto();
				} elseif($iTypeId == 2) {
					$sFile = $oTraveller->getPassport();
				}
			} else {
				$sFile = $oTraveller->getStudentUpload($iTypeId, $oTraveller->getSchool()->id, $oInquiry->id);
			}

			if($sFile !== null) {
				$sFile = \Util::getDocumentRoot(false).$sFile;
			}

			if(is_file($sFile)) {
				$oFileInfo = new \finfo();
				$sMimeType = $oFileInfo->file($sFile, FILEINFO_MIME);

				$this->_oView->set('file_path', $sFile);
				$this->_oView->set('mime_type', $sMimeType);

			} else {
				$this->_oView->setHTTPCode(404);
			}

		} else {
			$this->_oView->setHTTPCode(403);
		}

	}
}
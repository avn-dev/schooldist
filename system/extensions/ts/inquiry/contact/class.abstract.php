<?php

abstract class Ext_TS_Inquiry_Contact_Abstract extends Ext_TS_Contact {

	abstract protected function _getType();
	
	protected $_sTableAlias = 'cdb1';

	protected $_oInquiry = null;

	/**
	 * @var boolean
	 */
	public $bCheckGender = true;

	/**
	 * @var boolean
	 */
    public $bIgnoreSpecialValidate = false;

	public function __construct($iDataID = 0, $sTable = null) {

		parent::__construct((int)$iDataID, $sTable);

		$this->_aJoinTables['inquiries'] = array(
			#'class'					=> \Ext_TS_Inquiry::class
			'table'					=> 'ts_inquiries_to_contacts',
			'foreign_key_field'		=> 'inquiry_id',
			'primary_key_field'		=> 'contact_id',
			'static_key_fields'		=> array(
				'type' => $this->_getType()
			),
			'autoload'				=> false,
			#'check_active'			=> true
		);

		$this->_aJoinTables['logins'] = array(
			'table'					=> 'ts_inquiries_contacts_logins',
			'foreign_key_field'		=> 'id',
			'primary_key_field'		=> 'contact_id',
			'autoload'				=> false,
			'check_active'			=> true,
		);		

	}

	/**
	 * @param \DateTime $oDate Das Alter muss man für ein bestimmtes Datum abfragen können
	 * @return array
	 */
	public function getShortArray(\DateTime $oDate=null) {

		$aData = array();
		$aData['id']				= $this->id;
		$aData['customerNumber']	= $this->getCustomerNumber();
		$aData['active']			= $this->active;
		$aData['email']				= $this->getEmail();
		$aData['lastname']			= $this->lastname;
		$aData['firstname']			= $this->firstname;
		$aData['gender']			= $this->gender;
		$aData['language']			= $this->language;
		$aData['nationality']		= $this->nationality;
		$aData['age'] = $this->getAge($oDate);

		return $aData;
	}

	/**
	 *
	 * @param Ext_TS_Inquiry $oInquiry 
	 */
	public function setInquiry(Ext_TS_Inquiry $oInquiry)
	{
		$this->_oInquiry = $oInquiry;
	}

	/**
	 *
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry()
	{
		return $this->_oInquiry;
	}

	public function getInquiryById(int $iInquiryId) {
        $aInquiries = $this->getInquiries(false, true);
        foreach($aInquiries as $oInquiry) {
            if($oInquiry->getId() == $iInquiryId) {
                return $oInquiry;
            }
        }

        return null;
    }

	/**
	 *
	 * @return Ext_Thebing_School|false
	 */
	public function getSchool()
	{
		if(
			is_object($this->_oInquiry) &&
			$this->_oInquiry instanceof Ext_TS_Inquiry
		)
		{
			$oSchool = $this->_oInquiry->getSchool();

			return $oSchool;
		}

		return false;
	}

	/**
	 * @param bool $bOnlyFirst
	 * @param bool $bObjects
	 * @return Ext_TS_Inquiry[]
	 */
	public function getInquiries($bOnlyFirst = false, $bObjects=false)
	{
		$aInquiries = (array)$this->inquiries;

		if(
			count($aInquiries) > 0 &&
			$bOnlyFirst
		) {
			$aInquiries = [reset($aInquiries)];
		}

		if($bObjects) {
			$aReturn = array();

			foreach($aInquiries as $iInquiryId) {
				$aReturn[] = Ext_TS_Inquiry::getInstance($iInquiryId);
			}

			return $aReturn;
		}

		return $aInquiries;
	}

	public function getLatestInquiry(): Ext_TS_Inquiry {
		
		$inquiryIds = (array)$this->inquiries;
		rsort($inquiryIds, SORT_NUMERIC);
		
		$latestInquiryId = reset($inquiryIds);
		
		return Ext_TS_Inquiry::getInstance($latestInquiryId);
	}
	
	/**
	 * Liefert die nächstbeste Buchung:
	 * 	1. Aktuell stattfindent
	 * 	2. Zukünftig stattfindent
	 * 	3. Fand in der Vergangenheit statt
	 * @return Ext_TS_Inquiry
	 */
	public function getClosestInquiry() {

		$aInquiries = $this->getInquiries(false, true);
		$aInquiriesAfterNow = $aInquiriesBeforeNow = $aCancelledInquiries = [];
		$oClosestInquiry = null;
		$aDateTimes = []; // Da die WDBasic keine Typen hat…
		$dNow = new DateTime();

		// Zuerst schauen, welche Buchung in den aktuellen Zeitraum reinfällt
		foreach($aInquiries as $oInquiry) {

			if (!$oInquiry->isActive()) {
				continue;
			}

			$aDateTimes[$oInquiry->id]['from'] = new DateTime($oInquiry->service_from);
			$aDateTimes[$oInquiry->id]['until'] = new DateTime($oInquiry->service_until);

			// Stornierte Buchungen filtern
			if($oInquiry->isCancelled()) {
				$aCancelledInquiries[] = $oInquiry;
				continue;
			}

			if(
				$dNow >= $aDateTimes[$oInquiry->id]['from'] &&
				$dNow <= $aDateTimes[$oInquiry->id]['until']
			)  {
				// Buchung fällt in aktuellen Zeitraum
				$oClosestInquiry = $oInquiry;
				break;
			} elseif($aDateTimes[$oInquiry->id]['from'] > $dNow) {
				// Buchung ist zukünftig
				$aInquiriesAfterNow[] = $oInquiry;
			} elseif($aDateTimes[$oInquiry->id]['until'] < $dNow) {
				// Buchung liegt in der Vergangenheit
				$aInquiriesBeforeNow[] = $oInquiry;
			}
		}

		// Wenn keine Buchung in den aktuellen Zeitraum fällt
		if(!$oClosestInquiry) {

			$oSort = function($oInquiry1, $oInquiry2) use($aDateTimes) {
				return $aDateTimes[$oInquiry1->id]['until']->getTimestamp() > $aDateTimes[$oInquiry2->id]['until']->getTimestamp() ? 1 : -1;
			};

			usort($aInquiriesAfterNow, $oSort);
			usort($aInquiriesBeforeNow, $oSort);

			if(!empty($aInquiriesAfterNow)) {
				$oClosestInquiry = reset($aInquiriesAfterNow);
			} else {
				$oClosestInquiry = end($aInquiriesBeforeNow);
			}
		}

		// Wenn keine Buchungen gefunden, aber Stornobuchungen existieren. Nimm die erstbeste…
		if(
			!$oClosestInquiry &&
			!empty($aCancelledInquiries)
		) {
			$oClosestInquiry = reset($aCancelledInquiries);
		}

		return $oClosestInquiry;
	}

	/**
	 * Eigentlich gehört diese Funktion in die Ext_TS_Contact,
	 * nur ist der Link leider Schulabhängig
	 *
	 * @return string
	 */
	public function getNewsletterCancelLink() {

		$oSchool = $this->getSchool();
		$sLink = $oSchool->url_newsletter_unsubscribe.'?r='.$this->getNewsletterCancelHash();
		
		return $sLink;
	}

	/**
	 * @return string
	 */
	public function getNewsletterCancelHash() {
		$sMD5 = md5('newsletter_'.$this->id.'_cancellink');
		return $sMD5;
	}

	/**
	 * @param $sHash
	 * @return bool
	 */
	public static function cancelNewsletter($sHash) {

		$bRetVal = false;
		$sClassName = get_called_class();
		$oSelf = new $sClassName();
		
		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				MD5(
					CONCAT(
						'newsletter_',
						`id`,
						'_cancellink'
					)
				) = :hash
			LIMIT
				1
		";

		$aSql = array(
			'table'	=> $oSelf->_sTable,
			'hash'	=> $sHash
		);
		
		$iContact = (int)DB::getQueryOne($sSql, $aSql);
		if($iContact > 0) {
			$oContact = self::getInstance($iContact);
			$oDetail = $oContact->getDetail('newsletter');
			$oDetail->delete();
			$bRetVal = true;
		}

		return $bRetVal;
	}

	//Alle Pass&Bild Funktionen vorläufig hier drin, weil noch nicht ganz klar ist, ob der Bucher das auch
	//benötigen könnte, eventuell später in travellers verschieben...

	/**
	 * @param bool $bOnlyFilename
	 * @return string
	 */
	public function getPhoto($bOnlyFilename=false) {

		$sExists = '';
		
		$oSchool = $this->getSchool();
		
		if($oSchool instanceof Ext_Thebing_School) {

			$sPath = $oSchool->getSchoolFileDir(false);

			$sPath = $sPath.'/studentcards/';

			$sFile = 'photo_'.$this->id;

			$sExists = Ext_Thebing_Util::checkForFileExtensions($sPath, $sFile, $bOnlyFilename);

			// Abwärtskompatibilität
			if(empty($sExists)) {
				$sPath = '/storage/studentcards/';
				$sExists = Ext_Thebing_Util::checkForFileExtensions($sPath, $sFile, $bOnlyFilename);
			}

		}

		return $sExists;

	}

	/**
	 * @param bool $bOnlyFilename
	 * @return string
	 */
	public function getPassport($bOnlyFilename=false) {

		$oSchool = $this->getSchool();
		$sPath = $oSchool->getSchoolFileDir(false);

		$sPhoto = $sPath.'/passport/';

		$sFile = 'passport_'.$this->id;

		$sExists = Ext_Thebing_Util::checkForFileExtensions($sPhoto, $sFile, $bOnlyFilename);

		// Abwärtskompatibilität
		if(empty($sExists)) {
			$sPhoto = '/storage/passport/';
			$sExists = Ext_Thebing_Util::checkForFileExtensions($sPhoto, $sFile, $bOnlyFilename);
		}

		return $sExists;

	}

	/**
	 * @param int $iUploadId
	 * @param int  $iSchoolId
	 * @param int $iInquiryId
	 * @param bool $bOnlyFilename
	 * @return string
	 */
	public function getStudentUpload($iUploadId, $iSchoolId, $iInquiryId, $bOnlyFilename=false) {

		$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		$sPath = $oSchool->getSchoolFileDir(false,true);

		$sPhoto = $sPath.'/studentuploads/'.$iInquiryId.'/';
		$sFile = 'upload_'.$iUploadId;

		$sExists = Ext_Thebing_Util::checkForFileExtensions($sPhoto, $sFile, $bOnlyFilename);

		return $sExists;
	}

	/**
	 * @param string $sFileName
	 * @return string
	 */
	public function deletePhoto($sFileName) {

        $sError = $this->deleteUpload($sFileName, 'studentcards');
		
		return $sError;
	}

	/**
	 * @param string $sFileName
	 * @return string
	 */
	public function deletePassport($sFileName) {

        $sError = $this->deleteUpload($sFileName, 'passport');
		
		return $sError;
	}

	/**
	 * @param string $sFileName
	 * @param int $iInquiryId
	 * @return string
	 */
	public function deleteStudentUpload($sFileName, $iInquiryId) {
		
		$sDir = 'studentuploads/'.$iInquiryId;

        $sError = $this->deleteUpload($sFileName, $sDir);
		
		return $sError;
	}

	/**
	 * @param string $sFileName
	 * @param string $sDir
	 * @return string
	 */
	protected function deleteUpload($sFileName, $sDir) {
		
		$sError = '';
		
		$sDocumentRoot = Util::getDocumentRoot(false);
		
		$oSchool = $this->getSchool();
		$sPath = $oSchool->getSchoolFileDir(false);

		$sPath = $sPath.'/'.$sDir.'/';

		$sTargetPath = $sDocumentRoot.$sPath;
		
		if(is_file($sTargetPath.$sFileName)) {

			$bDelete = unlink($sTargetPath.$sFileName);
			if($bDelete !== true) {
				$sError = sprintf(L10N::t('Fehler beim Löschen der Datei "%s"! Die Zugriffsrechte reichen nicht aus.'), \Util::getEscapedString($sFileName));
			} else {
				// Eingebaut für Leeds #12322
				$oLogger = Ext_TC_Log::getLogger();
				$oLogger->addInfo('Deleted student upload: '.$sFileName.', '.$sDir.', contact: '.$this->id.', closest inquiry: '.$this->getClosestInquiry()->id.', user: '.System::getCurrentUser()->id);
			}

		} else {
			$sError = sprintf(L10N::t('Fehler beim Löschen der Datei "%s"! Datei ist nicht vorhanden.'), \Util::getEscapedString($sFileName));
		}

		return $sError;
	}

	/**
	 * @TODO Diese Methode sollte dringend entsorgt werden.
	 * @see \Ext_TS_Inquiry_Contact_Abstract::saveUpload2()
	 *
	 * @deprecated
	 * @param string $sFileName
	 * @param string $sTmpName
	 * @param string $sCurrentFile
	 * @param string $sDir
	 * @param string $sPrefix
	 * @param null|array $aAllowedExtensions
	 * @return string
	 */
	protected function saveUpload($sFileName, $sTmpName, $sCurrentFile, $sDir, $sPrefix, $aAllowedExtensions=null) {

		$sDocumentRoot = Util::getDocumentRoot(false);
		
		$oSchool = $this->getSchool();
		$sPath = $oSchool->getSchoolFileDir(false);

		$sPath = $sPath.'/'.$sDir.'/';

		$sTargetPath = $sDocumentRoot.$sPath;
		$sFile = $sPrefix;

		$sError = '';
		$bPath = Util::checkDir($sTargetPath);

		if(!$bPath) {
			$sError = L10N::t('Fehler beim Upload! Fehlende Zugriffsrechte.');
		}

		if(!empty($sTmpName)) {

			$sTmp = strtolower($sFileName);
			$sTmp = explode('.', $sTmp);
			$sUploadExtension = end($sTmp);

			if(
				$aAllowedExtensions !== null &&
				!in_array($sUploadExtension, $aAllowedExtensions)
			) {

				$sError = sprintf(Ext_Thebing_L10N::t('Fehler beim Upload! Erlaubte Bildformate sind: %s'), implode(', ', $aAllowedExtensions));

			} else if($sTmpName !== false) {

				if(
					!empty($sCurrentFile) &&
					is_file($sDocumentRoot.$sCurrentFile)
				) {
					unlink($sDocumentRoot.$sCurrentFile);
				}
				$aError = array();
				
				if(!is_file($sTmpName)) {
					$sCopyFile = str_replace($sDocumentRoot, '', $sTmpName);
					$sCopyFile = $sDocumentRoot.$sCopyFile;
				} else {
					$sCopyFile = $sTmpName;
				}

				$sTargetFile = $sTargetPath.$sFile.'.'.$sUploadExtension;

				if(is_uploaded_file($sCopyFile)) {
					$aError[] = move_uploaded_file($sCopyFile, $sTargetFile);
				} else {
					$aError[] = copy($sCopyFile, $sTargetFile);
				}
				$aError[] = chmod($sTargetFile, 0777);

				if(in_array(FALSE,$aError))  {
					$sError = Ext_Thebing_L10N::t('Fehler beim Upload! Fehlende Zugriffsrechte Error[2]');
				} else {
					// Eingebaut für Leeds #12322
					$oLogger = Ext_TC_Log::getLogger();
					$oLogger->addInfo('Added student upload: '.$sFile.', contact: '.$this->id.', closest inquiry: '.$this->getClosestInquiry()->id.', user: '.System::getCurrentUser()->id);
				}

			} else {

				$sError = sprintf(Ext_Thebing_L10N::t('Fehler beim Upload! Erlaubte Bildformate sind: %s'), implode(', ', $aAllowedExtensions));


			}

		}

		return $sError;

	}

	/**
	 * Macht das gleiche wie die Methode darüber (und die ganzen Metastasen davon), nur ohne Vanilla-PHP-Stil.
	 *
	 * @param string $sType static_1|static_2|flex_[0-9]+
	 * @param \Illuminate\Http\UploadedFile|\Symfony\Component\HttpFoundation\File\File $oFile
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
	 */
	public function saveUpload2(string $sType, \Symfony\Component\HttpFoundation\File\File $oFile) {

		$sPath = $this->getSchool()->getSchoolFileDir();

		if($sType === 'static_1') {
			$sPath .= '/studentcards/photo_'.$this->id;
		} elseif($sType === 'static_2') {
			$sPath .= '/passport/passport_'.$this->id;
		} elseif(\Illuminate\Support\Str::startsWith($sType, 'flex_')) {
			$iFlexId = (int)\Illuminate\Support\Str::afterLast($sType, '_');
			if(empty($iFlexId)) {
				throw new InvalidArgumentException('Unknown flex type: '.$sType);
			}
			$sPath .= '/studentuploads/'.$this->getInquiry()->id.'/upload_'.$iFlexId;
		} else {
			throw new \InvalidArgumentException('Unknown type: '.$sType);
		}

		$sExtension = $oFile->getExtension();

		if(empty($sExtension) && $oFile instanceof \Illuminate\Http\UploadedFile) {
		    $sExtension = $oFile->extension();
        }

		$sPath .= '.'.$sExtension;

		Util::checkDir(dirname($sPath));

		// Funktioniert mit Dateien wie auch Uploads, da move() entsprechend überschrieben wurde
		$oFile->move(dirname($sPath), basename($sPath));

	}

	/**
	 * @deprecated
	 * @param string $sFileName
	 * @param string $sTmpName
	 * @return string
	 */
	public function savePhoto($sFileName, $sTmpName) {

		$sExists = $this->getPhoto();
		$sPrefix = 'photo_'.$this->id;

		$sError = $this->saveUpload($sFileName, $sTmpName, $sExists, 'studentcards', $sPrefix, array('gif', 'jpg', 'jpeg', 'png'));
		
		return $sError;
	}

	/**
	 * @deprecated
	 * @param string $sFileName
	 * @param string $sTmpName
	 * @return string
	 */
	public function savePassport($sFileName, $sTmpName) {

		$sExists = $this->getPassport();
		$sPrefix = 'passport_'.$this->id;
		
		$sError = $this->saveUpload($sFileName, $sTmpName, $sExists, 'passport', $sPrefix, array('gif', 'jpg', 'jpeg', 'png', 'pdf'));
		
		return $sError;
	}

	/**
	 * @deprecated
	 * @param string $sFileName
	 * @param string $sTmpName
	 * @param int $iUploadId
	 * @param int $iInquiryId
	 * @return string
	 */
    public function saveStudentUpload($sFileName, $sTmpName, $iUploadId, $iInquiryId) {

		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

		$sExists = $this->getStudentUpload($iUploadId, $oInquiry->getSchool()->getId(), $iInquiryId);

		$sDir = 'studentuploads/'.$iInquiryId;
		$sPrefix = 'upload_'.$iUploadId;

		$sError = $this->saveUpload($sFileName, $sTmpName, $sExists, $sDir, $sPrefix);

		return $sError;
	}

	/**
	 * Uploads löschen
	 *
	 * @param int $iInquiryId Keine Ahnung was das für ein Chaos ist
	 */
	public function deleteUploads($iInquiryId) {

		$this->deletePhoto($this->getPhoto(true));
		$this->deletePassport($this->getPassport(true));

		$oSchool = $this->getSchool();
		if(
			$oSchool instanceof Ext_Thebing_School &&
			$oSchool->exist()
		) {
			$aUploadFields = Ext_Thebing_School_Customerupload::getUploadFieldsBySchoolIds([$oSchool->id]);
			foreach($aUploadFields as $oUploadField) {
				$this->deleteStudentUpload($this->getStudentUpload($oUploadField->id, $oSchool->id, $iInquiryId, true), $iInquiryId);
			}

			$sUploadDir = $oSchool->getSchoolFileDir(true, true).'/studentuploads/'.$iInquiryId.'/';
			if(is_dir($sUploadDir)) {
				Util::recursiveDelete($sUploadDir);
				$oLogger = Ext_TC_Log::getLogger();
				$oLogger->addInfo('Deleted studentuploads diretory of inquiry '.$iInquiryId.' (Contact '.$this->id.')');
			}
		}

	}

	/**
	 * @param bool $bLog
	 * @return $this|array
	 */
//	public function save($bLog = true) {
//
//		$aErrors = array();
//		
//		$aAddresses	= $this->getInquiryContactAddresses(true);
//		$aContactsToAddresses = array();
//
//		foreach($aAddresses as $oAddress) {
//			
//			$mValidateAddress = $oAddress->validate();
//		
//			if($mValidateAddress === true) {
//
//				$oAddress->save();
//				
//				if($oAddress->id > 0) {
//					$aContactsToAddresses[] = $oAddress->id;
//				}
//				
//			} else {
//				$aErrors = array_merge($aErrors, $mValidateAddress);
//			}
//			
//		}
//
//		$this->contacts_to_addresses = $aContactsToAddresses;
//
//		$mSuccess = parent::save($bLog);
//		
//		if(is_array($mSuccess)) {
//			$aErrors = array_merge($aErrors, $mSuccess);
//		}
//
//		if(empty($aErrors)) {
//			return $this;
//		} else {
//			return $aErrors;
//		}
//
//	}
	
	/**
	 * Gibt alle Kontaktadressen der Buchung zurück
	 * @return array
	 */
	public function getInquiryContactAddresses() {

		$aAddresses = array();

		$oAddressContact = $this->getAddress('contact');
		$aAddresses[] = $oAddressContact;
		
//		$oAddressContact = $this->getAddress('billing');
//		$aAddresses[] = $oAddressContact;

		return $aAddresses;

	}

	public function hasStudentApp() {
		
		$login = $this->getLoginData();

		if($login instanceof Ext_TS_Inquiry_Contact_Login) {
			$devices = $login->getDevices();
			
			if(!empty($devices)) {
				return true;
			}
		}

		return false;
	}

	public function routeNotificationFor($driver, $notification = null)
	{
		return match ($driver) {
			'app' => $this->getCommunicationRoutes($driver),
			default => parent::routeNotificationFor($driver, $notification),
		};
	}

	public function getCommunicationRoutes(string $channel): ?\Illuminate\Support\Collection
	{
		return match ($channel) {
			'app' => !empty($devices = $this->getLoginData()?->getDevices())
				? collect($devices)->filter(fn ($device) => (bool)$device->push_permission)->mapWithKeys(fn ($device) => [$device->id => $device])
				: null,
			default => parent::getCommunicationRoutes($channel),
		};
	}

	/**
	 * @param string $from
	 * @return \Ext_TS_Inquiry[]
	 */
	public function getInquiriesByDate(string $from)
	{
		$from = new \DateTimeImmutable($from);

		return Ext_TS_Inquiry::query()
			->select('ts_i.*')
			->join('ts_inquiries_to_contacts as ts_itc', function (\Illuminate\Database\Query\JoinClause $join) {
				$join->on('ts_itc.inquiry_id', 'ts_i.id')
					->where('ts_itc.contact_id', $this->id);
			})
			->inPeriod($from)
			->get();
	}
}


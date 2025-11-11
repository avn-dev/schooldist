<?php

abstract class Ext_Thebing_Placeholder { 

	protected $_sSection = false;
	protected $_aFlexFields = array();
	protected $_aFlexFieldLabels = array();
	protected $_iFlexId = 0;

	protected $_iSchoolId = null;

	protected $_iPlaceholderLib = 1;

	// Daten für die Entries Schleife
	protected $_aEntries			= array();
	protected $_sEntryObject		= '';
	protected $_oEntryObject		= null;
	
	// Placeholder that should NOT be replaced! /////////////////
	protected $aSpecialPlaceholder = array();

	// Zusätzliche Daten in die Klasse setzen
	protected $_aAdditionalData = array();
	
	public $sTemplateLanguage = '';

	protected $options = [];
	
	protected $replaceSmarty = false;

	/**
	 * Neuer Ansatz, um Platzhalter erst nachträglich zu ersetzen (Prozesse, die beim Preview nicht gestartet werden dürfen)
	 *
	 * @var bool
	 */
	public $bInitialReplace = false;

	// Generelle Platzhalter Daten Sammeln
	protected $_aPlaceholderTableData = array();

	/** @var array */
	protected static $aPlaceholderMonitoring = [
		'instance' => null,
		'placeholders' => []
	];

	/**
	 * Wenn die Platzhalterklasse in der Kommunikation benutzt wird, wird dieses Flag gesetzt
	 * @var bool
	 */
	public $bCommunication = false;

	/**
	 * GUI2-Objekt wird bedarfsweise übergeben
	 * @var Ext_Gui2
	 */
	public $oGui;

	/**
	 * Fest Schule setzen
	 * @var Ext_Thebing_School|null
	 */
	protected ?\Ext_Thebing_School $oFixSchool = null;

	public function  __construct() {


		if(isset($this->_iSchoolForFormat)){
			$this->_iSchoolId = $this->_iSchoolForFormat;
		}elseif(!$this->_iSchoolId){
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$this->_iSchoolId = $oSchool->id;
		}

		if($this->_sSection) {
			
			$aFlexFields = Ext_TC_Flexibility::getSectionFieldData(array($this->_sSection));

			$this->_aFlexFields = array();
			$this->_aFlexFieldLabels = array();
			foreach((array)$aFlexFields as $aField) {
				if(!empty($aField['placeholder'])) {
					$this->_aFlexFields[$aField['placeholder']] = $aField['id'];
					$this->_aFlexFieldLabels[$aField['placeholder']] = $aField['title'];
				}
			}
			
		}

	}

	public function setOption($field, $value){
		$this->options[$field] = $value;
	}

	public function unsetOption($field) {
		unset($this->options[$field]);
	}

	public function setSchool(\Ext_Thebing_School $oSchool) {
		$this->oFixSchool = $oSchool;
		$this->_iSchoolId = $oSchool->id;
		return $this;
	}

	public function getOption($field, $default = null) {

		if(isset($this->options[$field])) {
			return $this->options[$field];
		}

//		// Fallback, manche Daten sind in der GUI gesetzt
//		if(
//			$this->oGui instanceof \Ext_Gui2 &&
//			$this->oGui->getOption($field)
//		) {
//			return $this->oGui->getOption($field);
//		}
		
		return $default;
	}
	
	public function getLanguage(){
	
		return $this->sTemplateLanguage;
	
	}
	
	public function getLanguageObject() {
		
		$sLanguage = $this->getLanguage();
		
		$oLanguage = new \Tc\Service\Language\Frontend($sLanguage);
		
		return $oLanguage;
	}
	
	/*
	 * Funktion ersetzt Schleifen die überall verfügbar sind
	 */
	protected function _helperReplaceVars($sText, $iOptionalId = 0) { 
		
		$sText = preg_replace_callback('@\{start_selected_entries\}(.*?)\{end_selected_entries\}@ims', array( $this, "_helperReplaceEntriesLoop"), $sText);	
		
		return $sText;
	}
	
	/*
	 * Ersetzt die "Entries" Schleife
	 */
	protected function _helperReplaceEntriesLoop($aText){

		$sText = '';
		
		// Wenn es ein Objekt gibt
		if(!empty($this->_sEntryObject)){
			
			foreach((array)$this->_aEntries as $iId){
				// Aktuelles Schleifenobject
				$oPlaceholder = NULL;
				
				switch($this->_sEntryObject){
					case 'Ext_TS_Inquiry':
						$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($iId);
						break;
				}
				
				if($oPlaceholder instanceof Ext_Thebing_Placeholder){
					$sText .= $oPlaceholder->_helperReplaceVars($aText[1]);
				}
			}
		}
		
		$this->_oEntryObject = null;
		
		return $sText;
	}
	
	//
	protected function _helperReplaceVars2($sString, $iOptionalParentId = 0) {

		$aPlaceholders = $this->_getAllPlaceholders($sString);

		$bUnfoundPlaceholders = false;
		foreach((array)$aPlaceholders as $sPlaceholder=>$aPlaceholder) {

			// PDF Platzhalter nicht ersetzen hier
			if(!in_array($sPlaceholder, $this->aSpecialPlaceholder)) {

				foreach((array)$aPlaceholder as $sItem=>$aItem) {

					if($this->_iPlaceholderLib == 1) {
						$sReplace = $this->_getPlaceholderValue($sPlaceholder, $iOptionalParentId, $aItem);
					} else {
						$sReplace = $this->_getPlaceholderValue2($sPlaceholder, $iOptionalParentId, $aItem);
					}

					// Platzhalter die nicht erkannt wurden stehen lassen
					if($sReplace !== null) {
						$sString = $this->processReplacing($sString, $sPlaceholder, $aItem, $sReplace);
					} else {
						$bUnfoundPlaceholders = true;
					}

				}

			}

		}

		// Noch nicht ausführen, da vorher noch geklärt werden muss wie wir das mit PDF- und Nicht-PDF-Platzhaltern machen
		if(0 && $bUnfoundPlaceholders === true) {
			
			$this->replaceSmarty($sString);
			
		}
		
		return $sString;
	}

	public function getRootEntity() {
		return null;
	}
	
	public function setDisplayLanguage($templateLanguage) {
		$this->sTemplateLanguage = $templateLanguage;
	}
	
	public function replaceSmarty(&$sString) {
		
		$oRootEntity = $this->getRootEntity();

		if($oRootEntity) {

			$sBackup = $sString;

			// Nach fälschlicherweise umgewandelten Zeichen suchen "->" und diese korrigieren, aber nur innerhalb von {}
			if(
				str_contains($sString, '-&gt;') ||
				str_contains($sString, '&gt;') ||
				str_contains($sString, '&lt;') ||
				str_contains($sString, '&amp;')
			) {
				$sString = preg_replace_callback(
					'/\{(.*?)\}/',
					function ($matches) {
						$final = str_replace(['-&gt;', '&gt;', '&lt;', '&amp;'], ['->', '>', '<', '&'], $matches[0]);
						return $final;
					},
					$sString
				);
			}

			try {
				$oReplace = $oRootEntity->getPlaceholderObject();

				if(!$oReplace instanceof Ext_TC_Placeholder_Abstract) {
					return;
				}
				
				$oReplace->setDisplayLanguage($this->sTemplateLanguage);

				$sString = $oReplace->replace($sString, !$this->bInitialReplace);

				$aErrors = $oReplace->getErrors();

				// Falls Fehler aufgetreten sind, original String wieder herstellen
				if(!empty($aErrors)) {
					if(!empty($_REQUEST['placeholder_debug'])) {
						__pout($aErrors);
					}

					$sString = $sBackup;
				}

			} catch(\Throwable $e) {
				__pout('Error in '.__METHOD__);
				__pout($e->getMessage());
				__pout($e->getFile().'::'.$e->getLine());
				__pout(htmlspecialchars($sString));
				__pout($e->getTraceAsString());
			}

		}
	}
	
	protected function _getPlaceholderValue($sField, $iOptionalParentId = 0, $aPlaceholder=array()) {

		try {
			$mValue = $this->searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder);
		} catch (Exception $e) {
			__pout('Error in placeholder '.$sField.': '.$e->getMessage());
			__pout($e->getFile().'::'.$e->getLine());
			__pout($e->getTraceAsString());
			$mValue = '';
		}

		return $mValue;
	}

	// Wird abgeleitet
	public function searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder=array()) {

		return $mValue;
	}

	protected function _getPlaceholderValue2($sField, $iOptionalParentId = 0, $aPlaceholder=array()) {
		return $this->_getPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder);
	}

	protected function _getAllPlaceholders($sString) {

		if(!empty($_REQUEST['placeholder_debug'])) {
			__pout($sString);
		}
		
		//$iMatches = preg_match_all('@\{((if )?([^/][a-z_0-9]*))\}@ims', $sString, $aMatches);
		//$iMatches = preg_match_all('@\{(if )?(([^/][a-z_0-9]*)(\|([ 0-9a-z_]+)(\:(.+?))?)?)\}@ims', $sString, $aMatches);
		/**
		 * Platzhalter, die HTML Tag mit Leerzeichen enthalten werden nicht gematched!
		 * z.B: {fir<span style="color: #ff0000;">st</span>name}
		 */
		$aMatches = array();
		$iMatches = preg_match_all('@\{(if )?(([^/][^ \|}{]*)(\|([^\|}{:]+)(\:(.+?))?)?)(\s*(&lt;|&gt;|eq|neq|==|<|>|!=)\s*(.*?))?\}@ims', $sString, $aMatches);

		$aPlaceholders = array();
		if($iMatches > 0) { 
			
			foreach((array)$aMatches[3] as $iMatch=>$sMatch) {

				// HTML Löschen
				// bei z.b Fettmakieren kann es sein das nur teile makiert wurden.
				// daher muss html gelöschen werden
				$sMatch = strip_tags($sMatch);
				// fals mit leerzeichen begonnen wurde lösche diese
				$sMatch = ltrim($sMatch);

				if($sMatch != 'else') {
					
					$aItem = array(
						'placeholder'=>$sMatch,
						'complete'=>$aMatches[2][$iMatch],
						'code'=>$aMatches[0][$iMatch]
					);

					// Modifier
					if(!empty($aMatches[5][$iMatch])) {
						$aItem['modifier'] = $aMatches[5][$iMatch];
						if(!empty($aMatches[7][$iMatch])) {
							$aItem['parameter'] = $aMatches[7][$iMatch];
						}						
					}

					// IF
					if(!empty($aMatches[1][$iMatch])) {
						$aItem['if'] = true;
						if(!empty($aMatches[9][$iMatch])) {
							$aItem['operator'] = $aMatches[9][$iMatch];
							$aItem['value'] = $aMatches[10][$iMatch];
						}						
					}

					$aPlaceholders[$sMatch][$aMatches[0][$iMatch]] = $aItem;

				}

			}

		}

		// Damit innere IF-Abfragen zuerst ersetzt werden
		$aPlaceholders = array_reverse($aPlaceholders);

		if(!empty($_REQUEST['placeholder_debug'])) {
			__pout($aPlaceholders);
		}

		$this->addMonitoringEntry($aPlaceholders);

		return $aPlaceholders;
	}

	/**
	 * @param string $sPlaceholder
	 * @param array $aPlaceholder
	 * @return mixed
	 */
	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$mValue = null;

		if(isset($this->_aFlexFields[$sPlaceholder])) {

			$mValue = array();
			$aFlex = Ext_TC_Flexibility::getPlaceholderValue($sPlaceholder, $this->_iFlexId, true);

			$mValue['value'] = $this->convertFlexPlaceholderInfo($aFlex, $mValue['format']);

		} else {

			switch($sPlaceholder)
			{
				case 'today':
					$mValue = array(
						'value'=>time(),
						'format' => 'date'
					);		
					break;
				case 'school_name':
					$oSchool	= $this->getSchool();
					$mValue		= $oSchool->ext_1;
					break;
				case 'school_abbreviation':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->short;
					break;
				case 'school_address': 
				case 'school_address_addon':
				case 'school_zip':
				case 'school_city': 
				case 'school_url': 
				case 'school_email': 
					$oSchool	= $this->getSchool();
					$sKey		= substr($sPlaceholder, 7);
					$mValue		= $oSchool->$sKey;
					break;
				case 'school_country':
					$sLanguage	= $this->getLanguage();
					$oSchool	= $this->getSchool();
					$aCountry	= Ext_Thebing_Data::getCountryList(true, false, $sLanguage);
					$sCountry	= $oSchool->country_id;
					
					if(
						isset($aCountry[$sCountry])
					)
					{
						$mValue = $aCountry[$sCountry];
					}
					break;
				case 'school_phone': 
					$oSchool	= $this->getSchool();
					$mValue		= $oSchool->phone_1;
					break;
				case 'school_phone2': 
					$oSchool	= $this->getSchool();
					$mValue		= $oSchool->phone_2;
					break;
				case 'school_fax': 
					$oSchool	= $this->getSchool();
					$mValue		= $oSchool->fax;
					break;
				case 'school_bank_name':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->bank;
					break;
				case 'school_bank_code':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->bank_code;
					break;
				case 'school_bank_address':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->bank_address;
					break;
				case 'school_account_holder':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->account_holder;
					break;
				case 'school_account_number':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->account_number;
					break;
				case 'school_iban':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->iban;
					break;
				case 'school_bic':
				case 'school_swift':
					$oSchool = $this->getSchool();
					$mValue = $oSchool->bic;
					break;
				case 'system_user_name':
				case 'system_user_firstname':
				case 'system_user_surname':
				case 'system_user_email':
				case 'system_user_phone':
				case 'system_user_fax':
					
					// Wichtig, damit der Platzhalter auch ersetzt wird, wenn es keinen User gibt
					$mValue = '';
					
					$oUser = Access::getInstance();
					if($oUser instanceof Access_Backend) {

						$aUserData = $oUser->getUserData();

						switch($sPlaceholder) {
							case 'system_user_name':
								$mValue = $oUser->username;
								break;
							case 'system_user_firstname':
								$mValue = $aUserData['firstname'];
								break;
							case 'system_user_surname':
								$mValue = $aUserData['lastname'];
								break;
							case 'system_user_email':
								$mValue = $aUserData['email'];
								break;
							case 'system_user_phone':
								$mValue = $aUserData['data']['phone'];
								break;
							case 'system_user_fax':
								$mValue = $aUserData['data']['fax'];
								break;
						}

					}
					break;
				default:

					break;
			}

		}
						
		return $mValue;
	}

	public function replace($sString) {

		$aPlaceholders = $this->_getAllPlaceholders($sString);

		$bUnfoundPlaceholders = false;
		foreach((array)$aPlaceholders as $sPlaceholder=>$aPlaceholder) {

			foreach((array)$aPlaceholder as $sItem=>$aItem) {
				$mReplace = $this->_getReplaceValue($sPlaceholder, $aItem);
				
				if($mReplace !== null) {
					$sString = $this->processReplacing($sString, $sPlaceholder, $aItem, $mReplace);
				} else {
					$bUnfoundPlaceholders = true;
				}
				
			}

		}

		if(!empty($_REQUEST['placeholder_debug'])) {
			__pout($sString);
		}

		// Noch nicht ausführen, da vorher noch geklärt werden muss wie wir das mit PDF- und Nicht-PDF-Platzhaltern machen
		if(
			$this->bInitialReplace === false &&
			$this->replaceSmarty === true &&
			$bUnfoundPlaceholders === true
		) {

			$this->replaceSmarty($sString);
			
		}
		
		return $sString;
	}

	/**
	 * This method looks like this:
	 * 
	   $aPlaceholders = array(
			array(
				'section'		=> L10N::t('Section name', '...'),
				'placeholders'	=> array(
					'test'	=> L10N::t('Test Platzhalter', '...')
				)
			)		
		);

	 * @param string $sType
	 * @return array;
	 */
	abstract public function getPlaceholders($sType = '');

	/**
	 * Prapare and display the placeholder table
	 * 
	 * @return string
	 */
	public function displayPlaceholderTable($iCount = 1, $aFilter = array(), $sType = '')
	{

		$aPlaceholders = $this->getPlaceholders($sType);

		$aFlexPlaceholders = array();
		foreach((array)$this->_aFlexFieldLabels as $sPlaceholder=>$sLabel) {

			$aFlexPlaceholders[$sPlaceholder] = $sLabel;

		}

		if(!empty($aFlexPlaceholders)) {
			$aPlaceholders[] = array(
				'section'=>L10N::t('Individuelle Felder', 'Thebing » Placeholder'),
				'placeholders'=>$aFlexPlaceholders
			);
		}

		$sHtml = self::printPlaceholderList($aPlaceholders);

		return $sHtml;
	}

	/**
	 * Get the placeholder table HTML code
	 * 
	 * @param array $aPlaceholders
	 * @return string
	 */
	final public static function printPlaceholderList($aPlaceholders, $aFilter=array()) {

		$sHTML = '';

		$sHTML .= '<table cellspacing="0" cellpadding="2" style="width:100%;" class="table highlightRows placeholdertable">';

		$sHTML .= '
			<colgroup>
				<col style="width:30%;" />
				<col style="width:70%;" />
			</colgroup>
		';

		$iRow = 0;
		foreach((array)$aPlaceholders as $aSections)
		{
			if(isset($aSections['section']) && !empty($aSections['section']))
			{
				$sHTML .= '
					<tr class="placeholderTableHeader" id="placeholderrow_'.($iRow++).'">
						<th colspan="2" style="line-height:18px;">' . $aSections['section'] . '</th>
					</tr>
				';
			}
			else
			{
				$sHTML .= '
					<tr class="placeholderTableHeader" id="placeholderrow_'.($iRow++).'">
						<th style="line-height:18px;">' . L10N::t('Platzhalter', 'Thebing » Placeholder') . '</th>
						<th style="line-height:18px;">' . L10N::t('Beschreibung', 'Thebing » Placeholder') . '</th>
					</tr>
				';
			}

			foreach((array)$aSections['placeholders'] as $sKey => $sPlaceholder)
			{
				$sHTML .= '<tr class="placeholderTableRow" data-level="0" id="placeholderrow_'.($iRow++).'"><td>{' . $sKey . '}</td><td>' . $sPlaceholder . '</td></tr>';
			}
		}

		if(!$aFilter['communication']) {

			$sHTML .= '
						<tr id="placeholderrow_'.($iRow++).'">
							<th class="placeholderTableHeader" colspan="2" style="line-height:18px;">' . L10N::t('Sonstige Platzhalter', 'Thebing » Placeholder') . '</th>
						</tr>
					';

			$sHTML .= '<tr class="placeholderTableRow" data-level="0" id="placeholderrow_'.($iRow++).'"><td>{current_page}</td><td>' . L10N::t('Aktuelle Seite', 'Thebing » Placeholder') . '</td></tr>';
			$sHTML .= '<tr class="placeholderTableRow" data-level="0" id="placeholderrow_'.($iRow++).'"><td>{total_pages}</td><td>' . L10N::t('Anzahl der Seiten', 'Thebing » Placeholder') . '</td></tr>';
		} else {
			
			$sHTML .= '
						<tr id="placeholderrow_'.($iRow++).'">
							<th class="placeholderTableHeader" colspan="2" style="line-height:18px;">' . L10N::t('Kommunikation', 'Thebing » Placeholder') . '</th>
						</tr>
					';

			$sHTML .= '<tr class="placeholderTableRow" data-level="0" id="placeholderrow_'.($iRow++).'"><td>[#]</td><td>' . L10N::t('Identifizierungscode für eingehende E-Mails (Die Nutzung des Platzhalters weist eingehende E-Mails automatisch der entsprechenden Buchung zu.)', 'Thebing » Placeholder') . '</td></tr>';

		}
		
		$sHTML .= '</table>';

		return $sHTML;
	}


	/**
	 * @param string $sString
	 * @param string $sPlaceholder
	 * @param array $aPlaceholder
	 * @param mixed $mReplace
	 *
	 * @return mixed|string
	 */
	public function processReplacing($sString, $sPlaceholder, $aPlaceholder, $mReplace) {

		if(!is_array($mReplace)) {
			$sReplace	= $mReplace;
			$sFormat	= false;
		} else {
			$sReplace	= $mReplace['value'];
			$sFormat	= $mReplace['format'];
			$sLanguage	= $mReplace['language'];
		}

		// Modifier muss vor if angewendet werden, damit man die formatierten Werte auch vergleichen kann
		if(!empty($aPlaceholder['modifier'])) {
			switch($aPlaceholder['modifier']) {
				case 'date_format':

					$sFormat = false;

					// TODO Das kann man leider nicht einfach ersetzen, weil dieses strftime so PHP-spezifisch ist, dass es von nichts anderem (mehr) unterstützt wird
					if(!empty($sReplace)) {
						
						if(empty($sLanguage)) {
							$sLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
						}

						if(
							!is_numeric($sReplace) &&
							WDDate::isDate($sReplace, WDDate::DB_DATE)
						){
							$oDate = new WDDate($sReplace, WDDate::DB_DATE);
							$sReplace = $oDate->get(WDDate::TIMESTAMP);
						}

						if(is_numeric($sReplace)) {
							$sReplace = WDDate::strftime($aPlaceholder['parameter'], (int)$sReplace, $sLanguage);
						} else {

						}
					}

					break;
			}
		}

		if(isset($aPlaceholder['if']) && $aPlaceholder['if'] === true) {

			if(!empty($_REQUEST['placeholder_debug'])) {
				__pout($aPlaceholder);
			}
			
			$iSecurityCheck = 0;

			// Solange die Abfrage gefunden wird
			while(
				strpos($sString, $aPlaceholder['code']) !== false &&
				$iSecurityCheck < 20
			) {

				$iPos = strpos($sString, $aPlaceholder['code']);

				$iEndPos = strpos($sString, '{/if}', $iPos);
				if($iEndPos === false) {
					throw new RuntimeException(sprintf('Missing closing if for placeholder "%s"', $aPlaceholder['code']));
				}

				$iElsePos = strpos($sString, '{else}', $iPos);
				if($iEndPos < $iElsePos) {
					$iElsePos = false;
				}

				$sFirstPart = substr($sString, 0, $iPos);
				$sLastPart = substr($sString, $iEndPos + 5);
				$sIf = '';

				// Wenn else gefunden wurde
				if($iElsePos !== false) {
					$iFirstStart = $iPos;
					$iFirstEnd = $iElsePos;
					$iFirstEndLength = strlen('{else}');
					$iSecondStart = $iElsePos;
					$iSecondEnd = $iEndPos;
				} else {
					$iFirstStart = $iPos;
					$iFirstEnd = $iEndPos;
					$iFirstEndLength = strlen('{/if}');
					$iSecondStart = false;
					$iSecondEnd = false;
				}

				$iLen = strlen($aPlaceholder['code']);

				$bCondition = false;
				if(!empty($aPlaceholder['operator'])) {

					$sOperator = '==';
					switch($aPlaceholder['operator']) {
						case 'eq':
						case '==':
							$sOperator = '==';
							break;
						case 'neq':
						case '!=':
							$sOperator = '!=';
							break;
						case '&lt;':
						case '<':
							$sOperator = '<';
							break;
						case '&gt;':
						case '>':
							$sOperator = '>';
							break;
					}

					$sEval = '$bCondition = ($sReplace '.$sOperator.' '.$aPlaceholder['value'].');';
					try {
						eval($sEval);
					} catch (Exception $ex) {
						$bCondition = false;
					} catch (Error $e) {
						$bCondition = false;
					}

					if(!empty($_REQUEST['placeholder_debug'])) {
						__pout($sReplace);
						__pout($sEval);
						__pout($bCondition);
					}

				} else {

					if(
						$sFormat === 'date' &&
						$sReplace === '0000-00-00'
					) {
						$sReplace = null;
					}

					if(!empty($sReplace)) {
						$bCondition = true;
					}
					
					if(!empty($_REQUEST['placeholder_debug'])) {
						__pout($sReplace);
						__pout($bCondition);
					}

				}

				if($bCondition === false) {
					if($iSecondEnd !== false) {
						$sIf = substr($sString, $iSecondStart + $iFirstEndLength, $iSecondEnd - $iSecondStart - $iFirstEndLength);
					}
				} else {
					$sIf = substr($sString, $iFirstStart + $iLen, $iFirstEnd - $iFirstStart - $iLen);
				}

				$sString = $sFirstPart.$sIf.$sLastPart;
				
				$iSecurityCheck++;
				
			}

		}

		switch($sFormat) {
			case 'bool':
				if(!empty($sLanguage)) {
					$oLanguage = new Tc\Service\Language\Frontend($sLanguage);
					if($sReplace) {
						$sReplace = $oLanguage->translate('Ja');
					} else {
						$sReplace = $oLanguage->translate('Nein');
					}
				} else {
					// @TODO Alter Fall sollte eigentlich durch eine Exception abgefangen werden, ist hier aber zu gefährlich…
					Ext_TC_Util::reportError('Ext_Thebing_Placeholder::processReplacing(): Backend translation used for boolean flex field!', print_r(func_get_args(), true));
					$oFormat = new Ext_Thebing_Gui2_Format_YesNo();
					$sReplace = $oFormat->formatByValue($sReplace);
				}
				break;
			case 'bool_string':
				$oLanguage = new Tc\Service\Language\Frontend($sLanguage);
				if($sReplace == 'yes') {
					$sReplace = $oLanguage->translate('Ja');
				} elseif($sReplace == 'no') {
					$sReplace = $oLanguage->translate('Nein');
				}
				break;
			case 'date':
				$oFormat = new Ext_Thebing_Gui2_Format_Date();
				$aResultData = array('school_id'=>$this->_iSchoolId);
				$sReplace = $oFormat->format($sReplace, $oDummy, $aResultData);
				break;
			case 'amount':
				$sReplace = Ext_Thebing_Format::Number($sReplace, $this->getRootEntity()->getCurrency(), $this->_iSchoolId);
				break;
			case 'float':
				$sReplace = Ext_Thebing_Format::Number($sReplace, null, $this->_iSchoolId);
				break;
			case 'int':
				$sReplace = Ext_Thebing_Format::Int($sReplace, null, $this->_iSchoolId);
				break;
			case 'nl2br':
				$sReplace = nl2br($sReplace);
				break;
			default:
				break;
		}

		$sString = str_replace('{'.$aPlaceholder['complete'].'}', $sReplace, $sString);

		return $sString;

	}

	/**
	 * @param array $aValue
	 * @param string $sFormat
	 *
	 * @return mixed
	 */
	public function convertFlexPlaceholderInfo($aValue, &$sFormat) {

		switch($aValue['type']){
			case 2:
				$sFormat = 'bool';
				break;
			case 7:
				$sFormat = 'bool_string';
				break;
			case 4:
				$sFormat = 'date';
				break;
			default:
		}

		// Platzhalter muss immer ersetzt werden
		if($aValue['value'] === null) {
			$aValue['value'] = '';
		}
		
		return $aValue['value'];
	}

	/*
	 * Setzt die Daten die in dem "entries" loop durchlaufen werden sollen
	 * $sObject Klassennamen für ersetzung
	 * $mIds array mit allen Selectierten Einträgen
	 */
	public function setEntriesData($mIds, $sObject = ''){

		if(empty($sObject)){
			if($this instanceof Ext_Thebing_Inquiry_Placeholder){
				$sObject = 'Ext_TS_Inquiry';
}
		}

		$this->_sEntryObject = $sObject;
		

		if(is_array($mIds)){
			$this->_aEntries = $mIds;
		} else{
			$this->_aEntries = array($mIds);
		}

	}
	
	/**
	 * Daten vorbereiten für die generellen Platzhalter
	 */
	public function buildPlaceholderTableData() {

		$this->_aPlaceholderTableData['general'] = array(
			'header'	=> $this->_t('Generelle Platzhalter'),
			'data'		=> array(
				'today'						=> array(
					'tag'	=> 'today',
					'label'	=> $this->_t('Heute'),
				),
				'date_entry'				=> array(
					'tag'	=> 'date_entry',
					'label'	=> $this->_t('Erstellungsdatum'),
				),
				'system_user_name'				=> array(
					'tag'	=> 'system_user_name',
					'label'	=> $this->_t('Benutzername (angemeldeter Benutzer)'),
				),
				'system_user_firstname' => [
					'tag'	=> 'system_user_firstname',
					'label'	=> $this->_t('Vorname (angemeldeter Benutzer)'),
				],
				'system_user_surname' => [
					'tag'	=> 'system_user_surname',
					'label'	=> $this->_t('Nachname (angemeldeter Benutzer)'),
				],
				'system_user_email' => [
					'tag'	=> 'system_user_email',
					'label'	=> $this->_t('E-Mail (angemeldeter Benutzer)'),
				],
				'system_user_phone' => [
					'tag'	=> 'system_user_phone',
					'label'	=> $this->_t('Telefon (angemeldeter Benutzer)'),
				],
				'system_user_fax' => [
					'tag'	=> 'system_user_fax',
					'label'	=> $this->_t('Fax (angemeldeter Benutzer)'),
				],
				'school_name'				=> array(
					'tag'	=> 'school_name',
					'label'	=> $this->_t('Schule'),
				),
				'school_abbreviation' => [
					'tag' => 'school_abbreviation',
					'label' => $this->_t('Schule (Abkürzung)')
				],
				'school_address'			=> array(
					'tag'	=> 'school_address',
					'label'	=> $this->_t('Adresse der Schule'),
				),
				'school_address_addon'		=> array(
					'tag'	=> 'school_address_addon',
					'label'	=> $this->_t('Adresszusatz der Schule'),
				),
				'school_zip'				=> array(
					'tag'	=> 'school_zip',
					'label'	=> $this->_t('PLZ der Schule'),
				),
				'school_city'				=> array(
					'tag'	=> 'school_city',
					'label'	=> $this->_t('Stadt der Schule'),
				),
				'school_country'			=> array(
					'tag'	=> 'school_country',
					'label'	=> $this->_t('Land der Schule'),
				),
				'school_url'				=> array(
					'tag'	=> 'school_url',
					'label'	=> $this->_t('URL der Schule'),
				),
				'school_phone'				=> array(
					'tag'	=> 'school_phone',
					'label'	=> $this->_t('Telefon der Schule'),
				),	
				'school_phone2'				=> array(
					'tag'	=> 'school_phone2',
					'label'	=> $this->_t('Telefon 2 der Schule'),
				),	
				'school_fax' => array(
					'tag'	=> 'school_fax',
					'label'	=> $this->_t('Fax der Schule'),
				),	
				'school_email'				=> array(
					'tag'	=> 'school_email',
					'label'	=> $this->_t('E-Mail der Schule'),
				),
				'school_bank_name' => [
					'tag'	=> 'school_bank_name',
					'label'	=> $this->_t('Name der Bank der Schule'),
				],
				'school_bank_code' => [
					'tag'	=> 'school_bank_code',
					'label'	=> $this->_t('Bankleitzahl der Bank der Schule'),
				],
				'school_bank_address' => [
					'tag'	=> 'school_bank_address',
					'label'	=> $this->_t('Adresse der Bank der Schule'),
				],
				'school_account_holder' => [
					'tag'	=> 'school_account_holder',
					'label'	=> $this->_t('Kontoinhaber der Schule'),
				],
				'school_account_number' => [
					'tag'	=> 'school_account_number',
					'label'	=> $this->_t('Kontonummer der Schule'),
				],
				'school_iban' => [
					'tag'	=> 'school_iban',
					'label'	=> $this->_t('IBAN der Schule'),
				],
				'school_swift' => [
					'tag'	=> 'school_swift',
					'label'	=> $this->_t('Swift der Schule'),
				]
			)
		);
	}
	
	/**
	 * Plathalterkey für eine bestimmte Gruppe holen
	 * @param string $sType
	 * @param string $sKey
	 * @return string 
	 */
	public function getPlaceholderTag($sType, $sKey)
	{
		return $this->getPlaceholderData($sType, $sKey, 'tag');
	}
	
	/**
	 * Plathaltertitel für eine bestimmte Gruppe holen
	 * @param string $sType
	 * @param string $sKey
	 * @return string 
	 */
	public function getPlaceholderTitle($sType, $sKey)
	{
		return $this->getPlaceholderData($sType, $sKey, 'label');
	}
	
	/**
	 * Bestimmte Daten der vorbereiteten Platzhalter holen
	 * @param string $sType
	 * @param string $sKey
	 * @param string $sData
	 * @return string 
	 */
	public function getPlaceholderData($sType, $sKey, $sData)
	{
		if(isset($this->_aPlaceholderTableData[$sType]))
		{
			$aData = $this->_aPlaceholderTableData[$sType];
			
			if(isset($aData['data']) && isset($aData['data'][$sKey]) && isset($aData['data'][$sKey][$sData]))
			{
				return $aData['data'][$sKey][$sData];
			}
		}
		
		return null;
	}
	
	/**
	 * Titel für eine bestimmte Sektion holen
	 * @param string $sType
	 * @return string 
	 */
	public function getPlaceholderHeader($sType)
	{ 
		if(isset($this->_aPlaceholderTableData[$sType]) && isset($this->_aPlaceholderTableData[$sType]['header']))
		{
			return $this->_aPlaceholderTableData[$sType]['header'];
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Einen bestimmten key für einen generellen Platzhalter holen
	 * @param string $sKey
	 * @return string 
	 */
	public function getPlaceholderGeneralTag($sKey)
	{ 
		return $this->getPlaceholderTag('general', $sKey);
	}
	
	/**
	 * Einen bestimmten Titel für einen generellen Platzhalter holen
	 * @param string $sKey
	 * @return string 
	 */
	public function getPlaceholderGeneralTitle($sKey)
	{
		return $this->getPlaceholderTitle('general', $sKey);
	}

	/**
	 *
	 * @return Ext_Thebing_School 
	 */
	public function getSchool()
	{
		if ($this->oFixSchool !== null) {
			return $this->oFixSchool;
		}

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		
		return $oSchool;
	}
	
	/**
	 *
	 * Alle Platzhalter eines bestimmten Bereiches
	 * 
	 * @param string $sSection
	 * @return array 
	 */
	protected function _getPlaceholders($sSection)
	{		
		$aReturn = array();
		
		if(isset($this->_aPlaceholderTableData[$sSection]))
		{
			$aPlaceholderList			= array();
			$aPlaceholderSectionData	= (array)$this->_aPlaceholderTableData[$sSection];
			
			if(isset($aPlaceholderSectionData['data']))
			{
				$aSectionList				= $aPlaceholderSectionData['data'];

				foreach($aSectionList as $sKey => $mData)
				{
					$sArrayKey		= $this->getPlaceholderTag($sSection, $sKey);
					$mArrayValue	= $this->getPlaceholderTitle($sSection, $sKey);

					$aPlaceholderList[$sArrayKey] = $mArrayValue;
				}

				$aReturn = array(
					'section'		=> $this->getPlaceholderHeader($sSection),
					'placeholders'	=> $aPlaceholderList,
				);	
			}
			else
			{
				throw new Exception('Key "data" not defined in Section "'.$sSection.'"!');
			}
			
		}
		else
		{
			throw new Exception('Section "'.$sSection.'" not defined as placeholder data!');
		}
		
		return $aReturn;
	}
	
	/**
	 * Übersetzungsfunktion
	 * @param type $sString
	 * @return type 
	 */
	protected function _t($sString){
		return L10N::t($sString, 'Thebing » Placeholder');
	}

	/**
	 * Setzt individuelle Daten in die Klasse
	 *
	 * @param $sKey
	 * @param $mData
	 */
	public function setAdditionalData($sKey, $mData)
	{
		$this->_aAdditionalData[$sKey] = $mData;
	}

	/**
	 * Holt individuelle Daten
	 * @param string $sKey
	 * @return mixed
	 */
	public function getAdditionalData($sKey)
	{
		return $this->_aAdditionalData[$sKey];
	}

	/**
	 * @param WDBasic $oBasic
	 */
	public function setWDBasic($oBasic) {
		$this->_oWDBasic = $oBasic;
	}

	/**
	 * @param $mPlaceholder
	 */
	public function addMonitoringEntry($mPlaceholder) {

		// Singleton, damit pro Request nur ein Log-Eintrag generiert wird (bei mehreren Platzhalter-Objekten)
		if(self::$aPlaceholderMonitoring['instance'] === null) {
			self::$aPlaceholderMonitoring['instance'] = $this;
			\Core\Facade\SequentialProcessing::add('ts/old-placeholder-monitoring', $this);
		}

		$sClass = get_class($this);
		if(!isset(self::$aPlaceholderMonitoring['placeholders'][$sClass])) {
			self::$aPlaceholderMonitoring['placeholders'][$sClass] = [];
		}

		self::$aPlaceholderMonitoring['placeholders'][$sClass][] = $mPlaceholder;

	}

	/**
	 * @return array
	 */
	public static function getMonitoringPlaceholders() {
		return (array)self::$aPlaceholderMonitoring['placeholders'];
	}

	public function setType($sType) {		
	}
	
	public function setCommunicationSender(Ext_TC_User $oSender) {
	}
	
}

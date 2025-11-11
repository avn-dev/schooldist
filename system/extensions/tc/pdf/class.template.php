<?php

class Ext_TC_Pdf_Template extends Ext_TC_Basic {

	// The table name
	protected $_sTable = 'tc_pdf_templates';
	protected $_sTableAlias = 'tc_pt';

	// The additional language data
	protected $_aAdditional = array();

	protected static $_aCache = array();

	protected $_bAdditionalLoaded = false;

	// Joined table data
	protected $_aJoinTables = array(
		'objects' => array(
					'table' => 'tc_pdf_templates_objects',
					'foreign_key_field' => 'object_id',
					'primary_key_field' => 'template_id'
				),
		'languages' => array(
					'table' => 'tc_pdf_templates_languages',
					'foreign_key_field' => 'language_iso',
					'primary_key_field' => 'template_id'					
		),
		'settings' => array(
					'table' => 'tc_pdf_templates_settings',
					'foreign_key_field' => ['setting', 'value'],
					'primary_key_field' => 'template_id',
					'autoload' => false
		)
	);

	protected $_oLayout;

	/**
	 * Get the data
	 * 
	 * @param string $sName
	 * @return mixed
	 */
	public function __get($sName)
	{
		if(strpos($sName, 'setting_') !== false)
		{
			$aSettings = $this->settings;
			
			$sSetting = str_replace('setting_', '', $sName);
			
			$aFiltered = array_filter($aSettings, function($aSetting) use ($sSetting) {
				return ($aSetting['setting'] === $sSetting);
			});
			
			if(!empty($aFiltered)) {
				$aSetting = reset($aFiltered);
				return $aSetting['value'];
			}
			
			return null;
		}
		else if(strpos($sName, 'lang_tab_default_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_default_', '', $sName);

			$aParts = explode('_', $sName);

			$sValue = $this->_aAdditional['default'][$aParts[0]][$aParts[1]];
		}
		else if(strpos($sName, 'lang_tab_elements_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_elements_', '', $sName);

			$aParts = explode('_', $sName);

			$sValue = $this->_aAdditional['elements'][$aParts[0]][$aParts[1]];
		}
		else if(strpos($sName, 'lang_tab_school_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_school_', '', $sName);

			$aParts = explode('_', $sName, 3);

			$sValue = $this->_aAdditional['school'][$aParts[0]][$aParts[1]][$aParts[2]];
		}
		else
		{
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}


	/**
	 * Set the data
	 * 
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue)
	{
		if(strpos($sName, 'setting_') !== false)
		{
			$aSettings = $this->settings;
			
			$sSetting = str_replace('setting_', '', $sName);
			$bFound = false;
			
			foreach($aSettings as $iIndex => $aSetting) {
				if($aSetting['setting'] !== $sSetting) {
					continue;
				}
				
				$aSettings[$iIndex]['value'] = $mValue;
				
				$bFound = true;
				break;
			}
			
			if(!$bFound) {
				$aSettings[] = [
					'setting' => $sSetting,
					'value' => $mValue
				];
			}
			
			$this->settings = $aSettings;
		}
		else if(strpos($sName, 'lang_tab_default_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_default_', '', $sName);

			$aParts = explode('_', $sName);

			$this->_aAdditional['default'][$aParts[0]][$aParts[1]] = $mValue;
		}
		else if(strpos($sName, 'lang_tab_elements_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_elements_', '', $sName);

			$aParts = explode('_', $sName);

			$this->_aAdditional['elements'][$aParts[0]][$aParts[1]] = $mValue;
		}
		else if(strpos($sName, 'lang_tab_school_') !== false)
		{
			$this->_loadAdditionalData();
			$sName = str_replace('lang_tab_school_', '', $sName);

			$aParts = explode('_', $sName, 3);

			$this->_aAdditional['school'][$aParts[0]][$aParts[1]][$aParts[2]] = $mValue;
		}
		else
		{
			parent::__set($sName, $mValue);
		}
	}


	/*
	 * TODO UPLOAD Class
	 *
	 *
	 * Liefert die Werte die zum Template gespeichert wurden
	 * $bGetFile gibt an ob der Dateinamen oder die ID returned werden
	 * der hochgeladenen vorlagen (Hintergründe, Signaturen, Attachments)
	 *
	 */
	public function getOptionValue($sLang, $iObjectId, $sOption) {

		$mValue = '';

		$aSql = array(
					'template_id'=> (int)$this->id,
					'language_iso' => $sLang,
					'object_id' => (int)$iObjectId,
					'option' => $sOption
				);

		// Caching
		if(!isset(self::$_aCache['option_value'][$this->id])) {

			$sSql = "
						SELECT
							`tc_pto`.*,
							`tc_ptoa`.`file_id`
						FROM
							`tc_pdf_templates_options` `tc_pto` LEFT JOIN
							`tc_pdf_templates_options_attachments` `tc_ptoa` ON
								`tc_ptoa`.`option_id` = `tc_pto`.`id`
						WHERE
							`tc_pto`.`template_id` = :template_id
						";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			$aItems = (array)$aResult;

			self::$_aCache[$this->id] = array();
			foreach($aItems as $aItem) {
				self::$_aCache['option_value'][$this->id][$aItem['option']][$aItem['language_iso']][$aItem['object_id']][] = $aItem;
			}

		}

		// Nur weitermachen, wenn der Wert ein Array ist
		if(!is_array(self::$_aCache['option_value'][$this->id][$aSql['option']][$aSql['language_iso']][$aSql['object_id']])) {
			return;
		}

		if($sOption == 'attachments') {

			$mValue = array();
			foreach(self::$_aCache['option_value'][$this->id][$aSql['option']][$aSql['language_iso']][$aSql['object_id']] as $aData){
				$mValue[] = (int)$aData['file_id'];
			}

		} else {

			$aItem = reset(self::$_aCache['option_value'][$this->id][$aSql['option']][$aSql['language_iso']][$aSql['object_id']]);

			$mValue = $aItem['value'];

		}

		return $mValue;

	}

	public function saveOptionValue($sLang, $iObjectId, $sOption, $mValue){

		## START Atachment speichern
			if($sOption == 'attachments'){
				// Attachments werden in Verknüpfungstabelle gespeichert
				// alle bisherigen löschen
				$sSql = "SELECT
								`id`
							FROM
								`tc_pdf_templates_options`
							WHERE
								`template_id` = :template_id AND
								`language_iso` = :language_iso AND
								`object_id` = :object_id AND
								`option` = :option
							LIMIT 1
							";

			$aSql = array(
							'template_id'	=> (int)$this->id,
							'language_iso' => (string)$sLang,
							'object_id' => (int)$iObjectId,
							'option' 	=> (string)$sOption,
							'value' => (string)$mValue
						);
				$aData = DB::getQueryRow($sSql, $aSql);

				$iLastId = (int)$aData['id'];

				$sSql = "DELETE FROM
								`tc_pdf_templates_options_attachments`
							WHERE
								`option_id` = :option_id
						";
				$aSql = array();
				$aSql['option_id'] = (int)$iLastId;

				DB::executePreparedQuery($sSql, $aSql);

				// neu speichern
				foreach((array)$mValue as $iFileId){
					$sSql = "INSERT INTO
										`tc_pdf_templates_options_attachments`
									SET
										`option_id` = :option_id,
										`file_id`	= :file_id
								";

					$aSql = array();
					$aSql['option_id'] = (int)$iLastId;
					$aSql['file_id'] = (int)$iFileId;
					DB::executePreparedQuery($sSql, $aSql);
				}

		} else {

			$mValue = Ext_TC_Purifier::p($mValue);

			$sSql = " REPLACE INTO
							`tc_pdf_templates_options`
						SET
							`template_id` = :template_id ,
							`language_iso` = :language_iso ,
							`object_id` = :object_id ,
							`option` = :option ,
							`value` = :value ";

			$aSql = array(
							'template_id'	=> (int)$this->id,
							'language_iso' => (string)$sLang,
							'object_id' => (int)$iObjectId,
							'option' 	=> (string)$sOption,
							'value' => (string)$mValue
						);

			DB::executePreparedQuery($sSql, $aSql);

			}
		## ENDE
	}

	/* TODO no School data in Select part
	public function  manipulateSqlParts(&$aSqlParts) {
		$aSqlParts['select'] .= ', GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools` ';
		$aSqlParts['from']   .= ' LEFT OUTER JOIN 
									`kolumbus_pdf_templates_schools` `tc_pts` ON
								`tc_pts`.`template_id` = `tc_pt`.`id`';
	}
*/

	/**
	 * See parent
	 */
	public function save($bLog = true)
	{

		$this->_loadAdditionalData();
		$aAdditional = $this->_aAdditional;

		parent::save($bLog);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save additional language data

		$oType = new Ext_TC_Pdf_Layout($this->layout_id);

		$aTypeElements = $oType->getElements();

		foreach((array)$this->languages as $sLanguage)
		{

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Type elements fields

			foreach($aTypeElements as $oElement) {
				$oElement->saveValue($sLanguage, $this->id, $aAdditional['elements'][$oElement->id][$sLanguage]);
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // School fields

			foreach((array)$this->objects as $iObjectId)
			{

				$this->saveOptionValue(
					$sLanguage,
					$iObjectId,
					'filename_syntax',
					$aAdditional['school'][$iObjectId][$sLanguage]['filename']
				);

				$this->saveOptionValue(
					$sLanguage,
					$iObjectId,
					'first_page_pdf_template',
					$aAdditional['school'][$iObjectId][$sLanguage]['first_page_pdf_template']
				);

				$this->saveOptionValue(
					$sLanguage,
					$iObjectId,
					'additional_page_pdf_template',
					$aAdditional['school'][$iObjectId][$sLanguage]['additional_page_pdf_template']
				);

				if($this->user_signature != 1)
				{
					$this->saveOptionValue(
						$sLanguage,
						$iObjectId,
						'signatur_img',
						$aAdditional['school'][$iObjectId][$sLanguage]['signatur_img']
					);

					$this->saveOptionValue(
						$sLanguage,
						$iObjectId,
						'signatur_text',
						$aAdditional['school'][$iObjectId][$sLanguage]['signatur_text']
					);
				}

				$this->saveOptionValue(
					$sLanguage,
					$iObjectId,
					'attachments',
					$aAdditional['school'][$iObjectId][$sLanguage]['attachments']
				);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_loadData($this->id);

		return $this;
	}
	/**
	 * Damit bei änderungen in den Layout Felder , die Bearbeiter und Bearbeitet Spalte sich aktualisieren
	 * @return boolean
	*/ 
	public function checkUpdateUser() {
		return true;
	}
	
	/**
	 * See parent
	 * 
	 * @param int $iDataID
	 */
	protected function _loadData($iDataID)
	{
		parent::_loadData($iDataID);

		$oType = Ext_TC_Pdf_Layout::getInstance($this->layout_id);

		$this->_oLayout = $oType;

	}

	protected function _loadAdditionalData() {
		
		$iDataID = (int) $this->id;
		
		// Load additional language data
		if(
			$iDataID > 0 &&
			$this->_bAdditionalLoaded === false
		) {
			
			$oType = Ext_TC_Pdf_Layout::getInstance($this->layout_id);
			
			$aTypeElements = $oType->getElements();

			foreach((array)$this->languages as $sLanguage) {

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Type elements fields

				foreach($aTypeElements as $oElement)
				{
					$this->_aAdditional['elements'][$oElement->id][$sLanguage] =
						$oElement->getValue($sLanguage, $this->id);
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // School fields

				foreach((array)$this->objects as $iObjectId)
				{

					$oTempSchool = Ext_TC_Factory::getInstance('Ext_TC_SubObject', $iObjectId);
					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['filename'] = $this->getOptionValue($sLanguage, $oTempSchool->id, 'filename_syntax', false);
					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['first_page_pdf_template'] = $this->getOptionValue($sLanguage, $oTempSchool->id, 'first_page_pdf_template', false);
					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['additional_page_pdf_template'] = $this->getOptionValue($sLanguage, $oTempSchool->id, 'additional_page_pdf_template', false);

					if($this->user_signature != 1)
					{

						$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['signatur_img'] = $this->getOptionValue($sLanguage, $oTempSchool->id, 'signatur_img', false);
						
						$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['signatur_text'] = $this->getOptionValue($sLanguage, $oTempSchool->id, 'signatur_text');
					}

					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['attachments'] = array();

					$aTemp = (array)$this->getOptionValue($sLanguage, $oTempSchool->id, 'attachments', false);

					$this->_aAdditional['school'][$oTempSchool->id][$sLanguage]['attachments'] = $aTemp;

				}

			}

			$this->_bAdditionalLoaded = true;

		}

	}

	/**
	 * get the Layout of the Template
	 * @return Ext_TC_Pdf_Layout
	 */
	public function getLayout()
	{
		$oLayout = $this->_oLayout;

		if(!($oLayout instanceof Ext_TC_Pdf_Layout)){
			$oLayout = Ext_TC_Pdf_Layout::getInstance($this->layout_id);
		}

		return $oLayout;
	}

	/**
	 * Nach Templates suchen anhand ihres Typs und Objekt(en)
	 * @param $mType
	 * @param int|array $mObject
	 * @param string $sLanguage
	 * @return array
	 */
	public static function search($mType, $mObject = null, $sLanguage = null) {

        $query = static::query()
            ->onlyValid()
            ->select('tc_pt.*');

        if ($sLanguage !== null) {
            $query->join('tc_pdf_templates_languages as tc_ptl', function ($join) use ($sLanguage) {
                $join->on('tc_ptl.template_id', '=', 'tc_pt.id')
                    ->where('tc_ptl.language_iso', $sLanguage);
            });
        }

        if ($mObject !== null) {
            $query->join('tc_pdf_templates_objects as tc_pto', function ($join) use ($mObject) {
                $join->on('tc_pto.template_id', '=', 'tc_pt.id')
                    ->whereIn('tc_pto.object_id', \Illuminate\Support\Arr::wrap($mObject));
            });
        }

        if (is_array($mType)) {
            $query->whereIn('type', $mType);
        } else {
            $query->where('type', $mType);
        }

        $query->orderBy('name');

		return $query->get()->toArray();
	}

	/**
	 * get the Languages as Array Iso => Name
	 * @param string $sTranslationLanguage
	 * @return array
	 */
	public function getLanguages($sTranslationLanguage = ''){

		$aTemplateLanguages = (array)$this->languages;
		$aSystemLanguages = Ext_TC_Language::getSelectOptions($sTranslationLanguage);
		$aFinalLanguages = array();

		foreach($aTemplateLanguages as $sIso){
			$aFinalLanguages[$sIso] = $aSystemLanguages[$sIso];
		}

		return $aFinalLanguages;
	}
	
	/**
	 * Liefert alle Elemente des Layouts mit den entsprechenden Inhalten
	 * @param string $sLanguage
	 * @param string $sElementType
	 * @return array
	 */
	public function getElements($sLanguage, string $sElementType = null) {

		$aSql = [
			'layout_id'=>(int)$this->layout_id,
			'template_id'=>(int)$this->id,
			'language_iso'=>$sLanguage
		];

		$sWhere = "";
		if ($sElementType !== null) {
			$sWhere = " AND `tple`.`element_type` = :element_type ";
			$aSql['element_type'] = $sElementType;
		}

		$sSql = "
			SELECT
				`tple`.*,
				`tplev`.`value`
			FROM
				`tc_pdf_layouts_elements` `tple` LEFT JOIN
				`tc_pdf_layouts_elements_values` `tplev` ON
					`tple`.`id` = `tplev`.`element_id` AND
					`tplev`.`template_id` = :template_id AND
					`tplev`.`language_iso` = :language_iso
			WHERE
				`tple`.`layout_id` = :layout_id AND
				`tple`.`active` = 1
				".$sWhere."
			ORDER BY
				`tple`.`position`
			";

		$aElements = DB::getQueryRows($sSql, $aSql);

		return $aElements;		
	}

	public static function getApplicationName($sApplication) {		
		$aApplications = static::getApplications();		
		return (isset($aApplications[$sApplication])) ? $aApplications[$sApplication] : '';		
	}
	
	public static function getApplications($bOnlyAdditional = false) {
		$aApplications = [];
		return $aApplications;
	}
	
	/**
	 * Get the placeholders by type
	 * 
	 * @param string $sType
	 * @return null|array
	 */
	public static function getPdfPlaceholderObject($sType) {
		return null;
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ", GROUP_CONCAT(DISTINCT `languages`.`language_iso` ORDER BY `languages`.`language_iso` SEPARATOR ',') `languages`
			                     , GROUP_CONCAT(DISTINCT `objects`.`object_id` ORDER BY `objects`.`object_id` SEPARATOR ',') `objects`";

	}
	
	/**
	 * Liefert alle Templates, die vom Typ additional sind
	 */
	public static function getAdditionalTemplates($sDocumentType='additional') {

		$sSql = "
			SELECT
				`id`,
				`name`
			FROM
				`tc_pdf_templates`
			WHERE
				`active` != 0
		";

		$aSql = [];
		
		if($sDocumentType === 'additional') {
			$sSql .= " AND `type` IN (:types) ";
			$aSql['types'] = array_keys(static::getApplications(true));
		} else {
			$sSql .= " AND `type` = :type ";
			$aSql['type'] = $sDocumentType;
		}
		
		$aResult = DB::getQueryPairs($sSql, $aSql);

		return $aResult;		
	}
	
	/**
	 * @param bool $bForSelect
	 * @param string $sType
	 * @param int|array $mObject
	 * @param string $sLanguage
	 * @return array
	 */
	public static function getSelectOptions($bForSelect = false, $sType = null, $mObject = null, $sLanguage = null)
	{
		$aTemplates = array();
		$aTemplateObjs = (array)static::search($sType, $mObject, $sLanguage);

		foreach((array)$aTemplateObjs as $oTemplate){
			$aTemplates[$oTemplate->id] = $oTemplate->name;
		}

		if($bForSelect) {
			$aTemplates = Ext_TC_Util::addEmptyItem($aTemplates);
		}

		return $aTemplates;
	}

 }

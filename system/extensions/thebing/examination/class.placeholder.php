<?php


class Ext_Thebing_Examination_Placeholder extends Ext_Thebing_Inquiry_Placeholder
{
	protected $_oExaminationVersion;

	public function __construct($iInquiryId = 0, $iCustomerId = 0, $iVersion = 0)
	{
		parent::__construct($iInquiryId, $iCustomerId);
		$this->_oExaminationVersion = Ext_Thebing_Examination_Version::getInstance($iVersion);

		$oExamination = $this->_oExaminationVersion->getExamination();
		$this->_oInquiryCourse	= Ext_TS_Inquiry_Journey_Course::getInstance($oExamination->inquiry_course_id);
		$this->_oCourse			= Ext_Thebing_Tuition_Course::getInstance($oExamination->course_id);
	}
	
	public function displayPlaceholderTable($iCount = 1, $aFilter = array(), $sType = '')
	{
	
		$aPlaceholderFlex = self::getAllFlexTags();

		$aPlaceholder = self::getAllAvailableTags($iCount, true, $aFilter);

		$aParentPlaceholders = $aLine = array();

		$aPlaceholder = self::clearPlaceholders($aPlaceholder);

		$aParentPlaceholders = array_merge($aParentPlaceholders, $aPlaceholder);

		if(!empty($aPlaceholderFlex))
		{
			$aSection = array(
				array(
					'type'	=> 'headline',
					0		=> L10N::t('Flexibility Placeholder')
				)
			);

			$aPlaceholderFlex = array_merge($aSection, $aPlaceholderFlex);

			$aPlaceholder = self::clearPlaceholders($aPlaceholderFlex);

			$aParentPlaceholders = array_merge($aParentPlaceholders, $aPlaceholder);
		}

		return parent::printPlaceholderList($aParentPlaceholders,$aFilter);
	}

	public static function getAllAvailableTags($iPlaceholderLib = 1, $bShowHeadlines = false, $aFilter=array(), $mTemplateType='') {
		$aPlaceholder = parent::getAllAvailableTags($iPlaceholderLib, $bShowHeadlines, $aFilter);
		
		if(Ext_Thebing_Access::hasRight('thebing_tuition_examination')){
			if($bShowHeadlines){
				$aHeadline = array();
					$aHeadline[0] = L10N::t('Examen', 'Thebing » Placeholder');
				$aHeadline[1] = '';
				$aHeadline['type'] = 'headline';
				$aPlaceholder[] = $aHeadline;
			}

			$sDescriptionPart = 'Thebing » Placeholder';

			$aPlaceholder[] = '{examination_date}<b>'.L10N::t('Datum', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_from}<b>'.L10N::t('Von', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_until}<b>'.L10N::t('Bis', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_level}<b>'.L10N::t('Level', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_score}<b>'.L10N::t('Punkte', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_passed_yesno}<b>'.L10N::t('Bestanden', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_grade}<b>'.L10N::t('Grade', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_teachers}<b>'.L10N::t('Lehrer', $sDescriptionPart).'</b>';
			#$aPlaceholder[] = '{examination_sections}<b>'.L10N::t('Bereich', 'Thebing » Placeholder').'</b>';
			$aPlaceholder[] = '{examination_subjects_comment}<b>'.L10N::t('Kommentar zu den Fächern', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_comment}<b>'.L10N::t('Sonstiger Kommentar', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_class_names}<b>'.L10N::t('Namen aller Klassen des Kurses (während des Prüfungszeitraums)', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_attendance_notes}<b>'.L10N::t('Kommentare aller Anwesenheitseinträge des Kurses (während des Prüfungszeitraums)', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_tuition_allocated_days}<b>'.L10N::t('Anzahl der in der Klassenplanung zugewiesenen Tage des Kurses (während des Prüfungszeitraums)', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_attendance_days_present_completely}<b>'.L10N::t('Anzahl der Tage kompletter Anwesenheit des Kurses (während des Prüfungszeitraums)', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_attendance_days_absent_completely}<b>'.L10N::t('Anzahl der Tage kompletter Abwesenheit des Kurses (während des Prüfungszeitraums)', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_attendance_days_absent_partially}<b>'.L10N::t('Anzahl der Tage teilweiser Abwesenheit des Kurses (während des Prüfungszeitraums)', $sDescriptionPart).'</b>';
			$aPlaceholder[] = '{examination_holiday_days}<b>'.L10N::t('Anzahl der Ferientage (während der Kurswoche und während des Prüfungszeitraums)', $sDescriptionPart).'</b>';

			$aSectionCategories	= Ext_Thebing_Examination_SectionCategory::getOptionList();

			foreach($aSectionCategories as $sectionCategoryId=>$sectionCategoryName) {
				
				$category = Ext_Thebing_Examination_SectionCategory::getInstance($sectionCategoryId);
				
				$sections = $category->getJoinedObjectChilds('areas');
				
				if(!empty($sections)) {
	
					$sHtmlCategory = '{examination_'.$sectionCategoryId.'}<b>';
					$sHtmlCategory .= L10N::t('Prüfungsbereich Kategorie %s', $sDescriptionPart);
					$sHtmlCategory = str_replace('%s', $sectionCategoryName, $sHtmlCategory);
					$sHtmlCategory .= '</b>';
					$aPlaceholder[]	= $sHtmlCategory;

					foreach($sections as $section) {

						$sHtmlSection = '{examination_'.$category->id.'_'.$section->id.'}<b>';
						$sHtmlSection .= L10N::t('Prüfungsbereich %s', $sDescriptionPart);
						$sHtmlSection = str_replace('%s', $section->title, $sHtmlSection);
						$sHtmlSection .= '</b>';
						$aPlaceholder[]	= $sHtmlSection;

						$sHtmlSection = '{examination_'.$category->id.'_'.$section->id.'_result}<b>';
						$sHtmlSection .= L10N::t('Prüfungsbereich Wert %s', $sDescriptionPart);
						$sHtmlSection = str_replace('%s', $section->title, $sHtmlSection);
						$sHtmlSection .= '</b>';
						$aPlaceholder[]	= $sHtmlSection;

					}
				
				}
				
			}
			
		}

		return $aPlaceholder;
	}
	
	public function searchPlaceholderValue($sField, $iOptionalParentId,$aPlaceholder=array()) {

		if($this->_oExaminationVersion instanceof Ext_Thebing_Examination_Version) {

			$sType = null;
			$mValue = null;

			switch($sField) {
				case 'examination_date':
					$mValue	= $this->_oExaminationVersion->examination_date;
					$sType				= 'date';
					break;
				case 'examination_from':
					$mValue	= $this->_oExaminationVersion->from;
					$sType				= 'date';
					break;
				case 'examination_until':
					$mValue	= $this->_oExaminationVersion->until;
					$sType				= 'date';
					break;
				case 'examination_level':
					$oCustomer = $this->_oCustomer;
					if(is_object($oCustomer) && $oCustomer instanceof Ext_TS_Inquiry_Contact_Traveller)
					{
						$iLevel		= $this->_oExaminationVersion->level_id;
						$oLevel		= Ext_Thebing_Tuition_Level::getInstance($iLevel);
						$sMethod	= 'name_'.$oCustomer->getLanguage();
						$mValue		= $oLevel->$sMethod;
					}
					break;
				case 'examination_score':
					$mValue = $this->_oExaminationVersion->score;
					break;
				case 'examination_passed_yesno':
					$iPassed = $this->_oExaminationVersion->passed;
					if( 1 == $iPassed )
					{
						$mValue = L10N::t('Ja');
					}
					elseif( 0 == $iPassed )
					{
						$mValue = L10N::t('Nein');
					}
					break;
				case 'examination_grade':
					$mValue = $this->_oExaminationVersion->grade;
					$sType	= 'float';
					break;
				case 'examination_teachers':
					$oTemplate	= $this->_oExaminationVersion->getTemplate();
					if($oTemplate)
					{
						$mValue = '';
						$oSchool		= Ext_Thebing_School::getInstance($oTemplate->school_id);
						$aAllTeachers	= $oSchool->getTeacherList(true);
						$aTeachers		= $this->_oExaminationVersion->teachers;
						$iCount			= count($aTeachers);
						foreach((array)$aTeachers as $iKey => $iTeacherId)
						{
							if(array_key_exists($iTeacherId, $aAllTeachers))
							{
								$mValue .= $aAllTeachers[$iTeacherId];
								if($iKey < $iCount-1)
								{
									$mValue .= '<br />';
								}
							}
						}

						if(is_string($this->_oExaminationVersion->examiner_name))
						{
							$mValue .= '<br >'.$this->_oExaminationVersion->examiner_name;
						}
					}
					break;
				case 'examination_sections':
					$mValue = $this->_getSectionsTable();
					break;
				case 'examination_subjects_comment':
					$mValue = $this->_oExaminationVersion->comment_sections;
					break;
				case 'examination_comment':
					$mValue = $this->_oExaminationVersion->comment;
					break;
				case 'examination_class_names':
					$aClassNames = $this->_oExaminationVersion->getClassNames();
					$mValue = join(', ', $aClassNames);
					break;
				case 'examination_attendance_notes':
					if(
						!empty($this->_oExaminationVersion->from) &&
						!empty($this->_oExaminationVersion->until)
					) {
						$dFrom = new DateTime($this->_oExaminationVersion->from);
						$dUntil = new DateTime($this->_oExaminationVersion->until);
						$aComments = [];
						$aAttendances = Ext_Thebing_Tuition_Attendance::getAttendancesEntriesForPeriod($dFrom, $dUntil, ['`ts_ij`.`inquiry_id`' => $this->_oInquiry->id]);
						foreach($aAttendances as $oAttendance) {
							if(
								!empty($oAttendance->comment) &&
								$this->_oExaminationVersion->inquiry_course_id == $oAttendance->journey_course_id &&
								!in_array($oAttendance->comment, $aComments)
							) {
								$aComments[] = $oAttendance->comment;
							}
						}

						$mValue = join(', ', $aComments);
					}
					break;
				case 'examination_tuition_allocated_days':
				case 'examination_holiday_days':
					if(
						!empty($this->_oExaminationVersion->from) &&
						!empty($this->_oExaminationVersion->until)
					) {
						$dFrom = new DateTime($this->_oExaminationVersion->from);
						$dUntil = new DateTime($this->_oExaminationVersion->until);

						if($sField === 'examination_holiday_days') {
							$aDays = $this->_oInquiry->getHolidayDays($dFrom, $dUntil);
						} else {
							$aDays = $this->_oInquiry->getClassDays($dFrom, $dUntil, $this->_oInquiryCourse);
						}

						$mValue = count($aDays);
					}
					break;
				case 'examination_attendance_days_present_completely':
				case 'examination_attendance_days_absent_completely':
				case 'examination_attendance_days_absent_partially':
					if(
						!empty($this->_oExaminationVersion->from) &&
						!empty($this->_oExaminationVersion->until)
					) {
						$dFrom = new DateTime($this->_oExaminationVersion->from);
						$dUntil = new DateTime($this->_oExaminationVersion->until);

						$sType = 'present';
						if($sField === 'examination_attendance_days_absent_completely') {
							$sType = 'absent';
						} elseif($sField === 'examination_attendance_days_absent_partially') {
							$sType = 'absent_partial';
						}

						$aDays = Ext_Thebing_Tuition_Attendance::getPresentOrAbsentDays($sType, $this->_oInquiry, $dFrom, $dUntil, $this->_oInquiryCourse);
						$mValue = count($aDays);
					}
					break;
				default:
					$bMatch = preg_match('/^examination_([0-9]*)(_([0-9]*))?(_result)?$/', $sField, $aMatches);

					if($bMatch) {
						$iMatchCategory = (int)$aMatches[1];
						$iMatchSection = (int)$aMatches[3];

						if($iMatchSection > 0) {
							$oExaminationSection = Ext_Thebing_Examination_Sections::getInstance($iMatchSection);
							if(isset($aMatches[4])) {
								$mValue = $this->_oExaminationVersion->getEntityValue($iMatchSection);
								if ($oExaminationSection->entity_type_id == \Ext_Thebing_Examination_Sections::TYPE_TEXTAREA) {
									// TODO ist immer Html gewünscht?
									$mValue = nl2br($mValue);
								}
							} else {
								$mValue = $oExaminationSection->title;
							}
						} else {
							$oExaminationSectionCategory = Ext_Thebing_Examination_SectionCategory::getInstance($iMatchCategory);
							$mValue = $oExaminationSectionCategory->name;
						}
						
						$mValue = (string)$mValue;
						
					} else {

						$aHookData = [
							'placeholder' => $sField,
							'placeholder_object' => $this,
							'value' => $mValue,
							'found' => false
						];

						System::wd()->executeHook('ts_tuition_examination_placeholder_replace', $aHookData);

						if($aHookData['found'] === false) {
							$mValue = parent::searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder);
						} else {
							$mValue = (string)$aHookData['value'];
						}

					}
					
					break;
			}

			if(
				// 0 soll angezeigt werden
				$mValue !== null
			) {
				if($sType === 'date') {
					if($mValue !== '0000-00-00') {
						$oWdDate = new WDDate($mValue, WDDate::DB_DATE);
						$iDate = $oWdDate->get(WDDate::TIMESTAMP);

						if($iDate !== false) {
							return Ext_Thebing_Format::LocalDate($iDate);
						}
					}
				} elseif($sType === 'float') {
					$mValue = Ext_Thebing_Format::convertFloat($mValue);
					return $mValue;
				} else {
					return $mValue;
				}
			}

		}

		return null;
	}

	protected function _getSectionsTable()
	{
		$oTemplate = $this->_oExaminationVersion->getTemplate();
		if($oTemplate)
		{
			$sHtml = '';
			$aSections	= $this->_oExaminationVersion->getSections($oTemplate->id, $oTemplate->school_id,false);
			if(!empty($aSections))
			{
				$iCount		= count($aSections);
				$iDelimiter = round($iCount/2);
				$aRows1		= array();
				$aRows2		= array();
				for($i=0;$i<$iCount;$i++)
				{
					$aData		= $aSections[$i];
					$sClass		= $aData['model_class'];
					$oModel		= new $sClass();
					$sKey		= $oModel->getEntityKey();
					$mValue		= $aData[$sKey];
					$iSectionid	= $aData['id'];
					$oModel->setValue($mValue);
					$oModel->setSectionId($iSectionid);
					$mValue		= $oModel->getStringValue();

					if($i<$iDelimiter)
					{
						$aRows1[]	= '<td>'.$aData['title'].'</td><td>'.$mValue.'</td>';
					}
					else
					{
						$aRows2[]	= '<td>'.$aData['title'].'</td><td>'.$mValue.'</td>';
					}
					
				}

				$sHtml .= '<table border="0" cellpadding="0" cellspacing="5">';
				for($i=0;$i<$iDelimiter;$i++)
				{
					$sHtml .= '<tr>';
					$sHtml .= $aRows1[$i];
					if(isset($aRows2[$i]))
					{
						$sHtml .= $aRows2[$i];
					}
					$sHtml .= '</tr>';
				}
				$sHtml .= '</table>';

				/*
				if( 0 < strlen($this->_oExaminationVersion->comment_sections) )
				{
					$sHtml .= '<table border="0" celppadding="0" cellspacing="0">';
					$sHtml .= '<tr>';
					$sHtml .= '<td>'.L10N::t('Kommentar zu den Fächern', 'Thebing » Tuition » Examination').':</td>';
					$sHtml .= '</tr>';
					$sHtml .= '<tr>';
					$sHtml .= '<td>'.$this->_oExaminationVersion->comment_sections.'</td>';
					$sHtml .= '</tr>';
					$sHtml .= '</table>';
				}*/

			}

			return $sHtml;
		}
	}

	/**
	 * @return Ext_Thebing_Examination_Version
	 */
	public function getExaminationVersion() {
		return $this->_oExaminationVersion;
	}

	public function getRootEntity() {
		return $this->_oExaminationVersion->getExamination();
	}

}
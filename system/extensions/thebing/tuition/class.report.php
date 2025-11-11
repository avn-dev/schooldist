<?php

class Ext_Thebing_Tuition_Report extends Ext_Thebing_Basic
{
	// DB table
	protected $_sTable = 'kolumbus_tuition_reports';
	protected $_sTableAlias = 'ktr';

	// Flag für die Erlaubnis mit Fehlern zu speichern (falls Fehler ignoriert werden können => siehe $bCanIgnoreErrors)
	protected $_saveWithErrors;

	// Format data
	protected $_aFormat = array(
		'title' => array(
			'required' => true
		),
		'group_by' => array(
			'required' => true
		),
		'start_with' => array(
			'required' => true
		),
		'break' => array(
			'required' => true
		),
		'layout' => array(
			'required' => true
		)
	);

	// The linked columns
	protected $_aJoinTables = array(
		'columns' => array(
			'table'				=> 'kolumbus_tuition_report_cols',
			'primary_key_field'	=> 'report_id',
			'sort_column'		=> 'position'
		),
        'school_settings' => array(
            'table' => 'kolumbus_tuition_reports_to_schools',
            'foreign_key_field' => ['school_id', 'background_pdf'],
            'primary_key_field' => 'report_id'
        )
	);
	
	protected $_aJoinedObjects = [
		'layout' => [
			'class'	=> '\Ext_Thebing_Pdf_Template_Type',
			'key' => 'layout',
			'check_active' => true,
			'type' => 'parent',
		],
	];

	protected $_aAttributes = [
		'sub_group' => [
			'type' => 'text'
		],
		'subheading' =>[
			'type' => 'text'
		],
		'per_lesson' => [
			'type' => 'text'
		]
	];
	
	/* ==================================================================================================== */

	public function  __set($sName, $mValue) {
		if('ignore_errors'==$sName) {
			$this->_saveWithErrors = $mValue;
		} else if($sName === 'schools') {
            $this->setSchoolIds((array)$mValue);
        } else if(strpos($sName, 'background_pdf_', 0) !== false) {
            $this->setSchoolSetting((int)str_replace('background_pdf_', '', $sName), 'background_pdf', (int) $mValue);
        } else {
			parent::__set($sName, $mValue);
		}
	}

	public function  __get($sName) {
		
		if('ignore_errors'==$sName) {
            return $this->_saveWithErrors;
        } else if($sName === 'schools') {
		    return $this->getSchoolIds();
        } else if(strpos($sName, 'background_pdf_', 0) !== false) {
		    return $this->getSchoolSetting((int)str_replace('background_pdf_', '', $sName), 'background_pdf', 0);
		} else {
			return parent::__get($sName);
		}

	}

	/* ==================================================================================================== */

    public function getSchoolIds() {
        return array_column($this->school_settings, 'school_id');
    }

    public function setSchoolIds(array $schoolIds) {

        $settings = [];
        foreach($this->school_settings as $setting) {
            foreach($schoolIds as $index => $schoolId) {
                if($setting['school_id'] == $schoolId) {
                    $settings[] = $setting;
                    unset($schoolIds[$index]);
                    break;
                }
            }
        }

        foreach($schoolIds as $schoolId) {
            $settings[] = [
                'school_id' => $schoolId,
                'background_pdf' => 0,
            ];
        }

        $this->school_settings = $settings;

    }

    public function getSchoolSetting(int $schoolId, string $key, $default = null) {
        foreach($this->school_settings as $setting) {
            if($setting['school_id'] == $schoolId) {
                return $setting[$key];
            }
        }

        return $default;
    }

    public function setSchoolSetting(int $schoolId, string $key, $value) {

        $settings = $this->school_settings;

        foreach($this->school_settings as $index => $setting) {
            if($setting['school_id'] == $schoolId) {
                $settings[$index][$key] = $value;
                break;
            }
        }

        $this->school_settings = $settings;
    }

	/**
	 * Get the list of reports
	 *
	 * @param bool $bForSelect
	 * @return array
	 */
	public function getList($bForSelect = true)
	{
		$sSelect = "`ktr`.*";

		if($bForSelect)
		{
			$sSelect = "`ktr`.`id`, `ktr`.`title`";
		}

		$sSQL = "
			SELECT
				" . $sSelect . "
			FROM
				`kolumbus_tuition_reports` `ktr` INNER JOIN
				`kolumbus_tuition_reports_to_schools` `ktrts` ON
				    `ktrts`.`report_id` = `ktr`.`id` AND
				    `ktrts`.`school_id` = :school_id
			WHERE
				`ktr`.`active` = 1 
			ORDER BY
				`ktr`.`title`
		";

        $aSQL = array('school_id' => (int)\Core\Handler\SessionHandler::getInstance()->get('sid'));

		if($bForSelect)
		{
			$aList = DB::getQueryPairs($sSQL, $aSQL);
		}
		else
		{
			$aList = DB::getPreparedQueryData($sSQL, $aSQL);
		}

		return $aList;
	}

	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {

		$aColumns = $this->columns;
		foreach((array)$aColumns as $iKey => $aValue)
		{
			if((int)$aValue['column_id'] == 0)
			{
				unset($aColumns[$iKey]);
			}
		}
		$this->columns = $aColumns;

		$mReturn = parent::save();

		return $mReturn;

	}


	/**
	 * See parent
	 */
	public function validate($bThrowExceptions = false) {
		global $_VARS;

		if($this->active == 0) {
			$_VARS['save']['ignore_errors'] = 1;
		}

		$mErrors = parent::validate($bThrowExceptions);

		// Weiche Fehler ignorieren falls gewünscht
		if(
			$_VARS['save']['ignore_errors'] != 1 &&
			$mErrors === true
		) {

			$mErrors = array();

			$iTotal = 0;

			foreach((array)$this->columns as $iKey => $aValue) {
				if((int)$aValue['column_id'] != 0) {
					
					if(!is_numeric($aValue['width']) || $aValue['width'] <= 0) {
						$mErrors['field_width'][] = 'WRONG_WIDTH';
						return $mErrors;
					}

					$iTotal += (int)$aValue['width'];
				}
			}

			$oLayout = new WDBasic($this->layout, 'kolumbus_pdf_templates_types');

			$iCmp = $oLayout->page_format_width - $oLayout->first_page_border_right - $oLayout->first_page_border_left;

			if($iTotal > $iCmp) {
				$mErrors['total_width'][] = 'MAXIMUM_WIDTH';
			}

		}
		
		if(empty($mErrors)) {
			return true;
		}

		return $mErrors;
	}

    public function  manipulateSqlParts(&$aSqlParts, $sView=null) {
        $aSqlParts['select'] .= ',
 									GROUP_CONCAT(DISTINCT `school_settings`.`school_id`) AS `schools`
								';
    }

}

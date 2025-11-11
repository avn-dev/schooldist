<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property \Ext_Thebing_Form $oWDBasic
 */
class Ext_Thebing_Form_Gui2 extends Ext_Thebing_Gui2_Data
{

	// The GUI description
	public static $sDescription = 'Thebing » Admin » Formulare » Anmeldeformulare';

	/* ==================================================================================================== */

	/**
	 * Get draggable blocks
	 * 
	 * @return array
	 */
	public static function getBlocks()
    {

		$aBlocks = [
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_COLUMNS,
				'title' => L10N::t('Mehrspaltige Bereich', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_HEADLINE2,
				'title' => L10N::t('Überschrift H2', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_HEADLINE3,
				'title' => L10N::t('Überschrift H3', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_STATIC_TEXT,
				'title' => L10N::t('Textbereich', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD,
				'title' => L10N::t('Download', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_INPUT,
				'title' => L10N::t('Eingabefeld', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_SELECT,
				'title' => L10N::t('Auswahlliste', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT,
				'title' => L10N::t('Mehrfachauswahl', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_DATE,
				'title' => L10N::t('Datumsfeld', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX,
				'title' => L10N::t('Checkbox', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_UPLOAD,
				'title' => L10N::t('Upload', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA,
				'title' => L10N::t('Mehrzeilige Text', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_YESNO,
				'title' => L10N::t('Ja/Nein', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS,
				'title' => L10N::t('Navigation: Schritte', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS,
				'title' => L10N::t('Navigation: Buttons', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_HORIZONTAL_RULE,
				'title' => L10N::t('Trennlinie', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_COURSES,
				'title' => L10N::t('Kurse', self::$sDescription),
				'right' => 'thebing_tuition_icon'
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS,
				'title' => L10N::t('Unterkunft', self::$sDescription),
				'right' => 'thebing_accommodation_icon'
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS,
				'title' => L10N::t('Transfer', self::$sDescription),
				'right' => 'thebing_pickup_icon'
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_INSURANCES,
				'title' => L10N::t('Versicherung', self::$sDescription),
				'right' => 'thebing_insurance_icon'
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_PRICES,
				'title' => L10N::t('Preise', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_FEES,
				'title' => L10N::t('Zusätzliche Gebühren', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_PAYMENT,
				'title' => L10N::t('Zahlung', self::$sDescription)
			],
			[
				'key' => Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY,
				'title' => L10N::t('Aktivitäten', self::$sDescription)
			]
		];

		foreach($aBlocks as $iKey => $aValue) {
			if(isset($aValue['right']) && !Ext_Thebing_Access::hasLicenceRight($aValue['right'])) {
				unset($aBlocks[$iKey]);
			}
		}

		usort($aBlocks, array('Ext_Thebing_Form_Gui2', 'sortHelper'));
		$aBlocks = array_values($aBlocks);
		return $aBlocks;

	}

	/**
	 * Get the extra blocks
	 * 
	 * @param int $iBlockID
	 * @return array
	 */
	public static function getExtraBlocks($iBlockID)
    {
		$aExtraBlocks = self::_getExtraBlocks($iBlockID);

		return $aExtraBlocks;
	}

	/**
	 * Sort the blocks alphabetical (help method)
	 * 
	 * @param array $aX
	 * @param array $aY
	 * @return int
	 */
	public static function sortHelper($aX, $aY)
    {
		return strcmp($aX['title'], $aY['title']);
	}

	/**
	 * Get the blocks by parent data
	 * 
	 * @param int $iPageID
	 * @param int $iParentID
	 * @return array
	 */
	public static function getParentBlocks($iPageID, $iParentID)
    {

		$sSQL = "
			SELECT
				`kfpb`.`page_id`,
				`kfpb`.`block_id` `block_key`,
				`kfpb`.`id` `block_id`,
				`kfpb`.`parent_id`,
				`kfpb`.`parent_area`,
				`kfpb`.`position`,
				`kfp`.`position` `page_position`
			FROM
				`kolumbus_forms_pages_blocks` `kfpb`
			INNER JOIN
				`kolumbus_forms_pages` `kfp`
			ON
				`kfpb`.`page_id` = `kfp`.`id`
			WHERE
				`kfpb`.`active` = 1 AND
				`kfpb`.`page_id` = :iPageID AND
				`kfpb`.`parent_id` = :iParentID
			ORDER BY
				`kfpb`.`parent_area`, `kfpb`.`position`
		";
		$aSQL = array(
			'iPageID' => (int)$iPageID,
			'iParentID' => (int)$iParentID
		);
		$aBlocks = DB::getQueryRows($sSQL, $aSQL);

		return $aBlocks;

	}

	/**
	 * Get the templates path
	 * 
	 * @return string
	 */
	public static function getTemplatePath()
    {
		return Util::getDocumentRoot().'/system/legacy/admin/extensions/thebing/admin/smarty/';
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($sIconAction == 'new' || $sIconAction == 'edit') {
			if(isset($aData['tabs'][3])) {
				$aData['tabs'][3]['html'] = $this->_writeFormTabHTML($aData, $aSelectedIds);
				$aData['tab_form_designer'] = 3;
			}

			// Tab über JavaScript ausblenden, da überall mal wieder nummerische Tab-Indizes verwendet werden
			$aData['hide_prices'] = false;
			if(
				$this->oWDBasic->type === Ext_Thebing_Form::TYPE_ENQUIRY &&
				!Ext_Thebing_Access::hasLicenceRight('thebing_students_contact_gui')
			) {
				$aData['hide_prices'] = true;
			}
		}

		return $aData;

	}

	/* ==================================================================================================== */

	/**
	 * Plausibility check for elements: Course -> Accommodation -> Transfer
	 * 
	 * @return mixed
	 */
	protected function _checkPlausibility($sAction, $mParams)
    {

		switch($sAction) {

			case 'remove_page': /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Remove page

/*
				$sSQL = "
					SELECT
						`kfpb`.`block_id`,
						`kfpb`.`page_id`
					FROM
						`kolumbus_forms_pages` `kfp_1`
					INNER JOIN
						`kolumbus_forms_pages` `kfp_2`
					ON
						`kfp_1`.`form_id` = `kfp_2`.`form_id`
					INNER JOIN
						`kolumbus_forms_pages_blocks` `kfpb`
					ON
						`kfp_2`.`id` = `kfpb`.`page_id`
					WHERE
						`kfp_1`.`id` = :iPageID AND
						`kfp_2`.`active` = 1 AND
						`kfpb`.`active` = 1 AND
						`kfpb`.`block_id` IN(1,2,3)
					ORDER BY
						`kfpb`.`position`
				";
				$aSQL = array('iPageID' => $mParams->id);
				$aPageBlocks = (array)DB::getQueryPairs($sSQL, $aSQL);

				if(in_array($mParams->id, $aPageBlocks)) {
					if(isset($aPageBlocks[1])) { // Courses
						if(isset($aPageBlocks[2])) { // Accommodations

							if(isset($aPageBlocks[3])) { // Transfers
								if(
									( // All blocks are on this page
										$aPageBlocks[1] == $mParams->id &&
										$aPageBlocks[2] == $mParams->id &&
										$aPageBlocks[3] == $mParams->id
									) || ( // Only accommodations and transfers are on this page
										$aPageBlocks[2] == $mParams->id &&
										$aPageBlocks[3] == $mParams->id
									) || ( // Only transfers are on this page
										$aPageBlocks[3] == $mParams->id
									)
								) {
									return true;
								}
								return false;
							}

							if(
								( // All blocks are on this page
									$aPageBlocks[1] == $mParams->id &&
									$aPageBlocks[2] == $mParams->id
								) || ( // Only accommodations are on this page
									$aPageBlocks[2] == $mParams->id
								)
							) {
								return true;
							}
							return false;
						}

					}
				}
*/

				return true;

			case 'sort_pages': /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Sort pages

				$sSQL = "
					SELECT
						`kfpb`.`block_id`,
						`kfpb`.`page_id`
					FROM
						`kolumbus_forms_pages_blocks` `kfpb`
					INNER JOIN
						`kolumbus_forms_pages` `kfp`
					ON
						`kfpb`.`page_id` = `kfp`.`id`
					WHERE
						`kfpb`.`page_id` IN(:aPages) AND
						`kfpb`.`block_id` IN(1,2,3) AND
						`kfpb`.`active` = 1
					ORDER BY
						`kfp`.`position` 
				";
				$aSQL = array('aPages' => $mParams);
				$aPageBlocks = DB::getQueryPairs($sSQL, $aSQL);

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

				if(empty($aPageBlocks)) {
					return true; // Performance
				}

				$aTemp = array();

				foreach((array)$mParams as $iPageID) {
					if(in_array($iPageID, $aPageBlocks)) {
						while(array_search($iPageID, $aPageBlocks)) {
							$aTemp[$iPageID][] = array_search($iPageID, $aPageBlocks);
							unset($aPageBlocks[array_search($iPageID, $aPageBlocks)]);
						}
					}
				}

				$iLast = false;

				foreach((array)$aTemp as $iPageID => $aBlocks) {
					if($iLast === false) {
						$iLast = max($aBlocks);
						continue;
					}
					if($iLast > max($aBlocks)) {
						return false;
					}
				}

				return true;

			case 'edit_block': /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Edit columns block

				if($mParams->block_id == Ext_Thebing_Form_Page_Block::TYPE_COLUMNS) {

					if($mParams->id <= 0) {
						return true;
					}

					$sSQL = "
						SELECT
							MAX(`parent_area`)
						FROM
							`kolumbus_forms_pages_blocks`
						WHERE
							`parent_id` = :iBlockID AND
							`active` = 1
					";
					$aSQL = array('iBlockID' => $mParams->id);
					$iMaxArea = DB::getQueryOne($sSQL, $aSQL);

					if(!isset($mParams->set_numbers[$iMaxArea])) {
						return -1;
					}

					return true;

				}

				$aPagesBlocks = $this->_getContents($mParams->page_id, 0, array(), 0);
				$iPos = $mParams->position;
				$aBlock = array(
					'parent_id' => $mParams->parent_id,
					'parent_area' => $mParams->parent_area,
					'block_key' => $mParams->block_id,
				);
				$this->_pasteBlock($aPagesBlocks, $aBlock, $mParams->parent_id, 0);
				$aPagesBlocks = $this->_resetLevels($aPagesBlocks);
				$iLast = false;

				foreach((array)$aPagesBlocks as $iKey => $aBlock) {
					if(
						$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
						$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
						$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS
					) {
						if($iLast === false) {
							$iLast = $aBlock['block_key'];
						}
						if($iLast > $aBlock['block_key']) {
							return false;
						}
						$iLast = $aBlock['block_key'];
					}
				}

				return true;

			case 'remove_block': /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Remove columns block

				$aBlocks = $this->_getExtraBlocks($mParams->id);

				if(empty($aBlocks)) {
					return true; // Performance
				}

/*
				$aPagesBlocks = array();

				foreach((array)$aBlocks as $iKey => $aBlock) {
					if(!isset($aPagesBlocks[$aBlock['page_id']])) {
						$aPagesBlocks[$aBlock['page_id']] = $this->_getContents($aBlock['page_id'], 0, array(), 0);
					}
				}

				foreach((array)$aPagesBlocks as $iKey => $aContent) {
					$this->_cutBlock($aPagesBlocks[$iKey], $mParams->id);
					$aPagesBlocks[$iKey] = $this->_resetLevels($aPagesBlocks[$iKey]);
				}

				$iLast = false;

				foreach((array)$aPagesBlocks as $iPage => $aBlocks) {
					foreach((array)$aBlocks as $iKey => $aBlock) {
						if(
							$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
							$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
							$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS
						) {
							if($iLast === false) {
								if($aBlock['block_key'] != 1) {
									return false;
								}
								$iLast = 1;
							} elseif($iLast == 1) {
								if($aBlock['block_key'] != 2) {
									return false;
								}
								$iLast = 2;
							}
						}
					}
				}
*/

				return true;

			case 'sort_blocks': /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Sort blocks

				$oForm = Ext_Thebing_Form::getInstance($mParams['form_id']);
				if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
					return true;
				}

				$aBlocks = $this->_getExtraBlocks($mParams['sort'][0]);

				if(empty($aBlocks)) {
					return true; // Performance
				}

				$aPagesBlocks = array();

				foreach((array)$aBlocks as $iKey => $aBlock) {
					if(!isset($aPagesBlocks[$aBlock['page_id']])) {
						$aPagesBlocks[$aBlock['page_id']] = $this->_getContents($aBlock['page_id'], 0, array(), 0);
					}
				}

				$iPos = array_search($mParams['element_id'], $mParams['sort']) + 1;

				foreach(array_keys((array)$aPagesBlocks) as $iKey) {
					$aCutedBlock = $this->_cutBlock($aPagesBlocks[$iKey], $mParams['element_id']);
					$aCutedBlock['parent_area'] = $mParams['parent_area'];
					$this->_pasteBlock($aPagesBlocks[$iKey], $aCutedBlock, $mParams['parent_id'], $iPos);
					$aPagesBlocks[$iKey] = $this->_resetLevels($aPagesBlocks[$iKey]);
				}

				$iLast = false;

				foreach((array)$aPagesBlocks as $aBlocks) {
					foreach((array)$aBlocks as $iKey => $aBlock) {

						if(
							$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
							$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
							$aBlock['block_key'] == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS
						) {
							if($iLast === false) {
								$iLast = $aBlock['block_key'];
							}
							if($iLast > $aBlock['block_key']) {
								return false;
							}
							$iLast = $aBlock['block_key'];
						}

					}
				}

				return true;

		}

		return false;

	}

	/**
	 * Cut a block from recursive content array
	 * 
	 * @param array &$aContent
	 * @param int $iBlockID
	 * @return array
	 */
	protected function _cutBlock(&$aContent, $iBlockID)
    {

		$aReturn = array();

		foreach((array)$aContent as $iAreaKey => $aArea) {

			foreach((array)$aArea as $iKey => $aBlock) {

				$aTemp = $this->_cutBlock($aContent[$iAreaKey][$iKey]['content'], $iBlockID);

				if(!empty($aTemp)) {
					return $aTemp;
				}

				if(isset($aBlock['block_id']) && $aBlock['block_id'] == $iBlockID) {
					$aCutedBlock = $aBlock;
					unset($aContent[$iAreaKey][$iKey]);
					return $aCutedBlock;
				}

			}

		}

		return $aReturn;

	}

	/**
	 * Paste a block into a recursive content array
	 * 
	 * @param array &$aContent
	 * @param array $aCutedBlock
	 * @param int $iParentID
	 * @param int $iPos
	 */
	protected function _pasteBlock(&$aContent, $aCutedBlock, $iParentID, $iPos)
    {

		foreach((array)$aContent as $iAreaKey => $aArea) {

			foreach((array)$aArea as $iKey => $aBlock) {

				if(
					$aBlock['block_id'] == $iParentID ||
					$iParentID == 0
				) {

					$iArea = $aCutedBlock['parent_area'];
					$bPasted = false;

					if($iParentID == 0) {

						if(count($aContent[$iAreaKey]) > 0) {

							$aTemp = array();

							$aContent[$iAreaKey] = array_values($aContent[$iAreaKey]);

							for($i = 0; $i < count($aContent[$iAreaKey]); $i++) {
								if(($i + 1) == $iPos) {
									$bPasted = true;
									$aTemp[] = $aCutedBlock;
								}
								$aTemp[] = $aContent[$iAreaKey][$i];
							}

							if(!$bPasted) {
								$aTemp[] = $aCutedBlock;
							}

						} else {
							$aTemp[] = $aCutedBlock;
						}

						$aContent[$iAreaKey] = $aTemp;

					} else {

						if(
							is_array($aContent[$iAreaKey][$iKey]['content'][$iArea]) &&
							count($aContent[$iAreaKey][$iKey]['content'][$iArea]) > 0
						) {

							$aTemp = array();
							$aContent[$iAreaKey][$iKey]['content'][$iArea] = array_values($aContent[$iAreaKey][$iKey]['content'][$iArea]);

							if($iPos == 0) {
								$iPos = count($aContent[$iAreaKey][$iKey]['content'][$iArea]) + 1;
							}

							for($i = 0; $i < count($aContent[$iAreaKey][$iKey]['content'][$iArea]); $i++) {
								if(($i + 1) == $iPos) {
									$bPasted = true;
									$aTemp[] = $aCutedBlock;
								}
								$aTemp[] = $aContent[$iAreaKey][$iKey]['content'][$iArea][$i];
							}

							if(!$bPasted) {
								$aTemp[] = $aCutedBlock;
							}

						} else {
							$aTemp[] = $aCutedBlock;
						}

						$aContent[$iAreaKey][$iKey]['content'][$iArea] = $aTemp;
						ksort($aContent[$iAreaKey][$iKey]['content']);

					}

				} else {

					$this->_pasteBlock($aContent[$iAreaKey][$iKey]['content'], $aCutedBlock, $iParentID, $iPos);

				}

			}

		}

	}

	/**
	 * Reset the recursive content to an simpe array
	 * 
	 * @param array $aContent
	 * @return array
	 */
	protected function _resetLevels($aContent) {

		$aReturn = array();

		foreach((array)$aContent as $iAreaKey => $aArea) {

			foreach((array)$aArea as $iKey => $aBlock) {

				unset($aBlock['content']);

				if($aBlock['block_id'] > 0) {
					$aReturn[] = $aBlock;
				}

				$aTemp = $this->_resetLevels($aContent[$iAreaKey][$iKey]['content']);

				if(!empty($aTemp)) {
					$aReturn = array_merge($aReturn,$aTemp);
				}

			}

		}

		return $aReturn;

	}

	/**
	 * Get extra blocks
	 * 
	 * @param int $iBlockID
	 * @return array
	 */
	protected function _getExtraBlocks($iBlockID)
    {

		$sSQL = "
			SELECT
				`kfpb_2`.`block_id`,
				`kfpb_2`.`page_id`,
				`kfpb_2`.`id`,
				`kfpb_2`.`parent_id`,
				`kfpb_2`.`parent_area`,
				`kfpb_2`.`position`,
				`kfp_2`.`position` `page_position`
			FROM
				`kolumbus_forms_pages_blocks` AS `kfpb_1`
			INNER JOIN
				`kolumbus_forms_pages` `kfp_1`
			ON
				`kfpb_1`.`page_id` = `kfp_1`.`id`
			INNER JOIN
				`kolumbus_forms_pages` `kfp_2`
			ON
				`kfp_1`.`form_id` = `kfp_2`.`form_id`
			INNER JOIN
				`kolumbus_forms_pages_blocks` `kfpb_2`
			ON
				`kfp_2`.`id` = `kfpb_2`.`page_id`
			WHERE
				`kfpb_1`.`id` = :iBlockID AND
				`kfp_1`.`active` = 1 AND
				`kfp_2`.`active` = 1 AND
				`kfpb_2`.`active` = 1 AND
				`kfpb_2`.`block_id` IN(1,2,3)
			ORDER BY
				`kfp_2`.`position`, `kfpb_2`.`id`
		";
		$aSQL = array('iBlockID' => $iBlockID);
		$aBlocks = DB::getQueryRows($sSQL, $aSQL);
		return $aBlocks;

	}

	/**
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 * @throws Exception
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false)
    {

		global $_VARS;
		$aData = array();
		$aSelectedIds = (array)$aSelectedIds;

		if(count($aSelectedIds) > 1) {
			return $aData;
		} else {
			$iFormID = (int) reset($aSelectedIds);
		}

		$oForm = Ext_Thebing_Form::getInstance($iFormID);
		$aLanguages = Ext_Thebing_Data::getSystemLanguages();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		switch($sIconAction) {

			case 'edit_page':

				$oPage = Ext_Thebing_Form_Page::getInstance((int)$_VARS['page_id']);
				$oDialogData = $this->_oGui->createDialog('','');

				if($_VARS['page_id'] > 0) {
					$sTemp = L10N::t('Seite "%s" bearbeiten', self::$sDescription);
					$sTitle = sprintf($sTemp, $oPage->translations[$oForm->default_language]);
				} else {
					$sTitle = L10N::t('Neue Seite', self::$sDescription);
				}

				$sLabelDummy = $this->_oGui->t('Name: %s');
				$sHTML = '';

				$oDiv = $oDialogData->createSaveField(
					'input',
					array(
						'type' => 'hidden',
						'db_column' => 'page_id',
						'value' => $oPage->id
					)
				);
				$sHTML .= $oDiv->generateHtml();

				foreach((array)$oForm->languages as $sLanguage) {
					
					$sKey = 'title_'.$sLanguage;
					
					$sLanguageValue = $oPage->$sKey;
					
					// Fallback für Umstellung auf lokalisierte Sprachen
					if(
						empty($sLanguageValue) &&
						strpos($sLanguage, '_') !== false
					) {
						[$sFallbackLanguage, $sDummy] = explode('_', $sLanguage);
						$sLanguageValue = $oPage->{'title_'.$sFallbackLanguage};
					}
					
					$sLabel	= sprintf($sLabelDummy, $aLanguages[$sLanguage]);
					$oDiv = $oDialogData->createRow(
						$sLabel,
						'input',
						array(
							'db_column' => $sKey,
							'value' => $sLanguageValue
						)
					);
					$sHTML .= $oDiv->generateHtml();
				}

				if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
					$sHTML .= $oDialogData->createRow($this->t('Pfad'), 'select', [
						'db_column' => 'type',
						'select_options' => [
							'booking' => $this->t('Buchung'),
							'enquiry' => $this->t('Anfrage')
						],
						'required' => true,
						'default_value' => $oPage->type
					])->generateHTML();
				}

				$aData['tabs'][0]['title'] = L10N::t('Übersetzungen', self::$sDescription);
				$aData['tabs'][0]['html'] = $sHTML;
				$aData['id'] = 'page_'.$oPage->id;
				$aData['width'] = 700;
				$aData['height'] = 400;
				$aData['title'] = $sTitle;

				break;

			case 'edit_block':

				$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$_VARS['block_id']);
				$aConfig = (new \Core\Helper\Bundle())->readBundleFile('TsRegistrationForm', 'registration');

				if(strpos($_VARS['parent_id'], 'form_pages_content_block_') !== false) {
					$sTemp	= str_replace('form_pages_content_block_', '', $_VARS['parent_id']);
					$aTemp	= explode('_', $sTemp);
					$oParentBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$aTemp[0]);
					$oBlock->page_id = $oParentBlock->page_id;
					$oBlock->parent_id = $oParentBlock->id;
					$oBlock->parent_area = $aTemp[1];
					$oBlock->block_id = $_VARS['block_key'];
				} elseif(strpos($_VARS['parent_id'], 'form_pages_content_') !== false) {
					$sTemp	= str_replace('form_pages_content_', '', $_VARS['parent_id']);
					$aTemp	= explode('_', $sTemp);
					$oBlock->page_id = $aTemp[0];
					$oBlock->parent_id = 0;
					$oBlock->parent_area = 0;
					$oBlock->block_id = $_VARS['block_key'];
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Main block data

				$oDialogData = $this->_oGui->createDialog('','');
				$sHTML = '';

				$oDiv = $oDialogData->createSaveField(
					'input',
					array(
						'type' => 'hidden',
						'db_column' => 'page_id',
						'value' => $oBlock->page_id
					)
				);
				$sHTML .= $oDiv->generateHtml();

				$oDiv = $oDialogData->createSaveField(
					'input',
					array(
						'type' => 'hidden',
						'db_column' => 'parent_id',
						'value' => $oBlock->parent_id
					)
				);
				$sHTML .= $oDiv->generateHtml();

				$oDiv = $oDialogData->createSaveField(
					'input',
					array(
						'type' => 'hidden',
						'db_column' => 'parent_area',
						'value' => $oBlock->parent_area
					)
				);
				$sHTML .= $oDiv->generateHtml();

				$oDiv = $oDialogData->createSaveField(
					'input',
					array(
						'type' => 'hidden',
						'db_column' => 'id',
						'value' => $oBlock->id
					)
				);
				$sHTML .= $oDiv->generateHtml();

				$oDiv = $oDialogData->createSaveField(
					'input',
					array(
						'type' => 'hidden',
						'db_column' => 'block_id',
						'value' => $oBlock->block_id
					)
				);
				$sHTML .= $oDiv->generateHtml();

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Individual block data

				switch($oBlock->block_id) {

					case Ext_Thebing_Form_Page_Block::TYPE_COLUMNS:

						if((int)$oBlock->id > 0) {
							$sTitle = L10N::t('Mehrspaltigen Bereich bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Neuer mehrspaltige Bereich', self::$sDescription);
						}

						if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$sHTML .= $oDialogData->createNotification(
								$this->t('Info'),
								$this->t('Bitte eine Spaltenanzahl zwischen 1-12 verwenden, wie hier beschrieben:').' <a href="https://getbootstrap.com/docs/4.0/layout/grid/" target="_blank">https://getbootstrap.com/docs/4.0/layout/grid/</a>',
								'info'
							)->generateHTML();
						}

						$oDiv = $oDialogData->createRow(
							L10N::t('Anzahl der Spalten', self::$sDescription),
							'input',
							array(
								'db_column' => 'set_number_of_cols',
								'value' => $oBlock->set_number_of_cols
							)
						);
						$sHTML .= $oDiv->generateHtml();
						$sHTML .= '<div id="block_cols_container_'.$oBlock->id.'">';

						// Achtung: HTML im JS nochmal komplett redundant
						foreach((array)$oBlock->set_numbers as $iKey => $iValue) {
							$sLabel = ($iKey + 1).': '.($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 ? ' col-md-' : ' %');
							$sCode = '<div class="GUIDialogRow form-group form-group-sm col_width_input">';
								$sCode .= '<div class="GUIDialogRowLabelDiv">';
									$sCode .= '<div class="GUIDialogRowLabelDiv control-label col-sm-3">'.$sLabel.'</div>';
								$sCode .= '</div>';
								$sCode .= '<div class="GUIDialogRowInputDiv col-sm-9">';
									$sCode .= '<input type="text" name="save[set_numbers][]" class="txt form-control input-sm" value="'.(int)$iValue.'" />';
								$sCode .= '</div>';
								$sCode .= '<div class="divCleaner"></div>';
							$sCode .= '</div>';
							$sHTML .= $sCode;
						}

						$sHTML .= '</div>';

						$aData['tabs'][0]['title'] = L10N::t('Einstellungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_HEADLINE2:
					case Ext_Thebing_Form_Page_Block::TYPE_HEADLINE3:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$aEventValues = [];

						if($oBlock->id > 0) {
							$sTemp = L10N::t('Überschrift H%d bearbeiten', self::$sDescription);
							$sTitle = sprintf($sTemp, -1*$oBlock->block_id);
						} else {
							$sTemp = L10N::t('Neue Überschrift H%d', self::$sDescription);
							$sTitle = sprintf($sTemp, -1*$oBlock->block_id);
						}

						$sLabelDummy = $this->_oGui->t('Überschrift: %s');

						foreach((array)$oForm->languages as $sLanguage) {
							$sKey = 'title_'.$sLanguage;
							$sLabel	= sprintf($sLabelDummy, $aLanguages[$sLanguage]);
							$oDiv = $oDialogData->createRow(
								(string)$sLabel,
								'input',
								array(
									'db_column' => $sKey,
									'value' => $oBlock->$sKey
								)
							);
							$sHTML .= $oDiv->generateHtml();
						}

						$aData['tabs'][0]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_STATIC_TEXT:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$aEventValues = [];

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Textbereich bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Neuer Textbereich', self::$sDescription);
						}

						$sLabelDummy = $this->_oGui->t('Text: %s');

						foreach((array)$oForm->languages as $sLanguage) {
							$sKey = 'text_'.$sLanguage;
							$sLabel	= sprintf($sLabelDummy, $aLanguages[$sLanguage]);
							$oDiv = $oDialogData->createRow(
								(string)$sLabel,
								'html',
								array(
									'db_column' => $sKey,
									'value' => $oBlock->$sKey
								)
							);
							$sHTML .= $oDiv->generateHtml();
						}

						$aData['tabs'][0]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 800;
						$aData['height'] = 500;
						$aData['title'] = $sTitle;
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Download bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Neuer Download', self::$sDescription);
						}

						$aOptions = array('db_column' => 'required');

						if($oBlock->required) {
							$aOptions['checked'] = 'checked';
						}

//						$oDiv = $oDialogData->createRow(
//							L10N::t('Pflichtfeld', self::$sDescription),
//							'checkbox',
//							$aOptions
//						);
//						$sHTML .= $oDiv->generateHtml();

						$sLabelDummyFile = $this->_oGui->t('Datei: %s');
						$sLabelDummyTitle = $this->_oGui->t('Titel: %s');
						$sLabelDummyError = $this->_oGui->t('Fehlermeldung: %s');

						$sLabelDummyInfoText = $this->_oGui->t('Infotext: %s');

						foreach((array)$oForm->languages as $sLanguage) {

							$aFiles = [0 => ''];
							foreach((array)$oForm->schools as $iSchoolID) {
								$oSchool = Ext_Thebing_School::getInstance($iSchoolID);
								$aTempFiles = $oSchool->getSchoolFiles(5, $sLanguage, true);
								foreach((array)$aTempFiles as $aFile) {
									$aFiles[$aFile['id']] = $aFile['description'];
								}
							}

							$sKey = 'set_file_'.$sLanguage;
							$sLabel	= sprintf($sLabelDummyFile, $aLanguages[$sLanguage]);

							$oDiv = $oDialogData->createRow(
								$sLabel,
								'select',
								array(
									'db_column' => $sKey,
									'default_value' => $oBlock->$sKey,
									'select_options' => $aFiles,
								)
							);
							$sHTML .= $oDiv->generateHtml();

							$sKey = 'title_'.$sLanguage;
							$sLabel	= sprintf($sLabelDummyTitle, $aLanguages[$sLanguage]);

							$oDiv = $oDialogData->createRow(
								$sLabel,
								'input',
								array(
									'db_column' => $sKey,
									'value' => $oBlock->$sKey
								)
							);

							$sHTML .= $oDiv->generateHtml();

							// Keine Ahnung, wer das alles mal eingebaut hat, aber funktionieren tut nichts davon
//							$sKey = 'error_'.$sLanguage;
//							$sLabel	= sprintf($sLabelDummyError, $aLanguages[$sLanguage]);
//
//							$oDiv = $oDialogData->createRow(
//								$sLabel,
//								'input',
//								array(
//									'db_column' => $sKey,
//									'value' => $oBlock->$sKey
//								)
//							);
//							$sHTML .= $oDiv->generateHtml();
//
//							$sKey = 'infotext_'.$sLanguage;
//							$sLabel = sprintf($sLabelDummyInfoText, $aLanguages[$sLanguage]);
//
//							$oDiv = $oDialogData->createRow(
//								$sLabel,
//								'textarea',
//								array(
//									'db_column' => $sKey,
//									'value' => $oBlock->$sKey
//								)
//							);
//							$sHTML .= $oDiv->generateHTML();

						}

						$aData['tabs'][0]['title'] = L10N::t('Einstellungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 800;
						$aData['height'] = 500;
						$aData['title'] = $sTitle;

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
					case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
					case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
					case Ext_Thebing_Form_Page_Block::TYPE_DATE:
					case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
					case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
					case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
					case Ext_Thebing_Form_Page_Block::TYPE_YESNO:

						$aEventValues = [];
						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);

						$sTitle = '';
						if($oBlock->id > 0) {
							switch($oBlock->block_id) {
								case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
									$sTitle = L10N::t('Eingabefeld "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
									$sTitle = L10N::t('Auswahlfeld "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_DATE:
									$sTitle = L10N::t('Datumsfeld "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
									$sTitle = L10N::t('Checkbox "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
									$sTitle = L10N::t('Upload "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
									$sTitle = L10N::t('Mehrzeiligen Text "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
									$sTitle = L10N::t('Mehrfachauswahl "%s" bearbeiten', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_YESNO:
									$sTitle = L10N::t('Entscheidungsfeld "%s" bearbeiten', self::$sDescription);
									break;
							}
						} else {
							switch($oBlock->block_id) {
								case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
									$sTitle = L10N::t('Neues Eingabefeld', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
									$sTitle = L10N::t('Neues Auswahlfeld', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_DATE:
									$sTitle = L10N::t('Neues Datumsfeld', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
									$sTitle = L10N::t('Neue Checkbox', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
									$sTitle = L10N::t('Neuer Upload', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
									$sTitle = L10N::t('Neuer mehrzeilige Text', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
									$sTitle = L10N::t('Neue Mehrfachauswahl', self::$sDescription);
									break;
								case Ext_Thebing_Form_Page_Block::TYPE_YESNO:
									$sTitle = L10N::t('Neues Entscheidungsfeld', self::$sDescription);
									break;
							}
						}

						if (
							!$oForm->isCreatingBooking() &&
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_UPLOAD
						) {
							$sHTML .= $oDialogData->createNotification(
								$this->t('Achtung'),
								$this->t('Bereits vorhandene Uploads werden nicht erneut im Formular angezeigt.'),
								'hint'
							)->generateHTML();
						}

						$aFields = new Collection(); /** @var Illuminate\Support\Collection[] $aFields */
						foreach((array)$oForm->languages as $sLanguage) {
							$oLanguage = new \Tc\Service\Language\Frontend($sLanguage);
							$oLanguage->setContext(\TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT);
							$aFields[$sLanguage] = $this->getDefaultFields($oForm, $oLanguage, $oPage)->filter(function(array $aField) use ($oBlock) {
								// Typ vom Feld
								return (int)$oBlock->block_id === $aField['type'];
							});
						}

						// Rechte-Prüfung, select_options
						$aFieldOptions = $aFields[$oForm->default_language];
						$aFieldOptions = $aFieldOptions->filter(function (array $aField) use($oForm) {
							if(
								!empty($aField['schools']) &&
								!empty(array_diff($oForm->schools, $aField['schools']))
							) {
								return false;
							}
							if(!empty($aField['right'])) {
								return Ext_Thebing_Access::hasLicenceRight($aField['right']);
							}
							return true;
						})->mapWithKeys(function(array $aField) {
							return [$aField['key'] => $aField['backend_label']];
						})->toArray();

						$aFieldOptions = Ext_Thebing_Util::addEmptyItem($aFieldOptions);
						asort($aFieldOptions);
						$aUsedFields = $this->_getUsedFields($oBlock->block_id, $oForm);

						$oDiv = $oDialogData->createRow(
							L10N::t('Feld', self::$sDescription).' *',
							'select',
							[
								'db_column' => 'set_type',
								'default_value' => $oBlock->set_type,
								'select_options' => $aFieldOptions,
							]
						);

						/** @var Ext_Gui2_Html_Select $oSelect */
						$oSelect = $oDiv->filterElements(function($oElement) {
							return $oElement instanceof Ext_Gui2_Html_Select;
						})->current();

						$oSelect->setDataAttribute('used', $aUsedFields);
						$oSelect->setDataAttribute('default-titles', $aFields->map(function(Collection $aLanguageField) {
							return $aLanguageField->mapWithKeys(function(array $aField) {
								return [$aField['key'] => $aField['frontend_label']];
							});
						}));

						$sHTML .= $oDiv->generateHtml();

						$aOptions = array('db_column' => 'required');

						if($oBlock->required) {
							$aOptions['checked'] = 'checked';
						}

						$oDiv = $oDialogData->createRow(
							L10N::t('Pflichtfeld', self::$sDescription),
							'checkbox',
							$aOptions
						);
						$sHTML .= $oDiv->generateHtml();

						if(
							// Hier muss man sich etwas überlegen, denn Barrierefreiheit benötigt Labels
							$oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 && (
								$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT ||
								$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT ||
								$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA ||
								$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE
							)
						) {
							$aOptions = array('db_column' => 'set_label_as_placeholder');
							if($oBlock->set_label_as_placeholder) {
								$aOptions['checked'] = 'checked';
							}
							$oDiv = $oDialogData->createRow(
								L10N::t('Label im Feld anzeigen', self::$sDescription),
								'checkbox',
								$aOptions
							);
							$sHTML .= $oDiv->generateHtml();
						}

						if(
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 && (
								$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT ||
								$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_YESNO
							)
						) {
							$aOptions = ['db_column' => 'set_select_as_radio'];
							if($oBlock->set_select_as_radio) {
								$aOptions['checked'] = 'checked';
							}
							$oDiv = $oDialogData->createRow(
								L10N::t('Als Radiobuttons anzeigen', self::$sDescription),
								'checkbox',
								$aOptions
							);
							$sHTML .= $oDiv->generateHtml();
						}

						if($oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$oDiv = $oDialogData->createRow(
								L10N::t('CSS-Klasse', self::$sDescription),
								'input',
								array(
									'db_column' => 'css_class',
									'value' => $oBlock->css_class
								)
							);
							$sHTML .= $oDiv->generateHTML();
						}

						$sHTML .= $oDialogData->createNotification(
							$this->t('Info'),
							$this->t('Die Felder müssen nicht befüllt werden. Bei nicht befüllten Feldern werden die hinterlegten Frontend-Übersetzungen verwendet.'),
							'info'
						)->generateHTML();

						$sLabelDummyTitle = $this->_oGui->t('Titel');
						$sLabelDummyError = $this->_oGui->t('Fehlermeldung');
						$sLabelDummyInfoText = $this->_oGui->t('Infotext');

						foreach((array)$oForm->languages as $sLanguage) {

							$oH3 = $oDialogData->create('h4');
							$oH3->setElement((string)$aLanguages[$sLanguage]);
							$sHTML .= $oH3->generateHTML();

							$sKey = 'title_'.$sLanguage;
							$oDiv = $oDialogData->createRow(
								$sLabelDummyTitle,
								'input',
								[
									'db_column' => $sKey,
									'value' => $oBlock->$sKey,
									'class' => 'input_title'
								]
							);

							/** @var Ext_Gui2_Html_Input $oSelect */
							$oInput = $oDiv->filterElements(function($oElement) {
								return $oElement instanceof Ext_Gui2_Html_Input;
							})->current();
							$oInput->setDataAttribute('language', $sLanguage);

							$sHTML .= $oDiv->generateHtml();

							$sKey = 'error_'.$sLanguage;
							$oDiv = $oDialogData->createRow(
								$sLabelDummyError,
								'input',
								[
									'db_column' => $sKey,
									'value' => $oBlock->$sKey,
									'placeholder' => $oForm->getTranslation('errorrequired', $sLanguage)
								]
							);
							$sHTML .= $oDiv->generateHtml();

							if($oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
								$sKey = 'infotext_'.$sLanguage;
								$oDiv = $oDialogData->createRow(
									$sLabelDummyInfoText,
									'textarea',
									array(
										'db_column' => $sKey,
										'value' => $oBlock->$sKey
									)
								);
								$sHTML .= $oDiv->generateHTML();
							}

						}

						$aData['tabs'][0]['title'] = L10N::t('Einstellungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = sprintf($sTitle, e($oBlock->getTitle()));
						$aData['used_fields'] = $aUsedFields;
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_COURSES:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$aEventValues = [];

						$bFirstBlock = false;
						if ($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$aBlocks = $oForm->getFilteredBlocks($oForm->createFilteredBlocksCallbackType(Ext_Thebing_Form_Page_Block::TYPE_COURSES));
							$bFirstBlock = $oBlock->id == reset($aBlocks)->id;
						}

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Kurse bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Kurse anlegen', self::$sDescription);
						}

						// Kein neuer Wert (Setting) darf mit course beginnen!
						if(
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 ||
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_NEW ||
							$oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY
						) {
							$aOptions = array('db_column' => 'required');
							if($oBlock->required) {
								$aOptions['checked'] = 'checked';
							}

							if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
								$aHiddenOptions = $aOptions;
								$aHiddenOptions['value'] = 1;
								$sHTML .= $oDialogData->createSaveField('hidden', $aHiddenOptions, false)->generateHTML();
								$aOptions['checked'] = 'checked';
								$aOptions['disabled'] = true;
								$aOptions['create_hidden'] = false;
							}

							$oDiv = $oDialogData->createRow(
								L10N::t('Pflichtfeld', self::$sDescription),
								'checkbox',
								$aOptions
							);
							$sHTML .= $oDiv->generateHtml();

							$sHTML .= $oDialogData->createRow($this->t('Basierend auf'), 'select', [
								'db_column' => 'set_based_on',
								'select_options' => [
									'availability' => $this->t('Kurse'),
									...($oPage->type !== 'enquiry' ? ['scheduling' => $this->t('Planung')] : [])
								],
								'default_value' => $oBlock->getSetting('based_on'),
								'required' => true
							])->generateHTML();

							if (
								$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
								$oBlock->getSetting('based_on') !== 'scheduling'
							) {

								$sHTML .= $oDialogData->createRow(
									$this->t('Kurslevel anzeigen'),
									'select',
									[
										'db_column' => 'set_show_level',
										'select_options' => [
											'' => $this->t('nicht anzeigen'),
											'all' => $this->t('für alle Kurse'),
											'individual' => $this->t('pro Kurs einstellen')
										],
										'default_value' => $oBlock->getSetting('show_level'),
										'child_visibility' => [
											[
												'class' => 'course_show_level_individually',
												'on_values' => ['individual']
											]
										]
									]
								)->generateHTML();
								$aEventValues[] = ['db_column' => 'set_show_level', 'id' => 'saveid[set_show_level]'];

								$sHTML .= $oDialogData->createRow(
									$this->t('Kurse anzeigen als'),
									'select',
									[
										'db_column' => 'set_selection',
										'select_options' => [
											'select' => $this->t('Auswahlfeld'),
											'block' => $this->t('Block')
										],
										'default_value' => $oBlock->getSetting('selection')
									]
								)->generateHTML();

								$sHTML .= $oDialogData->createRow(
									$this->t('Kursgruppierung'),
									'select',
									[
										'db_column' => 'set_grouping',
										'select_options' => [
											'' => $this->t('keine'),
											'category' => $this->t('Kurskategorie'),
											'language' => $this->t('Kurssprache')
										],
										'default_value' => $oBlock->getSetting('grouping'),
										'child_visibility' => [
											[
												// Damit das hier überhaupt funktioniert, passieren unten ein paar manuelle Sachen
												'class' => 'course_grouping_visibility',
												'on_values' => ['category', 'language']
											]
										]
									]
								)->generateHTML();
								$aEventValues[] = ['db_column' => 'set_grouping', 'id' => 'saveid[set_grouping]'];

								$sHTML .= $oDialogData->createRow(
									$this->t('Kursgruppierung anzeigen als'),
									'select',
									[
										'db_column' => 'set_grouping_selection',
										'select_options' => [
											'button' => $this->t('Buttons'),
											'select' => $this->t('Auswahlfeld')
										],
										'default_value' => $oBlock->getSetting('grouping_selection'),
										'row_class' => 'course_grouping_visibility',
										'child_visibility' => [
											[
												// Damit das hier überhaupt funktioniert, passieren unten ein paar manuelle Sachen
												'class' => 'course_grouping_selection_visibility',
												'on_values' => ['button']
											]
										]
									]
								)->generateHTML();
								$aEventValues[] = ['db_column' => 'set_grouping_selection', 'id' => 'saveid[set_grouping_selection]'];

								$aOptions = ['db_column' => 'set_hide_fields_initially', 'row_class' => 'course_grouping_selection_visibility'];
								if($oBlock->getSetting('hide_fields_initially')) {
									$aOptions['checked'] = 'checked';
								}
								$sHTML .= $oDialogData->createRow(
									$this->t('Felder initial ausblenden'),
									'checkbox',
									$aOptions
								)->generateHtml();

								if (!$bFirstBlock) {
									$sHTML .= $this->generateBlockDialogField($oDialogData, 'Kursabhängigkeiten beachten', 'check_dependencies', $oBlock->getSetting('check_dependencies'));
								}

								$sHTML .= $oDialogData->createRow(
									$this->t('Maximale Anzahl'),
									'input',
									[
										'db_column' => 'set_count_max',
										'value' => $oBlock->getSetting('count_max'),
										'placeholder' => $aConfig['block_type_mapping'][Ext_Thebing_Form_Page_Block::TYPE_COURSES]['max']
									]
								)->generateHTML();

							}

							if (
								$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_NEW &&
								$oBlock->getSetting('based_on') !== 'scheduling'
							) {
								$aOptions = ['db_column' => 'set_show_levelgroups'];
								if($oBlock->set_show_levelgroups) {
									$aOptions['checked'] = 'checked';
								}
								$oDiv = $oDialogData->createRow(
									L10N::t('Kurssprachen anzeigen', self::$sDescription),
									'checkbox',
									$aOptions
								);
								$sHTML .= $oDiv->generateHtml();

								$aOptions = ['db_column' => 'set_startdates_depending_on_level'];
								if($oBlock->set_startdates_depending_on_level) {
									$aOptions['checked'] = 'checked';
								}
								$oDiv = $oDialogData->createRow(
									L10N::t('Startdaten abhängig von Level (Formular kann langsam werden)', self::$sDescription),
									'checkbox',
									$aOptions
								);
								$sHTML .= $oDiv->generateHtml();
							}

							if (
								$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
								$oBlock->getSetting('based_on') === 'scheduling'
							) {
								$aEventValues[] = [
									'db_column' => 'set_use_default_template', 
									'id' => 'saveid[set_use_default_template]',
									'value' => $oBlock->getSetting('use_default_template') ?? 1
								];
								$aEventValues[] = [
									'db_column' => 'set_default_template',
									'id' => 'saveid[set_default_template]',
									'value' => $oBlock->getSetting('default_template') ?? ''
								];
							
								$sHTML .= $oDialogData->createRow(
									$this->t('Standard-Template verwenden'),
									'checkbox',
									[
										'db_column' => 'set_use_default_template'
									]
								)->generateHTML();
								
								$sHTML .= $oDialogData->createRow(
									$this->t('Quelltext'),
									'textarea',
									[
										'db_column' => 'set_default_template',
										'dependency_visibility' => [
											'db_column' => 'set_use_default_template',
											'on_values' => [0]
										]
									]
								)->generateHTML();

							}

						}

						foreach($oForm->getSelectedSchools() as $oSchool) {

							$aCourses = (array)$oSchool->getCourseList(true, false, false, false, false);
							asort($aCourses);

							$oH3 = $oDialogData->create('h4');
							$sTempTitle = sprintf(L10N::t('Schule "%s"', self::$sDescription), $oSchool->ext_1);
							$oH3->setElement($sTempTitle);
							$sHTML .= $oH3->generateHtml();

							foreach($aCourses as $iElement => $sCourseName) {

								$sCheckedCourse = $oBlock->{'set_course_'.$iElement} ? 'checked' : null;
								$sCheckedCourseLevel = $oBlock->{'set_show_level_course_'.$iElement} ? 'checked' : null;

								$aMultiRow = [
									[
										'db_column' => 'set_course_'.$iElement,
										'input' => 'checkbox',
										'checked' => $sCheckedCourse,
										'value' => '1'
									],
								];

								if ($oBlock->getSetting('based_on') === 'availability') {
									$aMultiRow[] = [
										'db_column' => 'set_show_level_course_'.$iElement,
										'input' => 'checkbox',
										'checked' => $sCheckedCourseLevel,
										'value' => '1',
										'text_after' => '<span class="course_show_level_individually">&nbsp;'.$this->t('Level anzeigen').'</span>',
										'style' => 'margin-left: 20rem;',
										'class' => 'course_show_level_individually'
									];
								}

								$oDiv = $oDialogData->createMultiRow($sCourseName, ['items' => $aMultiRow]);
								$sHTML .= $oDiv->generateHtml();

							}

							// Nachfolgekurse weiter eingrenzen
							$oDiv = new Ext_Gui2_Html_Div();
							$oDiv->class = 'following_courses_container_'.$oSchool->id;

							$sLabel = $this->t('Auswahl für Nachfolgekurse weiter eingrenzen');
							$aOptions = [
								'db_column' => 'set_limit_following_'.$oSchool->id, // Kein course_ wegen strpos()
								'class' => 'checkbox_following_courses',
								'data-container-class' => $oDiv->class,
								'row_style' => $oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 ? 'display: none' : ''
							];
							if($oBlock->{$aOptions['db_column']}) {
								$aOptions['checked'] = 'checked';
							}
							$sHTML .= $oDialogData->createRow($sLabel, 'checkbox', $aOptions)->generateHTML();
							$oDiv->setElement((new Ext_Gui2_Html_H3())->setElement($oSchool->ext_1.': '.$sLabel));

							foreach($aCourses as $iElement => $sElement) {
								// Kein Unterstrich zwischen beiden Wörtern wegen strpos-Abfrage
								$aOptions = ['db_column' => 'set_coursefollowing_'.$iElement];
								if($oBlock->{$aOptions['db_column']}) {
									$aOptions['checked'] = 'checked';
								}
								$oDiv->setElement($oDialogData->createRow($sElement, 'checkbox', $aOptions));
							}
							$sHTML .= $oDiv->generateHTML();

						}

						$aData['tabs'][0]['title'] = L10N::t('Kurse', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][0]['html'] = $sHTML;
						if (!$bFirstBlock && $oBlock->getSetting('based_on') !== 'scheduling') {
							// Der erste Kursblock darf keine Abhängigkeit haben, da dieser ansonsten nicht mandatory sein kann
							$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
							$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						}
						$aData['tabs'][2]['html'] = $this->generateTranslationFieldsHtml($oBlock, $oDialogData);
						$aData['tabs'][2]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						if ($oBlock->getSetting('based_on') !== 'scheduling') {
							$aData['tabs'][3]['html'] = $this->buildAdditionalServicesHtml($oForm, $oBlock, $oDialogData, 'course');
							$aData['tabs'][3]['title'] = L10N::t('Zusatzleistungen', self::$sDescription);
						}
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;

						// Notwendig für child_visibility, da der ganze Mist hier die Werte direkt ins HTML hackt
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();

						$aData = $this->generateInfotextTab($aData, $aLanguages, $oForm, $oDialogData, $oBlock);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:

						$aEventValues = [];
						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Unterkünfte bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Unterkünfte anlegen', self::$sDescription);
						}

						$sDefLang = $oForm->default_language;

						if(
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 ||
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_NEW ||
							$oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY
						) {
							$aOptions = array('db_column' => 'required');
							if($oBlock->required) {
								$aOptions['checked'] = 'checked';
							}
//							if ($oBlock->getSetting('dependency_type')) {
//								$aOptions['disabled'] = 'disabled';
//							}
							$oDiv = $oDialogData->createRow(
								L10N::t('Pflichtfeld', self::$sDescription),
								'checkbox',
								$aOptions
							);
							$sHTML .= $oDiv->generateHtml();

//							$aOptions = array('db_column' => 'set_label_as_placeholder');
//							if($oBlock->set_label_as_placeholder) {
//								$aOptions['checked'] = 'checked';
//							}
//							$oDiv = $oDialogData->createRow(
//								L10N::t('Label im Feld anzeigen', self::$sDescription),
//								'checkbox',
//								$aOptions
//							);
//							$sHTML .= $oDiv->generateHtml();
						}

						if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {

							$sHTML .= $oDialogData->createRow(
								$this->t('Verfügbarkeit von Startdaten/Enddaten'),
								'select',
								[
									'db_column' => 'set_availability_start_end',
									'select_options' => [
										'course_period' => $this->t('an Starttagen innerhalb des Kurszeitraums'),
										'course_period_each_day' => $this->t('an allen Tagen innerhalb des Kurszeitraums'),
										'course_start_end' => $this->t('nur an Kursstart und Kursende'),
										'accommodation_start_end' => $this->t('nur an Starttag und Endtag der Unterkunft (unabhängig von Kurs)')
									],
									'default_value' => $oBlock->getSetting('availability_start_end') ?? 'course_period',
									'input_div_elements' => ['<span class="help-block">'.$this->t('Diese Einstellung wird außerdem beeinflusst von den eingestellten Inklusivtagen.').'</span>'],
								]
							)->generateHTML();
						}

						foreach((array)$oForm->schools as $iSchoolID) {

							$oSchool = Ext_Thebing_School::getInstance($iSchoolID);
							$oAcc = new Ext_Thebing_Accommodation_Util($oSchool);
							$aTemp = (array)$oSchool->getAccommodationList();
							asort($aTemp);

							$oH2 = $oDialogData->create('h2');
							$sTempTitle = sprintf(L10N::t('Schule "%s"', self::$sDescription), $oSchool->ext_1);
							$oH2->setElement($sTempTitle);
							$sHTML .= $oH2->generateHtml();

							foreach((array)$aTemp as $iElement => $mElement) {

								$oAcc->setAccommodationCategorie($iElement);
								if (!in_array($oAcc->getAccommodationCategory()->type_id, [Ext_Thebing_Accommodation_Category::TYPE_OTHERS, Ext_Thebing_Accommodation_Category::TYPE_HOSTFAMILY])) {
									continue;
								}

								$bEmpty = true;
								$oH3 = $oDialogData->create('h4');
								$oH3->setElement((string)$mElement.'&nbsp;');
								$sHTML .= $oH3->generateHtml();
								$aRoomMealCombi = $aRoomtypes = $aRoomtypeMeals = array();

								$aRooms = $oAcc->getRoomtypeList();

								foreach((array)$aRooms as $aRoom) {

									$aMeals = explode(',', $aRoom['meal']);
									$oAcc->setRoomtype($aRoom);

									foreach ((array)$aMeals as $iMeal) {

										if($aRoomMealCombi[$aRoom['id']][$iMeal] == 1) {
											continue;
										}

										$aRoomMealCombi[$aRoom['id']][$iMeal] = 1;
										$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($iMeal);
										$aOptions = array('db_column' => 'set_accommodation_'.$iElement.'_'.$aRoom['id'].'_'.$oMeal->id.'_'.$iSchoolID);

										if($oBlock->{$aOptions['db_column']}) {
											$aOptions['checked'] = 'checked';
										}

										$sLabel = '- '.(string)$aRoom['name_'.$sDefLang].' | '.(string)$oMeal->getData('name_'.$sDefLang);

										$oDiv = $oDialogData->createRow(
											(string)$sLabel,
											'checkbox',
											$aOptions
										);
										$sHTML .= $oDiv->generateHtml();

										$bEmpty = false;

									}

								}

								if($bEmpty) {
									$sHTML .= L10N::t('Keine Einträge gefunden', self::$sDescription);
								}

							}

						}

						$aData['tabs'][0]['title'] = L10N::t('Unterkünfte', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['tabs'][2]['html'] = $this->generateTranslationFieldsHtml($oBlock, $oDialogData);
						$aData['tabs'][2]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['tabs'][3]['html'] = $this->buildAdditionalServicesHtml($oForm, $oBlock, $oDialogData, 'accommodation');
						$aData['tabs'][3]['title'] = L10N::t('Zusatzleistungen', self::$sDescription);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();
						$aData = $this->generateInfotextTab($aData, $aLanguages, $oForm, $oDialogData, $oBlock);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:

						$aEventValues = [];
						$sHTML_Arr = $sHTML;
						$sHTML_Dep = $sHTML_Types = $sHTML_Trans = '';
						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Transfer bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Transfer anlegen', self::$sDescription);
						}

						$aTypes = $oBlock->getTranslationConfig(self::getLanguageObject(), null);
						$aTypes = collect($aTypes)->only(['no_transfer', 'arrival', 'departure', 'arrival_departure']);

						$oDiv = $oDialogData->createRow($this->t('Icon-Klasse'), 'input', [
							'db_column' => 'set_icon_class',
							'value' => $oBlock->set_icon_class,
						]);
						$sHTML_Trans .= $oDiv->generateHtml();

						$sHTML_Trans .= $this->generateTranslationFieldsHtml($oBlock, $oDialogData);

						/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

						if(
							$oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3 ||
							$oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_NEW ||
							$oForm->type == Ext_Thebing_Form::TYPE_ENQUIRY
						) {
							if($oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
								$aOptions = [];
								if (!empty($oBlock->getSetting('dependency_type'))) {
									$oBlock->required = 0;
									$aOptions['disabled'] = 'disabled';
								}
								$sHTML_Arr .= $this->generateBlockDialogField($oDialogData, 'Pflichtfeld', 'required', $oBlock->required, $aOptions);
							} else {
								$sHTML_Types .= $this->generateBlockDialogField($oDialogData, 'Pflichtfeld', 'required', $oBlock->required);
							}
						}

						if ($oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$sHTML_Arr .= $this->generateBlockDialogField($oDialogData, $this->t('Erweitertes Formular anzeigen'), 'show_extended_form', $oBlock->getSetting('show_extended_form'), [
								'input_div_elements' => ['<span class="help-block">'.$this->t('Standardmäßig wird die Abfrage des Transfers so kurz wie möglich gehalten.').'</span>'],
								'child_visibility' => [
									[
										'class' => 'transfer_extended_form',
										'on_values' => [1]
									]
								]
							]);
							$aEventValues[] = ['db_column' => 'set_show_extended_form', 'id' => 'saveid[set_show_extended_form]', 'value' => $oBlock->getSetting('show_extended_form')]; // value zwingend notwendig für Checkboxen

							$sHTML_Arr .= '<h3 class="transfer_extended_form">'.$this->t('Felder anzeigen').'</h3>';

							$sHTML_Arr .= $this->generateBlockDialogField($oDialogData, $this->t('An- und Abreise-Informationen immer anzeigen'), 'show_fields_without_type_check', $oBlock->getSetting('show_fields_without_type_check'), [
								'row_class' => 'transfer_extended_form',
//								'child_visibility' => [
//									[
//										'class' => 'transfer_extended_form_type',
//										'on_values' => [1]
//									]
//								],
								'input_div_elements' => ['<span class="help-block">'.$this->t('Standardmäßig werden die Felder pro Typ (Anreise, Abreise) nur bei der Auswahl des entsprechenden Typs angezeigt.').'</span>']
							]);
							$aEventValues[] = ['db_column' => 'set_show_fields_without_type_check', 'id' => 'saveid[set_show_fields_without_type_check]', 'value' => $oBlock->getSetting('show_fields_without_type_check')];

							$aFields = array_map(fn(string $sLabel) => $this->t($sLabel), \Ext_TS_Inquiry_Journey_Transfer::REGISTRATION_FORM_FIELDS);

							foreach ($aFields as $sField => $sLabel) {

								$sClass = 'transfer_extended_form';
								$sChecked1 = $oBlock->{'set_show_field_'.$sField} ? 'checked' : null;
//								$sChecked2 = $oBlock->{'set_show_level_course_'.$sField} ? 'checked' : null;

								// Nur Dekoration, weil createHiddenField irgendeinen Scheiß macht
								$aOptions = [];
								if ($oForm->isCreatingBooking() && in_array($sField, ['type', 'locations'])) {
									$sChecked1 = $sChecked2 = 'checked';
									$aOptions = ['disabled' => 'disabled'];
								}

//								if ($sField === 'type') {
//									$sClass .= ' transfer_extended_form_type';
//								}

								$sHTML_Arr .= $oDialogData->createMultiRow($sLabel, [
									'row_class' => $sClass,
									'items' => [
										[
											'db_column' => 'set_show_field_'.$sField,
											'input' => 'checkbox',
											'checked' => $sChecked1,
											'value' => '1',
											...$aOptions
										],
//										[
//											'db_column' => 'set_required_field_'.$sField,
//											'input' => 'checkbox',
//											'checked' => $sChecked2,
//											'value' => '1',
//											'text_after' => '<span class="course_show_level_individually">&nbsp;'.$this->t('Pflichtfeld').'</span>',
//											'style' => 'margin-left: 20rem;',
//											'class' => 'course_show_level_individually',
//											...$aOptions
//										]
									]
								])->generateHTML();

							}

						}

						foreach((array)$oForm->schools as $iSchoolID) {

							$oSchool = Ext_Thebing_School::getInstance($iSchoolID);
							$aTempArr = $oSchool->getTransferLocationsForInquiry('arrival', 0);
							$aTempDep = $oSchool->getTransferLocationsForInquiry('departure', 0);

							unset($aTempArr[0], $aTempDep[0]);

							asort($aTempArr);
							asort($aTempDep);

							$oH3 = $oDialogData->create('h4');
							$sTempTitle = sprintf(L10N::t('Schule "%s"', self::$sDescription), $oSchool->ext_1);
							$oH3->setElement($sTempTitle);

							$sHTML_Arr .= $oH3->generateHtml();
							$sHTML_Dep .= $oH3->generateHtml();
							$sHTML_Types .= $oH3->generateHtml();

							/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

							foreach($aTypes as $sTypeKey => $aTranslation) {

								$aOptions = array('db_column' => 'set_'.$sTypeKey.$iSchoolID);

								if($oBlock->{$aOptions['db_column']}) {
									$aOptions['checked'] = 'checked';
								}

								$oDiv = $oDialogData->createRow(
									$aTranslation[0],
									'checkbox',
									$aOptions
								);
								$sHTML_Types .= $oDiv->generateHtml();

							}

							$aOptions = array('db_column' => 'set_always_show_inputs_'.$iSchoolID);
							if($oBlock->{$aOptions['db_column']}) {
								$aOptions['checked'] = 'checked';
							}

							$oDiv = $oDialogData->createRow($this->t('An- und Abreise-Informationen immer anzeigen'), 'checkbox', $aOptions);
							$sHTML_Types .= $oDiv->generateHtml();

							/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

							$i = count($aTempArr);
							foreach((array)$aTempArr as $sTransferID => $sTransferTitle) {

								if(
									$oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
									Str::contains($sTransferID, 'school') ||
									Str::contains($sTransferID, 'accommodation')
								) {
									continue;
								}

								foreach((array)$aTempDep as $sTransferDepID => $sTransferDepTitle) {

									if(
										$oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
										Str::contains($sTransferDepID, 'location')
									) {
										continue;
									}

									if($sTransferDepID == $sTransferID) {
										continue;
									}

									$aOptions = array('db_column' => 'set_transfer_arr_'.$iSchoolID.'_'.$sTransferID.'_to_'.$sTransferDepID);

									if($oBlock->{$aOptions['db_column']}) {
										$aOptions['checked'] = 'checked';
									}

									$sArrow = $oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3 ? '↔' : '->';

									$oDiv = $oDialogData->createRow(
										$sTransferTitle.' '.$sArrow.' '.$sTransferDepTitle,
										'checkbox',
										$aOptions
									);
									$sHTML_Arr .= $oDiv->generateHtml();

								}

								$oH3 = $oDialogData->create('h4');
								$oH3->setElement('&nbsp;');

								if(--$i > 0) {
									$sHTML_Arr .= $oH3->generateHtml();
								}

							}

							/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

							$i = count($aTempDep);
							foreach((array)$aTempDep as $sTransferID => $sTransferTitle) {

								foreach((array)$aTempArr as $sTransferArrID => $sTransferArrTitle) {

									if($sTransferArrID == $sTransferID) {
										continue;
									}

									$aOptions = array('db_column' => 'set_transfer_dep_'.$iSchoolID.'_'.$sTransferID.'_to_'.$sTransferArrID);

									if($oBlock->{$aOptions['db_column']}) {
										$aOptions['checked'] = 'checked';
									}

									$oDiv = $oDialogData->createRow(
										$sTransferTitle.' -> '.$sTransferArrTitle,
										'checkbox',
										$aOptions
									);
									$sHTML_Dep .= $oDiv->generateHtml();

								}

								$oH3 = $oDialogData->create('h4');
								$oH3->setElement('&nbsp;');

								if(--$i > 0) {
									$sHTML_Dep .= $oH3->generateHtml();
								}

							}

						}

						$aData['tabs'][0]['title'] = L10N::t('Anreise', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML_Arr;
						if($oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$aData['tabs'][1]['title'] = L10N::t('Abreise', self::$sDescription);
							$aData['tabs'][1]['html'] = $sHTML_Dep;
							$aData['tabs'][2]['title'] = L10N::t('Arten', self::$sDescription);
							$aData['tabs'][2]['html'] = $sHTML_Types;
						} else {
							$aData['tabs'][0]['title'] = L10N::t('Wege', self::$sDescription);
						}
						$aData['tabs'][3]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][3]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['tabs'][4]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['tabs'][4]['html'] = $sHTML_Trans;
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();
						$aData = $this->generateInfotextTab($aData, $aLanguages, $oForm, $oDialogData, $oBlock);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$sHTML_Translations = '';

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Versicherungen bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Versicherungen anlegen', self::$sDescription);
						}

						if(
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 ||
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_NEW ||
							$oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY
						) {
							$aOptions = array('db_column' => 'required');
							if($oBlock->required) {
								$aOptions['checked'] = 'checked';
							}
							if ($oBlock->getSetting('require_selection')) {
								$aOptions['disabled'] = 'disabled';
							}

							$oDiv = $oDialogData->createRow(
								L10N::t('Pflichtfeld', self::$sDescription),
								'checkbox',
								$aOptions
							);
							$sHTML .= $oDiv->generateHtml();

						}

						if ($oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$aOptions = ['db_column' => 'set_require_selection'];
							if($oBlock->getSetting('require_selection')) {
								$aOptions['checked'] = 'checked';
							}
							$sHTML .= $oDialogData->createRow(
								$this->t('Auswahl erzwingen (ja/nein)'),
								'checkbox',
								$aOptions
							)->generateHtml();
						}

						foreach($oForm->getSelectedSchools() as $oSchool) {

							$aTemp = (array)Ext_Thebing_Insurances_Gui2_Insurance::getInsurancesListForInbox(false, $oSchool->id);
							usort($aTemp, array('Ext_Thebing_Form_Gui2', 'sortHelper'));

							$oH3 = $oDialogData->create('h4');
							$sTempTitle = sprintf(L10N::t('Schule "%s"', self::$sDescription), $oSchool->ext_1);
							$oH3->setElement($sTempTitle);
							$sHTML .= $oH3->generateHtml();

							foreach($aTemp as $iElement => $mElement) {

								$aOptions = array('db_column' => 'set_insurance_'.$oSchool->id.'_'.$mElement['id']);
								if($oBlock->{$aOptions['db_column']}) {
									$aOptions['checked'] = 'checked';
								}

								$oDiv = $oDialogData->createRow(
									(string)$mElement['title'],
									'checkbox',
									$aOptions
								);
								$sHTML .= $oDiv->generateHtml();

							}

						}

						$sHTML_Translations .= $this->generateTranslationFieldsHtml($oBlock, $oDialogData);

						$aData['tabs'][0]['title'] = L10N::t('Versicherungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][1]['html'] = $sHTML_Translations;
						$aData['tabs'][1]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;

						$aData = $this->generateInfotextTab($aData, $aLanguages, $oForm, $oDialogData, $oBlock);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_PRICES:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);

						$bConfigurable = true;
						$aBlocks = $oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
							return (int)$oBlock->block_id === Ext_Thebing_Form_Page_Block::TYPE_PRICES;
						});

						if(
							$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
							count($aBlocks) > 0 &&
							$aBlocks[0]->id != $oBlock->id
						) {
							$bConfigurable = false;
						}

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Preisblock bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Preisblock anlegen', self::$sDescription);
						}

						if($bConfigurable) {
							$sHTML .= $this->generateTranslationFieldsHtml($oBlock, $oDialogData);
						} else {
							$sHTML .= $oDialogData->createNotification(
								$this->t('Achtung'),
								$this->t('Die Einstellungen des ersten Blocks werden verwendet.'),
								'info'
							)->generateHTML();
						}

						$aData['tabs'][0]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['tabs'][0]['html'] = $sHTML;
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = $sTitle;

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_FEES:

						$aEventValues = [];
						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$sHtmlTranslations = '';

						if($oBlock->id > 0) {
							$sTitle = L10N::t('Gebühren bearbeiten', self::$sDescription);
						} else {
							$sTitle = L10N::t('Gebühren anlegen', self::$sDescription);
						}

						if ($oForm->type == Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
							$sInfo = collect([
								[$this->t('Keine Auswahl'), $this->t('Mehrfachauswahl, Anzeige als Checkboxen.')],
								[$this->t('Pflichtfeld'), $this->t('Mehrfachauswahl, Anzeige als Checkboxen, min. eine Leistung muss gewählt werden.')],
								[$this->t('Auswahl erzwingen'), $this->t('Mehrfachauswahl, Anzeige als Radio-Buttons mit ja/nein pro Leistung, Pflichtauswahl.')],
								[$this->t('Pflichtfeld und Auswahl erzwingen'), $this->t('Einzelauswahl, Anzeige als Radio-Buttons, eine Leistung muss gewählt werden.')]
							])->map(function (array $aText) {
								return '<tr><th>'.$aText[0].'</th><td>'.$aText[1].'</td></tr>';
							})->join('');
							$sHTML .= $oDialogData->createNotification($this->t('Info'), '<table>'.$sInfo.'</table>', 'info')->generateHTML();
							$sHTML .= $this->generateBlockDialogField($oDialogData, 'Pflichtfeld', 'required', $oBlock->required);
							$sHTML .= $this->generateBlockDialogField($oDialogData, 'Auswahl erzwingen', 'require_selection', $oBlock->getSetting('require_selection'));
							$sHTML .= $this->generateBlockDialogField($oDialogData, 'In zwei Spalten darstellen', 'show_two_columns', $oBlock->getSetting('show_two_columns'));
						}

						foreach($oForm->getSelectedSchools() as $oSchool) {

							$oH3 = $oDialogData->create('h4');
							$sTempTitle = sprintf(L10N::t('Schule "%s"', self::$sDescription), $oSchool->ext_1);
							$oH3->setElement($sTempTitle);
							$sHTML .= $oH3->generateHtml();

							foreach($oSchool->getGeneralCosts() as $oFee) {

//								if (
//									(
//										// Nicht in den alten Forms
//										$oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
//										$oFee->charge === 'semi'
//									) || (
//										// Auch nicht im neuen Enquiry Form
//										$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
//										$oPage->type !== 'booking' &&
//										$oFee->charge === 'semi'
//									)
//								) {
//									continue;
//								}

								$aOptions = array('db_column' => 'set_cost_'.$oSchool->id.'_'.$oFee->id);

								if($oBlock->{$aOptions['db_column']}) {
									$aOptions['checked'] = 'checked';
								}

								$sName = $oFee->getName(System::getInterfaceLanguage());
//								if ($oFee->charge === 'semi') {
//									$sName .= ' <sup>1</sup>';
//								}

								$oDiv = $oDialogData->createRow($sName, 'checkbox', $aOptions);
								$sHTML .= $oDiv->generateHtml();
							}
						}

//						$sHTML .= '<sup>1</sup> '.$this->_oGui->t('Semi-automatische Gebühren werden nur angezeigt wenn eine passende Leistung ausgewählt wurde.');

						$sHtmlTranslations .= $this->generateTranslationFieldsHtml($oBlock, $oDialogData);

						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][0]['title'] = L10N::t('Zusätzliche Gebühren', self::$sDescription);;
						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['tabs'][2]['html'] = $sHtmlTranslations;
						$aData['tabs'][2]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 700;
						$aData['title'] = $sTitle;
						$aData['values'] = $aEventValues;
						$aData['events'] = $oDialogData->getEvents();

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);

						$sHTML .= $oDialogData->createRow($this->t('Basierend auf'), 'select', [
							'db_column' => 'set_based_on',
							'select_options' => [
								'availability' => $this->t('Verfügbarkeit'),
								'scheduling' => $this->t('Planung')
							],
							'default_value' => $oBlock->getSetting('based_on'),
							'required' => true
						])->generateHTML();

						foreach($oForm->getSelectedSchools() as $oSchool) {
							if ($oBlock->getSetting('based_on') === 'scheduling') {
								break;
							}

							$sHTML .= $oDialogData->create('h4')->setElement($this->t(sprintf('Schule "%s"', $oSchool->ext_1)))->generateHTML();
							$aActivities = TsActivities\Entity\Activity::getRepository()->getActivitiesBySchool($oSchool);
							foreach($aActivities as $oActivity) {
								$sField = 'activity_'.$oSchool->id.'_'.$oActivity->id;
								$sHTML .= $this->generateBlockDialogField($oDialogData, $oActivity->getName(), $sField, $oBlock->{'set_'.$sField});
							}
						}

						$sHtmlTranslations = $this->generateTranslationFieldsHtml($oBlock, $oDialogData);

						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][0]['title'] = $this->t('Aktivitäten');
//						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
//						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['tabs'][2]['html'] = $sHtmlTranslations;
						$aData['tabs'][2]['title'] = L10N::t('Übersetzungen', self::$sDescription);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 700;
						$aData['title'] = $this->t('Aktivitäten bearbeiten');

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_PAYMENT:

						$sHTML .= $oDialogData->createNotification(
							$this->t('Achtung'),
							$this->t('Dieser Block muss auf einer Seite verwendet werden, auf der keine Änderungen am Preis mehr stattfinden können, da ansonsten der Bezahlbetrag inkorrekt sein kann. Es sollten außerdem keine Adressdaten sowie keine Pflichtfelder auf dieser Seite vorhanden sein.'),
							'info'
						)->generateHTML();

						$sHTML .= $oDialogData->createRow(
							$this->t('Pflichtfeld'), 'checkbox', [
								'db_column' => 'required',
								'default_value' => $oBlock->required,
								'input_div_elements' => ['<span class="help-block">'.$this->t('Als optionales Feld wird eine Option zum Überspringen der Zahlung angeboten.').'</span>']
							]
						)->generateHTML();

						$aEventValues = [];

						$aProviders = (new \TsFrontend\Factory\PaymentFactory())->getOptions(\TsFrontend\Interfaces\PaymentProvider\RegistrationForm::class);

						$sHTML .= $oDialogData->createRow(
							$this->t('Anbieter'), 'select', [
								'db_column' => 'set_provider',
								'select_options' => $aProviders,
								'default_value' => $oBlock->getSetting('provider'),
								'required' => true,
								'multiple' => 5,
								'jquery_multiple' => true,
								'style' => 'width: 490px;'
							]
						)->generateHTML();

						$sHTML .= $oDialogData->createRow(
							$this->t('Nur Anzahlungsbetrag verwenden'), 'checkbox', [
								'db_column' => 'set_pay_deposit',
								'default_value' => $oBlock->getSetting('pay_deposit'),
								'input_div_elements' => ['<span class="help-block">'.$this->t('Hiermit wird auch die zu leistende Anzahlung im Preisblock angezeigt.').'</span>']
							]
						)->generateHTML();

						$aData['tabs'][0]['html'] = $sHTML;
						$aData['tabs'][0]['title'] = L10N::t('Zahlung', self::$sDescription);;
						$aData['tabs'][1]['title'] = L10N::t('Abhängigkeit', self::$sDescription);
						$aData['tabs'][1]['html'] = $this->buildDependencyFields($oForm, $oBlock, $oDialogData, $aEventValues);
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 500;
						$aData['title'] = collect($this->getBlocks())->firstWhere('key', $oBlock->block_id)['title'];
						$aData['values'] = $aEventValues; // jQuery Multiselect
						$aData['events'] = $oDialogData->getEvents();

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS:
					case Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS:
					case Ext_Thebing_Form_Page_Block::TYPE_HORIZONTAL_RULE:

						if($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS) {
							$sHTML .= $oDialogData->createRow(
								$this->t('Ausrichtung'),
								'select',
								[
									'db_column' => 'set_align',
									'select_options' => [
										'justify' => $this->t('bündig ausgerichtet'),
										'right' => $this->t('rechts ausgerichtet')
									],
									'default_value' => $oBlock->getSetting('justify')
								]
							)->generateHTML();
						} else {
							$sHTML .= $oDialogData->createNotification(
								$this->t('Achtung'),
								$this->t('Keine Einstellungen vorhanden.'),
								'info'
							)->generateHTML();
						}

//						$aData['tabs'][0]['title'] = L10N::t('Übersetzungen', self::$sDescription);
//						$aData['tabs'][0]['html'] = $sHTML;
						$aData['html'] = $sHTML;
						$aData['id'] = 'block_'.$oBlock->id;
						$aData['width'] = 700;
						$aData['height'] = 400;
						$aData['title'] = collect($this->getBlocks())->firstWhere('key', $oBlock->block_id)['title'];

						break;

				}

				break;

			case 'move_block':

				$sHtml = '';
				$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$_VARS['block_id']);
				$oDialogData = $this->_oGui->createDialog('','');

				$oDiv = $oDialogData->createSaveField('hidden', [
					'db_column' => 'id',
					'value' => $oBlock->id
				]);
				$sHtml .= $oDiv->generateHtml();

				$aPages = collect($oForm->getPages())
					// Aktuell draußen, da der Dialog offen bleibt und das dann keinen Sinn macht
//					->filter(function(Ext_Thebing_Form_Page $oPage) use($oBlock) {
//						return $oPage->id != $oBlock->page_id;
//					})
					->mapWithKeys(function(Ext_Thebing_Form_Page $oPage) {
						return [$oPage->id => $oPage->getTitle()];
					});

				$oDiv = $oDialogData->createRow($this->t('Seite'), 'select', [
					'db_column' => 'page_id',
					'select_options' => $aPages
				]);
				$sHtml .= $oDiv->generateHtml();

				$aData['html'] = $sHtml;
				$aData['id'] = 'block_'.$oBlock->id;
				$aData['width'] = 500;
				$aData['height'] = 250;
				$aData['title'] = $this->t('Block verschieben', self::$sDescription);

				break;

			default:
				$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);

		}

		return $aData;

	}

	/**
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 * @throws Exception
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$aSelectedIds = (array)$aSelectedIds;

		if(count($aSelectedIds) > 1) {
			return array();
		} else {
			$iSelectedId = (int)reset($aSelectedIds);
		}

		$aLanguages = Ext_Thebing_Data::getSystemLanguages();

		if(
			empty($this->oWDBasic) ||
			$this->oWDBasic->id > 0
		) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$oFirst = reset($oDialogData->aElements);
		$oLast = $oDialogData->createTab($this->_oGui->t('Formular')); // Achtung: Nummerischer Index in Ext_Thebing_Form_Gui2::prepareOpenDialog()
		$oLast->class = 'tab_form_designer';

		$aElements	= array($oFirst);

		$aFields = $this->oWDBasic->getTranslationFields();

		$bPaymentFields = collect($this->oWDBasic->schools)->some(function($iSchoolId) {
			return !empty($this->oWDBasic->getSchoolSetting($iSchoolId, 'payment_provider'));
		});
		if(!$bPaymentFields) {
			unset($aFields['paymentsuccess']);
			unset($aFields['paymenterror']);
		}

		if($this->oWDBasic->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
			unset($aFields['errorrequired']);
		}

		$oTranslationsTab = $oDialogData->createTab($this->_oGui->t('Übersetzungen'));

		foreach((array)$this->oWDBasic->languages as $sLanguage) {
			$sLanguageLabel	= $aLanguages[$sLanguage];
			foreach($aFields as $sKey => $aField) {

				// Nur noch als Frontend-Übersetzung
				if(!empty($aField['hide'])) {
					continue;
				}

				$sPlaceholder = '';
				if(!empty($aField['frontend'])) {
					$sPlaceholder = \Ext_TC_L10N::tf($aField['frontend'], \TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT, $sLanguage);
				}

				$aInputElements = [];
				if(!empty($aField['help'])) {
					$aInputElements[] = '<span class="help-block">'.$this->t($aField['help']).'</span>';
				}

				$oTranslationsTab->setElement($oDialogData->createRow(
					$sLabel	= sprintf($this->t($aField['backend']), $sLanguageLabel),
					$aField['type'],
					[
						'db_column' => 'translation_'.$sKey.'_'.$sLanguage,
						'required' => $aField['required'] ?? empty($sPlaceholder),
						'placeholder' => $sPlaceholder,
						'input_div_elements' => $aInputElements
					]
				));

			}
		}


//		$oNotifyTab = $oDialogData->createTab($this->_oGui->t('Benachrichtigungen'));
		$oPricesTab = $oDialogData->createTab($this->_oGui->t('Schul-Einstellungen'));

		if ($this->oWDBasic->isCreatingBooking()) {
			if (\System::d('booking_auto_confirm') != \Ext_Thebing_Client::BOOKING_AUTO_CONFIRM_ALL) {
				$oPricesTab->setElement($oDialogData->createRow($this->t('Buchungen automatisch bestätigen'), 'checkbox', [
					'db_column' => 'booking_auto_confirm',
					'db_alias' => 'kf',
				]));
			}

			$aAttachDocumentValues = [Ext_Thebing_Form::TYPE_REGISTRATION_NEW, Ext_Thebing_Form::TYPE_REGISTRATION_V3];
			if(Ext_Thebing_Access::hasLicenceRight('thebing_students_contact_gui')) {
				$aAttachDocumentValues[] = Ext_Thebing_Form::TYPE_ENQUIRY;
			}

			$oPricesTab->setElement($oDialogData->createRow($this->t('Dokument an E-Mail anhängen'), 'checkbox', [
				'db_column' => 'email_attach_document',
				'db_alias' => 'kf',
				'dependency_visibility' => [
					'db_column' => 'type',
					'on_values' => $aAttachDocumentValues
				]
			]));
		}

		// Passende Templates (Automatische E-Mails) suchen (Typ, Büro und Sprache)
		$aEmails = Ext_TC_Communication_AutomaticTemplate::getSelectOptions(true, 'registration_mail');
		$aEmails = Ext_Thebing_Util::addEmptyItem($aEmails);

		foreach((array)$this->oWDBasic->schools as $iSchoolID) {

			$oSchool = Ext_Thebing_School::getInstance($iSchoolID);
			$oH3 = $oDialogData->create('h4');
			$sTempTitle = sprintf(L10N::t('Schule "%s"', self::$sDescription), $oSchool->ext_1);
			$oH3->setElement($sTempTitle);
//			$oNotifyTab->setElement($oH3);

//			if($this->oWDBasic->use_prices) {
				$oPricesTab->setElement($oH3);
//			}

			/*foreach((array)$this->oWDBasic->languages as $sLanguage) {

				$sTempKey = 'translation_schoolTpl'.$iSchoolID.'_'.$sLanguage;
				$oPricesTab->setElement(
					$oDialogData->createRow(
						sprintf(L10N::t('E-Mail-Vorlage "%s"', self::$sDescription), $aLanguages[$sLanguage]),
						'select',
						array(
							'db_column' => $sTempKey,
							//'default_value' => $oBlock->$sTempKey,
							'select_options' => $aEmails
						)
					)
				);

			}*/
			if ($this->oWDBasic->isCreatingBooking()) {
				$oNotification = $oDialogData->createNotification($this->_oGui->t('Achtung'), $this->_oGui->t('Sie haben keine Schule in der die Steuern aktiviert sind.'), 'info');
				$oPricesTab->setElement($oNotification);
			}

			if (
				$this->oWDBasic->isCreatingBooking() ||
				$this->oWDBasic->purpose == Ext_Thebing_Form::PURPOSE_EDIT
			) {
				$oPricesTab->setElement($oDialogData->createRow($this->t('Rechnung anstatt Proforma generieren'), 'checkbox', [
					'db_column' => 'school_settings_'.$iSchoolID.'_generate_invoice',
					'dependency_visibility' => [
						'db_column' => 'type',
						'on_values' => [Ext_Thebing_Form::TYPE_REGISTRATION_NEW, Ext_Thebing_Form::TYPE_REGISTRATION_V3, Ext_Thebing_Form::PURPOSE_EDIT]
					],
					'input_div_elements' => ['<span class="help-block">'.$this->t('Wenn dem Schüler eine Zahlung zugewiesen wird, wird die Proforma automatisch in eine Rechnung umgewandelt.').'</span>']
					// Funktioniert nicht, da dieses $oForm->_aSchoolSettings reingehackt wurde
//					'events' => [
//						[
//							// Abhängigkeit für die Warnung von fehlenden Pflicht-Blöcken ($this->checkMissingBlocks())
//							'event' => 'change',
//							'function' => 'reloadDialogTab',
//							'parameter' => 'aDialogData.id, 3'
//						]
//					],
				]));
			}

			if ($this->oWDBasic->isCreatingBooking()) {

//				$aVats = Ext_Thebing_School_Gui2::getTaxCalculations();
//
//				$sText = L10N::t('Aktuelle Schuleinstellung für Steuern: "%s"', self::$sDescription);
//				$oPricesTab->setElement(
//					$oDiv = $oDialogData->createNotification(
//						L10N::t('Information', self::$sDescription),
//						sprintf($sText, $aVats[$oSchool->tax]),
//						'info'
//					)
//				);
//
//				$sKey = 'school_settings_'.$iSchoolID.'_show_sum_vat';
//
//				$aOptions = array(
//					'db_column' => $sKey,
//					'events'	=> array(
//						array(
//							'event' => 'click',
//							'function' => 'reloadDialogTab',
//							'parameter' => 'aDialogData.id, 3'
//						)
//					)
//				);
//
//				$iValue = $this->oWDBasic->$sKey;
//
//				if($iValue) {
//					$aOptions['checked'] = 'checked';
//				}
//
//				$oPricesTab->setElement(
//					$oDiv = $oDialogData->createRow(
//						L10N::t('MwSt. ausweisen', self::$sDescription),
//						'checkbox',
//						$aOptions
//					)
//				);
//
//				if($iValue) {
//
//					$sKey = 'school_settings_'.$iSchoolID.'_show_positions_vat';
//
//					$aOptions = array(
//						'db_column' => $sKey
//					);
//
//					if($this->oWDBasic->$sKey) {
//						$aOptions['checked'] = 'checked';
//					}
//
//					$oPricesTab->setElement(
//						$oDiv = $oDialogData->createRow(
//							L10N::t('MwSt. ausweisen je Rechnungsposition', self::$sDescription),
//							'checkbox',
//							$aOptions
//						)
//					);
//
//					$sLabel	= L10N::t('%s: Steuer (inkl./exkl./zzgl./abzl.)', self::$sDescription);
//
//					foreach((array)$this->oWDBasic->languages as $sLanguage) {
//
//						$sLanguageLabel	= $aLanguages[$sLanguage];
//						$sKey = 'translation_VAT'.$iSchoolID.'_'.$sLanguage;
//						$oPricesTab->setElement(
//							$oDialogData->createRow(
//								(string)sprintf($sLabel, $sLanguageLabel),
//								'input',
//								array(
//									'db_column' => $sKey
//								)
//							)
//						);
//
//					}
//
//				}

//				$oPricesTab->setElement($oDialogData->createRow($this->t('Rechnungspositionen sind Vor-Ort-Kosten'), 'checkbox', [
//					'db_column' => 'school_settings_'.$iSchoolID.'_at_school_fees',
//					'dependency_visibility' => [
//						'db_column' => 'type',
//						'on_values' => [Ext_Thebing_Form::TYPE_ENQUIRY, Ext_Thebing_Form::TYPE_REGISTRATION_NEW, Ext_Thebing_Form::TYPE_REGISTRATION_V3]
//					]
//				]));

				// Nur V2
				$aPaymentProviderOptions = \TsFrontend\Handler\Payment\Legacy\AbstractPayment::getOptions();
				$oPricesTab->setElement($oDialogData->createRow($this->t('Zahlungsanbieter verwenden'), 'select', [
					'db_column' => 'school_settings_'.$iSchoolID.'_payment_provider',
					'select_options' => Util::addEmptyItem($aPaymentProviderOptions),
					'dependency_visibility' => [
						'db_column' => 'type',
						'on_values' => [Ext_Thebing_Form::TYPE_REGISTRATION_NEW]
					]
				]));

				// Nur V2
				$oPricesTab->setElement($oDialogData->createRow($this->t('Bezahlmethode'), 'select', [
					'db_column' => 'school_settings_'.$iSchoolID.'_payment_method',
					'select_options' => Util::addEmptyItem(Ext_Thebing_Admin_Payment::getPaymentMethods(true, [$iSchoolID])),
					'required' => true,
					'dependency_visibility' => [
						'db_column' => 'school_settings_'.$iSchoolID.'_payment_provider',
						'on_values' => array_keys($aPaymentProviderOptions)
					]
				]));

				$oPricesTab->setElement($oDialogData->createRow($this->t('Nur Anzahlungsbetrag verwenden'), 'checkbox', [
					'db_column' => 'school_settings_'.$iSchoolID.'_pay_deposit',
					'dependency_visibility' => [
						'db_column' => 'school_settings_'.$iSchoolID.'_payment_provider',
						'on_values' => array_keys($aPaymentProviderOptions)
					]
				]));

			}

				$aTemplates = Ext_Thebing_Pdf_Template_Search::s('document_invoice_customer', $this->oWDBasic->default_language, $iSchoolID, $this->oWDBasic->getInbox()->id, true);
				$aTemplatesEnquiry = Ext_Thebing_Pdf_Template_Search::s('document_offer_customer', $this->oWDBasic->default_language, $iSchoolID, null, true);

				$bRequired = true;
				if(
					$this->oWDBasic->type === Ext_Thebing_Form::TYPE_ENQUIRY &&
					!Ext_Thebing_Access::hasLicenceRight('thebing_students_contact_gui')
				) {
					// Auf nicht Pflicht setzen, sonst beschwert sich der Dialog trotz ausgeblendetem Tab
					$aTemplates = [];
					$bRequired = false;
				}

				$oPricesTab->setElement(
					$oDialogData->createRow(
						L10N::t('Template', self::$sDescription),
						'select',
						array(
							'db_column' => 'school_settings_'.$iSchoolID.'_tpl_id',
							'select_options' => Util::addEmptyItem($this->oWDBasic->type === Ext_Thebing_Form::TYPE_ENQUIRY ? $aTemplatesEnquiry : $aTemplates),
							'required' => $bRequired
						)
					)
				);

			if ($this->oWDBasic->isCreatingBooking()) {

				if (Ext_Thebing_Access::hasLicenceRight('thebing_students_contact_gui')) {
					$oPricesTab->setElement($oDialogData->createRow($this->t('Template (Anfrage)'), 'select', [
						'db_column' => 'school_settings_'.$iSchoolID.'_offer_template_id',
						'select_options' => Util::addEmptyItem($aTemplatesEnquiry),
						// 'required' => $bRequired,
						'dependency_visibility' => [
							'db_column' => 'type',
							'on_values' => [Ext_Thebing_Form::TYPE_REGISTRATION_V3]
						]
					]));
				}

//				$oPricesTab->setElement(
//					$oDiv = $oDialogData->createRow(
//						L10N::t('Verfügbare Währungen', self::$sDescription),
//						'select',
//						array(
//							'db_column' => 'currencies_'.$iSchoolID,
//							'multiple' => 3,
//							'select_options' => $oSchool->getSchoolCurrencyList(),
//							'jquery_multiple' => 1,
//							'searchable' => 1,
//							'required' => $bRequired
//						)
//					)
//				);

				$oPricesTab->setElement($oDialogData->createRow($this->t('Zahlungsbedingung'), 'select', [
					'db_column' => 'school_settings_'.$iSchoolID.'_payment_condition_id',
					'select_options' => Util::addEmptyItem(\Ext_TS_Payment_Condition::getSelectOptions(), $this->t('Standard der Schule'))
				]));

			}

			$oPricesTab->setElement($oDialogData->createRow($this->t('Schülerstatus'), 'select', [
				'db_column' => 'school_settings_'.$iSchoolID.'_student_status_id',
				'select_options' => Util::addEmptyItem(Ext_Thebing_Marketing_StudentStatus::getList(true, $iSchoolID))
			]));

		}

		if($iSelectedId <= 0) {

			$oDialogData->aElements = array($oFirst);

		} else {

			$oDialogData->setElement($oTranslationsTab);
//			$oDialogData->setElement($oNotifyTab);
			$oDialogData->setElement($oPricesTab);
			$oDialogData->setElement($oLast);

			$aElements[] = $oTranslationsTab;
//			$aElements[] = $oNotifyTab;
			$aElements[] = $oPricesTab;
			$aElements[] = $oLast;

			if (
				$this->oWDBasic->isCreatingBooking() &&
				$this->oWDBasic->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3
			) {
				$oFactory = new Ext_Gui2_Factory('TsFrontend_booking_template');
				$oGui = $oFactory->createGui('dialog', $this->_oGui);
				$oTab = $oDialogData->createTab($this->t('Buchungsvorlagen'));
				$oTab->setElement($oGui);
				$oDialogData->setElement($oTab);
				$aElements[] = $oTab;
			}

			$oDialogData->aElements = $aElements;

		}

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds);

		return $aData;

	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		global $_VARS;

		$aTransfer = array();
		$aSelectedIds = (array)$aSelectedIds;
		$iFormID = (int)reset($aSelectedIds);

		DB::begin(__METHOD__);

		switch($sAction) {

			case 'edit_page':

				$oForm = Ext_Thebing_Form::getInstance($iFormID);
				$oPage = Ext_Thebing_Form_Page::getInstance((int)$aData['page_id']);
				$oPage->form_id = $iFormID;
				$sDefault = '';
				unset($aData['page_id']);

				foreach((array)$aData as $sKey => $sValue) {

					if(trim($sValue) == '') {
						$sValue = '...';
					}

					$aTemp = explode('_', $sKey, 2);

					if($aTemp[1] == $oForm->default_language) {
						$sDefault = $sValue;
					}

					$oPage->$sKey = $sValue;

				}

				$aErrors = $oPage->validate();

				if ($aErrors === true) {
					$oPage->save();
				}

				$_VARS['page_id'] = $oPage->id;

				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit_page', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'page_';
				$aTransfer['success_message'] = L10N::t('Erfolgreich gespeichert.', $this->_oGui->gui_description);
				$aTransfer['pages_tab_title'] = $sDefault;
				$aTransfer['parent_form_id'] = $iFormID;
				$aTransfer['active_tab'] = $oPage->id;
				$aTransfer['error'] = $this->getErrorData($aErrors, $sAction, 'error');

				break;

			case 'remove_page':

				$oPage = Ext_Thebing_Form_Page::getInstance((int)$_VARS['page_id']);
				$mCheck = $this->_checkPlausibility($sAction, $oPage);

				if($mCheck === true) {
					$oPage->delete();
				}

				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'ID_';
				$aTransfer['error'] = array();
				$aTransfer['plausibility'] = $mCheck;

				break;

			case 'sort_pages':

				$mCheck = $this->_checkPlausibility($sAction, $_VARS['pages_tab']);

				if($mCheck === true) {
					foreach((array)$_VARS['pages_tab'] as $iKey => $iPageID) {
						$oPage = Ext_Thebing_Form_Page::getInstance((int)$iPageID);
						$oPage->position = $iKey + 1;
						$oPage->save(true, false);
					}
				}

				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'ID_';
				$aTransfer['active_tab'] = $_VARS['active_tab'];
				$aTransfer['error'] = array();
				$aTransfer['plausibility'] = $mCheck;

				break;

			case 'edit_block':

				$aErrors = array();
				$oForm = Ext_Thebing_Form::getInstance($iFormID);
				$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$_VARS['save']['id']);
				$mCheck = true;
				$aBlockKeys = [];

				if($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COLUMNS) {
					$aNumbers = $oBlock->set_numbers;
				}

				foreach((array)$_VARS['save'] as $sKey => $mValue) {

					if($sKey == 'id') {
						continue;
					}

					if(
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COLUMNS &&
						$sKey == 'set_numbers'
					) {

						if ($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {

							$bError = collect($mValue)->some(function ($iValue) {
								return !$iValue || $iValue < 1 || $iValue > 12;
							});

							// Versteht offensichtlich kein Benutzer ohne harte Validierung
							if ($bError) {
								$aErrors[] = sprintf($this->t('Nur Werte zwischen %d bis %d sind gültig.'), 1, 12);
							}

						} else {

							$iColsBlockWidth = array_sum($mValue);

							if($iColsBlockWidth > 100) {
								$sError = L10N::t('Bitte insgesamt nicht mehr als 100%% eingeben. %d%% eingegeben.', $this->_oGui->gui_description);
								$sError = sprintf($sError, $iColsBlockWidth);
								$aErrors[] = $sError;
							}

						}

					}

					$oBlock->$sKey = $mValue;
					$aBlockKeys[] = str_replace('set_', '', $sKey);

				}

				$oBlock->clearSettings($aBlockKeys);

				/*
				 * In den Einstellungen des Transfer-Blocks gab es früher eine allgemeine Checkbox um gültige
				 * Anreiseorte auszuwählen (Option "set_transfer_*_<SchilID>_<TransferID>"). Diese Checkbox musste
				 * aber immer aktiviert sein da sonst Transfer-Kombinationen, auch wenn sie aktiviert waren, nicht
				 * ausgewählt werden konnten.
				 *
				 * Die Checkbox wird nicht mehr angezeigt und die Option hier aktiviert bzw. deaktiviert je nachdem
				 * ob Überhaupt eine Transfer-Kombination ausgewählt ist oder nicht.
				 */
				if($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS) {
					$oForm = $oBlock->getPage()->getForm();
					foreach((array)$oForm->schools as $iSchoolID) {
						$oSchool = Ext_Thebing_School::getInstance($iSchoolID);
						$aTempArr = $oSchool->getTransferLocationsForInquiry('arrival', 0);
						$aTempDep = $oSchool->getTransferLocationsForInquiry('departure', 0);
						unset($aTempArr[0]);
						unset($aTempDep[0]);
						asort($aTempArr);
						asort($aTempDep);
						foreach(array_keys((array)$aTempArr) as $sTransferID) {
							$sTransferGroupKey = 'set_transfer_arr_'.$iSchoolID.'_'.$sTransferID;
							$bTransferGroupValue = false;
							foreach(array_keys((array)$aTempDep) as $sTransferDepID) {
								$sTransferCombinationKey = 'set_transfer_arr_'.$iSchoolID.'_'.$sTransferID.'_to_'.$sTransferDepID;
								if($oBlock->$sTransferCombinationKey) {
									$bTransferGroupValue = true;
								}
							}
							$oBlock->$sTransferGroupKey = $bTransferGroupValue;
						}
						foreach(array_keys((array)$aTempDep) as $sTransferID) {
							$sTransferGroupKey = 'set_transfer_dep_'.$iSchoolID.'_'.$sTransferID;
							$bTransferGroupValue = false;
							foreach(array_keys((array)$aTempArr) as $sTransferArrID) {
								$sTransferCombinationKey = 'set_transfer_dep_'.$iSchoolID.'_'.$sTransferID.'_to_'.$sTransferArrID;
								if($oBlock->$sTransferCombinationKey) {
									$bTransferGroupValue = true;
								}
							}
							$oBlock->$sTransferGroupKey = $bTransferGroupValue;
						}
					}
				}

				if(
					$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COLUMNS ||
					$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
					$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS
				) {

					$mCheck = $this->_checkPlausibility($sAction, $oBlock);

					if($mCheck === -1) {
						$oBlock->set_numbers = $aNumbers;
						$sError = L10N::t('Die Anzahl der Spalten kann nicht verringert werden, da manche Spalten nicht leer sind.', $this->_oGui->gui_description);
						$aErrors[] = $sError;
					}

				}

				if(
					$oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 && (
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INSURANCES
					)
				) {
					$aBlocks = $oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
						return $oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL;
					});

					if(!empty($aBlocks)) {
						$aErrors[] = $this->t('Blöcke für Leistungen und eine Auswahl der Schule können nicht miteinander kombiniert werden.');
					}
				}

				if(
					empty($oBlock->set_type) && (
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INPUT ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_SELECT ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_DATE ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_UPLOAD ||
						$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA
					)
				) {
					$sError = L10N::t('Bitte wählen Sie das Feld aus.', $this->_oGui->gui_description);
					$aErrors[] = $sError;
				}

				if(
					$oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 &&
					$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL
				) {
					$aFixedBlocks = $oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) {
						return
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS ||
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INSURANCES;
					});

					if(!empty($aFixedBlocks)) {
						$aErrors[] = $this->t('Blöcke für Leistungen und eine Auswahl der Schule können nicht miteinander kombiniert werden.');
					}
				}

				if(
					$bSave &&
					empty($aErrors) &&
					$mCheck === true
				) {
					$oBlock->save();
				}

				if($oBlock->parent_id > 0) {
					$_VARS['parent_id'] = 'form_pages_content_block_'.$oBlock->parent_id.'_'.$oBlock->parent_area;
				} else {
					$_VARS['parent_id'] = 'form_pages_content_'.$oBlock->page_id;
				}

				$_VARS['block_id'] = $oBlock->id;
				$_VARS['block_key'] = $oBlock->block_id;

				// Fehlerüberschrift
				if(!empty($aErrors)){
					array_unshift($aErrors, $this->_oGui->t('Fehler beim Speichern'));
				}
				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit_block', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'block_';
				$aTransfer['success_message'] = L10N::t('Erfolgreich gespeichert.', $this->_oGui->gui_description);
				$aTransfer['parent_block_id'] = $oBlock->parent_id;
				$aTransfer['active_tab'] = $oBlock->page_id;
				$aTransfer['parent_form_id'] = $iFormID;
				$aTransfer['error'] = $aErrors;
				$aTransfer['plausibility'] = $mCheck;

				break;

			case 'move_block':

				DB::begin(__METHOD__);

				$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$this->request->input('save.id'));
				$oPage = Ext_Thebing_Form_Page::getInstance((int)$this->request->input('save.page_id'));

				$oBlock->moveToPage($oPage);

				DB::commit(__METHOD__);

				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'ID_';
				$aTransfer['active_tab'] = $oBlock->page_id;
				$aTransfer['error'] = [];

				break;

			case 'remove_block':

				$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$_VARS['block_id']);
				$mCheck = $this->_checkPlausibility($sAction, $oBlock);

				if($mCheck === true) {
					$oBlock->active = 0;
					$oBlock->save();
				}

				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'ID_';
				$aTransfer['active_tab'] = $oBlock->page_id;
				$aTransfer['error'] = array();
				$aTransfer['plausibility'] = $mCheck;

				break;

			case 'sort_blocks':

				if(strpos($_VARS['parent_id'], 'form_pages_content_block_') !== false) {
					$sTemp = str_replace('form_pages_content_block_', '', $_VARS['parent_id']);
					$aTemp = explode('_', $sTemp);
					$iParentID = $aTemp[0];
					$iParentArea = $aTemp[1];
				} elseif(strpos($_VARS['parent_id'], 'form_pages_content_') !== false) {
					$iParentID = 0;
					$iParentArea = 0;
				}

				$aParams = array(
					'form_id' => reset($_VARS['id']),
					'sort' => $_VARS['sort'],
					'parent_id' => $iParentID,
					'parent_area' => $iParentArea,
					'element_id' => $_VARS['element_id']
				);

				$mCheck = $this->_checkPlausibility($sAction, $aParams);

				if($mCheck === true) {
					foreach((array)$_VARS['sort'] as $iKey => $iBlockID) {
						$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$iBlockID);
						$oBlock->position = $iKey + 1;
						$oBlock->parent_id = $iParentID;
						$oBlock->parent_area = $iParentArea;
						$oBlock->save(true, false);
					}
				}

				$aTransferData = $this->_oGui->getDataObject()->prepareOpenDialog('edit', $aSelectedIds);
				$aTransfer['data'] = $aTransferData;
				$aTransfer['action'] = 'saveDialogCallback';
				$aTransfer['dialog_id_tag'] = 'ID_';
				$aTransfer['active_tab'] = $_VARS['active_tab'];
				$aTransfer['error'] = array();
				$aTransfer['plausibility'] = $mCheck;

				break;

			default:
				$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		}

		if(empty($aTransfer['error'])) {

			if(
				isset($aTransfer['plausibility']) &&
				$aTransfer['plausibility'] !== true
			) {

				$sError = L10N::t('Fehler! Bitte beachten sie die Reihenfolge der Module: Kurse -> Unterkunft -> Transfer.', $this->_oGui->gui_description);
				$aTransfer['error'][] = $sError;
				$aTransfer['plausibility_error'] = true;
				$aTransfer['parent_form_id'] = $iFormID;

			}

		}

		if(empty($aTransfer['error'])) {
			DB::commit(__METHOD__);
		} else {
			DB::rollback(__METHOD__);
		}

		return $aTransfer;

	}

	/**
	 * Get the block contents
	 *
	 * @param int $iPageID
	 * @param int $iParentID
	 * @param array $aTranslations
	 * @return array
	 */
	protected function _getContents($iPageID, $iParentID, $aTranslations, $iLevel = false) {

		$aCode = array();
		$aBlocks = self::getParentBlocks($iPageID, $iParentID);

		if($iLevel === false) {
			$oSmarty = new SmartyWrapper();
			$oSmarty->assign('aTranslations', $aTranslations);
			$oSmarty->assign('sRemoveIconPath', Ext_Thebing_Util::getIcon('delete'));
			$oSmarty->assign('sEditIconPath', Ext_Thebing_Util::getIcon('edit'));
			$oSmarty->assign('sMoveIconPath', 'fa-arrows-h');
		}

		foreach((array)$aBlocks as $aBlock) {

			$aSubCode = $this->_getContents($iPageID, $aBlock['block_id'], $aTranslations, $iLevel);
			$oBlock = Ext_Thebing_Form_Page_Block::getInstance((int)$aBlock['block_id']);

			$cFormatDependencies = function () use ($oBlock) {
				$sHtml = '';
				if ($oBlock->getSetting('dependency_type')) {
					$sHtml .= '<hr>'.$this->t('Abhängigkeit von:').' ';
					if ($oBlock->getSetting('dependency_field') === 'any') {
						$sHtml .= collect(self::getBlocks())->firstWhere('key', $oBlock->getSetting('dependency_type'))['title'];
					} else {
						$oBlock2 = Ext_Thebing_Form_Page_Block::getInstance($oBlock->getSetting('dependency_field'));
						$sHtml .= '<em>'.e($oBlock2->getTitle()).'</em>';
					}
				}
				return $sHtml;
			};

			$aContent = array(
				'parent_id' => $aBlock['parent_id'],
				'parent_area' => $aBlock['parent_area'],
				'page_id' => $iPageID,
				'block_id' => $aBlock['block_id'],
				'block_key' => $aBlock['block_key'],
				'position' => $aBlock['position'],
				'content' => $aSubCode
			);

			if($iLevel === false) {

				$aContent['settings'] = $oBlock->getSettings();

				switch((int)$aBlock['block_key']) {

					case Ext_Thebing_Form_Page_Block::TYPE_COLUMNS:

						$sTemplate = 'form_columns';
						$aContent['title'] = L10N::t('Mehrspaltige Bereich', self::$sDescription);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_HEADLINE2:
					case Ext_Thebing_Form_Page_Block::TYPE_HEADLINE3:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$sKey = 'title_'.$oForm->default_language;
						$iH = (-1 * $aBlock['block_key']);
						$sTemplate = 'form_h';
						$aContent['title'] = L10N::t('Überschrift H'.$iH, self::$sDescription);
						$aContent['content'] = $oBlock->$sKey;
						$aContent['dependency'] = $cFormatDependencies();
						$oSmarty->assign('iH', $iH);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_STATIC_TEXT:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$sKey = 'text_'.$oForm->default_language;
						$sTemplate = 'form_text';
						$aContent['title'] = L10N::t('Textbereich', self::$sDescription);
						$aContent['content'] = $oBlock->$sKey;
						$aContent['dependency'] = $cFormatDependencies();

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_DOWNLOAD:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$sKeyTitle = 'title_'.$oForm->default_language;
						$sKeyFile = 'set_file_'.$oForm->default_language;
						$sTemplate = 'form_download';
						$aContent['title'] = L10N::t('Download', self::$sDescription);

						if($oBlock->required) {
							$aContent['title'] .= ' *';
						}

						$oFile = Ext_Thebing_Upload_File::getInstance($oBlock->$sKeyFile);
						$sFile = str_replace(\Util::getDocumentRoot(), '', $oFile->getPath());

						$aContent['content']	= array(
							'title' => $oBlock->$sKeyTitle,
							'file' => $sFile,
							'required' => $oBlock->required
						);

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_INPUT:
					case Ext_Thebing_Form_Page_Block::TYPE_SELECT:
					case Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT:
					case Ext_Thebing_Form_Page_Block::TYPE_DATE:
					case Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX:
					case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
					case Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA:
					case Ext_Thebing_Form_Page_Block::TYPE_YESNO:

						$oPage = Ext_Thebing_Form_Page::getInstance($oBlock->page_id);
						$oForm = Ext_Thebing_Form::getInstance($oPage->form_id);
						$sTemplate = 'form_input'; // form_input.tpl

						$aContent['title'] = collect($this->getBlocks())->firstWhere('key', $aBlock['block_key'])['title'];
						$aContent['dependency'] = $cFormatDependencies();

						if($oBlock->required) {
							$aContent['title'] .= ' *';
						}

						$aContent['content']	= array(
							'title' => $oBlock->getTitle($oForm->default_language),
							'required' => $oBlock->required
						);

						if ($oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_YESNO) {
							$aContent['select_as_radio'] = !!$oBlock->getSetting('select_as_radio');
						}

						$oSmarty->assign('sCalendarIconPath', Ext_Thebing_Util::getIcon('calendar'));

						break;

					case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
					case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
					case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:
					case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
					case Ext_Thebing_Form_Page_Block::TYPE_PRICES:
					case Ext_Thebing_Form_Page_Block::TYPE_FEES:
					case Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS:
					case Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS:
					case Ext_Thebing_Form_Page_Block::TYPE_HORIZONTAL_RULE:
					case Ext_Thebing_Form_Page_Block::TYPE_PAYMENT:
					case Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY:

						$sTemplate	= 'form_block'; // form_block.tpl

						$aContent['title'] = collect($this->getBlocks())->firstWhere('key', $aBlock['block_key'])['title'];
						$aContent['content'] = '';
						$aContent['dependency'] = $cFormatDependencies();

						if($oBlock->required) {
							$aContent['title'] .= ' *';
						}

						if($oBlock->isFixedBlock()) {
							$aContent['fixed'] = $oBlock->block_id;
						}

						if (
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS ||
							$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_FEES
						) {
							$aContent['content'] = '<em>'.collect($oBlock->getServiceBlockServices())->map(function ($oService) use ($oBlock) {
								switch(true) {
									case $oService instanceof Ext_Thebing_Tuition_Course:
										return $oService->getShortName();
									case $oService instanceof \TsAccommodation\Dto\AccommodationCombination:
										return $oService->buildLabel(System::getInterfaceLanguage());
									case $oService instanceof Ext_Thebing_School_Additionalcost:
										return $oService->getName();
									default:
										//throw new InvalidArgumentException('Unknown service');
										return (string)$oService;
								}
							})->sort()->join(', ').'</em>';
						}

						$aContent['dependency'] = $cFormatDependencies();

						break;

				}

				$oSmarty->assign('aContent', $aContent);
				$aCode[$aBlock['parent_area']][] = $oSmarty->fetch(self::getTemplatePath().$sTemplate.'.tpl');

			} else {

				$aCode[$aBlock['parent_area']][] = $aContent;

			}

		}

		return $aCode;

	}

	/**
	 * Alle verfügbaren Felder des Formulars inkl. Labels
	 *
	 * @param Ext_Thebing_Form $oForm
	 * @param \Tc\Service\Language\Frontend $oFrontendLanguage
	 * @return \Illuminate\Support\Collection
	 */
	protected function getDefaultFields(Ext_Thebing_Form $oForm, \Tc\Service\Language\Frontend $oFrontendLanguage, Ext_Thebing_Form_Page $oPage = null) {

		$oFieldsGenerator = new \TsRegistrationForm\Generator\FormFieldsGenerator();
		$oFieldsGenerator->setBackendLanguage(self::getLanguageObject());
		$oFieldsGenerator->setFrontendLanguage($oFrontendLanguage);
		$aFields = $oFieldsGenerator->generate();

		if (
			$oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY || (
				$oPage !== null &&
				$oPage->type === 'enquiry'
			)
		) {
			$aFields = $aFields->filter(function (array $aField) {
				return (
					in_array($aField['mapping'][0], ['tc_c', 'tc_cd', 'tc_a_c', 'tc_a_b', 'tc_e']) ||
					in_array($aField['mapping'][1], ['referer_id', 'profession', 'social_security_number', 'promotion', 'school_id']) ||
					strpos($aField['mapping'][1], 'enquiry_') !== false ||
					$aField['mapping'][0] === 'flex' && in_array($aField['usage'], ['enquiry', 'enquiry_booking'])
				);
			});
		} else {
			$aFields = $aFields->reject(function (array $aField) {
				return $aField['mapping'][0] === 'flex' && !in_array($aField['usage'], ['booking', 'enquiry_booking']);
			});
		}

		// Im RegForm V2 darf es das Feld nicht geben
		if ($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_NEW) {
			$aFields = $aFields->reject(function (array $aField) {
				return $aField['key'] === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL;
			});
		}

		return $aFields;

	}

	/**
	 * Get the list of dropped fields by block key
	 * 
	 * @param int $iBlock
	 * @param object $oForm
	 * @return array
	 */
	protected function _getUsedFields($iBlock, $oForm) {

		$sSQL = "
			SELECT
				`kfpbs`.`value`
			FROM
				`kolumbus_forms` `kf`
			INNER JOIN
				`kolumbus_forms_pages` `kfp`
			ON
				`kf`.`id` = `kfp`.`form_id`
			INNER JOIN
				`kolumbus_forms_pages_blocks` `kfpb`
			ON
				`kfp`.`id` = `kfpb`.`page_id`
			INNER JOIN
				`kolumbus_forms_pages_blocks_settings` `kfpbs`
			ON
				`kfpb`.`id` = `kfpbs`.`block_id`
			WHERE
				`kf`.`active` = 1 AND
				`kfp`.`active` = 1 AND
				`kfpb`.`active` = 1 AND
				`kfpb`.`block_id` <= -6 AND
				`kfpb`.`block_id` >= -11 AND
				`kfpbs`.`setting` = 'type' AND
				`kf`.`id` = :iFormID AND
				`kfpb`.`block_id` = :iBlockID
		";
		$aSQL = array(
			'iFormID' => $oForm->id,
			'iBlockID' => $iBlock
		);
		$aFields = DB::getQueryCol($sSQL, $aSQL);

		if(empty($aFields)) {
			$aFields = array();
		}

		return $aFields;

	}

	/**
	 * Write the HTML code for the form dialog tab
	 * 
	 * @param array &$aData
	 * @param array $aSelectedIds
	 * @return string
	 */
	protected function _writeFormTabHTML(&$aData, $aSelectedIds) {

		$this->_getWDBasicObject($aSelectedIds);

		if(!is_array($aSelectedIds)) {
			$aSelectedIds = array();
		}

		$iFormID = (int)reset($aSelectedIds);
		$oForm = Ext_Thebing_Form::getInstance($iFormID);

		$aTranslations = array(
			'remove' => L10N::t('Entfernen', $this->_oGui->gui_description),
			'edit' => L10N::t('Bearbeiten', $this->_oGui->gui_description),
			'move' => L10N::t('Verschieben', $this->_oGui->gui_description),
			'remove_page' => L10N::t('Seite entfernen', $this->_oGui->gui_description),
			'edit_page' => L10N::t('Seite bearbeiten', $this->_oGui->gui_description),
			'add_page' => L10N::t('Seite hinzufügen', $this->_oGui->gui_description),
			'remove_message' => L10N::t('Möchten Sie dieses Element wirklich entfernen?', $this->_oGui->gui_description),
			'area_block_message' => L10N::t('Möchten Sie wirklich die Anzahl der Spalten verringern?', $this->_oGui->gui_description),
			'info' => L10N::t('Hinweis', $this->_oGui->gui_description),
			'info_data' => L10N::t('Bleibt das Formular nach dem Bearbeiten eines Elementes leer, speichern Sie bitte das gesamte Formular erneut.', $this->_oGui->gui_description).'<br />'
			             . L10N::t('Wenn die Formularart geändert wurde muss das Formular erst gespeichert werden, ansonsten sind unter Umständen nicht die korrekten Felder auswählbar.', $this->_oGui->gui_description),
			'missing_blocks' => $this->t('Für die korrekte Funktionsweise des Formulars müssen folgende Felder im Formular vorhanden sein und als Pflichtfeld markiert sein:')
		);

		$aData['form_translations'] = $aTranslations;

		$oForm->checkPages();
		$aPages = array_map(fn(Ext_Thebing_Form_Page $oPage) => $oPage->getData(), $oForm->getPages());
		$aContents = array();

		foreach((array)$aPages as $iPageKey => $aPage) {
			$oPage = Ext_Thebing_Form_Page::getInstance($aPage['id']);
			foreach((array)$oForm->languages as $sLanguage) {
				$sLanguageKey = 'title_'.$sLanguage;
				$aPages[$iPageKey][$sLanguageKey] = $oPage->$sLanguageKey;
				if($sLanguage == $oForm->default_language) {
					$aPages[$iPageKey]['title'] = $oPage->$sLanguageKey;
				}
			}
			$aContents[$oPage->id] = $this->_getContents($oPage->id, 0, $aTranslations);
		}

		$aBlocks = collect(self::getBlocks());

		$aBlocks = $aBlocks->filter(function(array $aBlockData) use ($oForm) {

			switch($aBlockData['key']) {
				case Ext_Thebing_Form_Page_Block::TYPE_UPLOAD:
					if($oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY) {
						return false;
					}
					break;
				case Ext_Thebing_Form_Page_Block::TYPE_NAV_STEPS:
				case Ext_Thebing_Form_Page_Block::TYPE_NAV_BUTTONS:
				case Ext_Thebing_Form_Page_Block::TYPE_HORIZONTAL_RULE:
				case Ext_Thebing_Form_Page_Block::TYPE_PAYMENT:
				case Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY:
					if($oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
						return false;
					}
					break;
			}

			return true;

		});

		$aBlocks = $aBlocks->map(function(array $aBlockData) use ($oForm) {
			if (
				$aBlockData['key'] != Ext_Thebing_Form_Page_Block::TYPE_PRICES &&
				$aBlockData['key'] > 0 && (
					$oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 || (
						$aBlockData['key'] != Ext_Thebing_Form_Page_Block::TYPE_COURSES &&
						$aBlockData['key'] != Ext_Thebing_Form_Page_Block::TYPE_FEES
					)
				)
			) {
				$aBlockData['fixed'] = $aBlockData['key'];
			}

			// Leistungsblöcke im Anfrageformular nur ausgegraut anzeigen wenn das Recht nicht gesetzt ist (Ticket #8984)
			if (
				$oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY &&
				!Ext_Thebing_Access::hasRight('thebing_students_contact_gui') &&
				$aBlockData['key'] > 0
			) {
				$aBlockData['disabled'] = true;
			}
			return $aBlockData;
		});

		$aErrors = [];
		$oPaymentBlock = $oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_PAYMENT, false);
		if (!empty($oPaymentBlock)) {
			/*$aBlocksWithPayment = $oForm->getFilteredBlocks(function (Ext_Thebing_Form_Page_Block $oBlock) use ($oPaymentBlock) {
				return $oBlock->page_id === $oPaymentBlock->page_id && (
					in_array($oBlock->block_id, Ext_Thebing_Form_Page_Block::TYPES_SERVICES) ||
					(
						$oBlock->block_id < 0 &&
						in_array($oBlock->getSetting('type'), Ext_Thebing_Form_Page_Block::SUBTYPES_ADDRESS)
					)
				);
			});
			if (!empty($aBlocksWithPayment)) {
				$aErrors[] = $this->t('Der Block für Zahlungen darf weder mit Leistungen noch Adressdaten auf der gleichen Seite verwendet werden.');
			}*/

			if ($oPaymentBlock->page_id != \Illuminate\Support\Arr::last($aPages)['id']) {
				$aErrors[] = $this->t('Der Block für Zahlungen muss auf der letzten Seite sein.');
			}
		}

		if ($oForm->hasInvalidBlocksForEditPurpose()) {
			$aErrors[] = $this->t('Leistungsblöcke funktionieren bisher nicht korrekt beim Aktualisieren von Daten und dürfen nicht verwendet werden.');
		}

		$oSmarty = new SmartyWrapper();
		$oSmarty->assign('iFormID', $iFormID);
		$oSmarty->assign('sDefaultLanguage', $oForm->default_language);
		$oSmarty->assign('aPages', $aPages);
		$oSmarty->assign('aBlocks', $aBlocks);
		$oSmarty->assign('aContents', $aContents);
		$oSmarty->assign('sRemoveIconPath', Ext_Thebing_Util::getIcon('delete'));
		$oSmarty->assign('sEditIconPath', Ext_Thebing_Util::getIcon('edit'));
		$oSmarty->assign('sAddIconPath', Ext_Thebing_Util::getIcon('add'));
		$oSmarty->assign('sMoveIconPath', 'fa-arrows-h');
		$oSmarty->assign('aTranslations', $aTranslations);
		$oSmarty->assign('aErrors', $aErrors);
		$oSmarty->assign('aBlockDependency', json_encode(array_keys(\Ext_Thebing_Form_Page::BLOCK_DEPENDENCIES['booking'])));

		$sCode = $oSmarty->fetch(self::getTemplatePath().'form.tpl'); // system/legacy/admin/extensions/thebing/admin/smarty/form.tpl
		$sCode = str_replace(array("\n", "\t"), '', $sCode);

		return $sCode;

	}

	/**
	 * Prüfen, ob Pflicht-Blöcke auch im Formular drin sind
	 *
	 * @param Ext_Thebing_Form $oForm
	 * @return array
	 */
	/*private function checkMissingBlocks(Ext_Thebing_Form $oForm) {

		if ($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
			return [];
		}

		$oFrontendLanguage = new \Tc\Service\Language\Frontend($oForm->default_language);
		$oFrontendLanguage->setContext(\TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT);

		$aMissingBlocks = [];
		$aFieldLabels = $this->getDefaultFields($oForm, $oFrontendLanguage)->mapWithKeys(function(array $aField) {
			return [$aField['key'] => $aField['backend_label']];
		});

		$aNeededBlocks = [
			Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME,
			Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME,
			Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_CONTACT_EMAIL
		];

		// Pflichtfelder vom SR ergänzen
		if(
			$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_NEW ||
			$oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3
		) {
			$bGenerateInvoice = false;
			foreach($oForm->getSelectedSchools() as $oSchool) {
				if($oForm->getSchoolSetting($oSchool, 'generate_invoice')) {
					$bGenerateInvoice = true;
					break;
				}
			}

			if($bGenerateInvoice) {
				$aNeededBlocks[] = Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SEX;
				$aNeededBlocks[] = Ext_Thebing_Form_Page_Block::SUBTYPE_DATE_BIRTHDATE;
				$aNeededBlocks[] = Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_NATIONALITY;
				$aNeededBlocks[] = Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_MOTHERTONGE;
			}
		}

		$aFilteredBlocks = $oForm->getFilteredBlocks(function(Ext_Thebing_Form_Page_Block $oBlock) use($aNeededBlocks) {
			return in_array($oBlock->set_type, $aNeededBlocks) && $oBlock->required;
		});

		$aFilteredBlocks = array_map(function(Ext_Thebing_Form_Page_Block $oBlock) {
			return $oBlock->set_type;
		}, $aFilteredBlocks);


		foreach($aNeededBlocks as $sBlockSet) {
			if(!in_array($sBlockSet, $aFilteredBlocks)) {
				$aMissingBlocks[$sBlockSet] = $aFieldLabels[$sBlockSet];
			}
		}

		// Da Name »oder« ist, anders benennen
		if(
			$oForm->type === Ext_Thebing_Form::TYPE_ENQUIRY && (
				isset($aMissingBlocks[Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME]) ||
				isset($aMissingBlocks[Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME])
			)
		) {
			unset($aMissingBlocks[Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_FIRSTNAME]);
			unset($aMissingBlocks[Ext_Thebing_Form_Page_Block::SUBTYPE_INPUT_LASTNAME]);
			array_unshift($aMissingBlocks, $this->t('Vorname oder Nachname'));
		}

		return $aMissingBlocks;

	}*/

	/**
	 * @param array $aData
	 * @param array $aLanguages
	 * @param Ext_Thebing_Form $oForm
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param Ext_Thebing_Form_Page_Block $oBlock
	 * @return array
	 */
	protected function generateInfotextTab($aData, $aLanguages, Ext_Thebing_Form $oForm, Ext_Gui2_Dialog $oDialogData, Ext_Thebing_Form_Page_Block $oBlock) {

		if($oForm->type === Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
			return $aData;
		}

		if(
			$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_COURSES ||
			$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS ||
			$oBlock->block_id == Ext_Thebing_Form_Page_Block::TYPE_INSURANCES
		) {
			$aElements = [
				'startdate' => L10N::t('%s: Startdatum', $this->_oGui->gui_description),
				'enddate' => L10N::t('%s: Enddatum', $this->_oGui->gui_description),
				'duration' => L10N::t('%s: Wochen', $this->_oGui->gui_description),
			];
		}

		switch($oBlock->block_id) {
			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
				$aElements['courses'] = L10N::t('%s: Kurse', $this->_oGui->gui_description);
				$aElements['lessons'] = L10N::t('%s: Einheiten', $this->_oGui->gui_description);
				$aElements['level'] = L10N::t('%s: Niveau', $this->_oGui->gui_description);
//				$aElements['modules'] = L10N::t('%s: Module', $this->_oGui->gui_description);
//				$aElements['module'] = L10N::t('%s: Modul', $this->_oGui->gui_description);
//				$aElements['no-modules'] = L10N::t('%s: Kein Modul', $this->_oGui->gui_description);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
				$aElements['accommodations'] = L10N::t('%s: Unterkünfte', $this->_oGui->gui_description);
				$aElements['roomtype'] = L10N::t('%s: Raumart', $this->_oGui->gui_description);
				$aElements['meal'] = L10N::t('%s: Verpflegung', $this->_oGui->gui_description);
				//$aElements['extra'] = L10N::t('%s: Extranächte', $this->_oGui->gui_description);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:
				$aElements['from'] = L10N::t('%s: Anreiseort', $this->_oGui->gui_description);
				$aElements['to'] = L10N::t('%s: Abreiseort', $this->_oGui->gui_description);
				$aElements['line'] = L10N::t('%s: Fluglinie', $this->_oGui->gui_description);
				$aElements['number'] = L10N::t('%s: Flugnummer', $this->_oGui->gui_description);
				$aElements['date'] = L10N::t('%s: Datum', $this->_oGui->gui_description);
				$aElements['time'] = L10N::t('%s: Zeit', $this->_oGui->gui_description);
				$aElements['comment'] = L10N::t('%s: Kommentar', $this->_oGui->gui_description);
				break;
			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
				$aElements['insurances'] = L10N::t('%s: Versicherungen', $this->_oGui->gui_description);
		}

		$sInfoFieldsHTML = '';

		$iNumberOfTabs = count($aData['tabs']);

		foreach((array)$oForm->languages as $sLanguage) {

			$oH3 = $oDialogData->create('h4');
			$oH3->setElement((string)$aLanguages[$sLanguage]);
			$sInfoFieldsHTML .= $oH3->generateHTML();

			if(!empty($aElements)) {

				foreach($aElements as $sArrayKey => $sValue) {

					$sKey = 'infotext-'.$sArrayKey.'_'.$sLanguage;

					$sLabel = sprintf($sValue, $aLanguages[$sLanguage]);

					$oDiv = $oDialogData->createRow(
						(string)$sLabel,
						'textarea',
						[
							'db_column' => $sKey,
							'value' => $oBlock->$sKey
						]
					);

					$sInfoFieldsHTML .= $oDiv->generateHTML();
				}

			}

		}

		$aData['tabs'][$iNumberOfTabs]['html'] = $sInfoFieldsHTML;
		$aData['tabs'][$iNumberOfTabs]['title'] = L10N::t('Infofelder', self::$sDescription);

		return $aData;

	}

	/**
	 * Liste mit gültigen Formulararten.
	 *
	 * Siehe Ext_Thebing_Form::TYPE_* Konstanten
	 *
	 * @see Ext_Thebing_Form::$type
	 * @return string[]
	 */
	public static function getFormTypes() {

		$aFormTypes = array(
//			Ext_Thebing_Form::TYPE_REGISTRATION_NEW => $this->t('Anmeldeformular V2'),
			Ext_Thebing_Form::TYPE_REGISTRATION_V3 => \L10N::t('Anmeldeformular V3'),
			//Ext_Thebing_Form::TYPE_REGISTRATION => $this->t('Anmeldeformular (alt)'),
//			Ext_Thebing_Form::TYPE_ENQUIRY => $this->t('Anfrageformular V2'),
		);

		return $aFormTypes;

	}

	/**
	 * @inheritdoc
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		switch ($sError) {
			case 'MISSING_TEMPLATE_LANGUAGES':
				return $this->t('Das ausgewählte Proforma-Template enthält nicht alle der im Formular ausgewählten Sprachen.');
			case 'PAGE_TYPES_CANNOT_BE_MIXED':
				return $this->t('Aktuell können keine unterschiedlichen Typen innerhalb eines Formulares verwendet werden.');
			case 'PAGE_HAS_INVALID_BLOCKS':
				return $this->t('Der Typ kann nicht geändert werden, da die Seite Blöcke enthält, die für diesen Typ nicht gültig sind.');
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function deleteRow($iRowId) {

		// Muss wegen saveTranslations() in einer Transaktion passieren
		DB::begin(__METHOD__);

		$mDelete = parent::deleteRow($iRowId);

		DB::commit(__METHOD__);

		return $mDelete;

	}

	/**
	 * Neuer Ansatz für diesen redundanten Translations-Mist, plus Default-Translations
	 *
	 * @param Ext_Thebing_Form_Page_Block $oBlock
	 * @param Ext_Gui2_Dialog $oDialog
	 * @return string
	 * @throws Exception
	 */
	private function generateTranslationFieldsHtml(Ext_Thebing_Form_Page_Block $oBlock, Ext_Gui2_Dialog $oDialog) {

		$sHtml = '';
		$oForm = $oBlock->getPage()->getForm();
		$aLanguages = Ext_Thebing_Data::getSystemLanguages();

		$sHtml .= $oDialog->createNotification(
			$this->t('Achtung'),
			$this->t('Die Felder müssen nicht befüllt werden. Bei nicht befüllten Feldern werden die hinterlegten Frontend-Übersetzungen verwendet.'),
			'info'
		)->generateHTML();

		foreach((array)$oForm->languages as $sLanguage) {

			$oLanguage = new \Tc\Service\Language\Frontend($sLanguage);
			$oLanguage->setContext(\TsRegistrationForm\Generator\CombinationGenerator::FRONTEND_CONTEXT);
			$aTranslations = $oBlock->getTranslationConfig(self::getLanguageObject(), $oLanguage);

			$oH3 = $oDialog->create('h4');
			$oH3->setElement((string)$aLanguages[$sLanguage]);
			$sHtml .= $oH3->generateHtml();

			foreach($aTranslations as $sKey => $aTranslation) {

				if (!empty($aTranslation[2])) {
					$sKey = 'translation_'.$sKey;
				}

				$sKey = $sKey.'_'.$sLanguage;
				$sLabel	= sprintf($aTranslation[0], $aLanguages[$sLanguage]);

				$oDiv = $oDialog->createRow(
					(string)$sLabel,
					'input',
					[
						'db_column' => $sKey,
						'value' => $oBlock->{$sKey},
						'placeholder' => $aTranslation[1]
					]
				);

				$sHtml .= $oDiv->generateHtml();

			}

		}

		return $sHtml;

	}

	/**
	 * @return \Tc\Service\Language\Backend
	 */
	public static function getLanguageObject() {
		if (System::wd()->getInterface() !== 'backend') {
			return null;
		}

		$oBackendLanguage = new \Tc\Service\Language\Backend(\System::getInterfaceLanguage());
		$oBackendLanguage->setContext(self::$sDescription);
		return $oBackendLanguage;
	}

	public function buildDependencyFields(Ext_Thebing_Form $oForm, Ext_Thebing_Form_Page_Block $oBlock, Ext_Gui2_Dialog $oDialog, array &$aEventValues): string {

		$sHtml = '';

		if(
			$oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3 ||
			$oBlock->set_type === Ext_Thebing_Form_Page_Block::SUBTYPE_SELECT_SCHOOL
		) {
			return $sHtml;
		}

		$aBlocks = collect(self::getBlocks());

		$aAvailableFields = [
			Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX,
			Ext_Thebing_Form_Page_Block::TYPE_YESNO,
			Ext_Thebing_Form_Page_Block::TYPE_COURSES,
			Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS
		];

		$aFieldLabels = collect($aAvailableFields)->mapWithKeys(function ($iBlockType) use ($aBlocks) {
			return [$iBlockType => $aBlocks->firstWhere('key', $iBlockType)['title']];
		})->prepend('', '');

		$sHtml .= $oDialog->createRow(
			$this->t('Abhängig von'),
			'select',
			[
				'db_column' => 'set_dependency_type',
				'select_options' => $aFieldLabels,
				'default_value' => $oBlock->getSetting('dependency_type'),
				'disabled' => !$oBlock->exist(), // Dieses ganze _aSettings-Ding geht ohne ID irgendwo verloren, demnach würde sich das Feld immer zurücksezten
				'events' => [
					[
						'event' => 'change',
						'function' => 'reloadDialogTab',
						'parameter' => 'aDialogData.id, 1'
					]
				]
			]
		)->generateHTML();
		$aEventValues[] = ['db_column' => 'set_dependency_type', 'id' => 'saveid[set_dependency_type]'];

		if ($oBlock->getSetting('dependency_type')) {

			$aBlockFields = collect($oForm->getFilteredBlocks(function (Ext_Thebing_Form_Page_Block $oBlock2) use ($oBlock) {
				return $oBlock2->id != $oBlock->id && $oBlock2->block_id == $oBlock->getSetting('dependency_type');
			}))->mapWithKeys(function (Ext_Thebing_Form_Page_Block $oBlock, $iKey) use ($aFieldLabels) {
				$sTitle = $oBlock->getTitle();
				if ($oBlock->isServiceBlock()) {
					$sTitle .= sprintf(' (%s #%d)', $aFieldLabels[$oBlock->block_id], $iKey + 1);
				}
				return [$oBlock->id => $sTitle];
			});

			if ($oBlock->getSetting('dependency_type') == Ext_Thebing_Form_Page_Block::TYPE_COURSES) {
				$aBlockFields->prepend($this->t('Beliebiger Block'), 'any');
			}

			$aBlockFields->prepend('', '');

			$sHtml .= $oDialog->createRow(
				$this->t('Block'),
				'select',
				[
					'db_column' => 'set_dependency_field',
					'select_options' => $aBlockFields,
					'default_value' => $oBlock->getSetting('dependency_field'),
					'required' => true,
					'events' => [
						[
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, 1'
						]
					]
				]
			)->generateHTML();
			$aEventValues[] = ['db_column' => 'set_dependency_field', 'id' => 'saveid[set_dependency_field]'];

		}

		if (
			in_array($oBlock->getSetting('dependency_type'), [Ext_Thebing_Form_Page_Block::TYPE_COURSES, Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS]) &&
			$oBlock->getSetting('dependency_field')
		) {

			$aServices = collect($oForm->getFilteredBlocks(function (Ext_Thebing_Form_Page_Block $oFilterBlock) use ($oBlock) {
				return (
					$oFilterBlock->block_id == $oBlock->getSetting('dependency_type') && (
						$oBlock->getSetting('dependency_field') === 'any' ||
						$oBlock->getSetting('dependency_field') == $oFilterBlock->id
					)
				);
			}))->mapWithKeys(function (Ext_Thebing_Form_Page_Block $oBlock) {
				return collect($oBlock->getServiceBlockServices())->mapWithKeys(function ($oService) {
					switch(true) {
						case $oService instanceof Ext_Thebing_Tuition_Course:
							return [$oService->id => $oService->getSchool()->short.' – '.$oService->getName()];
						case $oService instanceof \TsAccommodation\Dto\AccommodationCombination:
							return [$oService->buildKey() => $oService->buildLabel(System::getInterfaceLanguage())];
						default:
							throw new InvalidArgumentException('Unknown service');
					}
				});
			});

			$sHtml .= $oDialog->createRow(
				$this->t('Leistungen'),
				'select',
				[
					'db_column' => 'set_dependency_services',
					'select_options' => $aServices,
					'default_value' => $oBlock->getSetting('dependency_services'),
					'required' => true,
					'multiple' => 5,
					'jquery_multiple' => true,
					'searchable' => true,
					'style' => 'width: 500px;'
				]
			)->generateHTML();

		}

		return $sHtml;

	}

	private function buildAdditionalServicesHtml(Ext_Thebing_Form $oForm, Ext_Thebing_Form_Page_Block $oBlock, Ext_Gui2_Dialog $oDialog, string $sType): string
    {

		$sHtml = '';

		if ($oForm->type !== Ext_Thebing_Form::TYPE_REGISTRATION_V3) {
			return $sHtml;
		}

		$sHtml .= $oDialog->createNotification($this->t('Info'), $this->t('Die tatsächliche Anzeige der Zusatzleistung hängt von den Einstellungen der Zusatzgebühr ab.'), 'info')->generateHTML();

		foreach ($oForm->getSelectedSchools() as $oSchool) {
			$sHtml .= $oDialog->create('h4')->setElement($oSchool->ext_1)->generateHTML();
			foreach ($oSchool->getAdditionalServices($sType) as $aAdditionalService) {
				$sKey = 'additionalservice_'.$aAdditionalService['id'];
				$sHtml .= $this->generateBlockDialogField($oDialog, $aAdditionalService['name'], $sKey, $oBlock->getSetting($sKey));
			}
		}

		return $sHtml;

	}

	private function generateBlockDialogField(Ext_Gui2_Dialog $oDialog, string $sLabel, string $sField, $mValue = null, array $aOptions = []): string
    {

		$aOptions['db_column'] = $sField !== 'required' ? 'set_'.$sField : $sField;
		if ($mValue) {
			$aOptions['checked'] = 'checked';
		}

		return $oDialog->createRow(
			$this->t($sLabel),
			'checkbox',
			$aOptions
		)->generateHtml();

	}

	static public function getOrderby()
    {
		
		return ['kf.title' => 'ASC'];
	}

	static public function getWhere()
    {
		
		return ['kf.client_id' => \Ext_Thebing_Client::getInstance()->id];
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui)
    {
		
		$oDialog = $oGui->createDialog(
			$oGui->t('Formular "{title}" bearbeiten'),
			$oGui->t('Neues Formular')
		);
		$oDialog->width = 1200;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Tab "Settings"
		$oSchool = Ext_Thebing_School::getInstance();
		$oDataObject = $oGui->getDataObject();
		$oClient = Ext_Thebing_Client::getInstance();
		$aSchools = $oSchool->getArrayList(true, 'short');
		$aLanguages  = Ext_Thebing_Data::getSystemLanguages();
		$oDefaultLanguage = new Ext_Thebing_Gui2_Selection_School_DefaultLanguage();
		$aInboxes = $oClient->getInboxList(true);

		$aFormTypes = $oDataObject->getFormTypes();
		$aFormTypes = Ext_Thebing_Util::addEmptyItem($aFormTypes);

		if(isset($aInboxes[0]['id'])) {
			unset($aInboxes[0]);
		}

		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->setElement($oDialog->createNotification(
				$oGui->t('Hinweis'), $oGui->t('Bitte speichern Sie zuerst die Einstellungen, um das Formular zu pflegen.'), 'info', ['row_id' => 'note-new']));

		$oTab->setElement($oDialog->createNotification(
				$oGui->t('Achtung'), $oGui->t('Diese alte, langsame, dysfunktionale Version des Formulars wird in Kürze entfernt, daher bitte auf Version 3 migrieren.'), 'hint', ['row_id' => 'note-migrate']));

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Name'),
				'input',
				array(
					'db_column' => 'title',
					'db_alias' => 'kf',
					'required' => 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Art'),
				'select',
				array(
					'db_column' => 'type',
					'select_options' => $aFormTypes,
					'required' => 1,
					'events' => array(
						array(
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, [1,2,3,4,5]'
						)
					),
					'child_visibility' => [
						[
							'id' => 'note-migrate',
							'on_values' => [Ext_Thebing_Form::TYPE_ENQUIRY, Ext_Thebing_Form::TYPE_REGISTRATION_NEW]
						]
					]
				)
			)
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Zweck'), 'select', [
			'db_column' => 'purpose',
			'select_options' => [
				Ext_Thebing_Form::PURPOSE_NEW => $oGui->t('Neue Buchung'),
				Ext_Thebing_Form::PURPOSE_TEMPLATE => $oGui->t('Neue Buchung (nur über Buchungsvorlage)'),
				Ext_Thebing_Form::PURPOSE_EDIT => $oGui->t('Buchung aktualisieren'),
		//		Ext_Thebing_Form::PURPOSE_CONFIRM => $oGui->t('Angebot bestätigen')
			],
			'dependency_visibility' => [
				'db_column' => 'type',
				'on_values' => [Ext_Thebing_Form::TYPE_REGISTRATION_V3]
			]
		]));

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				array(
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $aSchools,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
					'events' => array(
						array(
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, 3'
						)
					)
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Sprachen'),
				'select',
				array(
					'db_column' => 'languages',
					'multiple' => 5,
					'select_options' => $aLanguages,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
					'events' => array(
						array(
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, 3'
						)
					)
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Standardsprache'),
				'select',
				array(
					'db_column' => 'default_language',
					'db_alias' => 'kf',
					'selection' => $oDefaultLanguage,
					'required' => 1,
					'dependency' => array(
						array(
							'db_column' => 'languages'
						)
					)
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Cache ignorieren'),
				'checkbox',
				array(
					'db_column' => 'ignore_cache',
					'db_alias' => 'kf',
					'dependency_visibility' => [
						'db_column' => 'type',
						'on_values' => [Ext_Thebing_Form::TYPE_ENQUIRY, Ext_Thebing_Form::TYPE_REGISTRATION_NEW, Ext_Thebing_Form::TYPE_REGISTRATION_V3]
					]
				)
			)
		);

		// Sollte eigentlich im Unterkunfts-Block sein
		$oTab->setElement($oDialog->createRow($oGui->t('Unterkunft abhängig vom Kurs'), 'checkbox', [
			'db_column' => 'acc_depending_on_course',
			'db_alias' => 'kf',
			'events' => [
				[
					// Ist eingebaut für diese Abhängigkeiten im JS, dass die Felder nur nacheinander reingezogen werden können
					'event' => 'change',
					'function' => 'reloadDialogTab',
					'parameter' => 'aDialogData.id, 3'
				]
			],
			'dependency_visibility' => [
				'db_column' => 'type',
				'on_values' => [Ext_Thebing_Form::TYPE_REGISTRATION_NEW]
			]
		]));

		if(!empty($aInboxes)) {
			$oTab->setElement(
				$oDialog->createRow(
					$oGui->t('Eingangs-Inbox'),
					'select',
					array(
						'db_column' => 'inbox',
						'db_alias' => 'kf',
						'required' => 1,
						'select_options' => $aInboxes,
						'events' => array(
							array(
								'event' => 'change',
								'function' => 'reloadDialogTab',
								'parameter' => 'aDialogData.id, 3'
							)
						),
						'dependency_visibility' => array(
							'db_column' => 'type',
							'on_values' => [Ext_Thebing_Form::TYPE_REGISTRATION_NEW, Ext_Thebing_Form::TYPE_REGISTRATION_V3]
						)
					)
				)
			);
		}

		$oDialog->setElement($oTab);

		return $oDialog;
	}
}

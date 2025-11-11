<?php

namespace TcComplaints\Entity;

use Core\Helper\DateTime;
use \Ext_TC_Util;
use TcComplaints\Entity\SubCategory;
use TcComplaints\Repository\Category as TcComplaints_Repository_Category;
use TcComplaints\Repository\SubCategory as TcComplaints_Repository_SubCategory;

/**
 * Class Category
 * @package TcComplaints\Entity
 *
 * @property string id
 * @property string changed
 * @property string created
 * @property string active
 * @property string creator_id
 * @property string editor_id
 * @property string position
 * @property string title
 * @property string short_name
 * @property string description
 * @property string valid_until
 */
class Category extends \Ext_TC_Basic {

	// Tabellennamen
	protected $_sTable = 'tc_complaints_categories';

	// Tabellen alias
	protected $_sTableAlias = 'tc_cc';

	protected $_aJoinedObjects = array(
		'subcategories' => array(
			'class' => '\TcComplaints\Entity\SubCategory',
			'type' => 'child',
			'key' => 'category_id',
			'check_active' => true,
			'orderby' => 'position',
			'on_delete' => 'cascade'
		)
	);

	/**
	 * Gibt alle Unterkategorien einer Kategorie zurück
	 *
	 * @return \TcComplaints\Entity\SubCategory[]
	 */
	public function getSubCategories() {

		$aSubCategories = $this->getJoinedObjectChilds('subcategories', true);

		$oDateTime = new DateTime('now');
		foreach($aSubCategories as $iKey => $oSubCategory) {

			if($oSubCategory->valid_until > 0) {

				$oValidUntil = new DateTime(date('Y-m-d', $oSubCategory->valid_until));

				if($oValidUntil < $oDateTime) {
					unset($aSubCategories[$iKey]);
				}

			}

		}

		return $aSubCategories;
	}

	/**
	 * Prüft ob die Kategorie Unterkategorien hat
	 *
	 * @return bool
	 */
	public function hasChilds() {
		$aSubCategories = $this->getSubCategories();
		if(!empty($aSubCategories)) {
			return true;
		}
		return false;
	}

	/**
	 * @return array
	 */
	public static function getSelectOptions() {
		$oSelf = new self;

		$aList = $oSelf->getArrayList(true, 'title');

		$aReturn = Ext_TC_Util::addEmptyItem($aList);
		asort($aReturn);

		return $aReturn;

	}

}
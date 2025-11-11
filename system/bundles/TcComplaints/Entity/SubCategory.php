<?php

namespace TcComplaints\Entity;

use Core\Helper\DateTime;
use \Ext_TC_Util;
use TcComplaints\Entity\Category;

/**
 * Class SubCategory
 * @package TcComplaints\Entity
 *
 * @property string id
 * @property string changed
 * @property string created
 * @property string active
 * @property string creator_id
 * @property string editor_id
 * @property string category_id
 * @property string position
 * @property string title
 * @property string short_name
 * @property string description
 */
class SubCategory extends \Ext_TC_Basic {

	// Tabellennamen
	protected $_sTable = 'tc_complaints_categories_subcategories';

	// Tabellen alias
	protected $_sTableAlias = 'tc_ccsc';

	/**
	 * @return array
	 */
	public static function getSelectOptions() {
		$oSelf = new self;

		$aList = $oSelf->getArrayList(true, 'title');

		asort($aList);
		$aReturn = Ext_TC_Util::addEmptyItem($aList);

		return $aReturn;

	}

}
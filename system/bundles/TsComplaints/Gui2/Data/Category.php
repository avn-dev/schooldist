<?php

namespace TsComplaints\Gui2\Data;

use \TcComplaints\Gui2\Data\Category as TcComplaints_Gui2_Data_Category;

class Category extends TcComplaints_Gui2_Data_Category {

	/**
	 * Gibt den Pfad der TS Kategorie YML-Datei wieder
	 * @return string
	 */
	public static function getCategoryYMLPath() {
		return 'TsComplaints_category_list';
	}

	/**
	 * Gibt den Pfad der TS Unterkategorie YML-Datei wieder
	 *
	 * @return string
	 */
	public static function getSubCategoryYMLPath() {
		return 'TsComplaints_subcategory_list';
	}

}
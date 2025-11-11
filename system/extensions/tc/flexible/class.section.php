<?php

/**
 * Verfügbarkeiten in der Flexibität
 *
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property string $title
 * @property string $type
 * @property string $category
 */
class Ext_TC_Flexible_Section extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_flex_sections';
	protected $_sTableAlias = 'tc_fs';

	protected $_aJoinedObjects = [
		'fields' => [
			'class' => Ext_TC_Flexibility::class,
			'key' => 'section_id',
			'check_active' => true,
			'type' => 'child'
		]
	];

	// Key für Gui-Designer-Section-Cache
	protected static $sGuiDesignerSectionCacheKey = 'Ext_TC_Flexible_Section::getGuiDesignerSection';
	
	protected static $oGuiDesignerSection;
	
	/**
	 * Gibt die Section von den Gui-Designer-Feldern zurück
	 * @return self
	 */
	public static function getGuiDesignerSection() {

		if(self::$oGuiDesignerSection === null) {

			self::$oGuiDesignerSection = WDCache::get(self::$sGuiDesignerSectionCacheKey);

			if(self::$oGuiDesignerSection === null) {

				$oSectionRepository = self::getRepository();
				self::$oGuiDesignerSection = $oSectionRepository->findOneBy(array('type' => 'gui_designer'));

				WDCache::set(self::$sGuiDesignerSectionCacheKey, (24*60*60), self::$oGuiDesignerSection);

			}
			
		}

		return self::$oGuiDesignerSection;

	}

	/**
	 * @return Ext_TC_Flexibility[]
	 */
	public function getFields() {
		return $this->getJoinedObjectChilds('fields');
	}

}
<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;

class SuperordinateCourse extends AbstractImport {
	
	protected $sEntity = \TsTuition\Entity\SuperordinateCourse::class;
	
	/**
	 * @var \TsFrontend\Gui2\SuperordinateCourseData
	 */
	protected $gui2Data;
	 
	protected $translationLanguages = [];

	public function __construct() {
		
		$this->translationLanguages	= \Ext_Thebing_Util::getTranslationLanguages();
		
		parent::__construct();
		
	}
	
	public function setGui2Data(\TsFrontend\Gui2\SuperordinateCourseData $data) {
		
		$this->gui2Data = $data;
		
		$this->aFields = $this->getFields();
		
	}
	
	public function getFields() {
			
		if($this->gui2Data === null) {
			return [];
		}
		
		$interfaceLanguage	= \Ext_Thebing_School::fetchInterfaceLanguage();
		
		/**
		 * Mapping
		 */
		$aFields = [];
		foreach($this->translationLanguages as $aTranslationLanguage) {
			$aFields[] = ['field'=> 'Name ('.$aTranslationLanguage['name'].')', 'target' => 'name_'.$aTranslationLanguage['iso'], 'mandatory'=>true];
		}

		$aFields = array_values($aFields);
		
		return $aFields;
	}

	protected function getBackupTables() {
		
		$aTables = [
			'ts_superordinate_courses',
			'ts_superordinate_courses_i18n'
		];
	
		return $aTables;
	}

	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {
			$sReport = 'update';

			$aData = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $aData);

			$this->checkArraySplitFields($aItem, $aData);

			$superordinateCourseId = null;
			foreach($this->translationLanguages as $translationLanguage) {
				$superordinateCourseId = \DB::getQueryOne("SELECT `ts_sc`.`id` FROM `ts_superordinate_courses` `ts_sc` JOIN `ts_superordinate_courses_i18n` `ts_sci` ON `ts_sc`.`id` = `ts_sci`.`superordinate_course_id` WHERE `active` = 1 AND `name` LIKE :name AND `language_iso` = :language_iso", ['name'=>$aData['name_'.$translationLanguage['iso']], 'language_iso' => $translationLanguage['iso']]);
				if(!empty($superordinateCourseId)) {
					break;
				}
			}

			// Wenn Unterkunft schon vorhanden und nicht aktualisiert werden soll
			if(
				$superordinateCourseId !== null &&
				!$this->aSettings['update_existing']
			) {
				return;
			}

			if($superordinateCourseId === null) {
				$sReport = 'insert';
				$superordinateCourse = new \TsTuition\Entity\SuperordinateCourse;
			} else {
				$superordinateCourse = \TsTuition\Entity\SuperordinateCourse::getInstance($superordinateCourseId);
			}

			foreach($aData as $sField=>$mValue) {
				$superordinateCourse->$sField = $mValue;
			}

			$superordinateCourse->save();
			
			$this->aReport[$sReport]++;

			return $superordinateCourse->id;
			
		} catch(\Exception $e) {

			if ($e instanceof ImportRowException && $e->hasPointer()) {
				$this->aErrors[$iItem] = [['message' => $e->getMessage(), 'pointer' => $e->getPointer()]];
			} else {
				$this->aErrors[$iItem] = [['message' => $e->getMessage(), 'pointer' => new ErrorPointer("", $iItem)]];
			}

			$this->aReport['error']++;
			
			if(empty($this->aSettings['skip_errors'])) {
				throw new \Exception('Terminate import');
			}
	
		}
		
	}
	
	protected function getCheckItemFields(array $aPreparedData) {
		
	}
		
}

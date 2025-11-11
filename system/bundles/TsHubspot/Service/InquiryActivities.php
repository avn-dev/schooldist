<?php
/**
 * Gibt es erstmal nicht
 */
//
//namespace TsHubspot\Service;
//
//class InquiryActivities extends Api
//{
//
//	const HUBSPOT_FIELD_PROPERTIES_KEY = 'hubspot_activity_fields';
//
//	// Übersetzung wo anders
//	const SERVICE = 'Aktivitätsbuchung';
//
//	public function update(\Ext_TS_Inquiry_Journey_Activity $activity) {
//
//		$school = $course->getJourney()->getSchool();
//		$lang = $school->getLanguage();
//
//		$helper = new General();
//
//		$helper->setExistingPropertiesCustomObjects(self::HUBSPOT_OBJECT_KEY, $this->oHubspot);
//		$helper->addCustomFieldProperties($course);
//
//		$properties = $helper->getProperties();
//
//		$service = new Services();
//
//		return $service->update($course, self::HUBSPOT_OBJECT_KEY, $properties);
//	}
//}
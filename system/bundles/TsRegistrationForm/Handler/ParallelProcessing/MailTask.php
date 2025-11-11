<?php

namespace TsRegistrationForm\Handler\ParallelProcessing;

// TODO durch die Ereignissteuerung sollte das hier nicht mehr nötig sein
class MailTask extends AbstractTask {

	/**
	 * $data = [
	 *    'combination_id' => 1,
	 *    'object' => 'Ext_TS_Inquiry',
	 *    'object_id' => 1,
	 *    'document_type' => 'brutto'
	 * ];
	 *
	 * @inheritdoc
	 */
	public function execute(array $data, $debug = false) {

		$combination = $this->createCombination($data);
		$inquiry = $this->createObject($data);

		$form = $combination->getForm();
		$school = $combination->getSchool();
		$automaticMailTemplateId = $form->getSchoolDependentTranslation($school, 'schoolTpl', $combination->getLanguage()->getLanguage());

		if (empty($automaticMailTemplateId)) {
			return;
		}

		$automaticMailTemplate = \Ext_Thebing_Email_TemplateCronjob::getInstance($automaticMailTemplateId);
		$mailTemplate = \Ext_Thebing_Email_Template::getInstance((int)$automaticMailTemplate->layout_id);

		$attachments = $mailTemplate->buildMailAttachmentArray();

		// Generiertes Dokument an E-Mail anhängen
		$this->addAttachments($data, $form, $inquiry, $attachments);

		$customer = $inquiry->getCustomer();

		$mailData = \Ext_Thebing_Mail::createMailDataArray($inquiry, $customer, $school, $mailTemplate, $attachments);
		$modifiedMailData = $automaticMailTemplate->modifyMailDataArray($mailData, $school);

		if (
			$modifiedMailData !== null &&
			!empty($modifiedMailData['to']) // Sollte eigentlich immer vorhanden sein
		) {
			$stackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
			$stackRepository->writeToStack('ts/automatic-email', $modifiedMailData, 5);
		}

	}

	public function getLabel() {
		return \L10N::t('Anmeldeformular – E-Mail', 'School');
	}

	private function addAttachments(array $data, \Ext_Thebing_Form $form, \Ext_TS_Inquiry $inquiry, array &$attachments) {

		if (!$form->email_attach_document) {
			return;
		}

		$search = new \Ext_Thebing_Inquiry_Document_Search($inquiry->id);
		$search->addJourneyDocuments();
		$document = $inquiry->getLastDocument($data['document_type'], [], $search);

		if ($document === null) {
			return;
		}

		$version = $document->getLastVersion();
		$path = $version->getPath(true);

		$attachments['_'][] = [
			'path' => $path,
			'name' => basename($path),
			'relation' => get_class($version),
			'relation_id' => $version->id
		];

	}

}

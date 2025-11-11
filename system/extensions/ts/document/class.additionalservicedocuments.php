<?php

class Ext_TS_Document_AdditionalServiceDocuments {

	private Ext_TS_Inquiry $inquiry;

	public function __construct(Ext_TS_Inquiry $inquiry) {
		$this->inquiry = $inquiry;
	}

	public function buildOptions($items): array {

		$customer = $this->inquiry->getCustomer();
		$language = $customer->corresponding_language;
		$templates = [];

		foreach ($items as $aItem) {

			$item = \Ext_Thebing_Inquiry_Document_Version_Item::createFromArray($aItem);

			$service = $item->getService();
			if (!$service) {
				continue;
			}

			$joinTable = $service->getJoinTable('pdf_templates');
			if (!$joinTable) {
				continue;
			}

			/** @var Ext_Thebing_Pdf_Template[] $serviceTemplates */
			$serviceTemplates = $service->getJoinTableObjects('pdf_templates');
			foreach($serviceTemplates as $template) {

				if (!in_array($this->inquiry->getSchool()->id, $template->schools)) {
					// Schule muss in Template enthalten sein, sonst fehlt das Hintergrundbild
					continue;
				}

				$templates[$template->id] = [
					'template' => $template,
					'disabled' => !in_array($language, $template->languages),
					'version' => null,
					'created' => false,
					'created_at' => null
				];

			}

		}

		$this->checkCreatedDocuments($templates);

		usort($templates, function ($template1, $template2) {
			return strnatcmp($template1['template']->name, $template2['template']->name);
		});

		return $templates;
		
	}

	private function checkCreatedDocuments(array &$templates) {

		if (empty($templates)) {
			return;
		}

		$documents = $this->inquiry->getDocuments('additional_document', true, true);
		foreach ($documents as $document) {
			$version = $document->getLastVersion();
			if (isset($templates[$version->template_id])) {
				$templates[$version->template_id]['version'] = $version;
				$templates[$version->template_id]['created'] = true;
				$templates[$version->template_id]['created_at'] = Ext_Thebing_Format::LocalDate($document->getDate());
			}
		}

	}

	public function prepareBackgroundTasks(array $templateIds) {

		foreach ($templateIds as $templateId) {

			$data = [
				'type' => 'additional_document',
				'inquiry_id' => $this->inquiry->id,
				'template_id' => (int)$templateId,
				'user_id' => System::getCurrentUser()->id
			];

			$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
			$oStackRepository->writeToStack('ts/document-generating', $data, 5);

		}

	}

	public function generateDocumentByTask(array $data): bool {

		DB::begin(__METHOD__);

		$template = \Ext_Thebing_Pdf_Template::getInstance($data['template_id']);
		$contact = $this->inquiry->getCustomer();

		$service = new \Ts\Helper\Document($this->inquiry, $this->inquiry->getSchool(), $template, $contact->corresponding_language);
		$service->create();
		$service->setUser(User::getInstance($data['user_id']));

		if (!empty($data['document_id'])) {
			$document = Ext_Thebing_Inquiry_Document::getInstance($data['document_id']);
			$service->setParentDocument($document, 'parent_documents_attached_additional');
		}

		$service->save();

		DB::commit(__METHOD__);

		return true;

	}
	
}

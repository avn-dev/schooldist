<?php

namespace TsStudentApp\Pages;

use Illuminate\Support\Str;
use TsStudentApp\AppInterface;

class Documents extends AbstractPage {

	const SEGMENT_ALL = 'all';
	const SEGMENT_DOCUMENTS = 'documents';
	const SEGMENT_OTHERS = 'others';

	private $appInterface;

	private $inquiry;

	private $student;

	private $school;

	public function __construct(AppInterface $appInterface, \Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Contact_Traveller $student, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->inquiry = $inquiry;
		$this->student = $student;
		$this->school = $school;
	}

	public function init(): array {
		$data = $this->refresh();

		$data['segments'] = [
			['title' => $this->appInterface->t('All'), 'value' => self::SEGMENT_ALL, 'selected' => true],
			['title' => $this->appInterface->t('Documents'), 'value' => self::SEGMENT_DOCUMENTS],
			['title' => $this->appInterface->t('Others'), 'value' => self::SEGMENT_OTHERS],
		];

		return $data;
	}

	public function refresh(): array {

		$items = collect([]);

		$documents = $this->inquiry->getDocuments('all', true, true);

		foreach($documents as $document) {
			// Freigegebene Dokumente (aber niemals Nettorechnungen)
			if(
				$document->isReleasedForApp() &&
				!$document->isNetto()
			) {
				$version = $document->getLastVersion();
				$template = $version->getTemplate();

				$date = (new \DateTime())->setTimestamp($version->created);

				$description = [];
				if(!empty($document->document_number)) $description[] = $document->document_number;
				$description[] = $this->appInterface->formatDate($date);

				$items->push([
					'icon' => 'document-outline',
					'label' => $template->getName(),
					'file_name' => basename($version->getPath()),
					'url' => $this->appInterface->document('document', $document->getId()),
					'description' => implode(' - ', $description),
					'segments' => [self::SEGMENT_ALL, self::SEGMENT_DOCUMENTS]
				]);
			}
		}

		// Schülerfoto
		if(
			$this->inquiry->isUploadReleasedForStudentApp('static', 1)
		) {
			$photo = $this->student->getPhoto(true);
			if(!empty($photo)) {
				$items->push([
					'icon' => 'person-circle-outline',
					'label' => $this->appInterface->t('Student image'),
					'file_name' => $photo,
					'url' => $this->appInterface->document('static_1', $this->student->getId()),
					'segments' => [self::SEGMENT_ALL, self::SEGMENT_OTHERS]
				]);
			}
		}

		// Reisepass
		if(
			$this->inquiry->isUploadReleasedForStudentApp('static', 2)
		) {
			$passport = $this->student->getPassport(true);
			if(!empty($passport)) {
				$items->push([
					'icon' => 'person-circle-outline',
					'label' => $this->appInterface->t('Passport'),
					'file_name' => $passport,
					'url' => $this->appInterface->document('static_2', $this->student->getId()),
					'segments' => [self::SEGMENT_ALL, self::SEGMENT_OTHERS]
				]);
			}
		}

		// Flexible Uploadfelder (Buchungsdialog)
		$uploadFields = \Ext_Thebing_School_Customerupload::getUploadFieldsBySchoolIds([$this->school->getId()]);
		foreach($uploadFields as $uploadField) {
			if($this->inquiry->isUploadReleasedForStudentApp('flex', $uploadField->getId())) {
				$upload = $this->student->getStudentUpload($uploadField->getId(), $this->school->getId(), $this->inquiry->getId(), true);
				if(!empty($upload)) {
					$icon = in_array(Str::afterLast($upload, '.'), ['png', 'jpg', 'jpeg', 'gif']) ? 'image-outline' : 'document-outline';
					$items->push([
						'icon' => $icon,
						'label' => $uploadField->getName(),
						'url' => $this->appInterface->document('flex', $uploadField->getId()),
						'file_name' => $upload,
						'segments' => [self::SEGMENT_ALL, self::SEGMENT_OTHERS]
					]);
				}
			}
		}

		return [
			'documents' => $items->toArray()
		];
	}

}

<?php

namespace TsStudentApp\Pages;

use Illuminate\Http\Request;
use TsStudentApp\AppInterface;

class PersonalData extends AbstractPage {

	private AppInterface $appInterface;

	private \Ext_TS_Inquiry $inquiry;

	private \Ext_TS_Inquiry_Contact_Traveller $student;

	private \Ext_Thebing_School $school;

	public function __construct(AppInterface $appInterface, \Ext_Thebing_School $school, \Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Contact_Traveller $student) {
		$this->appInterface = $appInterface;
		$this->school = $school;
		$this->inquiry = $inquiry;
		$this->student = $student;
	}

	public function init(): array {
		$data = $this->refresh();

		$data['picture_enabled'] = false;
		$data['delete_picture_enabled'] = false;
		if ($this->school->getMeta('student_app_student_can_change_picture')) {
			$data['picture_enabled'] = true;
			$data['delete_picture_enabled'] = true;
		}

		$data['toast_duration'] = 3000;

		$data['camera_options'] = [
			'allow_edit' => false,
			'quality' => $this->appInterface->config('uploads.images.quality'),
			'save_on_device' => false
		];

		return $data;
	}

	public function refresh(): array {

		$firstEmail = $this->student->getFirstEmailAddress(false);

		$address = $this->student->getAddress('contact');
		$addressBilling = $this->student->getAddress('billing');

		$nationalityFormat = new \Ext_Thebing_Gui2_Format_Nationality($this->appInterface->getLanguage());

		$data = [
			'has_image' => !empty($this->student->getPhoto(true)),
			'student' => [
				'name' => $this->student->getName(),
				'image' => $this->appInterface->image('student', $this->student->getId()).'?time='.time(),
				'number' => $this->student->getCustomerNumber(),
				'birthdate' => $this->appInterface->formatDate($this->student->birthday),
				'email' => ($firstEmail) ? $firstEmail->email : '',
				'nationality' => $nationalityFormat->format($this->student->nationality)
			]
		];

		if(!$address->isEmpty()) {
			$data['student']['addresses'][] = $this->buildAddress($address, $this->appInterface->t('Address'));
		}

		if(!$addressBilling->isEmpty()) {
			$data['student']['addresses'][] = $this->buildAddress($addressBilling, $this->appInterface->t('Billing address'));
		}

		$emergency = $this->inquiry->getEmergencyContact();
		if($emergency->exist()) {
			$data['emergency'] = [
				'name' => $emergency->getName(),
				'email' => $emergency->getFirstEmailAddress()->email,
				'phone' => $emergency->getDetail('phone_private'),
			];
		}

		return $data;
	}

	public function deletePicture() {

		$this->student->setInquiry($this->appInterface->getInquiry());

		$file = $this->student->getPhoto(true);

		if(!empty($file)) {
			$success = empty($this->student->deletePhoto($file));
		} else {
			$success = false;
		}

		$this->appInterface->getLoggingService()->info('Student picture delete', ['success' => $success, 'student' => $this->student->getData(), 'inquiry' => $this->inquiry->getData()]);

		return [
			'success' => $success,
			'message' => ($success)
				? $this->appInterface->t('Your image has been deleted.')
				: $this->appInterface->t('Image could not be deleted.')
		];
	}

	public function savePicture(Request $request) {

		if($request->has('image')) {

			$file = $request->file('image');

			if(
				$file instanceof \Illuminate\Http\UploadedFile &&
				in_array($file->getMimeType(), ['image/jpeg'])
			) {
				// Das sollte eigentlich nicht notwendig sein, aber scheinbar kann sich die Schule innerhalb von student ändern?
				$this->student->setInquiry($this->appInterface->getInquiry());

				// Das aktuelle Bild muss immer gelöscht werden, da bei einer anderen Extension dann mehrere Bilder existieren würden
				$this->student->deletePhoto($this->student->getPhoto(true));

				$this->student->saveUpload2('static_1', $request->file('image'));

				$this->appInterface->getLoggingService()->info('Student picture upload', ['success' => true, 'student' => $this->student->getData(), 'inquiry' => $this->inquiry->getData()]);

				return [
					'success' => true,
					'message' => $this->appInterface->t('Your image has been uploaded.'),
					'image' => $this->appInterface->image('student', $this->student->getId()).'?time='.time()
				];
			}
		}

		$this->appInterface->getLoggingService()->info('Student picture upload', ['success' => false, 'student' => $this->student->getData(), 'inquiry' => $this->inquiry->getData()]);

		return [
			'success' => false,
			'message' => $this->appInterface->t('Your image could not be uploaded.')
		];
	}

	private function buildAddress(\Ext_TC_Address $address, string $label) {
		return [
			'icon' => 'location-outline',
			'label' => $label,
			'address' => $address->address,
			'zip' => $address->zip,
			'city' => $address->city,
			'country' => $address->getCountry($this->appInterface->getLanguage())
		];
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.personal_data.picture_button' => $appInterface->t('Photo'),
			'tab.personal_data.picture' => $appInterface->t('Student photo'),
			'tab.personal_data.picture.action.new' => $appInterface->t('Take photo'),
			'tab.personal_data.picture.action.choose' => $appInterface->t('Choose photo'),
			'tab.personal_data.picture.action.delete' => $appInterface->t('Delete photo'),
			'tab.personal_data.student_id' => $appInterface->t('Student ID'),
			'tab.personal_data.birthdate' => $appInterface->t('Birthdate'),
			'tab.personal_data.email' => $appInterface->t('Email'),
			'tab.personal_data.phone' => $appInterface->t('Phone'),
			'tab.personal_data.nationality' => $appInterface->t('Nationality'),
			'tab.personal_data.emergency' => $appInterface->t('Emergency contact'),
			'tab.personal_data.no_image' => $appInterface->t('No image'),
		];
	}

}

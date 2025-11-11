<?php

namespace TsStudentApp\Http\Controller;

use FileManager\Entity\File;
use Illuminate\Support\Arr;
use TsActivities\Entity\Activity;
use TsStudentApp\Service\LoggingService;
use TsStudentApp\Service\MessengerService;

class FileController extends \Illuminate\Routing\Controller {

	public function __construct(private LoggingService $loggingService) {}

	/**
	 * Bilder der App
	 *
	 * @param string $type
	 * @param string $id
	 * @param \Ext_Thebing_School $school
	 * @param \Ext_TS_Inquiry_Contact_Traveller $student
	 * @return mixed
	 * @throws \Exception
	 */
	public function image(string $type, string $id, \Ext_Thebing_School $school, \Ext_TS_Inquiry_Contact_Traveller $student) {

		$file = null;

		switch($type) {
			case "student":
				$file = \Util::getDocumentRoot().$student->getPhoto(false);
				break;
			case "school_image":
				$file = $school->getFirstFile(\TsActivities\Entity\Activity::APP_IMAGE_TAG)?->getPathname();
				break;
			case "accommodation":
				$upload = \Ext_Thebing_Accommodation_Upload::getInstance($id);
				$file = $upload->getPath();
				break;
			case "activity":
				$activity = Activity::getInstance($id);
				$image = $activity->getAppImage();

				if($image) {
					$file = $image->getPath(true).$image->file;
				}

				break;
			case "messenger_thread":
				$messenger = app()->make(MessengerService::class);
				$thread = $messenger->getThreadByToken($id);

				if($thread && $thread->canCommunicate()) {
					$file = $thread->getImage();
				}

				break;
			case "teacher":
				$teacher = \Ext_Thebing_Teacher::getInstance($id);
				$image = $teacher->getProfilePicture();

				if ($image) {
					$file = $image->getPath(true).$image->file;
				}
				break;
		}

		if(!is_file($file) || !file_exists($file)) {
			return response('File not found', 404);
		}

		$headers = [
			'Content-Type' => $this->getMimeInfo($file)
		];

		return response()->file($file, $headers);
	}

	/**
	 * Dokumente des Schülers (können auch Bilder sein)
	 *
	 * @param string $type
	 * @param string $id
	 * @param \Ext_Thebing_School $school
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param \Ext_TS_Inquiry_Contact_Traveller $student
	 * @return mixed
	 * @throws \Exception
	 */
	public function document(string $type, string $id, \Ext_Thebing_School $school, \Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Contact_Traveller $student) {

		$file = null;

		switch($type) {
			case "document":

				$document = \Ext_Thebing_Inquiry_Document::getInstance($id);

				if(
					$document->exist() &&
					$document->isReleasedForApp() &&
					!$document->isNetto()
				) {
					$documentInquiry = $document->getInquiry();

					if($documentInquiry->getId() === $inquiry->getId()) {
						$version = $document->getLastVersion();
						$file = $version->getPath(true);
					}
				}

				break;
			case "static_1":
			case "static_2":

				list($staticType, $staticTypeId) = explode('_', $type);

				if($inquiry->isUploadReleasedForStudentApp($staticType, $staticTypeId)) {
					if($type === 'static_1') {
						$file = \Util::getDocumentRoot().$student->getPhoto(false);
					} else {
						$file = \Util::getDocumentRoot().$student->getPassport(false);
					}
				}

				break;
			case "flex":

				if($inquiry->isUploadReleasedForStudentApp('flex', $id)) {
					$file = \Util::getDocumentRoot().$student->getStudentUpload($id, $school->getId(), $inquiry->getId(), false);
				}

				break;
			case "attachment":

				$attachment = \Ext_TC_Communication_Message_File::getInstance($id);

				if($attachment->exist()) {

					$message = \Ext_TC_Communication_Message::getRepository()->findOneBy(['id' => $attachment->message_id]);
					if($message instanceof \Ext_TC_Communication_Message) {

						$inquiries = $student->getInquiries(false, false);

						// Prüfen ob die Nachricht zu den Inquiries des Schülers passt
						$relation = Arr::first($message->relations, function ($relation) use ($inquiries) {
							return (
								$relation['relation'] === \Ext_TS_Inquiry::class &&
								in_array((int)$relation['relation_id'],  $inquiries)
							);
						});

						if($relation !== null) {
							$file = \Util::getDocumentRoot(false).$attachment->file;
						}
					}
				}

				break;
			case 'class':
				/* @var File $fileEntity */
				$fileEntity = File::query()
					->where('entity', \Ext_Thebing_Tuition_Class::class)
					->find($id);

				if ($fileEntity->hasTag('App-Upload')) {
					$search = new \Ext_Thebing_School_Tuition_Allocation_Result();
					$search->setInquiry($inquiry);
					$search->setClass($fileEntity->getEntity());
					if (!empty($search->fetch())) {
						$file = $fileEntity->getPathname();
					}
				}

				break;
		}

		if(!is_file($file) || !file_exists($file)) {
			return response('File not found', 404);
		}

		$headers = [
			'Content-Type' => $this->getMimeInfo($file),
		];

		return response()->file($file, $headers);
	}

	/**
	 * Liest den Mime-Type für ein Bild aus
	 *
	 * @param string $path
	 * @return string
	 */
	private function getMimeInfo(string $path): string {
		return (new \finfo())
			->file($path, FILEINFO_MIME);
	}
}

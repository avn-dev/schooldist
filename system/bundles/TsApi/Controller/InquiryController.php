<?php

namespace TsApi\Controller;

use Api\Service\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InquiryController extends AbstractController {
	
	protected $handlerClass = \TsApi\Handler\Inquiry::class;
	
	public function list(float $version, Request $request) {
		
		ini_set('memory_limit', '4G');
		
		$language = $request->get('language', 'en');
		$profilePictures = (bool)$request->get('profile_pictures', true);

		/*
		 * @todo Sprache auf Anwendbarkeit überprüfen
		 */
		\System::setInterfaceLanguage($language);
		// Backendsprache gesondert setzen
		\System::s('systemlanguage', $language, false);
		
		$filters = $request->input('filter');

		$factory = new \Ext_Gui2_Factory('ts_inquiry', false, true, true, true);
		$gui = $factory->createGui();
		$gui->setRequest($request);

		$inquiryIds = [];
		
		if(!empty($filters['contact_id'])) {
			$inquiryIds = \DB::getQueryCol("SELECT * FROM `ts_inquiries_to_contacts` WHERE `contact_id` = :contact_id AND `type` = 'traveller'", ['contact_id'=>(int)$filters['contact_id']]);
			unset($filters['contact_id']);
		}
		
		$gui->column_flexibility = false;
		$data = $gui->getTableData($filters, [], $inquiryIds, 'api', true);

		$entries = [];
		if (isset($data['body'])) {
			foreach ($data['body'] as $row) {

				$entry = [];
				foreach ($row['items'] as $colIndex => $col) {
					$key = $col['db_column'];
					if (!empty($col['db_alias'])) {
						$key = $col['db_alias'] . '.' . $key;
					}

					if ($version == 1.1) {
						$entry[$key] = $col['text'];
						if (
							!isset($entry[$key . '_original']) &&
							$col['original'] != $col['text']
						) {
							$entry[$key . '_original'] = $col['original'];
						}
					} else {
						$entry[$key] = $col['original'];
					}

				}

				$inquiry = \Ext_TS_Inquiry::getInstance($row['id']);
				$student = $inquiry->getTraveller();

				if ($profilePictures) {
					$photo = $student->getPhoto();

					if ($photo) {
						$entry['profile_picture'] = 'data:image/png;base64,' . base64_encode(file_get_contents(\Util::getDocumentRoot(false) . $photo));
					}
				}

				$entries[$row['id']] = $entry;

			}
		}

		return response()->json(['hits' => count($entries), 'entries' => $entries]);
	}
	
	public function details(int $id, string $version, Request $request) {
		
		ini_set('memory_limit', '4G');

		$inquiry = \Ext_TS_Inquiry::getInstance($id);
		
		if(!$inquiry->exist()) {
			abort(404);
		}
		
		$includeInactiveServices = (bool)$request->get('include_inactive_services', false);
		$includeCreditNotes = (bool)$request->get('include_credit_notes', false);
		
		$contact = $inquiry->getTraveller();
		$school = $inquiry->getSchool();
		
		$data = [
			'student' => [
				'firstname' => $contact->firstname,
				'surname' => $contact->lastname,
				'email' => $contact->email,
				'number' => $contact->getCustomerNumber(),
				'custom_fields' => $contact->getFlexValues()
			],
			'booking' => [
				'courses' => [],
				'accommodations' => [],
				'transfers' => [],
				'holidays' => [],
				'custom_fields' => $inquiry->getFlexValues()
			],
			'invoices' => [],
			'school' => [
				'name' => $school->name,
				'address' => $school->address,
				'zip' => $school->zip,
				'city' => $school->city,
				'country_id' => $school->country_id,
				'phone' => $school->phone_1,
				'email' => $school->email,
			]
		];
		
		$courses = $inquiry->getCourses(true, true, true, !$includeInactiveServices);

		foreach($courses as $course) {

			/** @var \Ext_Thebing_School_Tuition_Allocation[] $courseAssignments */
			$courseAssignments = $course->getJoinedObjectChilds('tuition_blocks');
			
			$assignmentData = [];
			foreach($courseAssignments as $courseAssignment) {

				$assignmentData[] = [
					'week' => $courseAssignment->getBlock()->week,
					'class' => $courseAssignment->getBlock()->getClass()->getName(),
					'days' => $courseAssignment->getBlock()->days,
					'start' => $courseAssignment->getBlock()->getTemplate()->from,
					'end' => $courseAssignment->getBlock()->getTemplate()->until,
					'teacher' => $courseAssignment->getBlock()->getTeacher()?->getName()
				];
			}
			
			$data['booking']['courses'][$course->id] = [
				'active' => $course->visible,
				'created' => $course->aData['created'], # Direkter Zugriff gibt Unix-Timestamp zurück
				'from' => $course->from,
				'until' => $course->until,
				'weeks' => $course->weeks,
				'name' => $course->getCourse()->getName(),
				'category' => $course->getCourse()->getCategory()->getName(),
				'note' => $course->comment,
				'flexible_allocation' => $course->flexible_allocation,
				'custom_fields' => $course->getFlexValues(),
				'additional_services' => $course->additionalservices,
				'assignments' => $assignmentData
			];
			
		}
		
		$accommodations = $inquiry->getAccommodations(true, $includeInactiveServices);

		foreach($accommodations as $accommodation) {
			
			$accommodationAllocations = $accommodation->getAllocations();
			$assignmentData = [];
			
			foreach($accommodationAllocations as $accommodationAllocation) {
				$assignmentData[] = [
					'from' => $accommodationAllocation->from,
					'until' => $accommodationAllocation->until,
					'provider' => $accommodationAllocation->getRoom()->getProvider()->getName(),
					'room' => $accommodationAllocation->getRoom()->getName()
				];
			}
			
			$data['booking']['accommodations'][$accommodation->id] = [
				'active' => $accommodation->visible,
				'created' => $accommodation->aData['created'], # Direkter Zugriff gibt Unix-Timestamp zurück
				'from' => $accommodation->from,
				'until' => $accommodation->until,
				'weeks' => $accommodation->weeks,
				'category' => $accommodation->getCategory()->getName(),
				'roomtype' => $accommodation->getRoomType()->getName(),
				'board' => $accommodation->getMeal()->getName(),
				'from_time' => $accommodation->from_time,
				'until_time' => $accommodation->until_time,
				'note' => $accommodation->comment,
				'additional_services' => $accommodation->additionalservices,
				'assignments' => $assignmentData
			];
		}
		
		$transfers = $inquiry->getTransfers();

		foreach($transfers as $transfer) {
			$data['booking']['transfers'][$transfer->id] = [
				'date' => $transfer->transfer_date,
				'time' => $transfer->transfer_time,
				'type' => $transfer->transfer_type
			];
		}
		
		$holidays = $inquiry->getJoinedObjectChilds('holidays', true);

		foreach($holidays as $holiday) {
			
			$splitting = [];
			$splittingItems = $holiday->getSplittings();
			foreach($splittingItems as $splittingItem) {
				
				if(
					$splittingItem->journey_course_id > 0 &&
					$splittingItem->journey_split_course_id > 0
				) {
					$splitting[$splittingItem->id] = [
						'type' => 'course',
						'original_id' => $splittingItem->journey_course_id,
						'split_id' => $splittingItem->journey_split_course_id,
						'original_from' => $splittingItem->original_from,
						'original_until' => $splittingItem->original_until
					];
				} elseif(
					$splittingItem->journey_accommodation_id > 0 &&
					$splittingItem->journey_split_accommodation_id > 0
				) {
					$splitting[$splittingItem->id] = [
						'type' => 'accommodation',
						'original_id' => $splittingItem->journey_accommodation_id,
						'split_id' => $splittingItem->journey_split_accommodation_id,
						'original_from' => $splittingItem->original_from,
						'original_until' => $splittingItem->original_until
					];
				}
				
			}
			
			$data['booking']['holidays'][$holiday->id] = [
				'from' => $holiday->from,
				'until' => $holiday->until,
				'weeks' => $holiday->weeks,
				'type' => $holiday->type,
				'splitting' => $splitting
			];
		}

		if (version_compare($version, '1.0', '<=')) {
			$invoices = $inquiry->getDocuments('invoice_brutto_without_proforma', true, true);
		} else if ($includeCreditNotes) {
			$invoices = $inquiry->getDocuments('invoice_with_creditnote', true, true);
		} else {
			$invoices = $inquiry->getDocuments('invoice', true, true);
		}

		if ($invoices) {
			
			foreach($invoices as $invoice) {
				
				if(
					version_compare($version, '1.0', '<=') &&
					$invoice->released_student_login == 0
				) {
					continue;
				}

				$invoiceVersion = $invoice->getLastVersion();
				$path = $invoiceVersion->getPath(true);
				$pathInfo = pathinfo($path);

				$invoicePayload = [
					'filename' => $pathInfo['basename'],
					'number' => $invoice->document_number
				];

				if (version_compare($version, '1.0', '>')) {
					$invoicePayload['type'] = $invoice->type;
					$invoicePayload['released_for_student'] = (bool)$invoice->released_student_login;
				}

				$creditNote = $invoice->getCreditNote();
				$invoicePayload['credit_note_number'] = $creditNote ? $creditNote->document_number : "";
				$invoicePayload['created'] = \Carbon\Carbon::parse((int)$invoice->created)->format('Y-m-d H:i:s');
				$invoicePayload['updated'] = \Carbon\Carbon::parse((int)$invoice->changed)->format('Y-m-d H:i:s');
				$invoicePayload['is_last_document'] = $invoice->isLastInquiryDocument();
				$invoicePayload['date'] = $invoice->getLastVersionDateField('date');

				try {
					$invoicePayload['updated_by'] = \Ext_Thebing_User::getInstance($invoice->editor_id)->name;
				} catch (\Exception $e) {
					$invoicePayload['updated_by'] = "";
				}
				try {
					$invoicePayload['created_by'] = \Ext_Thebing_User::getInstance($invoice->creator_id)->name;
				} catch (\Exception $e) {
					$invoicePayload['created_by']  = "";
				}

				$parentDocument = $invoice->getMainParentDocument();
				$invoicePayload['parent_document_number']  = $parentDocument ? $parentDocument->document_number : "";

				$items = array_map(function (\Ext_Thebing_Inquiry_Document_Version_Item $item) {
					$payload = [];
					$payload['id'] = $item->id;
					$payload['description'] = $item->description;
					$payload['service_from'] = $item->index_from;
					$payload['service_until'] = $item->index_until;
					$payload['amount'] = (float)$item->amount;
					$payload['amount_discount'] = (float)bcsub($item->amount, round((float)$item->getAmount(),2), 2);
					$payload['amount_commission'] = round((float)$item->getAmount('commission'), 2);
					$payload['amount_net'] = (float)bcsub(round((float)$item->getAmount(),2), $payload['amount_commission'], 2);
					$payload['tax'] = (float)$item->tax;
					$payload['active'] = (bool)$item->onPdf;
					$payload['created'] = \Carbon\Carbon::parse((int)$item->created)->format('Y-m-d H:i:s');
					$payload['updated'] = \Carbon\Carbon::parse((int)$item->changed)->format('Y-m-d H:i:s');
					return $payload;
				}, $invoiceVersion->getJoinedObjectChilds('items', true));

				if (!empty($path)) { // file_gets_contents throws fatal error if path empty since 8.0
					$invoicePayload['base64'] = 'data:application/pdf;base64,' . base64_encode(file_get_contents($path));
				} else {
					$invoicePayload['base64'] = '';
				}

				$invoicePayload['items'] = array_values($items);

				$data['invoices'][] = $invoicePayload;
			}
			
		}
		
		return response()->json(['data' => $data]);
	}

}
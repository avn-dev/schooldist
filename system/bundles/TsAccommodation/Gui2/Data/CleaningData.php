<?php

namespace TsAccommodation\Gui2\Data;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use TsAccommodation\Entity\Cleaning\Status;
use TsAccommodation\Entity\Cleaning\Type;
use TsAccommodation\Service\RoomCleaningService;

class CleaningData extends \Ext_Thebing_Gui2_Data {

	public static function getSettingsDialog(\Ext_Gui2 $gui2): \Ext_Gui2_Dialog {

		$dialog = $gui2->createDialog($gui2->t('Einstellungen'), $gui2->t('Einstellungen'));

		$settingsGui = new \Ext_TC_Config_Gui2(md5('cleaning_config'), 'Ext_TC_Config_Gui2_Data');
		$settingsGui->gui_description = $gui2->gui_description;
		$settingsGui->gui_title = $gui2->t('Einstellungen');
		$settingsGui->include_jscolor = true;
		$settingsGui->setOption('right', 'thebing_cleaning_schedule_settings');

		$config = [];
		$config['cleaning_schedule_bed_multiple'] = array(
			'description' => $settingsGui->t('Betten mehrfach anzeigen bei Überlappungen am selben Tag'),
			'type' => 'checkbox',
			'format' => new \Ext_Gui2_View_Format_YesNo()
		);

		$settingsGui->setConfigurations($config);
		$settingsGui->setWDBasic(\Ext_TS_Config::class);

		$dialog->setElement($settingsGui);

		return $dialog;
	}

	protected function _getWDBasicObject($aSelectedIds) {
		return null;
	}

	/**
	 * Standardwert für den Zeitraumfilter (heute)
	 *
	 * @return int|string
	 */
	public static function getDefaultFilterDate() {
		$now = new \DateTime();
		return (new \Ext_Thebing_Gui2_Format_Date)->formatByValue($now);
	}

	public static function getStatuses(\Ext_Gui2 $gui) {
		return [
			Status::STATUS_DIRTY => $gui->t('Schmutzig'),
			Status::STATUS_CLEAN => $gui->t('Sauber'),
			Status::STATUS_NEEDS_REPAIR => $gui->t('Reparatur nötig'),
			Status::STATUS_CHECKED => $gui->t('Geprüft'),
		];
	}

	/**
	 * Schulspalten ergänzen
	 *
	 * @inheritDoc
	 */
	public function prepareColumnListByRef(&$columnList) {

		parent::prepareColumnListByRef($columnList);

		$schools = $this->getSchools();

		foreach($schools as $school) {
			$column = $this->_oGui->createColumn();
			$column->db_column = 'school_'.$school->getId();
			$column->title = $school->short;
			$column->width = \Ext_Thebing_Util::getTableColumnWidth('short_name');
			$column->sortable = false;

			$columnList[] = $column;
		}
	}

	public function getTableQueryData($filter = array(), $orderBy = array(), $selectedIds = array(), $skipLimit=false) {

		$cleaningService = new RoomCleaningService();

		$final = [
			'data' => [],
			'offset' => 0,
			'show' => 0,
		];

		$dateRange = $this->getFilterDateRange();

		foreach ($dateRange as $date) {

			$cleanOrders = $cleaningService->getCleanOrdersForDate($date);

			$cleanings = Status::query()
				->where('date', $date->toDateString())
				->get();

			foreach($cleanOrders as $cleanOrder) {

				$room = $cleanOrder->getRoom();

				$bedsCleaning = $cleanOrder->getBedsForCleaning()
					->toArray();

				foreach($bedsCleaning as $cleaning) {

					/** @var Status $status */
					$status = $cleanings->first(fn(Status $status) => $status->room_id == $room->id && $status->bed == $cleaning['bed']);

					$entry = [];
					$entry['status'] = $status?->status ?: 'dirty';
					$entry['type'] = $cleaning['type']->getShortName();
					$entry['type_id'] = $cleaning['type']->getId();
					$entry['creator_id'] = $status?->creator_id ?: 0;
					$entry['editor_id'] = $status?->editor_id ?: 0;
					$entry['created'] = $status?->created ?: null;
					$entry['changed'] = $status?->changed ?: null;

					if(
						isset($filter['status']) &&
						$filter['status'] !== "xNullx" &&
						$entry['status'] !== $filter['status']
					) {
						continue;
					}
					if(
						isset($filter['type_id']) &&
						$filter['type_id'] !== "xNullx" &&
						$entry['type_id'] !== $filter['type_id']
					) {
						continue;
					}
					if(
						isset($filter['school_id']) &&
						$filter['school_id'] !== "0" &&
						!in_array($filter['school_id'], $cleaning['type']->schools)
					) {
						continue;
					}

					$entry['date'] = $date->toDateString();
					$entry['room_id'] = $room->getId();
					$entry['room'] = $room->getName();
					$entry['bed'] = $cleaning['bed'];
					$entry['type_full'] = $cleaning['type']->getName();
					$entry['cycle_id'] = $cleaning['cycle']->getId();
					$entry['transfer_time'] = "";
					$entry['next_arrival'] = ($cleaning['next_arrival'] instanceof \DateTime)
						? $cleaning['next_arrival']->format('Y-m-d')
						: '';

					if($cleaning['allocation'] instanceof \Ext_Thebing_Accommodation_Allocation) {
						$inquiry = $cleaning['allocation']->getInquiry();
						$journeyAccommodation = $cleaning['allocation']->getInquiryAccommodation();
						$customer = $inquiry->getCustomer();

						$entry['inquiry_id'] = $inquiry->id; // Flex Fields
						$entry['inquiry_roomtype'] = $journeyAccommodation->getRoomType()->getName();
						$entry['customer'] = $customer->getName();
						$entry['accommodation'] = $cleaning['allocation']->getAccommodationProvider()->ext_33;
						$entry['gender'] = $customer->getGender();
						$entry['agency'] = ($inquiry->hasAgency()) ? $inquiry->getAgency()->getName() : '';
						$entry['group'] = ($inquiry->hasGroup()) ? $inquiry->getGroupName() : '';
						$entry['departure'] = \DateTime::createFromFormat('Y-m-d H:i:s', $cleaning['allocation']->until)->format('Y-m-d');

						$getTransferTime = function (\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer) {
							$time = (!empty($journeyTransfer->pickup)) ? $journeyTransfer->pickup : $journeyTransfer->transfer_time;
							return (new \Ext_Thebing_Gui2_Format_Time())->formatByValue($time);
						};

						if ($cleaning['cycle']->time == 'before_departure') {
							$transfer = $inquiry->getDepartureTransfer();

							if (
								$transfer && !empty($getTransferTime($transfer))
							) {
								$entry['transfer_time'] = $this->_oGui->t('Abreise').': '.$getTransferTime($transfer);
							}
						} else if ($cleaning['cycle']->time == 'after_arrival') {
							$transfer = $inquiry->getArrivalTransfer();

							if (
								$transfer && !empty($getTransferTime($transfer))
							) {
								$entry['transfer_time'] = $this->_oGui->t('Anreise').': '.$getTransferTime($transfer);
							}
						}

						$entry['school_'.$inquiry->getSchool()->getId()] = 'x';

					} else {
						$entry['inquiry_roomtype'] = "";
						$entry['customer'] = "";
						$entry['accommodation'] = "";
						$entry['gender'] = "";
						$entry['agency'] = "";
						$entry['group'] = "";
						$entry['departure'] = "";
					}

					// 'id' ist wichtig für encode_data
					$entry['id'] = implode('_', [
						$entry['room_id'],
						$entry['bed']
					]);

					$final['data'][] = $entry;
				}

			}

		}

		// Werte der Pagination aktualisieren
		$final['count'] = count($final['data']);
		$final['end'] = $final['count'];

		return $final;
	}

	/**
	 * Generiert den Putzplan als PDF
	 *
	 * @param $data
	 * @return array
	 */
	protected function requestExportPdf($data) {

		$tableData = $this->getTableQueryData($data['filter'], $data['orderby'], $data['id'], true);

		$transfer = [];

		if(!empty($tableData['data'])) {

			$pdf = new \Pdf\Service\Tcpdf();

			$grouped = collect($tableData['data'])
				->mapToGroups(function($entry) {
					return [$entry['date'] => $entry];
				})
				->toArray();

			$schools = $this->getSchools();

			$arrivalColumnStyle = new \TsAccommodation\Gui2\Style\Cleaning\Arrival(true);

			$statusFormat = new \TsAccommodation\Gui2\Format\Cleaning\Status(false);
			$statusFormat->oGui = $this->_oGui;

			$roomFormat = new \TsAccommodation\Gui2\Format\Cleaning\Room();
			$roomFormat->oGui = $this->_oGui;

			$file = \Ext_Thebing_Util::getSecureDirectory(false).'room_cleaning.pdf';

			foreach($grouped as $date => $dateEntries) {

				$pdf->addPage();

				$html = "
						<h1>".$this->_oGui->calendar_format->formatByValue($date)."</h1>

						<table width=\"100%\" border=\"1\" cellpadding=\"2\">
							<tr>
								<th>".$this->t('Raum')."</th>
								<th>".$this->t('Bett')."</th>
								<th>".$this->t('Status')."</th>
								<th>".$this->t('Reinigungsart')."</th>
								<th>".$this->t('Name')."</th>
								<th>".$this->t('Abreise')."</th>
								<th>".$this->t('Anreise')."</th>
					";

				foreach($schools as $school) {
					$html .= "<th>".$school->short."</th>";
				}

				$html .= "</tr>";

				foreach($dateEntries as $entry) {

					$column = null;
					$arrivalStyle = $arrivalColumnStyle->getStyle($entry['next_arrival'], $column, $entry);

					$html .= "
								<tr>
									<td>".$roomFormat->formatByValue($entry['room'])."</td>
									<td>".$entry['bed']."</td>
									<td>".$statusFormat->formatByValue($entry['status'])."</td>
									<td>".$entry['type']."</td>
									<td>".$entry['customer']."</td>                                
									<td>".$this->_oGui->calendar_format->formatByValue($entry['departure'])."</td>                                
									<td ".$arrivalStyle.">".$this->_oGui->calendar_format->formatByValue($entry['next_arrival'])."</td>                                
						";

					foreach($schools as $school) {
						$html .= "<td>".$entry['school_'.$school->getId()]."</td>";
					}

					$html .= "</tr>";
				}

				$html .= "</table>";

				$pdf->writeHtml($html, true, false, true, false, '');

			}

			$pdf->Output(\Util::getDocumentRoot(false).$file, 'F');

			$transfer['action'] = 'showSuccess';
			$transfer['success_title'] = $this->t('PDF Download');
			$transfer['message'] = $this->t('PDF erfolgreich generiert!')
				.' <a href="'.str_replace('/storage/', '/storage/download/', $file).'?time='.time().'" target="_blank">'.$this->t('Download').'</a>';

		} else {

			$transfer['action'] = 'showError';
			$transfer['error'] =  [
				$this->_oGui->t('Für diesen Zeitraum stehen keine Daten zur Verfügung')
			];

		}

		return $transfer;

	}

	/**
	 * @param $data
	 * @param \Closure $closure
	 * @return array
	 */
	protected function requestSetStatus($_VARS) {

		$selectedIds = (array)$_VARS['id'];
		$status = $_VARS['additional'];

		foreach($selectedIds as $id) {
			$decodedId = $this->_oGui->decodeId($id);

			/* @var Status $existing */
			$cleaning = Status::getRepository()->findOneBy(['room_id' => $decodedId['room_id'], 'bed' => $decodedId['bed'], 'date' => $decodedId['date']]);

			if(!$cleaning) {
				$cleaning = new Status();
				$cleaning->room_id = (int)$decodedId['room_id'];
				$cleaning->bed = (int)$decodedId['bed'];
				$cleaning->date = $decodedId['date'];

				$lastCleaning = Status::getRepository()->getLastStatus($decodedId['room_id'], $decodedId['bed']);
			} else {
				$lastCleaning = Status::getRepository()->getLastStatus($decodedId['room_id'], $decodedId['bed'], [$cleaning->getId()]);
			}

			$cleaning->status = $status;

			if ($status !== Status::STATUS_DIRTY) {
				$cleaning->type_id = $decodedId['type_id'];
				$cleaning->cycle_id = $decodedId['cycle_id'];
			}

			$cleaning->save();

			if (
				$lastCleaning === null ||
				// Verhindern das eine Aktion auf einen älteren Eintrag einen neueren überschreibt
				$cleaning->getDate() > $lastCleaning->getDate()
			) {
				// Aktuellen Reinigungsstatus des Raumes setzen um diesen im Matching anzuzeigen
				$room = \Ext_Thebing_Accommodation_Room::getInstance($cleaning->room_id);
				$room->setCleaningStatus($cleaning->bed, $cleaning->status)
					->save();
			}

		}

		$transfer = [];
		$transfer['action'] = 'loadTable';

		return $transfer;
	}

	private function getFilterDateRange(): CarbonPeriod {

		$from = Carbon::parse($this->_oGui->calendar_format->convert($this->request->input('filter.search_time_from_1')));
		$until = Carbon::parse($this->_oGui->calendar_format->convert($this->request->input('filter.search_time_until_1')));

		return new CarbonPeriod($from->startOfDay(), $until->startOfDay());

	}

	/**
	 * Liefert alle Schulen für die Reinigungsarten erstellt wurden
	 *
	 * @return array
	 */
	private function getSchools() {

		$schoolIds = Type::getRepository()->getAllSchoolIds();

		return \Ext_Thebing_School::getRepository()
			->findBy(['id' => array_unique($schoolIds)]);

	}

	static public function getSelectOptionsTypeFilter(): array
	{

		$types = new Type();
		$options = $types->getArrayList(true);

		return $options;
	}
}

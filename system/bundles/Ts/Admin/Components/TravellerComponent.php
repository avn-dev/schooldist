<?php

namespace Ts\Admin\Components;

use Admin\Attributes\Component\Parameter;
use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Facades\Admin;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Traits\Component\WithParameters;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Notices\Http\Resources\NoticeResource;
use Ts\Http\Resources\Admin\Inquiry\InvoiceResource;
use Ts\Http\Resources\Admin\Inquiry\ServiceResource;
use Ts\Http\Resources\Admin\InquiryResource;
use Ts\Http\Resources\Admin\TravellerResource;

#[Parameter(name: 'inquiry')]
class TravellerComponent implements Component\VueComponent, Component\HasParameters
{
	use WithParameters;

	public function __construct(
		private Instance $admin,
		private \Access_Backend $access,
		private \Ext_TS_Inquiry_Contact_Traveller $traveller
	) {}

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('Traveller', '@Ts/admin/components/Traveller.vue');
	}

	public function rules(): array
	{
		$inquiries = $this->getInquiriesWithAccess()->map(fn ($inquiry) => $inquiry->id);

		return [
			'inquiry' => ['int', Rule::in($inquiries)]
		];
	}

	public function isAccessible(\Access $access): bool
	{
		return $this->getInquiriesWithAccess()->isNotEmpty();
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$inquiries = $this->getInquiriesWithAccess();

		return (new InitialData([
				'student' => $this->buildStudentPayload($request),
				'inquiries' => $inquiries->map(fn ($inquiry) => $this->buildInquiryPayload($inquiry, $request))->values(),
				'inquiryId' => (int)$this->parameters->get('inquiry', $inquiries->first()->id),
			]))
			->l10n([
				'ts.traveller.no_email' => $admin->translate('Keine E-Mail-Adresse'),
				'ts.traveller.label.booking' => $admin->translate('Buchung'),
				'ts.traveller.label.communication' => $admin->translate('Kommunikation'),
				'ts.traveller.label.reload' => $admin->translate('Ansicht aktualisieren'),
				'ts.traveller.inquiry.btn.edit' => $admin->translate('Buchung öffnen'),
				'ts.traveller.inquiry.btn.invoices' => $admin->translate('Rechnungen'),
				'ts.traveller.inquiry.btn.payments' => $admin->translate('Zahlungen'),
				'ts.traveller.inquiry.label.cancelled' => $admin->translate('Diese Buchung wurde am "%s" storniert'),
				'ts.traveller.inquiry.label.due_payments' => $admin->translate('Zahlungsverzug'),
				'ts.traveller.inquiry.label.number' => $admin->translate('Buchungsnummer'),
				'ts.traveller.inquiry.label.created' => $admin->translate('Erstellt am'),
				'ts.traveller.inquiry.label.inbox' => $admin->translate('Inbox'),
				'ts.traveller.inquiry.label.school' => $admin->translate('Schule'),
				'ts.traveller.inquiry.label.agency' => $admin->translate('Agentur'),
				'ts.traveller.inquiry.label.group' => $admin->translate('Gruppe'),
				'ts.traveller.inquiry.label.timeframe' => $admin->translate('Zeitraum'),
				'ts.traveller.inquiry.label.state' => $admin->translate('Status d. Schülers'),
				'ts.traveller.inquiry.label.tags' => $admin->translate('Tags'),
				'ts.traveller.inquiry.label.sales_person' => $admin->translate('Vertriebsmitarbeiter'),
				'ts.traveller.inquiry.label.booked' => $admin->translate('Gebucht'),
				'ts.traveller.inquiry.label.not_booked' => $admin->translate('Nicht gebucht'),
				'ts.traveller.inquiry.label.week' => $admin->translate('Woche'),
				'ts.traveller.inquiry.label.weeks' => $admin->translate('Wochen'),
				'ts.traveller.inquiry.label.block' => $admin->translate('Block'),
				'ts.traveller.inquiry.label.blocks' => $admin->translate('Blöcke'),
				'ts.traveller.inquiry.label.no_notices' => $admin->translate('Keine Notizen vorhanden'),
			]);
	}

	public function reload(Request $request)
	{
		$inquiries = $this->getInquiriesWithAccess();

		return InterfaceResponse::json([
			'student' => $this->buildStudentPayload($request),
			'inquiries' => $inquiries->map(fn ($inquiry) => $this->buildInquiryPayload($inquiry, $request))->values(),
		]);
	}

	private function buildStudentPayload(Request $request)
	{
		$inquiries = collect($this->traveller->getInquiries(bObjects: true))
			->filter(fn ($inquiry) => $this->access->hasRight('thebing_invoice_inbox_'.$inquiry->getInbox()->id))
			->mapWithKeys(fn ($inquiry) => [$inquiry->id => $inquiry]);

		$photoAction = null;
		if ($inquiries->isNotEmpty()) {
			// Ansonsten wird das Bild nicht geladen
			$this->traveller->setInquiry($inquiries->first());
			if ($this->access->hasRight('thebing_student_cards_camera')) {
				$photoAction = Router::openGui2Dialog('ts_inquiry|students_arrival', 'camera', [$inquiries->first()->id], ['inbox_id' => $inquiries->first()->getInbox()->id], initialize: false);
			}
		}

		$payload = (new TravellerResource($this->traveller))->toArray($request);
		$payload['edit_student_photo'] = $photoAction;

		return $payload;
	}

	private function buildInquiryPayload(\Ext_TS_Inquiry $inquiry, Request $request): array
	{
		$payload = (new InquiryResource($inquiry))->toArray($request);
		$payload['services'] = ServiceResource::collection($this->buildServices($inquiry));
		$payload['tabs'] = [
			['text' => Admin::translate('Allgemein'), 'component' => 'InfoTab']
		];

		if ($this->access->hasRight('thebing_invoice_display_pdf')) {
			$invoices = $inquiry->getDocuments('invoice', true, true);
			$payload['invoices'] = InvoiceResource::collection($invoices);
		}

		/*if (
			$this->access->hasRight('thebing_accommodation_icon')
		) {
			$accommodationAllocations = \Ext_Thebing_Allocation::getAllocationByInquiryId($inquiry->id, 0, true, false);
			$transfersWithProviders = array_filter($inquiry->getJourney()->getTransfersAsObjects(), fn ($journeyTransfer) => $journeyTransfer->provider_id > 0);
			dd($accommodationAllocations, $transfersWithProviders);

			$payload['tabs'][] = ['text' => Admin::translate('Zuweisungen'), 'component' => 'AllocationsTab'];
		}*/

		if ($this->access->hasRight(['ts_bookings', 'notes'])) {
			$payload['notices'] = NoticeResource::collection($inquiry->getNotices());
			$payload['tabs'][] = ['text' => Admin::translate('Notizen'), 'component' => 'NoticesTab'];
		}

		if ($this->access->hasRight('thebing_invoice_edit_student')) {
			$payload['open_dialog'] = Router::openGui2Dialog('ts_inquiry|inquiry', 'edit', [$inquiry->id], ['inbox_id' => $inquiry->getInbox()->id], initialize: false);
		}

		if ($this->access->hasRight('thebing_invoice_communication')) {
			$payload['open_communication'] = Router::openCommunication(collect([$inquiry]), application: 'booking', access: 'thebing_invoice_communication', initialize: false);
			//$payload['open_communication'] = Router::openGui2Dialog('ts_inquiry|inquiry', 'communication', [$inquiry->id], ['inbox_id' => $inquiry->getInbox()->id]);
		}

		#if ($this->access->hasRight('thebing_invoice_communication')) {
		$payload['open_invoices_dialog'] = Router::openGui2Dialog('ts_inquiry|inquiry', 'invoice', [$inquiry->id], ['inbox_id' => $inquiry->getInbox()->id], initialize: false);
		#}

		if ($this->access->hasRight('thebing_invoice_enter_payments')) {
			$payload['open_payments_dialog'] = Router::openGui2Dialog('ts_inquiry|inquiry', 'payment', [$inquiry->id], ['inbox_id' => $inquiry->getInbox()->id], initialize: false);
		}

		return $payload;
	}

	private function getInquiriesWithAccess(): Collection
	{
		$inquiries = collect($this->traveller->getInquiries(bObjects: true))
			->filter(fn ($inquiry) => $this->access->hasRight('thebing_invoice_inbox_'.$inquiry->getInbox()->id));

		return $inquiries;
	}

	private function buildServices(\Ext_TS_Inquiry $inquiry): array
	{
		$services = [];
		if ($this->access->hasRight('thebing_tuition_icon')) {
			$services = [...$services, ...$inquiry->getCourses(true)];
		}

		if ($this->access->hasRight('thebing_accommodation_icon')) {
			$services = [...$services, ... $inquiry->getAccommodations()];
		}

		if ($this->access->hasRight('thebing_pickup_icon')) {
			$services = [...$services, ...$inquiry->getTransfers('', false)];
		}

		if ($this->access->hasRight('thebing_insurance_icon')) {
			$services = [...$services, ...$inquiry->getInsurances(true)];
		}

		$services = [...$services, ...$inquiry->getActivities()];

		return $services;
	}

}
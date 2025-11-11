<?php

namespace Ts\Admin\Components\Communication;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Instance;
use Admin\Interfaces\Component;
use Admin\Traits\Component\WithParameters;
use Core\Exception\ValidatorException;
use Core\Factory\ValidatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AllocateMessageComponent implements Component\VueComponent, Component\HasParameters
{
	use WithParameters;

	public function __construct(
		private Instance $admin,
		private \Ext_TC_Communication_Message $message
	) {}

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('AllocateMessage', '@Ts/admin/components/communication/AllocateMessage.vue');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$address = Arr::first($this->message->getAddresses('from'));

		$form = [
			'firstname' => $address?->name,
			'lastname' => $address?->name,
			'phone' => '',
			'email' => '',
		];

		if ($this->message->getChannel() === \Communication\Notifications\Channels\MailChannel::CHANNEL_KEY) {
			$form['email'] = $address?->address;
		} else {
			$form['phone'] = $address?->address;
		}

		return (new InitialData(['form' => $form]))
			->l10n([
				'communication.allocate.action.enquiry.new.btn' => $admin->translate('Neue Anfrage generieren'),
				'communication.allocate.action.enquiry.allocate.btn' => $admin->translate('Zu bestehender Anfrage hinzufügen'),
				'communication.allocate.action.inquiry.allocate.btn' => $admin->translate('Zu bestehender Buchung hinzufügen'),
				'communication.allocate.action.enquiry.new.text' => $admin->translate('Sie können direkt aus dieser Nachricht eine neue Anfrage im System erstellen. Dabei werden relevante Informationen wie Absender bereits übernommen.'),
				'communication.allocate.action.enquiry.allocate.text' => $admin->translate('Sie können diese Nachricht einer bestehenden Anfrage im System zuordnen. Sie wird dann automatisch im zugehörigen Kommunikationsverlauf angezeigt.'),
				'communication.allocate.action.inquiry.allocate.text' => $admin->translate('Sie können diese Nachricht einer bestehenden Buchung im System zuordnen. Sie wird dann automatisch im zugehörigen Kommunikationsverlauf angezeigt.'),
				'communication.allocate.btn' => $admin->translate('Zuweisen'),
				'communication.allocate.form.firstname' => $admin->translate('Vorname'),
				'communication.allocate.form.lastname' => $admin->translate('Nachname'),
				'communication.allocate.form.email' => $admin->translate('E-Mail'),
				'communication.allocate.form.phone' => $admin->translate('Telefon'),
				'communication.allocate.form.search' => $admin->translate('Bestehenden Eintrag suchen'),
				'communication.allocate.form.search.placeholder' => $admin->translate('Suche').'…',
				'communication.allocate.form.search.empty' => $admin->translate('Kein Ergebnis'),
			]);
	}

	public function search(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'type' => ['required', Rule::in(['inquiry', 'enquiry'])],
			'query' => ['required', 'string'],
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$type = match ($validated['type']) {
			'inquiry' => \Ext_TS_Inquiry::TYPE_BOOKING_STRING,
			'enquiry' => \Ext_TS_Inquiry::TYPE_ENQUIRY_STRING,
			default => throw new \InvalidArgumentException('Invalid search type'),
		};

		$search = new \Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry($type);

		$options = $search->getOptions($validated['query'], [], []);

		return response()
			->json(
				collect($options)
					->map(fn ($text, $value) => ['value' => $value, 'text' => $text])
					->values()
			);
	}

	public function save(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'type' => ['required', Rule::in(['new_enquiry', 'inquiry', 'enquiry'])],
			'existing' => Rule::requiredIf(fn () => in_array($request->input('type'), ['inquiry', 'enquiry'])),
			'firstname' => Rule::requiredIf(fn () => $request->input('type') === 'new_enquiry'),
			'lastname' => Rule::requiredIf(fn () => $request->input('type') === 'new_enquiry'),
			'email' => 'email_mx',
			'phone' => 'phone_itu',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => Arr::flatten($validator->getMessageBag()->messages())
			]);
		}

		$validated = $validator->validated();

		try {
			[$success, $errors] = match ($validated['type']) {
				'new_enquiry' => $this->buildNewEnquiry($validated),
				'inquiry' => $this->assignToExisting(\Ext_TS_Inquiry::TYPE_BOOKING, $validated['existing']),
				'enquiry' => $this->assignToExisting(\Ext_TS_Inquiry::TYPE_ENQUIRY, $validated['existing']),
			};
		} catch (\Throwable $e) {
			$success = false;
			$errors = [$this->admin->translate('Es ist ein Fehler aufgetreten. Bitte wenden Sie sich an den Support', ['Communication', 'Allocation'])];

			$this->admin->getLogger('AllocateMessage')->error('Could not allocate communication message', ['log_id' => $this->message->id, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

			if (\Util::isDebugIP()) {
				dd($e);
			}
		}

		if (!$success) {
			return response()->json([
				'success' => false,
				'errors' => $errors
			]);
		}

		return response()->json(['success' => true]);
	}

	private function buildNewEnquiry(array $payload): array
	{
		$school = \Ext_Thebing_School::getSchoolFromSession();

		$inquiry = new \Ext_TS_Inquiry();
		$inquiry->type = \Ext_TS_Inquiry::TYPE_ENQUIRY;
		$inquiry->created = time();
		$inquiry->payment_method = 1;
		$inquiry->currency_id = $school->getCurrency();

		// Journey mit school_id generieren - muss da sein
		$journey = $inquiry->getJourney();
		$journey->school_id = $school->id;
		$journey->productline_id = $school->getProductLineId();
		$journey->type = \Ext_TS_Inquiry_Journey::TYPE_DUMMY;

		$customer = $inquiry->getCustomer();
		$customer->firstname = $payload['firstname'];
		$customer->lastname = $payload['lastname'];
		$customer->corresponding_language = $school->getLanguage();

		if (!empty($payload['email'])) {
			$email = $customer->getFirstEmailAddress(true);
			$email->email = $payload['email'];
			$email->master = 1;
		}

		if (!empty($payload['phone'])) {
			$detail = $customer->getJoinedObjectChild('details');
			$detail->type = 'phone_private';
			$detail->value = $payload['phone'];
		}

		if (is_array($errors = $inquiry->validate())) {
			return [false, $errors];
		}

		$numberHelper = new \Ext_Thebing_Customer_CustomerNumber($inquiry);
		$numberErrors = $numberHelper->saveCustomerNumber(true, false);

		if (!empty($numberErrors)) {
			return [false, $numberErrors];
		}

		$inquiry->save();

		\Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->id, 2);
		\Ext_Gui2_Index_Stack::save(true);

		// Nachricht mit der Abfrage verknüpfen

		$this->message->addRelation($inquiry);
		$this->message->save();

		return [true, []];
	}

	private function assignToExisting(int $type, int $id): array
	{
		$existing = \Ext_TS_Inquiry::query()
			->where('type', '&', $type)
			->findOrFail($id);

		$this->message->addRelation($existing);
		$this->message->save();

		return [true, []];
	}

}
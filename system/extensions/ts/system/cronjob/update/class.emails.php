<?php

class Ext_TS_System_Cronjob_Update_Emails extends Ext_Thebing_System_Server_Update {

	/**
	 * @var \Monolog\Logger
	 */
	protected $oLog;

	/**
	 * @var int
	 */
	protected $iMailCount = 0;

	/**
	 * Berücksichtigte Typen
	 *
	 * @var array
	 */
	protected $aTypes = [
		'booking_mail',
		'enquiry_mail',
		'birthday_mail'
	];

	/**
	 * @var bool
	 */
	public $bIgnoreExecutionError = true;

	public function __construct() {
		$this->oLog = Log::getLogger('cronjob');
	}

	/**
	 * @inheritdoc
	 */
	public function executeUpdate() {

		$this->oLog->error(__CLASS__.': Automatic emails deactivated');
		return;





//		if(!Ext_Thebing_Access::hasLicenceRight('thebing_admin_email_templates_automatic_cronjob')) {
//			$this->oLog->addInfo(__CLASS__.': No licence right');
//			return;
//		}

		$this->oLog->info(__CLASS__.': Started');

		$dNow = new DateTime();

		/** @var Ext_Thebing_Email_TemplateCronjob[] $aTemplatesCJ */
		$aTemplatesCJ = Ext_Thebing_Email_TemplateCronjob::getRepository()->findBy(['type' => $this->aTypes]);

		foreach($aTemplatesCJ as $oTemplateCJ) {

			$oTemplate = $oTemplateCJ->getTemplate();
			$aSchools = $oTemplate->getSchools();

			$this->oLog->info(__CLASS__.': Automatic e-mail "'.$oTemplateCJ->name.'"', [$oTemplate->join_schools]);
			
			foreach($aSchools as $oSchool) {

				// Auf Zeitzone der Schule wechseln
				$sTimezone = $oSchool->getTimezone();
				$dSchoolNow = clone $dNow;
				$dSchoolNow->setTimezone(new DateTimeZone($sTimezone));

				// Wenn Ausführungszeitpunkt != Schul-Uhrzeit
				if(!$oTemplateCJ->checkExecutionHour($dSchoolNow->format('G'))) {
					continue;
				}

				$this->executeType($oSchool, $oTemplateCJ, $dSchoolNow);

			}

		}

		$this->oLog->info(__CLASS__.': '.$this->iMailCount.' mail jobs added');
		$this->oLog->info(__CLASS__.': Finished');

	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Email_TemplateCronjob $oTemplateCJ
	 * @param DateTime $dDate
	 */
	protected function executeType(Ext_Thebing_School $oSchool, Ext_Thebing_Email_TemplateCronjob $oTemplateCJ, \DateTime $dDate) {

		$this->oLog->info(__CLASS__.': Execute type "'.$oTemplateCJ->type.'"', [$oTemplateCJ->id, $oTemplateCJ->name]);
		
		$oTemplate = $oTemplateCJ->getTemplate();
		$aAttachments = $oTemplate->buildMailAttachmentArray();

		switch($oTemplateCJ->type) {
			case 'birthday_mail':

				$aCustomers = $oSchool->getBirthdayCustomers($dDate, $oTemplateCJ->recipient_type);

				foreach ($aCustomers as $oTraveller) {
					try {
						// Wenn der Kunde keine automatischen E-Mails empfangen möchte diesen ignorieren
						$aExcludedRecipients = (!$oTraveller->isReceivingAutomaticEmails()) ? ['customer'] : [];

						$oInquiry = $oTraveller->getClosestInquiry();
						$aMailData = Ext_Thebing_Mail::createMailDataArray($oInquiry, $oTraveller, $oSchool, $oTemplate, $aAttachments);
						$aModifiedMailData = $oTemplateCJ->modifyMailDataArray($aMailData, $oSchool, $aExcludedRecipients);

						if ($aModifiedMailData !== null) {
							$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
							$oStackRepository->writeToStack('ts/automatic-email', $aModifiedMailData, 5);
							$this->iMailCount++;
						}
					} catch (Exception $e) {
						$this->oLog->error(__CLASS__.': '.$e->getMessage(), [
							'trace' => $e->getTraceAsString(),
							'template_cj_id' => $oTemplateCJ->id,
							'contact_id' => $oTraveller->id
						]);
					}
				}

				break;
			case 'booking_mail':
			case 'enquiry_mail':

				$aInquiries = $this->getInquiriesByCondition($oSchool, $oTemplateCJ, $dDate);

				foreach ($aInquiries as $oInquiry) {
					try {
						$oTraveller = $oInquiry->getCustomer();

						// Wenn der Kunde keine automatischen E-Mails empfangen möchte diesen ignorieren
						$aExcludedRecipients = (!$oTraveller->isReceivingAutomaticEmails()) ? ['customer'] : [];

						$aMailData = Ext_Thebing_Mail::createMailDataArray($oInquiry, $oTraveller, $oSchool, $oTemplate, $aAttachments);
						$aModifiedMailData = $oTemplateCJ->modifyMailDataArray($aMailData, $oSchool, $aExcludedRecipients);

						if ($aModifiedMailData !== null) {
							$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
							$oStackRepository->writeToStack('ts/automatic-email', $aModifiedMailData, 5);
							$this->iMailCount++;
						}
					} catch (Exception $e) {
						$this->oLog->error(__CLASS__.': '.$e->getMessage(), [
							'trace' => $e->getTraceAsString(),
							'template_cj_id' => $oTemplateCJ->id,
							'inquiry_id' => $oInquiry->id
						]);
					}
				}

				break;
			default:
				throw new RuntimeException('Unknown template type: '.$oTemplateCJ->type);
		}

	}

	/**
	 * Typ: Buchungs-E-Mail
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Email_TemplateCronjob $oTemplateCJ
	 * @param DateTime $dDate
	 * @return Ext_TS_Inquiry[]
	 */
	protected function getInquiriesByCondition(Ext_Thebing_School $oSchool, Ext_Thebing_Email_TemplateCronjob $oTemplateCJ, \DateTime $dDate) {

		$oSearch = new \ElasticaAdapter\Facade\Elastica(\ElasticaAdapter\Facade\Elastica::buildIndexName('ts_inquiry'));

		$oQuery = new \Elastica\Query\Term();
		$oQuery->setTerm('school_id', $oSchool->id);
		$oSearch->addQuery($oQuery);

		// Auskommentiert, da die automatische E-Mail nicht unbedingt an den Kunden gehen muss

		//$oQuery = new \Elastica\Query\Term();
		//$oQuery->setTerm('newsletter_original', 1);
		//$oSearch->addQuery($oQuery);

		//$oQuery = new \Elastica\Query\QueryString();
		//$oQuery->setQuery('_exists_:email_original');
		//$oSearch->addQuery($oQuery);

		if ($oTemplateCJ->type === 'enquiry_mail') {
			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('type', Ext_TS_Inquiry::TYPE_ENQUIRY_STRING);
			$oSearch->addQuery($oQuery);

			// Nur Anfragen beachten die noch nicht umgewandelt wurden
			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('type', Ext_TS_Inquiry::TYPE_BOOKING_STRING);
			$oSearch->addMustNotQuery($oQuery);
		} else {
			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('type', Ext_TS_Inquiry::TYPE_BOOKING_STRING);
			$oSearch->addQuery($oQuery);
		}

		// Storno ausschließen
		if(
			$oTemplateCJ->type === 'booking_mail' &&
			!$oTemplateCJ->ignore_cancellation
		) {
			$oQuery = new \Elastica\Query\Term();
			$oQuery->setTerm('invoice_status', 'not_cancelled');
			$oSearch->addQuery($oQuery);
		}

		// Minimale Anzahl an vergangenen Tagen seit letzter Korrespondenz
		if($oTemplateCJ->days_after_last_message > 0) {

			$oInterval = new DateInterval('P'.$oTemplateCJ->days_after_last_message.'D');
			$dDateTmp = clone $dDate;
			$dDateTmp->sub($oInterval);

			$oBoolQuery = new \Elastica\Query\BoolQuery();

			$oQuery = new \Elastica\Query\Range('last_message_date_original', ['lte' => $dDateTmp->format('Y-m-d')]);
			$oBoolQuery->addShould($oQuery);

			// Wenn es gar kein Datum gibt, dann würde Range das Dokument einfach ignorieren
//			$oQuery = new \Elastica\Query\QueryString();
//			$oQuery->setQuery('_missing_:last_message_date_original');
//			$oBoolQuery->addShould($oQuery);

			$oBoolQuery->setMinimumShouldMatch(1);
			$oSearch->addQuery($oBoolQuery);

		}

		// Tage vor/nach
		// Wenn der Wert auf 0 steht, wird demnach der heutige Tag ($dDate) als Filter benutzt
		$dDateDays = clone $dDate;
		if($oTemplateCJ->days > 0) {
			$oInterval = new DateInterval('P'.$oTemplateCJ->days.'D');
			if($oTemplateCJ->temporal_direction  === 'after') {
				$dDateDays->sub($oInterval);
			} else {
				$dDateDays->add($oInterval);
			}
		}

		$aCriteria = [
			'gte' => $dDateDays->format('Y-m-d'),
			'lte' => $dDateDays->format('Y-m-d'),
			//'format' => 'yyyy-MM-dd' // ab Elasticsearch 1.5 erst
		];

		if($oTemplateCJ->event_type === 'reminder_date') {
			$oQuery = \Ext_TS_Inquiry_Index_Gui2_Data::getPaymentDueQuery($aCriteria);
		} else {
			$oQuery = new \Elastica\Query\Range($this->getEventTypeIndexField($oTemplateCJ->event_type), $aCriteria);
		}

		$oSearch->addQuery($oQuery);

		$oSearch->setFields(['_id']);
		$oSearch->setLimit(1000);
		$aResult = $oSearch->search();

		$aResult = array_map(function($aInquiry) {
			return Ext_TS_Inquiry::getInstance($aInquiry['_id']);
		}, $aResult['hits']);

		return $aResult;

	}

	/**
	 * Mapping: Template-Event-Typ <> Index-Feld
	 *
	 * @param string $sField
	 * @return string
	 */
	protected function getEventTypeIndexField($sField) {
		switch($sField) {
			case 'created':
				return 'created_original';
			case 'service_start';
				return 'all_start_original';
			case 'service_end':
				return 'all_end_original';
			case 'course_start':
				return 'first_course_start_original';
			case 'course_end':
				return 'course_last_end_date_original';
			case 'follow_up_date':
				return 'follow_up_original';
		}

		throw new InvalidArgumentException('Invalid field: '.$sField);
	}

}

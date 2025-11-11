<?php

namespace TsRdstation\Service;

use \League\OAuth2\Client\Token\AccessToken;

/**
 * Inquiry (lead) in Fidelo -> CONVERSION IN RD (conversion event name as "Query") and also OPPORTUNITY in RD (funnel stage: lead)
 * Booking (registration) in Fidelo -> CONVERSION IN RD (conversion event name as "Reservation") and also OPPORTUNITY in RD (funnel stage: qualified lead)
 * Payment in Fidelo -> CONVERSION IN RD (name of the conversion event as "Payment") and also SALES in RD (funnel stage: customer) ** Here is it possible to send the "Amount" paid in the sale to the RD? **
 * Cancellation in Fidelo -> CONVERSION in RD (the name of the conversion event can be "Cancellation") and change in the funnel stage to: LEAD.
 */
class RDStation {
	
	protected $accessToken;
	protected $log;

	public function __construct(AccessToken $accessToken) {
		$this->accessToken = $accessToken;
		$this->log = \Log::getLogger('api', 'rdstation');
	}
	
	public function syncInquiry(\Ext_TS_Inquiry $inquiry, $create=false) {
		
		$student = $inquiry->getFirstTraveller();

		if(empty($student->email)) {
			throw new \RuntimeException(sprintf('Student "%s" could not be transferred because no e-mail address is stored.', $student->getName()));
		}
		
		if($inquiry->isCancelled()) {
			
			$patchContact = $this->patchContact($student);
			
			$this->postConversion($inquiry, 'Cancellation');
			$this->putFunnel($patchContact, 'Lead', false);

		} elseif($create === true) {
			
			$patchContact = $this->patchContact($student);
			
			$this->postConversion($inquiry, 'Reservation');
			$this->postOpportunity($student, 'Qualified Lead');
			$this->putFunnel($patchContact, 'Qualified Lead');

		}
	
	}
	
	public function syncEnquiry(\Ext_TS_Enquiry $enquiry, $create=false) {

		$student = $enquiry->getFirstTraveller();

		if(empty($student->email)) {
			throw new \RuntimeException(sprintf('Student "%s" could not be transferred because no e-mail address is stored.', $student->getName()));
		}
		
		if($create === true) {
			
			$this->patchContact($student);
			
			$this->postConversion($enquiry, 'Query');
			$this->postOpportunity($student, 'Lead');

		}

	}
	
	public function syncPayment(\Ext_Thebing_Inquiry_Payment $payment) {
		
		$inquiry = $payment->getInquiry();

		$student = $inquiry->getFirstTraveller();

		if(empty($student->email)) {
			throw new \RuntimeException(sprintf('Student "%s" could not be transferred because no e-mail address is stored.', $student->getName()));
		}
		
		$patchContact = $this->patchContact($student);

		$this->postConversion($inquiry, 'Payment');
		$this->postSale($student, 'Client', (float)$inquiry->getTotalPayedAmount());
		
	}

	public function postSale(\Ext_TS_Inquiry_Contact_Abstract $student, $funnelName, $payedAmount) {

		$aData = [
			'event_type' => 'SALE',
			'event_family' => 'CDP'
		];
		
		$aData['payload'] = [
			'email' => $student->email,			
			'funnel_name' => $funnelName,
			'value' => $payedAmount
		];
		
		$postEvent = $this->send('/platform/events', $aData, 'POST');

		return $postEvent;
	}
	
	public function putFunnel($contact, $funnelName, $opportunity=true) {

		$aData = [
			'lifecycle_stage' => $funnelName,
			'opportunity' => $opportunity
		];
		
		$putFunnel = $this->send('/platform/contacts/'.$contact['uuid'].'/funnels/default', $aData, 'PUT');

		return $putFunnel;
	}
	
	public function postOpportunity(\Ext_TS_Inquiry_Contact_Abstract $student, $funnelName) {

		$aData = [
			'event_type' => 'OPPORTUNITY',
			'event_family' => 'CDP'
		];
		
		$aData['payload'] = [
			'email' => $student->email,			
			'funnel_name' => $funnelName
		];
		
		$postEvent = $this->send('/platform/events', $aData, 'POST');

		return $postEvent;
	}
	
	public function postConversion(\Ext_TS_Inquiry_Abstract $inquiry, $name) {

		$student = $inquiry->getFirstTraveller();

		$aData = [
			'event_type' => 'CONVERSION',
			'event_family' => 'CDP'
		];
		
		$aData['payload'] = [
			'conversion_identifier' => $name,
			'email' => $student->email,			
			'name' => $student->getName(),
			'traffic_source' => $inquiry->getMeta('rdstation_traffic_source', '')
		];	
		
		$postEvent = $this->send('/platform/events', $aData, 'POST');

		return $postEvent;
	}
	
	public function patchContact(\Ext_TS_Inquiry_Contact_Abstract $student) {

		$nationalities = \Ext_Thebing_Nationality::getNationalities(true, 'en');
		$languages = \Ext_Thebing_Data::getLanguageSkills(true);

		$aData = [
			'name' => $student->getName(),
			'personal_phone' => $student->detail_phone_private,
			'mobile_phone' => $student->detail_phone_mobile,
			'cf_date_of_birth' => $student->birthday,
			'cf_nationality' => $nationalities[$student->nationality],
			'cf_mother_tongue' => $languages[$student->language],
		];
		
		$gender = $student->getGender('en');

		// RDStation mag nur male/female
		if(!empty($gender) && $student->gender != 3) {
			$aData['cf_gender'] = $gender;
		}

		$patchContact = $this->send('/platform/contacts/email:'.$student->email, $aData, 'PATCH');

		return $patchContact;
	}
	
	public function getAccountInfo() {
		
		$accountInfo = $this->send('/marketing/account_info');
		
		return $accountInfo;
	}
	
	public function getContact(\Ext_TS_Inquiry_Contact_Abstract $student) {

		try {
			$contact = $this->send('/platform/contacts/email:'.$student->email, [], 'GET');
			
			return $contact;
		} catch(\GuzzleHttp\Exception\ClientException $e) {
			
		}
		
	}
	
	protected function send($sUrl, array $aData=[], $sMethod='GET') {
		
		$oauth2 = new \TsRdstation\Provider\Oauth2();

		$sUrl = $oauth2->getHost().$sUrl;

		$client = new \GuzzleHttp\Client(['headers' => ['Authorization' => 'Bearer '.$this->accessToken->getToken()]]);
		
		if(!empty($aData)) {
			$aData = [
				\GuzzleHttp\RequestOptions::JSON => $aData
			];
		}
		$res = $client->request($sMethod, $sUrl, $aData);
		
		$sResponse = $res->getBody()->getContents();
		
		$aResponse = json_decode($sResponse, true);
		
		$this->log->info('Request', ['url'=>$sUrl, 'data'=>$aData, 'response'=>$aResponse]);
		
		return $aResponse;		
	}

	static public function getProvider() {
		
		$oBundleHelper = new \Core\Helper\Bundle();
		$aBundleConfig = $oBundleHelper->getBundleConfigData('TsRdstation');
		
		$oProvider = new \TsRdstation\Provider\Oauth2([
			'clientId'                => $aBundleConfig['rdstation']['client_id'],    // The client ID assigned to you by the provider
			'clientSecret'            => $aBundleConfig['rdstation']['client_secret'],   // The client password assigned to you by the provider
			'redirectUri'             => \Util::getProxyHost().'rdstation/callback'
		]);

		return $oProvider;
	}
	
	static public function getAccessToken() {
		
		$log = \Log::getLogger('api', 'rdstation');
		
		$sAccessToken = \System::d('rdstation_access_token');
		
		if(!empty($sAccessToken)) {
			$oAccessToken = unserialize($sAccessToken);
			
			if(
				$oAccessToken instanceof AccessToken &&
				$oAccessToken->hasExpired()
			) {
				
				$oProvider = \TsRdstation\Service\RDStation::getProvider();
				
				$oAccessToken = $oProvider->getAccessToken('refresh_token', [
					'refresh_token' => $oAccessToken->getRefreshToken()
				]);
				
				\System::s('rdstation_access_token', serialize($oAccessToken));
				
				$log->info('Access token refreshed');
				
			}
			
			return $oAccessToken;
		}
		
	}
	
}

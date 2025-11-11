<?php

namespace TsActivities\Entity\Activity;

use Carbon\Carbon;
use Communication\Interfaces\Model\CommunicationSubObject;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;
use TsActivities\Dto\BlockEvent;

/**
 * @property string $id
 * @property string $created
 * @property string $active
 * @property string $valid_until ???
 * @property string $editor_id
 * @property string $creator_id
 * @property string $block_id
 * @property string $traveller_id
 * @property string $journey_activity_id
 * @property string $week
 * @method static BlockTravellerRepository getRepository()
 */
class BlockTraveller extends \Ext_Thebing_Basic implements HasCommunication {

	protected $_sTable = 'ts_activities_blocks_travellers';

	protected $_sTableAlias = 'ts_actbt';

	protected $_sPlaceholderClass = \TsActivities\Service\Placeholder\BlockTraveller::class;

	protected $_aJoinedObjects = [
		'contact' => [
			'class' => \Ext_TS_Contact::class,
			'type' => 'parent',
			'key' => 'traveller_id'
		],
		'block' => [
			'class' => \TsActivities\Entity\Activity\Block::class,
			'type' => 'parent',
			'key' => 'block_id'
		],
        'journey_activity' => [
            'class' => \Ext_TS_Inquiry_Journey_Activity::class,
            'type' => 'parent',
            'key' => 'journey_activity_id'
        ],
	];

	public function getContact(): \Ext_TS_Contact {
		return $this->getJoinedObject('contact');
	}

    /**
     * @return Block
     */
	public function getBlock() : Block {
	    return $this->getJoinedObject('block');
    }

    /**
     * @return \Ext_TS_Inquiry_Journey_Activity
     */
    public function getJourneyActivity() : \Ext_TS_Inquiry_Journey_Activity {
        return $this->getJoinedObject('journey_activity');
    }

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getLanguage() {
		$oInquiry = $this->getInquiry();
		$oCustomer = $oInquiry->getCustomer();
		$sLanguage = $oCustomer->getLanguage();
		return $sLanguage;
	}

	public function getInquiry() {
		return \Ext_TS_Inquiry_Journey_Activity::getInstance($this->journey_activity_id)->getInquiry();
	}


	/**
	 * @TODO Das ist total fahrlässig, dass hier nicht noch einmal eine activity_id in der Entität existiert
	 *
	 * @return \TsActivities\Entity\Activity
	 */
	public function getActivity() {
		return \Ext_TS_Inquiry_Journey_Activity::getInstance($this->journey_activity_id)->getActivity();
	}

	/**
	 * @return BlockEvent[]
	 */
	public function generateBlockEvents() {

		$periods = [];
		$block = $this->getBlock();
		$days = $block->getDays();

		foreach ($days as $day) {
			$start = Carbon::createFromFormat('Y-m-d H:i:s', $this->week.' '.$day->start_time);
			$end = Carbon::createFromFormat('Y-m-d H:i:s', $this->week.' '.$day->end_time);

			if ($day->day != 1) {
				$addModifier = sprintf('+ %sdays', $day->day - 1);
				$start->modify($addModifier);
				$end->modify($addModifier);
			}

			$periods[] = new BlockEvent($start, $end, $day->place);
		}

		return $periods;

	}

	public function getStartDate()
	{
		return $this->getBlock()->getStartDate($this->week);
	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getInquiry()
		];
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsActivities\Communication\Application\Activities::class;
	}

	public function getCommunicationLabel(LanguageAbstract $l10n): string
	{
		return $this->getInquiry()->getCommunicationLabel($l10n);
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getJourneyActivity()->getJourney()->getSchool();
	}

}

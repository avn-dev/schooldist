<?php

namespace Ts\Admin\Search;

use Admin\Dto\Component\Search;
use Admin\Instance;
use Admin\Interfaces\Component\InteractsWithSearch;
use Admin\Interfaces\RouterAction;
use Elastica\Query;
use ElasticaAdapter\Facade\Elastica;

class Traveller implements InteractsWithSearch
{
	public function __construct(
		private Instance $admin
	) {}

	public function isAccessible(\Access $access): bool
	{
		return $access->hasRight('thebing_invoice_icon');
	}

	public function getLabel(): string
	{
		return $this->admin->translate('Schüler');
	}

	public function search(string $query, int $limit): Search\SearchResult
	{
		$searchResult = new Search\SearchResult();

		$search = static::elastica();
		$search->setSort('created_original');
		$search->setLimit($limit);

		$bool = new Query\BoolQuery();
		$bool->setMinimumShouldMatch(1);

		$escapedQuery = $search->escapeTerm($query);
		if (mb_substr($query, -1) !== '*') {
			$escapedQuery .= '*';
		}

		$queryObject = new Query\QueryString();
		$queryObject->setQuery($escapedQuery);
		$queryObject->setDefaultField('document_number_all');
		$queryObject->setDefaultOperator('AND');
		$bool->addShould($queryObject);

		$queryObject = new Query\QueryString();
		$queryObject->setQuery($escapedQuery);
		$queryObject->setDefaultField('customer_number');
		$queryObject->setDefaultOperator('AND');
		$bool->addShould($queryObject);

		$queryObject = new Query\QueryString();
		$queryObject->setQuery($escapedQuery);
		$queryObject->setDefaultField('customer_name');
		$queryObject->setDefaultOperator('AND');
		$bool->addShould($queryObject);

		$queryObject = new Query\QueryString();
		$queryObject->setQuery($escapedQuery);
		$queryObject->setDefaultField('email_original');
		$queryObject->setDefaultOperator('AND');
		$bool->addShould($queryObject);

		$queryObject = new Query\QueryString();
		$queryObject->setQuery(\Ext_TS_Inquiry::TYPE_BOOKING_STRING);
		$queryObject->setDefaultField('type');
		$queryObject->setDefaultOperator('AND');
		$bool->addMust($queryObject);

		$search->addMustQuery($bool);

		// Nur berechtige Schulen
		$schoolIds = array_keys(\Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true));
		$queryObject = new \Elastica\Query\Terms('school_id', $schoolIds);
		$search->addQuery($queryObject);

		// Nur berechtigte Inboxen
		$inboxes = array_keys(\Ext_Thebing_Client::getFirstClient()->getInboxList(true));
		$queryObject = new \Elastica\Query\Terms('inbox', $inboxes);
		$search->addQuery($queryObject);

		$result = $search->search();

		$customerIds = [];
		foreach ($result['hits'] as $hit) {
			if (in_array($hit['fields']['customer_id'][0], $customerIds)) {
				continue;
			}

			$searchResult->addRow('inquiry.'.$hit['_id'], static::buildRouterAction($hit, false), [$query]);

			$customerIds[] = $hit['fields']['customer_id'][0];
		}

		return $searchResult;
	}

	private static function elastica(): Elastica
	{
		$search = new Elastica(Elastica::buildIndexName('ts_inquiry'));
		$search->setFields(['_id', 'customer_id', 'customer_number', 'customer_name']);
		return $search;
	}

	private static function buildRouterAction(array $hit, bool $initialize = true): RouterAction
	{
		$text = [$hit['fields']['customer_number'][0], $hit['fields']['customer_name'][0]];

		return \Ts\Admin\Router::openTraveller(
			travellerId: $hit['fields']['customer_id'][0],
			inquiryId: $hit['_id'],
			text: $text,
			initialize: $initialize
		);
	}
}
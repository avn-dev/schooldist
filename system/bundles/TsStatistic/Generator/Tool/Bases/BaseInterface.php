<?php

namespace TsStatistic\Generator\Tool\Bases;

interface BaseInterface {

	public function getTitle(): string;

	public function getQuery(string $select, string $joins, string $where, string $groupBy);

}
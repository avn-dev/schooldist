<?php

namespace TsStatistic\Model\Filter\Tool;

interface FilterInterface {

	public function getJoinParts(): array;

	public function getJoinPartsAdditions(): array;

	public function getSqlWherePart(): string;

}

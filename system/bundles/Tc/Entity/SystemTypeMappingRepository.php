<?php

namespace Tc\Entity;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SystemTypeMappingRepository extends \WDBasic_Repository
{
	public function findBySystemType(string $entityType, string|array $systemTypes): Collection
	{
        $systemTypes = Arr::wrap($systemTypes);

        $collection = $this->_oEntity::query()
            ->select('tc_stm.*')
            ->join('tc_system_type_mapping_to_system_types as tc_stmtst', function ($join) use ($systemTypes) {
                $join->on('tc_stmtst.mapping_id', '=', 'tc_stm.id')
                    ->whereIn('tc_stmtst.type', Arr::except($systemTypes, null));
            })
            ->where('tc_stm.type', $entityType)
            ->groupBy('tc_stm.id')
            ->get();

        if (in_array(null, $systemTypes)) {
            $emptyTypeCollection = $this->_oEntity::query()
                ->select('tc_stm.*')
                ->leftJoin('tc_system_type_mapping_to_system_types as tc_stmtst', function ($join) use ($systemTypes) {
                    $join->on('tc_stmtst.mapping_id', '=', 'tc_stm.id');
                })
                ->whereNull('tc_stmtst.mapping_id')
                ->groupBy('tc_stm.id')
                ->get();

            $collection = $collection->merge($emptyTypeCollection);
        }

        return $collection;
	}
}
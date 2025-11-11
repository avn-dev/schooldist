<?php

namespace Cms\Helper\Collector;

use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\TimeDataCollector;

class QueryCollector extends PDOCollector implements \DebugBar\DataCollector\Renderable {
 
    protected $timeCollector;
    protected $queries = [];
    protected $renderSqlWithParams = false;
    protected $findSource = false;
    protected $middleware = [];
    protected $explainQuery = false;
    protected $explainTypes = ['SELECT']; // ['SELECT', 'INSERT', 'UPDATE', 'DELETE']; for MySQL 5.6.3+
    protected $showHints = false;
    protected $reflection = [];

    /**
     * @param TimeDataCollector $timeCollector
     */
    public function __construct(TimeDataCollector $timeCollector = null)
    {
        $this->timeCollector = $timeCollector;
    }

    /**
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @param \Illuminate\Database\Connection $connection
     */
    public function addQuery($query, $time, $sTrace, $aExplain)
    {
        $explainResults = [];
        #$time = $time / 1000;
        $endTime = microtime(true);
        $startTime = $endTime - $time;
        $hints = $this->performQueryAnalysis($query);

        // Run EXPLAIN on this query (if needed)
        if (!empty($aExplain)) {
            $explainResults = $aExplain;
        }

        $this->queries[] = [
            'query' => $query,
            'type' => 'query',
            'time' => $time,
            'trace' => $sTrace,
            'explain' => $explainResults,
            'hints' => $this->showHints ? $hints : null,
        ];

    }

    /**
     * Explainer::performQueryAnalysis()
     *
     * Perform simple regex analysis on the code
     *
     * @package xplain (https://github.com/rap2hpoutre/mysql-xplain-xplain)
     * @author e-doceo
     * @copyright 2014
     * @version $Id$
     * @access public
     * @param string $query
     * @return string
     */
    protected function performQueryAnalysis($query)
    {
        $hints = [];
        if (preg_match('/^\\s*SELECT\\s*`?[a-zA-Z0-9]*`?\\.?\\*/i', $query)) {
            $hints[] = 'Use <code>SELECT *</code> only if you need all columns from table';
        }
        if (preg_match('/ORDER BY RAND()/i', $query)) {
            $hints[] = '<code>ORDER BY RAND()</code> is slow, try to avoid if you can.
				You can <a href="http://stackoverflow.com/questions/2663710/how-does-mysqls-order-by-rand-work" target="_blank">read this</a>
				or <a href="http://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function" target="_blank">this</a>';
        }
        if (strpos($query, '!=') !== false) {
            $hints[] = 'The <code>!=</code> operator is not standard. Use the <code>&lt;&gt;</code> operator to test for inequality instead.';
        }
        if (stripos($query, 'WHERE') === false && preg_match('/^(SELECT) /i', $query)) {
            $hints[] = 'The <code>SELECT</code> statement has no <code>WHERE</code> clause and could examine many more rows than intended';
        }
        if (preg_match('/LIMIT\\s/i', $query) && stripos($query, 'ORDER BY') === false) {
            $hints[] = '<code>LIMIT</code> without <code>ORDER BY</code> causes non-deterministic results, depending on the query execution plan';
        }
        if (preg_match('/LIKE\\s[\'"](%.*?)[\'"]/i', $query, $matches)) {
            $hints[] = 	'An argument has a leading wildcard character: <code>' . $matches[1]. '</code>.
								The predicate with this argument is not sargable and cannot use an index if one exists.';
        }
        return $hints;
    }

    /**
     * Reset the queries.
     */
    public function reset()
    {
        $this->queries = [];
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $totalTime = 0;
        $queries = $this->queries;

        $statements = [];
        foreach ($queries as $query) {
            $totalTime += $query['time'];

            $statements[] = [
                'sql' => $query['query'],
                'type' => $query['type'],
                'params' => [],
                'bindings' => $query['bindings'],
                'hints' => $query['hints'],
                'backtrace' => $query['trace'],
                'duration' => $query['time'],
                'duration_str' => ($query['type'] == 'transaction') ? '' : $this->formatDuration($query['time']),
                'stmt_id' => $query['trace'],
                'connection' => $query['connection'],
            ];

            //Add the results from the explain as new rows
            foreach($query['explain'] as $explain){
                $statements[] = [
                    'sql' => ' - EXPLAIN #' . $explain->id . ': `' . $explain->table . '` (' . $explain->select_type . ')',
                    'type' => 'explain',
                    'params' => $explain,
                    'row_count' => $explain->rows,
                    'stmt_id' => $explain->id,
                ];
            }
        }

        $nb_statements = array_filter($queries, function ($query) {
            return $query['type'] == 'query';
        });

        $data = [
            'nb_statements' => count($nb_statements),
            'nb_failed_statements' => 0,
            'accumulated_duration' => $totalTime,
            'accumulated_duration_str' => $this->formatDuration($totalTime),
            'statements' => $statements
        ];
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'queries';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return [
            "queries" => [
                "icon" => "database",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "queries",
                "default" => "[]"
            ],
            "queries:badge" => [
                "map" => "queries.nb_statements",
                "default" => 0
            ]
        ];
    }

}
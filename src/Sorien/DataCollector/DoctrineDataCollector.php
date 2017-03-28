<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataCollector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DoctrineDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineDataCollector extends DataCollector
{
    private $loggers = array();

    /**
     * @var Connection[] $dbs
     */
    private $dbs;

    function __construct($dbs)
    {
        $this->dbs = $dbs;
    }

    /**
     * Adds the stack logger for a connection.
     *
     * @param string     $name
     * @param DebugStack $logger
     */
    public function addLogger($name, DebugStack $logger)
    {
        $this->loggers[$name] = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $queries = array();
        foreach ($this->loggers as $name => $logger) {
            $queries[$name] = $this->sanitizeQueries($logger->queries, $name);
        }

        $this->data = array(
            'queries'     => $queries,
        );
    }

    public function getQueryCount()
    {
        return array_sum(array_map('count', $this->data['queries']));
    }

    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function getTime()
    {
        $time = 0;
        foreach ($this->data['queries'] as $queries) {
            foreach ($queries as $query) {
                $time += $query['executionMS'];
            }
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'db';
    }

    private function sanitizeQueries($queries, $connectionName)
    {
        foreach ($queries as $i => $query) {
            $queries[$i] = $this->sanitizeQuery($query, $connectionName);
        }

        return $queries;
    }

    private function sanitizeQuery($query, $connectionName)
    {
        $query['params'] = $query['params'] ? $this->cloneVar($query['params']) : $query['params'];

        if ($query['sql'] instanceof QueryBuilder) {
            $query['sql'] = $query['sql']->getSQL();
        }

        return $query;
    }

}

<?php

namespace SynergiTech\DataTables\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    public function loadFixture($name)
    {
        $path = dirname(__FILE__) . '/fixtures/'. $name . '.json';
        $fixture = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error loading fixture {$name}.json: " . json_last_error_msg());
        }

        $baseRequest = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => [],
            'order' => [],
        ];

        $request = array_merge($baseRequest, $fixture['request']);

        return [ $request, $fixture['data'] ];
    }
}

<?php

namespace SynergiTech\DataTables\Tests;

class TestModel
{
    public static $currentResultSet = [];

    public static function reset()
    {
        self::$currentResultSet = [];
    }

    public static function populateFromFixture($fixture)
    {
        self::$currentResultSet = $fixture;
    }

    public static function query()
    {
        return new TestQuery(self::class);
    }

    public function __get($thing)
    {
        throw new \OutOfBoundsException();
    }
}

<?php

namespace SynergiTech\DataTables\Tests;

class TestQuery
{
    private $model;
    private $resultSet;
    public $rows_offset;
    public $rows_limit;

    public function __construct($model)
    {
        $this->model = $model;
        $this->resultSet = $model::$currentResultSet;
    }

    public function count()
    {
        return count($this->resultSet);
    }

    public function rows_offset($offset)
    {
        $this->rows_offset = $offset;
        return $this;
    }

    public function rows_limit($limit)
    {
        $this->rows_limit = $limit;
        return $this;
    }

    public function createModel($properties)
    {
        $model = new $this->model();

        foreach ($properties as $property => $value) {
            if (!is_array($value)) {
                $model->{$property} = $value;
                continue;
            }

            $model->{$property} = $this->createModel($value);
        }

        return $model;
    }

    public function get()
    {
        $models = [];

        foreach ($this->resultSet as $set) {
            $model = $this->createModel($set);
            $models[] = $model;
        }

        return $models;
    }
}

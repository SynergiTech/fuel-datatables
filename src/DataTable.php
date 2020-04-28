<?php

namespace SynergiTech\DataTables;

class DataTable
{
    protected $draw;
    protected $start;
    protected $length;
    protected $search;
    protected $order;
    protected $class;
    protected $query;
    protected $columns;
    protected $allowedColumns = [];
    protected $rowFormatters = [];
    protected $escapedColumns = [];
    protected $rawColumns = [];

    public function __construct($params, $class, $query = null)
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Could not find class $class");
        }

        $this->draw = $params['draw'];
        $this->start = $params['start'];
        $this->length = $params['length'];
        $this->search = (array_key_exists('value', $params['search'])) ? array_filter(preg_split('/\s+/', trim($params['search']['value']))) : [];
        $this->order = $params['order'];
        $this->class = $class;
        $this->columns = $params['columns'];

        if ($query === null) {
            $query = $this->createQuery();
        }
        $this->query = $query;
    }

    protected function createQuery()
    {
        $class = $this->class;
        return $class::query();
    }

    protected function buildQuery($query)
    {
        if (!empty($this->search)) {
            foreach ($this->search as $term) {
                $query->and_where_open();
                foreach ($this->columns as $column) {
                    if ($column['searchable'] == 'true') {
                        $query->or_where($column['data'], 'LIKE', '%' . $term . '%');
                    }
                }
                $query->and_where_close();
            }
        }

        foreach ($this->order as $order) {
            if (isset($this->columns[$order['column']]) && $this->columns[$order['column']]['orderable'] == 'true') {
                $query->order_by($this->columns[$order['column']]['data'], $order['dir']);
            }
        }

        return $query;
    }

    protected function formatValue($value, $key)
    {
        if ($value instanceof \Fuel\Core\Date) {
            return $value->get_timestamp();
        }

        return $value;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getResponse()
    {
        foreach ($this->columns as $key => $column) {
            if (!array_key_exists('data', $column) || ! in_array($column['data'], $this->allowedColumns)) {
                unset($this->columns[$key]);
            }
        }

        $query = clone $this->query;

        $response = [
            'draw' => $this->draw,
            'recordsTotal' => $query->count(),
            'data' => [],
        ];

        $query = $this->buildQuery($query);

        $response['recordsFiltered'] = $query->count();

        $query = $query
            ->rows_offset($this->start)
            ->rows_limit($this->length)
            ->get();

        foreach ($query as $db_row) {
            $data_row = [];

            foreach ($this->columns as $column) {
                $properties = $db_row;
                $key = $column['data'];
                $value = null;

                // get related value
                if (strpos($key, '.') !== false) {
                    $split = explode('.', $key);
                    $ref = &$data_row;

                    // follow the path to get the value we want
                    foreach ($split as $piece) {
                        if (!is_object($properties)) {
                            $properties = null;
                        } else {
                            try {
                                $properties = $properties->{$piece};
                            } catch (\OutOfBoundsException $oob) {
                                // don't include this column if it's out of bounds
                                continue 2;
                            }
                        }
                        // if it isn't already set, assume we will go deeper
                        if (!isset($ref[$piece])) {
                            $ref[$piece] = [];
                        }

                        // if it is set but isn't an array, the inputs were invalid
                        // don't set this property to allow datatables to explain to the UI whats messed up
                        if (!is_array($ref[$piece])) {
                            continue 2;
                        }

                        // get a reference to the item we just set
                        $ref = &$ref[$piece];
                    }

                    // finally overwrite the last array created to the actual value
                    $ref = $this->formatValue($properties, $key);
                    continue;
                }

                if (isset($properties->{$key})) {
                    $value = $properties->$key;
                }

                if ($value === null && isset($properties->virtualFields) && array_key_exists($key, $properties->virtualFields)) {
                    $val = $properties->virtualFields[$key];
                    $value = (is_callable($val)) ? $val() : $val;
                }

                $data_row[$key] = $this->formatValue($value, $key);
            }

            $row = $this->formatRow($db_row, $data_row);
            $response['data'][] = $this->escapeRow($row);
        }

        return $response;
    }

    public function setAllowedColumns($input)
    {
        $this->allowedColumns = $input;
        return $this;
    }

    public function addAllowedColumn($column)
    {
        if (!in_array($column, $this->allowedColumns)) {
            $this->allowedColumns[] = $column;
        }

        return $this;
    }

    public function setEscapedColumns($columns = null)
    {
        if ($columns === null) {
            $columns = ['*'];
        }

        $this->escapedColumns = $columns;
        return $this;
    }

    public function getEscapedColumns()
    {
        return $this->escapedColumns;
    }

    public function setRawColumns($columns)
    {
        $this->rawColumns = $columns;
        return $this;
    }

    public function getRawColumns()
    {
        return $this->rawColumns;
    }

    public function addRowFormatter(callable $formatter)
    {
        $this->rowFormatters[] = $formatter;
        return $this;
    }

    public function formatRow($db_row, $data_row)
    {
        foreach ($this->rowFormatters as $formatter) {
            $data_row = $formatter($db_row, $data_row);
        }

        return $data_row;
    }

    public function escapeValue($column, $value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $leaf) {
                $value[$key] = $this->escapeValue("$column.$key", $leaf);
            }

            return $value;
        }

        if (!in_array($column, $this->escapedColumns) && !in_array('*', $this->escapedColumns)) {
            return $value;
        }
        if (in_array($column, $this->rawColumns)) {
            return $value;
        }


        return e($value);
    }

    public function escapeRow($row)
    {
        foreach ($row as $column => $value) {
            $row[$column] = $this->escapeValue($column, $value);
        }

        return $row;
    }

    public static function fromGet($class)
    {
        return new self(\Input::get(), $class);
    }
}

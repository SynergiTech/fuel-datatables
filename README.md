# FuelPHP DataTables
![Build status](https://api.travis-ci.org/SynergiTech/fuel-datatables.svg?branch=master)

Implement DataTables with FuelPHP's ORM

## Installation

```
$ composer require synergitech/fuel-datatables
```

## Basic Usage

You need to call the `setAllowedColumns()` function to define which columns can be accessed and returned.

```php
class API extends Controller_Rest
{
  public function get_index()
  {
      $datatable = \SynergiTech\DataTables\DataTable::fromGet(\Model\YourModel::class);
      $datatable->setAllowedColumns([
         'id',
         'uuid',
         'name',
         'related_model.name',
         'created_at',
         'updated_at'
      ]);

      return $this->response($datatable->getResponse());
  }
}
```

### Customize query

If you wish to do more complex ORM queries, you can simply call the `getQuery()` function which will return the FuelPHP ORM's query object, which you can then manipulate as you need to.

```php
$datatable->getQuery()
   ->where('id', ">", 0)
   ->related("group")
   ->where("group.name", "!=", "guests")
   ->related("another_relation");
```

### Row formatters

You can provide custom callbacks that will be executed for each row to be returned in the response. You can use this to manipulate each row in the response.
```php
$datatable->addRowFormatter(function ($model, $outputRow) {
    $outputRow['example'] = count($model->a_many_relation);
    return $outputRow;
});
```

### XSS filtering

To make it easier for you to manage filtering your output, you can ask for all or specific rows to be encoded on output.
By default, we leave XSS filtering up to you.

#### Escape all columns
```php
$datatable->setEscapedColumns();
```
With exceptions:
```php
$datatable->setEscapedColumns()
    ->setRawColumns(['html_body']);
```

#### Escape some columns
```php
$datatable->setEscapedColumns(['id', 'slug']);
```

#### Turn off escaping
```php
$datatable->setEscapedColumns([]);
```

### Pre-requisites

* [FuelPHP ORM (SynergiTech Fork)](https://github.com/SynergiTech/fuel-orm)
* jQuery
* DataTables

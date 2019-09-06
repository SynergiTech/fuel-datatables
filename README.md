# FuelPHP DataTables

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

### Advanced Usage

If you wish to do more complex ORM queries, you can simply call the `getQuery()` function which will return the FuelPHP ORM's query object, which you can then manipulate as you need to.

```php
    $datatable->getQuery()
       ->where('id', ">", 0)
       ->related("group")
       ->where("group.name", "!=", "guests")
       ->related("another_relation");
```

### Pre-requisites

* [FuelPHP ORM (SynergiTech Fork)](https://github.com/SynergiTech/fuel-orm)
* jQuery
* DataTables

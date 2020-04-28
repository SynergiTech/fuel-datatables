<?php

namespace SynergiTech\DataTables\Tests;

class DataTableTest extends BaseTestCase
{
    public function setUp()
    {
        TestModel::reset();
    }

    public function test_throwsOnInvalidClass()
    {
        $this->expectException(\RuntimeException::class);
        new \SynergiTech\DataTables\DataTable([], 'ClassThatDoesNotExist');
    }

    public function test_emptyAllowedColumns()
    {
        $fixture = $this->loadFixture('empty-columns');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);

        $res = $dt->getResponse();

        $this->assertCount(1, $res['data']);
        $this->assertEmpty($res['data'][0]);
    }

    public function test_obeysAllowedColumns()
    {
        $fixture = $this->loadFixture('allowed-columns');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['allowed']);
        $dt->addAllowedColumn('add-col');

        $res = $dt->getResponse();
        $this->assertCount(1, $res['data']);
        $this->assertCount(2, $res['data'][0]);
        $this->assertArrayHasKey('allowed', $res['data'][0]);
        $this->assertArrayHasKey('add-col', $res['data'][0]);
        $this->assertArrayNotHasKey('not-allowed', $res['data'][0]);
    }

    public function test_search()
    {
        $fixture = $this->loadFixture('search');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $mock = $this->getMockBuilder(TestQuery::class)
            ->setConstructorArgs([TestModel::class])
            ->setMethods(['and_where_open', 'and_where_close', 'or_where'])
            ->getMock();
        $mock->expects($this->exactly(2))->method('and_where_open');
        $mock->expects($this->exactly(2))->method('and_where_close');
        $mock->expects($this->exactly(2))
            ->method('or_where')
            ->withConsecutive(
                [ 'searchable', 'LIKE', '%search%' ],
                [ 'searchable', 'LIKE', '%test%' ]
            );

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class, $mock);
        $dt->setAllowedColumns(['searchable', 'not-searchable']);
        $dt->getResponse();
    }

    public function test_order()
    {
        $fixture = $this->loadFixture('order');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $mock = $this->getMockBuilder(TestQuery::class)
            ->setConstructorArgs([TestModel::class])
            ->setMethods(['order_by'])
            ->getMock();
        $mock->expects($this->once())
            ->method('order_by')
            ->with('orderable', 'asc');

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class, $mock);
        $dt->setAllowedColumns(['orderable', 'not-orderable']);
        $dt->getResponse();
    }

    public function test_paging()
    {
        $fixture = $this->loadFixture('paging');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $mock = $this->getMockBuilder(TestQuery::class)
            ->setConstructorArgs([TestModel::class])
            ->setMethods(['rows_limit', 'rows_offset'])
            ->getMock();
        $mock->expects($this->once())
            ->method('rows_limit')
            ->with(100)
            ->will($this->returnSelf());
        $mock->expects($this->once())
            ->method('rows_offset')
            ->with(200)
            ->will($this->returnSelf());

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class, $mock);
        $dt->setAllowedColumns(['orderable', 'not-orderable']);
        $dt->getResponse();
    }

    public function test_rowFilterAll()
    {
        $fixture = $this->loadFixture('filtering');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['text', 'unsafe_html', 'safe_html']);
        $dt->setEscapedColumns();

        $this->assertSame(['*'], $dt->getEscapedColumns());

        $response = $dt->getResponse();
        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $row) {
            foreach ($row as $val) {
                $this->assertSame('ENCODED', $val);
            }
        }
    }

    public function test_rowFilterDisable()
    {
        $fixture = $this->loadFixture('filtering');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['text', 'unsafe_html', 'safe_html']);
        $dt->setEscapedColumns();
        $dt->setEscapedColumns([]);

        $response = $dt->getResponse();
        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $row) {
            foreach ($row as $val) {
                $this->assertNotSame('ENCODED', $val);
            }
        }
    }

    public function test_rowFilterSome()
    {
        $fixture = $this->loadFixture('filtering');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['text', 'unsafe_html', 'safe_html']);
        $dt->setEscapedColumns(['unsafe_html']);

        $response = $dt->getResponse();
        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $row) {
            $this->assertSame('ENCODED', $row['unsafe_html']);
            $this->assertNotSame('ENCODED', $row['safe_html']);
            $this->assertNotSame('ENCODED', $row['text']);
        }
    }

    public function test_rowFilterAllWithExceptions()
    {
        $fixture = $this->loadFixture('filtering');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['text', 'unsafe_html', 'safe_html']);
        $dt->setEscapedColumns();
        $dt->setRawColumns(['safe_html']);

        $this->assertSame(['safe_html'], $dt->getRawColumns());

        $response = $dt->getResponse();
        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $row) {
            $this->assertSame('ENCODED', $row['unsafe_html']);
            $this->assertNotSame('ENCODED', $row['safe_html']);
            $this->assertSame('ENCODED', $row['text']);
        }
    }

    public function test_rowFormatters()
    {
        $fixture = $this->loadFixture('row-formatter');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['test']);

        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $mock->expects($this->exactly(2))
            ->method('myCallBack')
            ->willReturn([ 'test' => 'changed' ]);

        $dt->addRowFormatter([$mock, 'myCallback']);

        $response = $dt->getResponse();
        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $row) {
            $this->assertArrayHasKey('test', $row);
            $this->assertSame('changed', $row['test']);
        }
    }

    public function test_getRelationValue()
    {
        $fixture = $this->loadFixture('relations');
        $request = $fixture[0];
        $data = $fixture[1];

        TestModel::populateFromFixture($data);

        $dt = new \SynergiTech\DataTables\DataTable($request, TestModel::class);
        $dt->setAllowedColumns(['one.name', 'one.two.name', 'one.property.does.not.exist']);

        $response = $dt->getResponse();

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $row) {
            $this->assertSame('test', $row['one']['name']);
            $this->assertSame('deep', $row['one']['two']['name']);
            $this->assertArrayNotHasKey('property', $row['one']);
        }
    }
}

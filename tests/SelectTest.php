<?php

namespace Tivins\Database\Tests;

use Tivins\Database\Exceptions\{ConnectionException, DatabaseException};

class SelectTest extends TestBase
{
    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testSelect()
    {
        $db = TestConfig::db();
        $query = $db->select('test', 't')
            ->addFields('t');
        $this->checkQuery($query,
            'select t.* from t_test `t`',
            []);
    }

    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testSelectFieldWithoutAlias()
    {
        $db = TestConfig::db();
        $query = $db->select('test', 't')
            ->addField('t', 'id');
        $this->checkQuery($query,
            'select t.`id` from t_test `t`',
            []);
    }

    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testSelectFieldAlias()
    {
        $db = TestConfig::db();
        $query = $db->select('test', 't')
            ->addField('t', 'id', 't_id');
        $this->checkQuery($query,
            'select t.`id` as t_id from t_test `t`',
            []);
    }

    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testSelectJoin()
    {
        $db = TestConfig::db();

        $query = $db->select('test', 't')
            ->addField('t', 'id', 't_id')
            ->leftJoin('other', 'o', 'o.oid = t.id')
            ;
        $this->checkQuery($query,
            'select t.`id` as t_id from t_test `t` left join `t_other` `o` on o.oid = t.id',
            []);

        $query = $db->select('test', 't')
            ->addField('t', 'id', 't_id')
            ->innerJoin('other', 'o', 'o.oid = t.id')
        ;
        $this->checkQuery($query,
            'select t.`id` as t_id from t_test `t` inner join `t_other` `o` on o.oid = t.id',
            []);
    }

    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testOrderBy()
    {
        $db = TestConfig::db();

        $query = $db->select('test', 't')
            ->addFields('t')
            ->groupBy('t.value')
        ;
        $this->checkQuery($query,
            'select t.* from t_test `t` group by t.value',
            []);
    }

    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testNull()
    {
        $db = TestConfig::db();
        $query = $db
            ->select('test', 't')
            ->addFields('t')
            ->isNull('t.field')
            ->isNotNull('t.another_field')
            ;
        $this->checkQuery($query,
            'select t.* from t_test `t` where t.field is null and t.another_field is not null',
            []);
    }

    /**
     * @throws ConnectionException | DatabaseException
     */
    public function testGroupBy()
    {
        $db = TestConfig::db();

        $query = $db->select('geometries', 'g')
            ->addExpression('ST_Simplify(g.geom, 1024)', 'geom')
            ->having($db->and()->isNotNull('geom'))
            ;

        $this->checkQuery($query,
            'select ST_Simplify(g.geom, 1024) as geom from t_geometries `g` having geom is not null',
            []);

    }

    /**
     * @throws ConnectionException
     * @throws DatabaseException
     */
    public function testLimits()
    {
        $db = TestConfig::db();
        
        $db->truncateTable('users');
        foreach (range(0, 19) as $index) {
            $db->insert('users')
                ->fields(['name' => 'user' . $index])
                ->execute();
        }

        $num = $db->select('users', 'u')
            ->addCount('*', 'count')
            ->execute()
            ->fetchField();
        $this->assertEquals(20, $num);

        $query = $db->select('users', 'u')
            ->addFields('u')
            ->limit(5)
            ->orderBy('u.uid', 'asc');
        $this->checkQuery($query, 'select u.* from t_users `u` order by u.uid asc limit 5', []);
        $results = $query->execute()->fetchAll();
        $this->assertCount(5, $results);
        $this->assertEquals('user0', reset($results)->name);


        $query = $db->select('users', 'u')
            ->addFields('u')
            ->limitFrom(5, 5)
            ->orderBy('u.uid', 'asc');
        $results = $query->execute()->fetchAll();
        $this->assertCount(5, $results);
        $this->assertEquals('user5', reset($results)->name);
    }
}
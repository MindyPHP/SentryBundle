<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 20/06/16
 * Time: 10:25
 */

namespace Mindy\QueryBuilder\Tests;

use Mindy\QueryBuilder\Aggregation\Avg;
use Mindy\QueryBuilder\Aggregation\Count;
use Mindy\QueryBuilder\Aggregation\Max;
use Mindy\QueryBuilder\Aggregation\Min;
use Mindy\QueryBuilder\Aggregation\Sum;
use Mindy\QueryBuilder\Database\Pgsql\Adapter as PgsqlAdapter;
use Mindy\QueryBuilder\Database\Mysql\Adapter as MysqlAdapter;
use Mindy\QueryBuilder\Database\Sqlite\Adapter as SqliteAdapter;
use Mindy\QueryBuilder\LookupBuilder\Legacy;
use Mindy\QueryBuilder\Q\QAndNot;
use Mindy\QueryBuilder\Q\QOr;
use Mindy\QueryBuilder\Q\QOrNot;
use Mindy\QueryBuilder\QueryBuilderFactory;
use PDO;

abstract class DummyQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueryBuilderFactory
     */
    public $factory;

    /**
     * @return \Mindy\QueryBuilder\BaseAdapter
     */
    abstract public function getAdapter();

    protected function setUp()
    {
        parent::setUp();
        $this->factory = new QueryBuilderFactory($this->getAdapter(), new Legacy($this->getAdapter()->getLookupCollection()->getLookups()));
    }

    protected function tearDown()
    {
        $this->qb = null;
        parent::tearDown();
    }

    protected function getQueryBuilder()
    {
        return $this->factory->getQueryBuilder();
    }

    public function testSimple()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT * FROM [[tests]]'),
            $qb->from('tests')->toSQL()
        );
    }

    public function testGroupOrder()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT * FROM [[comment]] GROUP BY [[id]] ORDER BY [[id]] ASC'),
            $qb->from('comment')->order(['id'])->group(['id'])->toSQL()
        );
    }

    public function testGroupOrderAlias()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].* FROM [[comment]] AS [[t]] GROUP BY [[t]].[[id]] ORDER BY [[t]].[[id]] ASC'),
            $qb->select('t.*')->from(['t' => 'comment'])->order(['t.id'])->group(['t.id'])->toSQL()
        );
    }

    public function testJoin()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].* FROM [[comment]] AS [[t]] LEFT JOIN [[user]] AS [[u]] ON [[t]].[[user_id]]=[[u]].[[id]]'),
            $qb->select('t.*')->from(['t' => 'comment'])->join('LEFT JOIN', 'user', ['t.user_id' => 'u.id'], 'u')->toSQL()
        );
    }

    public function testJoinCloneAfterToSQL()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].* FROM [[comment]] AS [[t]] LEFT JOIN [[user]] AS [[u]] ON [[t]].[[user_id]]=[[u]].[[id]]'),
            $qb->select('t.*')->from(['t' => 'comment'])->join('LEFT JOIN', 'user', ['t.user_id' => 'u.id'], 'u')->toSQL()
        );

        $clone = clone $qb;
        $clone->select('t.id');
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].[[id]] FROM [[comment]] AS [[t]] LEFT JOIN [[user]] AS [[u]] ON [[t]].[[user_id]]=[[u]].[[id]]'),
            $clone->toSQL()
        );
    }

    public function testJoinCloneBeforeToSQL()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $qb->select('t.*')->from(['t' => 'comment'])->join('LEFT JOIN', 'user', ['t.user_id' => 'u.id'], 'u');
        $clone = clone $qb;

        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].* FROM [[comment]] AS [[t]] LEFT JOIN [[user]] AS [[u]] ON [[t]].[[user_id]]=[[u]].[[id]]'),
            $qb->toSQL()
        );

        $clone->select('t.id');
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].[[id]] FROM [[comment]] AS [[t]] LEFT JOIN [[user]] AS [[u]] ON [[t]].[[user_id]]=[[u]].[[id]]'),
            $clone->toSQL()
        );
    }

    public function testJoinRawSelect()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[t]].[[id]] AS [[foo]], [[t]].[[user_id]] AS [[bar]] FROM [[comment]] AS [[t]] LEFT JOIN [[user]] AS [[u]] ON [[t]].[[user_id]]=[[u]].[[id]]'),
            $qb->select('t.id AS foo, t.user_id AS bar')->from(['t' => 'comment'])
                ->join('LEFT JOIN', 'user', ['t.user_id' => 'u.id'], 'u')->toSQL()
        );
    }

    public function testJoinSimple()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT * FROM [[comment]] LEFT JOIN [[user]] ON [[user_id]]=[[id]]'),
            $qb->from('comment')->join('LEFT JOIN', 'user', ['user_id' => 'id'])->toSQL()
        );
    }

    public function testJoinMultiple()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT * FROM [[comment]] LEFT JOIN [[user]] ON [[user_id]]=[[id]] LEFT JOIN [[group]] ON [[group_id]]=[[id]]'),
            $qb->from('comment')
                ->join('LEFT JOIN', 'user', ['user_id' => 'id'])
                ->join('LEFT JOIN', 'group', ['group_id' => 'id'])
                ->toSQL()
        );
    }

    public function testInsert()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('INSERT INTO [[test]] ([[name]]) VALUES ((@qwe@))'),
            $qb->setTypeInsert()->insert('test', ['name'], [
                ['qwe']
            ])->toSQL()
        );
    }

    public function testUpdate()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('UPDATE [[test]] SET [[name]]=@bar@ WHERE [[name]]=@foo@'),
            $qb->setTypeUpdate()->update('test', ['name' => 'bar'])->where(['name' => 'foo'])->toSQL()
        );
    }

    public function testDelete()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('DELETE FROM [[test]] WHERE [[name]]=@qwe@'),
            $qb->setTypeDelete()->where(['name' => 'qwe'])->from('test')->toSQL()
        );
    }

    public function testSelectSubSelect()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $qbSub = clone $qb;
        $qbSub
            ->setTypeSelect()->from('comment')
            ->where(['is_published' => true]);
        if ($adapter instanceof \Mindy\QueryBuilder\Database\Pgsql\Adapter) {
            $sql = 'SELECT (SELECT * FROM [[comment]] WHERE [[is_published]]=TRUE) AS [[id]] FROM [[test]]';
        } else {
            $sql = 'SELECT (SELECT * FROM [[comment]] WHERE [[is_published]]=1) AS [[id]] FROM [[test]]';
        }
        $this->assertEquals(
            $adapter->quoteSql($sql),
            $qb->setTypeSelect()->select([
                'id' => $qbSub->toSQL()
            ])->from('test')->toSQL()
        );
    }

    public function testFromSubSelect()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $qbSub = clone $qb;
        $qbSub
            ->setTypeSelect()->from(['comment'])->select('user_id')
            ->where(['is_published' => true]);
        if ($adapter instanceof \Mindy\QueryBuilder\Database\Pgsql\Adapter) {
            $sql = 'SELECT [[t]].[[user_id]] FROM (SELECT [[user_id]] FROM [[comment]] WHERE [[is_published]]=TRUE) AS [[t]]';
        } else {
            $sql = 'SELECT [[t]].[[user_id]] FROM (SELECT [[user_id]] FROM [[comment]] WHERE [[is_published]]=1) AS [[t]]';
        }
        $this->assertEquals(
            $adapter->quoteSql($sql),
            $qb->setTypeSelect()->select(['t.user_id'])->from(['t' => $qbSub->toSQL()])->toSQL()
        );
    }

    public function testJoinSubSelect()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $qbSub = clone $qb;
        $qbSub->setTypeSelect()->from('user')->select('id');
        $subSql = $qbSub->toSQL();
        $this->assertEquals(
            $adapter->quoteSql('SELECT [[c]].* FROM [[comment]] AS [[c]] INNER JOIN (SELECT [[id]] FROM [[user]]) AS [[u]] ON [[u]].[[id]]=[[c]].[[user_id]]'),
            $qb->setTypeSelect()->select(['c.*'])->from(['c' => 'comment'])
                ->join('INNER JOIN', $subSql, ['u.id' => 'c.user_id'], 'u')->toSQL()
        );
    }

    public function testQOr()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT * FROM [[test]] WHERE [[id]]=1 AND ([[username]]=@foo@ OR [[username]]=@bar@)'),
            $qb->setTypeSelect()->from('test')
                ->where([
                    'id' => 1,
                    new QOr([
                        ['username' => 'foo'],
                        ['username' => 'bar']
                    ])
                ])->toSQL()
        );
    }

    public function testQNot()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        if ($adapter instanceof \Mindy\QueryBuilder\Database\Pgsql\Adapter) {
            $sql = 'SELECT * FROM [[test]] WHERE [[is_published]]=TRUE AND (NOT ([[id]]=2)) AND (NOT ([[username]]=@foo@ OR [[username]]=@bar@))';
        } else {
            $sql = 'SELECT * FROM [[test]] WHERE [[is_published]]=1 AND (NOT ([[id]]=2)) AND (NOT ([[username]]=@foo@ OR [[username]]=@bar@))';
        }
        $this->assertEquals(
            $adapter->quoteSql($sql),
            $qb
                ->setTypeSelect()
                ->from('test')
                ->where(['is_published' => true])
                ->addWhere(new QAndNot(['id' => 2]))
                ->addWhere(new QOrNot([
                    ['username' => 'foo'],
                    ['username' => 'bar']
                ]))->toSQL()
        );
    }

    public function testOrderLimitOffset()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $this->assertEquals(
            $adapter->quoteSql('SELECT * FROM [[test]] ORDER BY [[id]] ASC LIMIT 10 OFFSET 10'),
            $qb->from('test')->order('id')->limit(10)->offset(10)->toSQL()
        );
    }

    public function testWhere()
    {
        $qb = $this->getQueryBuilder();
        $qb->where('[[a]] != 1');
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT * WHERE [[a]] != 1'), $qb->toSQL());

        $qb->addWhere([
            'id__in' => [1, 2, 3]
        ]);
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT * WHERE [[a]] != 1 AND ([[id]] IN (1, 2, 3))'), $qb->toSQL());

        $subQb = $this->getQueryBuilder();
        $subQb->from('users')->where(['id' => 5]);

        $qb->addWhere([
            'username__in' => $subQb
        ]);
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT * WHERE [[a]] != 1 AND ([[id]] IN (1, 2, 3)) AND ([[username]] IN (SELECT * FROM [[users]] WHERE [[id]]=5))'), $qb->toSQL());
    }

    public function testWhereNot()
    {
        $qb = $this->getQueryBuilder();
        $qb->where(new QAndNot('[[a]] != 1'));
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT * WHERE NOT ([[a]] != 1)'), $qb->toSQL());

        $qb = $this->getQueryBuilder();
        $qb->where(new QAndNot([
            'id__in' => [1, 2, 3],
            'price__gte' => 100
        ]));
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT * WHERE NOT ([[id]] IN (1, 2, 3) AND [[price]]>=100)'), $qb->toSQL());

        $qb = $this->getQueryBuilder();
        $qb->where(new QOrNot([
            'id__in' => [1, 2, 3],
            'price__gte' => 100
        ]));
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT * WHERE NOT ([[id]] IN (1, 2, 3) OR [[price]]>=100)'), $qb->toSQL());
    }

    public function testClone()
    {
        $sql = $this->getAdapter()->quoteSql('SELECT [[a]], [[b]], [[c]] FROM [[test]]');

        $qb = $this->getQueryBuilder();
        $qb->select('a, b, c')->from('test');

        $this->assertEquals($sql, $qb->toSQL());
        $copy = clone $qb;
        $this->assertEquals($sql, $copy->toSQL());
    }

    public function testUnion()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('a, b, c')->from('test');

        $newQb = clone $qb;
        $qb->union($newQb, true);
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('SELECT [[a]], [[b]], [[c]] FROM [[test]] UNION ALL (SELECT [[a]], [[b]], [[c]] FROM [[test]])'), $qb->toSQL());
    }

    public function testCrazyTwo()
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM [[test]]
INNER JOIN [[test2]] ON [[test]].[[fkey]]=[[test2]].[[id]]
LEFT JOIN [[test3]] ON [[test2]].[[fkey]]=[[test3]].[[id]]
WHERE [[test]].[[name]]=@username@ AND [[test2]].[[amount]] IS NOT NULL AND
([[test3]].[[id]] IS NULL OR [[test3]].[[status]] IN (@passed@, @active@, @registered@))
GROUP BY [[test]].[[user_id]]
HAVING [[test3]].[[age]]>10
ORDER BY [[test]].[[created]] DESC NULLS LAST
SQL;
        $qb = $this->getQueryBuilder();
        $qb
            ->select('COUNT(*)')
            ->from('test')
            ->join('INNER JOIN', 'test2', ['test.fkey' => 'test2.id'])
            ->join('LEFT JOIN', 'test3', ['test2.fkey' => 'test3.id'])
            ->where([
                'test.name' => 'username',
                'test2.amount__isnull' => false,
                new QOr([
                    ['test3.id__isnull' => true],
                    ['test3.status__in' => ['passed', 'active', 'registered']]
                ])
            ])
            ->group(['test.user_id'])
            ->having(['test3.age__gt' => 10])
            ->order(['-test.created'], 'NULLS LAST');
        $adapter = $qb->getAdapter();
        $sqlRaw = str_replace(["\n"], ' ', str_replace('  ', '', $sql));
        $this->assertEquals($adapter->quoteSql($sqlRaw), $qb->toSQL());
    }

    public function testCrazyOne()
    {
        $qb = $this->getQueryBuilder();
        $qb
            ->select(['u.*', 'count' => 'SELECT 1+1'])
            ->from(['c' => 'comment'])
            ->where([
                'u.is_published' => true,
                'u.group_id' => $this->getQueryBuilder()->select('id')->from('group')->where(['is_published' => true])->toSQL()
            ])
            ->addWhere(new QAndNot([
                'u.id__gte' => 1
            ]))
            ->join('LEFT JOIN', 'users', ['c.user_id' => 'u.id'], 'u');
        $adapter = $qb->getAdapter();

        $sql = 'SELECT [[u]].*, (SELECT 1+1) AS [[count]] FROM [[comment]] AS [[c]] LEFT JOIN [[users]] AS [[u]] ON [[c]].[[user_id]]=[[u]].[[id]] WHERE [[u]].[[is_published]]=' . $adapter->getBoolean(1) . ' AND [[u]].[[group_id]]=(SELECT [[id]] FROM [[group]] WHERE [[is_published]]=' . $adapter->getBoolean(1) . ') AND (NOT ([[u]].[[id]]>=1))';

        $this->assertEquals($adapter->quoteSql($sql), $qb->toSQL());
    }

    public function testAggregation()
    {
        $adapter = $this->getAdapter();
        $qb = $this->getQueryBuilder();
        $qb->from('comment')->select(new Count('*', 'test'));
        $this->assertEquals($adapter->quoteSql('SELECT COUNT(*) AS [[test]] FROM [[comment]]'), $qb->toSQL());
        $qb->from('comment')->select(new Count('*'));
        $this->assertEquals($adapter->quoteSql('SELECT COUNT(*) FROM [[comment]]'), $qb->toSQL());
        $qb->from('comment')->select(new Avg('*'));
        $this->assertEquals($adapter->quoteSql('SELECT AVG(*) FROM [[comment]]'), $qb->toSQL());
        $qb->from('comment')->select(new Sum('*'));
        $this->assertEquals($adapter->quoteSql('SELECT SUM(*) FROM [[comment]]'), $qb->toSQL());
        $qb->from('comment')->select(new Min('*'));
        $this->assertEquals($adapter->quoteSql('SELECT MIN(*) FROM [[comment]]'), $qb->toSQL());
        $qb->from('comment')->select(new Max('*'));
        $this->assertEquals($adapter->quoteSql('SELECT MAX(*) FROM [[comment]]'), $qb->toSQL());
    }

    public function testRawTableName()
    {
        $this->assertEquals('test', $this->getAdapter()->getRawTableName("{{%test}}"));
        $this->assertEquals('test', $this->getAdapter()->getRawTableName("test"));
    }

    public function testCreateTable()
    {
        $adapter = $this->getAdapter();

        $qb = $this->getQueryBuilder();
        $qb->createTable('test', [
            'id' => 'int(11)'
        ], '');
        $this->assertEquals($adapter->quoteSql('CREATE TABLE [[test]] (
	[[id]] int(11)
)'), $qb->toSQL());

        $qb->createTable('test', [
            'id' => 'int(11)'
        ], 'CHARACTER SET utf8 COLLATE utf8_bin');
        $this->assertEquals($adapter->quoteSql('CREATE TABLE [[test]] (
	[[id]] int(11)
) CHARACTER SET utf8 COLLATE utf8_bin'), $qb->toSQL());

        $qb->createTable('test', 'SELECT * FROM [[clone]]', '');
        $this->assertEquals($adapter->quoteSql('CREATE TABLE [[test]] SELECT * FROM [[clone]]'), $qb->toSQL());

        $qb->createTable('test', 'LIKE [[clone]]', '');
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('CREATE TABLE [[test]] LIKE [[clone]]'), $qb->toSQL());

        $qb->createTableIfNotExists('test', 'LIKE [[clone]]', '');
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('CREATE TABLE IF NOT EXISTS [[test]] LIKE [[clone]]'), $qb->toSQL());
        
        $qb->createTableIfNotExists('test', ['id' => 'int(11)'], '');
        $adapter = $qb->getAdapter();
        $this->assertEquals($adapter->quoteSql('CREATE TABLE IF NOT EXISTS [[test]] (
	[[id]] int(11)
)'), $qb->toSQL());
    }

    public function testConvertToDbValue()
    {
        $a = $this->getAdapter();
        $this->assertEquals('1', $a->convertToDbValue(true));
        $this->assertEquals('0', $a->convertToDbValue(false));
        $this->assertEquals('NULL', $a->convertToDbValue(null));
    }

    public function testDropTable()
    {
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $qb->dropTable('test');
        $this->assertEquals($adapter->quoteSql('DROP TABLE [[test]]'), $qb->toSQL());
    }

    protected function createPDOInstance()
    {
        $adapter = $this->getAdapter();
        if ($adapter instanceof PgsqlAdapter) {
            return new PDO('pgsql:dbname=test;host=localhost', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else if ($adapter instanceof SqliteAdapter) {
            return new PDO('sqlite::memory:', '', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else if ($adapter instanceof MysqlAdapter) {
            return new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
    }

    public function testRenameColumn($resultSql = null)
    {
        if (empty($resultSql)) {
            $resultSql = 'ALTER TABLE [[test]] CHANGE [[name]] [[title]] varchar(255) DEFAULT NULL';
        }
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $adapter->setDriver($this->createPDOInstance());
        $sql = $qb->renameColumn('test', 'name', 'title');
        $this->assertEquals($adapter->quoteSql($resultSql), $sql);
    }
    
    public function testRenameTable($resultSql = null)
    {
        if (empty($resultSql)) {
            $resultSql = 'RENAME TABLE [[test]] TO [[foo]]';
        }
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $sql = $qb->renameTable('test', 'foo');
        $this->assertEquals($adapter->quoteSql($resultSql), $sql);
    }

    public function testAddPrimaryKey($resultSql = null)
    {
        if (empty($resultSql)) {
            $resultSql = 'ALTER TABLE [[test]] ADD CONSTRAINT [[user_id]] PRIMARY KEY ([[foo]])';
        }
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $sql = $qb->addPrimaryKey('user_id', 'test', ['foo']);
        $this->assertEquals($adapter->quoteSql($resultSql), $sql);
        $sql = $qb->addPrimaryKey('user_id', 'test', 'foo');
        $this->assertEquals($adapter->quoteSql($resultSql), $sql);
    }

    public function testDropPrimaryKey($resultSql = null)
    {
        if (empty($resultSql)) {
            $resultSql = 'ALTER TABLE [[test]] DROP CONSTRAINT [[user_id]]';
        }
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $sql = $qb->dropPrimaryKey('user_id', 'test');
        $this->assertEquals($adapter->quoteSql($resultSql), $sql);
    }

    public function testAlterColumn($resultSql = null)
    {
        if (empty($resultSql)) {
            $resultSql = 'ALTER TABLE [[test]] CHANGE [[name]] [[name]] varchar(255)';
        }
        $qb = $this->getQueryBuilder();
        $adapter = $qb->getAdapter();
        $sql = $qb->alterColumn('test', 'name', 'varchar(255)');
        $this->assertEquals($adapter->quoteSql($resultSql), $sql);
    }

    public function testSqlSelect()
    {
        $a = $this->getAdapter();
        $this->assertEquals('SELECT *', $a->sqlSelect([]));
        $this->assertEquals($a->quoteSql('SELECT [[foo]], [[bar]]'), $a->sqlSelect(['foo', 'bar']));
    }

    public function testAddColumn($resultSql = null)
    {
        if ($resultSql === null) {
            $resultSql = 'ALTER TABLE [[test]] ADD COLUMN [[name]] varchar(255)';
        }
        $a = $this->getAdapter();
        $this->assertEquals($a->quoteSql($resultSql), $a->sqlAddColumn('test', 'name', 'varchar(255)'));
    }

    public function testCreateIndex()
    {
        $a = $this->getAdapter();
        $this->assertEquals($a->quoteSql('CREATE INDEX [[idx_name]] ON [[test]] ([[name]])'),
            $a->sqlCreateIndex('test', 'idx_name', ['name'], false));
    }

    public function testAddForeignKey()
    {
        $a = $this->getAdapter();
        // $tableName, $name, $columns, $refTable, $refColumns, $delete = null, $update = null
        $this->assertEquals($a->quoteSql('ALTER TABLE [[test]] ADD CONSTRAINT [[name]] FOREIGN KEY ([[fk_qwe]]) REFERENCES [[foo]] ([[bar]]) ON DELETE SET NULL ON UPDATE SET NULL'),
            $a->sqlAddForeignKey('test', 'name', 'fk_qwe', 'foo', 'bar', 'SET NULL', 'SET NULL'));
    }

    public function testDropColumn()
    {
        $a = $this->getAdapter();
        $this->assertEquals($a->quoteSql('ALTER TABLE [[test]] DROP COLUMN [[name]]'),
            $a->sqlDropColumn('test', 'name'));
    }

    public function testDropIndex()
    {
        $a = $this->getAdapter();
        $this->assertEquals($a->quoteSql('DROP INDEX [[name]]'), $a->sqlDropIndex('test', 'name'));
    }
}
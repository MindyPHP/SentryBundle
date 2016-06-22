<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 20/06/16
 * Time: 17:17
 */

namespace Mindy\QueryBuilder\Sqlite;

use Mindy\QueryBuilder\BaseAdapter;
use Mindy\QueryBuilder\IAdapter;
use Mindy\QueryBuilder\ILookupCollection;

class Adapter extends BaseAdapter implements IAdapter
{
    /**
     * Quotes a table name for use in a query.
     * A simple table name has no schema prefix.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, "`") !== false ? $name : "`" . $name . "`";
    }

    /**
     * Quotes a column name for use in a query.
     * A simple column name has no prefix.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '`') !== false || $name === '*' ? $name : '`' . $name . '`';
    }
    
    public function convertToDateTime($value = null)
    {
        static $dateTimeFormat = "Y-m-d H:i:s";
        if ($value === null) {
            $value = date($dateTimeFormat);
        } elseif (is_numeric($value)) {
            $value = date($dateTimeFormat, $value);
        } elseif (is_string($value)) {
            $value = date($dateTimeFormat, strtotime($value));
        }
        return $value;
    }

    /**
     * @param $value
     * @return int
     */
    public function convertToBoolean($value)
    {
        return (bool)$value ? 1 : 0;
    }

    /**
     * @return ILookupCollection
     */
    public function getLookupCollection()
    {
        return new LookupCollection($this->lookups);
    }

    /**
     * @return string
     */
    public function getRandomOrder()
    {
        return 'RANDOM()';
    }
}
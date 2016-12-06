<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05/12/2016
 * Time: 21:34
 */

namespace Mindy\Pagination;

use Mindy\Pagination\DataSource\DataSourceInterface;
use Mindy\Pagination\Handler\NativePaginationHandler;
use Mindy\Pagination\Handler\PaginationHandlerInterface;

/**
 * Class PaginationFactory
 * @package Mindy\Pagination
 */
class PaginationFactory
{
    /**
     * @var DataSourceInterface[]
     */
    protected $dataSources = [];

    /**
     * @param $source
     * @param array $options
     * @param PaginationHandlerInterface $handler
     * @return Pagination
     */
    public function createPagination($source, array $options = [], PaginationHandlerInterface $handler)
    {
        $handler = $handler ?: new NativePaginationHandler();
        return new Pagination($source, $options, $handler, $this->findDataSource($source));
    }

    /**
     * @param $source
     * @return DataSourceInterface
     */
    protected function findDataSource($source)
    {
        foreach ($this->dataSources as $dataSource) {
            if ($dataSource->supports($source)) {
                return $dataSource;
            }
        }

        throw new \RuntimeException('Unknown source type');
    }

    /**
     * @param DataSourceInterface $dataSource
     */
    public function addDataSource(DataSourceInterface $dataSource)
    {
        $this->dataSources[] = $dataSource;
    }
}
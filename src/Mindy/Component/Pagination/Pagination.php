<?php

namespace Mindy\Component\Pagination;

use Mindy\Component\Application\App;

/**
 * Class Pagination
 * @package Mindy\Pagination
 */
class Pagination extends BasePagination
{
    public function __toString()
    {
        @trigger_error('The ' . __CLASS__ . ' class is deprecated since version 3.0 and will be removed in 4.0.', E_USER_DEPRECATED);
        return (string)$this->render();
    }

    public function toJson()
    {
        @trigger_error('The ' . __CLASS__ . ' class is deprecated since version 3.0 and will be removed in 4.0.', E_USER_DEPRECATED);
        return [
            'objects' => $this->data,
            'meta' => [
                'total' => (int)$this->getTotal(),
                'pages_count' => (int)$this->getPagesCount(),
                'page' => (int)$this->getPage(),
                'page_size' => (int)$this->getPageSize(),
            ]
        ];
    }

    public function render($view = "core/pager/pager.html")
    {
        @trigger_error('The ' . __CLASS__ . ' class is deprecated since version 3.0 and will be removed in 4.0.', E_USER_DEPRECATED);
        return App::getInstance()->template->render($view, [
            'pager' => $this,
            'view' => $this->createView()
        ]);
    }
}

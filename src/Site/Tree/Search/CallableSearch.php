<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Site\Tree\Search;

use Ixocreate\Cms\Site\Tree\Item;
use Ixocreate\Cms\Site\Tree\SearchInterface;

final class CallableSearch implements SearchInterface
{
    /**
     * @param Item $item
     * @param array $params
     * @return bool
     */
    public function search(Item $item, array $params = []): bool
    {
        if (empty($params['callable'])) {
            return false;
        }

        $callable = $params['callable'];

        return $callable($item, $params);
    }
}

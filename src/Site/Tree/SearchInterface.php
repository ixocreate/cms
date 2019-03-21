<?php
declare(strict_types=1);
namespace Ixocreate\Cms\Site\Tree;

interface SearchInterface
{
    /**
     * @param Item $item
     * @param array $params
     * @return bool
     */
    public function search(Item $item, array $params = []): bool;
}
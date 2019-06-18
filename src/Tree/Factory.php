<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Tree;

use Ixocreate\Cms\PageType\PageTypeSubManager;
use Ixocreate\Cms\Tree\Structure\Structure;
use Ixocreate\Cms\Tree\Structure\StructureItem;

final class Factory implements FactoryInterface
{
    /**
     * @var PageTypeSubManager
     */
    private $pageTypeSubManager;

    public function __construct(PageTypeSubManager $pageTypeSubManager)
    {
        $this->pageTypeSubManager = $pageTypeSubManager;
    }

    public function createContainer(Structure $structure): ContainerInterface
    {
        return new Container($structure, $this);
    }

    public function createItem(StructureItem $structureItem): ItemInterface
    {
        return new Item($structureItem, $this, $this->pageTypeSubManager);
    }
}

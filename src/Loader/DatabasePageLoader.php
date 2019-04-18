<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Package\Loader;

use Ixocreate\Cms\Package\Entity\Page;
use Ixocreate\Cms\Package\Repository\PageRepository;
use Ixocreate\Entity\Package\EntityCollection;

final class DatabasePageLoader implements PageLoaderInterface
{
    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var EntityCollection
     */
    private $collection;

    /**
     * @param PageRepository $pageRepository
     */
    public function __construct(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param string $pageId
     * @return Page|null
     */
    public function receivePage(string $pageId): ?Page
    {
        $this->init();

        if (!$this->collection->has($pageId)) {
            return null;
        }

        return $this->collection->get($pageId);
    }

    /**
     *
     */
    private function init(): void
    {
        if ($this->collection instanceof EntityCollection) {
            return;
        }

        $result = $this->pageRepository->findAll();
        $this->collection = new EntityCollection($result, 'id');
    }
}

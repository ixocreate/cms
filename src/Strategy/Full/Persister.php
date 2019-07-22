<?php
declare(strict_types=1);

namespace Ixocreate\Cms\Strategy\Full;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Ixocreate\Cache\CacheInterface;
use Ixocreate\Cms\Entity\Page;
use Ixocreate\Cms\Strategy\PersisterInterface;
use SplFixedArray;

final class Persister implements PersisterInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CacheInterface $cache
     */
    public function __construct(EntityManagerInterface $entityManager, CacheInterface $cache)
    {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }

    public function persistSitemap(): void
    {
        list($root, $tree) = $this->createSitemapTree();
        $this->createPageData($tree);

        $list = [];
        foreach ($tree as $sitemapId => $item) {
            $list[$sitemapId] = Structure::prepare(
                $sitemapId,
                $item['sitemap'],
                $item['pages'],
                $item['navigation'],
                $item['children'],
                $item['level']
            );
            unset($tree[$sitemapId]);
        }

        $data = SplFixedArray::fromArray([
            $root,
             $list
        ]);

        $this->cache->put(Strategy::CACHE_KEY, $data);
    }

    private function createSitemapTree()
    {
        $sql = "SELECT * FROM cms_sitemap ORDER BY nestedLeft";
        $rm = new ResultSetMapping();
        $rm->addScalarResult('id', 'id', 'string');
        $rm->addScalarResult('parentId', 'parentId', 'string');
        $rm->addScalarResult('nestedLeft', 'nestedLeft', 'integer');
        $rm->addScalarResult('nestedRight', 'nestedRight', 'integer');
        $rm->addScalarResult('pageType', 'pageType', 'string');
        $rm->addScalarResult('handle', 'handle', 'string');
        $query = $this->entityManager->createNativeQuery($sql, $rm);
        $result = $query->execute(null, AbstractQuery::HYDRATE_SCALAR);

        $root = [];
        $tree = [];
        foreach ($result as $item) {
            $tree[$item['id']] = [
                'sitemap' => $item,
                'pages' => [],
                'children' => [],
                'navigation' => [],
                'level' => 0,
            ];

            if (empty($item['parentId'])) {
                $root[] = $item['id'];
                continue;
            }

            $tree[$item['parentId']]['children'][] = $item['id'];
        }
        unset($result);

        $this->recursiveBuildLevel($tree, $root, 0);

        return [$root, $tree];
    }

    private function recursiveBuildLevel(array &$tree, array $items, int $level): void
    {
        foreach ($items as $id) {
            $tree[$id]['level'] = $level;
            $this->recursiveBuildLevel($tree, $tree[$id]['children'], $level + 1);
        }
    }

    private function createPageData(array &$tree): void
    {
        $navigation = [];
        $sql = "SELECT pageId, navigation FROM cms_navigation";
        $rm = new ResultSetMapping();
        $rm->addScalarResult('pageId', 'pageId', 'string');
        $rm->addScalarResult('navigation', 'navigation', 'string');
        $query = $this->entityManager->createNativeQuery($sql, $rm);
        $result = $query->execute(null, AbstractQuery::HYDRATE_SCALAR);
        foreach ($result as $item) {
            if (empty($navigation[$item['pageId']])) {
                $navigation[$item['pageId']] = [];
            }
            $navigation[$item['pageId']][] = $item['navigation'];
        }
        unset($result);

        $sql = "SELECT * FROM cms_page";
        $rm = new ResultSetMapping();
        $rm->addScalarResult('id', 'id', 'string');
        $rm->addScalarResult('sitemapId', 'sitemapId', 'string');
        $rm->addScalarResult('locale', 'locale', 'string');
        $rm->addScalarResult('name', 'name', 'string');
        $rm->addScalarResult('slug', 'slug', 'string');
        $rm->addScalarResult('publishedFrom', 'publishedFrom', 'string');
        $rm->addScalarResult('publishedUntil', 'publishedUntil', 'string');
        $rm->addScalarResult('status', 'status', 'string');
        $rm->addScalarResult('updatedAt', 'updatedAt', 'string');
        $rm->addScalarResult('releasedAt', 'releasedAt', 'string');
        $rm->addScalarResult('createdAt', 'createdAt', 'string');
        $query = $this->entityManager->createNativeQuery($sql, $rm);
        $result = $query->execute(null, AbstractQuery::HYDRATE_SCALAR);

        foreach ($result as $item) {
            if (!isset($tree[$item['sitemapId']])) {
                continue;
            }

            $tree[$item['sitemapId']]['pages'][] = $item;

            if (!empty($navigation[$item['id']])) {
                $tree[$item['sitemapId']]['navigation'][$item['locale']] = $navigation[$item['id']];
            }
        }
    }

    public function persistNavigation(Page $page): void
    {
        $this->persistSitemap();
    }

    public function persistPage(Page $page): void
    {
        $this->persistSitemap();
    }
}
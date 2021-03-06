<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Command\Page;

use Ixocreate\Cache\CacheManager;
use Ixocreate\Cms\Cacheable\PageVersionCacheable;
use Ixocreate\Cms\Entity\Page;
use Ixocreate\Cms\Entity\PageVersion;
use Ixocreate\Cms\Entity\Sitemap;
use Ixocreate\Cms\Event\PageEvent;
use Ixocreate\Cms\PageType\PageTypeInterface;
use Ixocreate\Cms\PageType\PageTypeSubManager;
use Ixocreate\Cms\Repository\PageRepository;
use Ixocreate\Cms\Repository\PageVersionRepository;
use Ixocreate\Cms\Repository\SitemapRepository;
use Ixocreate\CommandBus\Command\AbstractCommand;
use Ixocreate\Event\EventDispatcher;
use Ixocreate\Filter\FilterableInterface;
use Ixocreate\Schema\Type\SchemaType;
use Ixocreate\Schema\Type\Type;
use Ixocreate\Validation\ValidatableInterface;
use Ixocreate\Validation\Violation\ViolationCollectorInterface;

final class CreateVersionCommand extends AbstractCommand implements FilterableInterface, ValidatableInterface
{
    /**
     * @var PageVersionRepository
     */
    private $pageVersionRepository;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var PageTypeSubManager
     */
    private $pageTypeSubManager;

    /**
     * @var SitemapRepository
     */
    private $sitemapRepository;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var PageVersionCacheable
     */
    private $pageVersionCacheable;

    /**
     * CreateVersionCommand constructor.
     * @param PageVersionRepository $pageVersionRepository
     * @param PageRepository $pageRepository
     * @param SitemapRepository $sitemapRepository
     * @param PageTypeSubManager $pageTypeSubManager
     * @param EventDispatcher $eventDispatcher
     * @param CacheManager $cacheManager
     * @param PageVersionCacheable $pageVersionCacheable
     */
    public function __construct(
        PageVersionRepository $pageVersionRepository,
        PageRepository $pageRepository,
        SitemapRepository $sitemapRepository,
        PageTypeSubManager $pageTypeSubManager,
        EventDispatcher $eventDispatcher,
        CacheManager $cacheManager,
        PageVersionCacheable $pageVersionCacheable
    ) {
        $this->pageVersionRepository = $pageVersionRepository;
        $this->pageRepository = $pageRepository;
        $this->pageTypeSubManager = $pageTypeSubManager;
        $this->sitemapRepository = $sitemapRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->cacheManager = $cacheManager;
        $this->pageVersionCacheable = $pageVersionCacheable;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        /** @var Page $page */
        $page = $this->pageRepository->find($this->dataValue('pageId'));
        /** @var Sitemap $sitemap */
        $sitemap = $this->sitemapRepository->find($page->sitemapId());
        /** @var PageTypeInterface $pageType */
        $pageType = $this->pageTypeSubManager->get($sitemap->pageType());

        if ($this->dataValue('approve') === true) {
            $queryBuilder = $this->pageVersionRepository->createQueryBuilder();
            $queryBuilder->update(PageVersion::class, 'version')
                ->set('version.approvedAt', ':approvedAt')
                ->setParameter('approvedAt', null)
                ->where('version.pageId = :pageId')
                ->setParameter('pageId', (string)$page->id());
            $queryBuilder->getQuery()->execute();
        }

        $content = $this->dataValue('content');
        if (!($content instanceof SchemaType)) {
            $content = Type::create(
                $this->dataValue('content'),
                SchemaType::class,
                [
                    'provider' => [
                        'class' => PageTypeSubManager::class,
                        'name' => $pageType::serviceName(),
                    ],
                ]
            );
        }

        $pageVersion = new PageVersion([
            'id' => $this->uuid(),
            'pageId' => (string)$page->id(),
            'content' => $content,
            'createdBy' => $this->dataValue('createdBy'),
            'approvedAt' => ($this->dataValue('approve') === true) ? $this->createdAt() : null,
            'createdAt' => $this->createdAt(),
        ]);

        /** @var PageVersion $pageVersion */
        $pageVersion = $this->pageVersionRepository->save($pageVersion);

        $this->cacheManager->fetch($this->pageVersionCacheable->withPageId((string)$page->id()), true);

        $pageEvent = new PageEvent(
            $page,
            $sitemap,
            $pageVersion,
            $pageType
        );

        $this->eventDispatcher->dispatch(PageEvent::PAGE_VERSION_PUBLISH, $pageEvent);

        return true;
    }

    public static function serviceName(): string
    {
        return 'cms.page-version-create';
    }

    public function filter(): FilterableInterface
    {
        $newData = [];
        $newData['pageId'] = (string)$this->dataValue('pageId');
        $newData['createdBy'] = (string)$this->dataValue('createdBy');
        $newData['content'] = $this->dataValue('content', []);
        $newData['approve'] = (bool)$this->dataValue('approve', false);

        return $this->withData($newData);
    }

    public function validate(ViolationCollectorInterface $violationCollector): void
    {
        $page = $this->pageRepository->find($this->dataValue('pageId'));
        if (empty($page)) {
            $violationCollector->add('page', 'invalid_page');
        }
    }
}

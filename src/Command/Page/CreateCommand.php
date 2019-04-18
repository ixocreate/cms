<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Package\Command\Page;

use Doctrine\DBAL\Driver\Connection;
use Ixocreate\Cms\Package\Entity\Page;
use Ixocreate\Cms\Package\Entity\Sitemap;
use Ixocreate\Cms\Package\PageType\HandlePageTypeInterface;
use Ixocreate\Cms\Package\PageType\PageTypeInterface;
use Ixocreate\Cms\Package\PageType\PageTypeSubManager;
use Ixocreate\Cms\Package\Repository\PageRepository;
use Ixocreate\Cms\Package\Repository\SitemapRepository;
use Ixocreate\CommandBus\Package\Command\AbstractCommand;
use Ixocreate\CommandBus\CommandBus;
use Ixocreate\Cache\CacheInterface;
use Ixocreate\CommandBus\Package\CommandInterface;
use Ixocreate\Filter\Package\FilterableInterface;
use Ixocreate\Validation\Package\ValidatableInterface;
use Ixocreate\Validation\Package\ViolationCollectorInterface;
use Ixocreate\Intl\Package\LocaleManager;

final class CreateCommand extends AbstractCommand implements CommandInterface, ValidatableInterface, FilterableInterface
{
    /**
     * @var PageTypeSubManager
     */
    private $pageTypeSubManager;

    /**
     * @var SitemapRepository
     */
    private $sitemapRepository;

    /**
     * @var LocaleManager
     */
    private $localeManager;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var Connection
     */
    private $master;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * CreateCommand constructor.
     * @param PageTypeSubManager $pageTypeSubManager
     * @param SitemapRepository $sitemapRepository
     * @param PageRepository $pageRepository
     * @param LocaleManager $localeManager
     * @param CommandBus $commandBus
     * @param Connection $master
     * @param CacheInterface $cms
     */
    public function __construct(
        PageTypeSubManager $pageTypeSubManager,
        SitemapRepository $sitemapRepository,
        PageRepository $pageRepository,
        LocaleManager $localeManager,
        CommandBus $commandBus,
        Connection $master,
        CacheInterface $cms
    ) {
        $this->pageTypeSubManager = $pageTypeSubManager;
        $this->sitemapRepository = $sitemapRepository;
        $this->localeManager = $localeManager;
        $this->pageRepository = $pageRepository;
        $this->commandBus = $commandBus;
        $this->master = $master;
        $this->cache = $cms;
    }

    /**
     * @throws \Exception
     * @return bool
     */
    public function execute(): bool
    {
        $this->master->transactional(function () {
            /** @var PageTypeInterface $pageType */
            $pageType = $this->pageTypeSubManager->get($this->dataValue("pageType"));

            $sitemap = new Sitemap([
                'id' => $this->uuid(),
                'pageType' => $pageType::serviceName(),
            ]);

            if (\is_subclass_of($pageType, HandlePageTypeInterface::class)) {
                $sitemap = $sitemap->with("handle", $pageType::serviceName());
            }

            if (empty($this->dataValue('parentSitemapId'))) {
                $sitemap = $this->sitemapRepository->createRoot($sitemap);
            } else {
                /** @var Sitemap $parent */
                $parent = $this->sitemapRepository->find($this->dataValue('parentSitemapId'));
                $sitemap = $this->sitemapRepository->insertAsLastChild($sitemap, $parent);
            }

            $page = new Page([
                'id' => $this->uuid(),
                'sitemapId' => $sitemap->id(),
                'locale' => $this->dataValue('locale'),
                'name' => $this->dataValue('name'),
                'status' => 'offline',
                'updatedAt' => $this->createdAt(),
                'createdAt' => $this->createdAt(),
                'releasedAt' => $this->createdAt(),
            ]);

            /** @var Page $page */
            $page = $this->pageRepository->save($page);

            $this->cache->clear();

            $this->commandBus->command(SlugCommand::class, [
                'name' => (string) $page->name(),
                'pageId' => (string) $page->id(),
            ]);
        });

        return true;
    }

    public static function serviceName(): string
    {
        return 'cms.page-create';
    }

    public function validate(ViolationCollectorInterface $violationCollector): void
    {
        if (!$this->pageTypeSubManager->has($this->dataValue('pageType'))) {
            $violationCollector->add("pageType", "invalid_pageType");
        }

        if (empty($this->dataValue('name')) || !\is_string($this->dataValue('name'))) {
            $violationCollector->add("name", "invalid_name");
        }

        if (!empty($this->dataValue("parentSitemapId"))) {
            if (!\is_string($this->dataValue("parentSitemapId"))) {
                $violationCollector->add("parentSitemapId", "invalid_parentSitemapId");
            } else {
                $sitemap = $this->sitemapRepository->find($this->dataValue("parentSitemapId"));
                if (empty($sitemap)) {
                    $violationCollector->add("parentSitemapId", "invalid_parentSitemapId");
                }
            }
        }

        if (!$this->localeManager->has((string) $this->dataValue("locale"))) {
            $violationCollector->add("locale", "invalid_locale");
        }
    }

    public function filter(): FilterableInterface
    {
        $newData = [];
        $newData['pageType'] = (string) $this->dataValue('pageType');
        $newData['parentSitemapId'] = $this->dataValue('parentSitemapId');
        $newData['locale'] = (string) $this->dataValue('locale');
        $newData['name'] = (string) $this->dataValue('name');
        $newData['createdBy'] = (string) $this->dataValue('createdBy');
        $newData['idFromOriginal'] = (string) $this->dataValue('idFromOriginal');
        $newData['content'] = $this->dataValue('content');
        $newData['status'] = $this->dataValue('status');

        return $this->withData($newData);
    }
}

<?php
namespace KiwiSuite\Cms\Command\Page;

use KiwiSuite\Cms\Config\Config;
use KiwiSuite\Cms\Entity\Navigation;
use KiwiSuite\Cms\Entity\Page;
use KiwiSuite\Cms\Repository\NavigationRepository;
use KiwiSuite\Cms\Repository\PageRepository;
use KiwiSuite\CommandBus\Command\AbstractCommand;
use KiwiSuite\CommandBus\CommandBus;
use KiwiSuite\Contract\CommandBus\CommandInterface;
use KiwiSuite\Contract\Filter\FilterableInterface;
use KiwiSuite\Contract\Validation\ValidatableInterface;
use KiwiSuite\Contract\Validation\ViolationCollectorInterface;
use Ramsey\Uuid\Uuid;

final class UpdateCommand extends AbstractCommand implements CommandInterface, ValidatableInterface, FilterableInterface
{
    /**
     * @var PageRepository
     */
    private $pageRepository;
    /**
     * @var CommandBus
     */
    private $commandBus;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var NavigationRepository
     */
    private $navigationRepository;

    /**
     * CreateCommand constructor.
     * @param PageRepository $pageRepository
     * @param CommandBus $commandBus
     * @param Config $config
     * @param NavigationRepository $navigationRepository
     */
    public function __construct(
        PageRepository $pageRepository,
        CommandBus $commandBus,
        Config $config,
        NavigationRepository $navigationRepository
    ) {
        $this->pageRepository = $pageRepository;
        $this->commandBus = $commandBus;
        $this->config = $config;
        $this->navigationRepository = $navigationRepository;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function execute(): bool
    {
        /** @var Page $page */
        $page = $this->pageRepository->find($this->dataValue("pageId"));

        $updated = false;
        if ($this->dataValue('name') !== false) {
            $updated = true;
            $page = $page->with("name", $this->dataValue('name'));
        }

        if ($this->dataValue('publishedFrom') !== false) {
            $updated = true;
            $page = $page->with("publishedFrom", $this->dataValue('publishedFrom'));

            if (empty($this->dataValue('publishedFrom'))) {
                $page = $page->with("releasedAt", $page->createdAt());
            } else {
                $page = $page->with("releasedAt", $this->dataValue('publishedFrom'));
            }
        }

        if ($this->dataValue('publishedUntil') !== false) {
            $updated = true;
            $page = $page->with("publishedUntil", $this->dataValue('publishedUntil'));
        }

        if ($this->dataValue('status') !== false) {
            $updated = true;
            $page = $page->with("status", $this->dataValue('status'));
        }

        if ($this->dataValue('slug') !== false) {
            $this->commandBus->command(SlugCommand::class, [
                'name' => (string) $this->dataValue('slug'),
                'pageId' => (string) $page->id()
            ]);
        }

        if (!empty($this->dataValue('navigation'))) {
            $queryBuilder = $this->navigationRepository->createQueryBuilder();
            $queryBuilder->delete(Navigation::class, "nav")
                ->where("nav.pageId = :pageId")
                ->setParameter("pageId", (string) $page->id());
            $queryBuilder->getQuery()->execute();

            foreach ($this->dataValue('navigation') as $nav) {
                $navigationEntity = new Navigation([
                    'id' => Uuid::uuid4()->toString(),
                    'pageId' => (string) $page->id(),
                    'navigation' => $nav,
                ]);

                $this->navigationRepository->save($navigationEntity);
            }
        }

        if ($updated === true) {
            $page = $page->with('updatedAt', new \DateTime());
            $this->pageRepository->save($page);
        }

        return true;
    }

    public static function serviceName(): string
    {
        return 'cms.page-update';
    }

    public function validate(ViolationCollectorInterface $violationCollector): void
    {
        if (empty($this->dataValue("pageId"))) {
            $violationCollector->add("page", "invalid_pageId");
        } else {
            $page = $this->pageRepository->find($this->dataValue("pageId"));
            if (empty($page)) {
                $violationCollector->add("page", "invalid_pageId");
            }
        }

        if (!empty($this->dataValue("status"))) {
            if (!in_array($this->dataValue("status"), ['online', 'offline'])) {
                $violationCollector->add("status", "invalid_status");
            }
        }

        if (!empty($this->dataValue("navigation")) && !is_array($this->dataValue("navigation"))) {
            $violationCollector->add("navigation", "invalid_navigation");
        }
    }

    public function filter(): FilterableInterface
    {
        $newData = [];
        $newData['pageId'] = $this->dataValue('pageId');
        $newData['name'] = $this->dataValue('name', false);
        $newData['publishedFrom'] = $this->dataValue('publishedFrom', false);
        $newData['publishedUntil'] = $this->dataValue('publishedUntil', false);
        $newData['status'] = $this->dataValue('status', false);
        $newData['slug'] = $this->dataValue('slug', false);
        $newData['navigation'] = null;

        if (!empty($this->dataValue('navigation'))) {
            $newData['navigation'] = [];
            $navItems = array_map(function($nav) {
                return $nav['name'];
            }, $this->config->navigation());

            foreach ($this->dataValue("navigation") as $nav) {
                if (in_array($nav, $navItems)) {
                    $newData['navigation'][] = $nav;
                }
            }
        }


        return $this->withData($newData);
    }
}
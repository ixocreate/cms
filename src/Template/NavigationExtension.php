<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Template;

use Ixocreate\Cms\Navigation\Container;
use Ixocreate\Cms\Navigation\Item;
use Ixocreate\Cms\Repository\NavigationRepository;
use Ixocreate\Cms\Repository\PageRepository;
use Ixocreate\Template\Extension\ExtensionInterface;

final class NavigationExtension implements ExtensionInterface
{
    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var NavigationRepository
     */
    private $navigationRepository;

    private $container = null;

    public function __construct(PageRepository $pageRepository, NavigationRepository $navigationRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->navigationRepository = $navigationRepository;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'nav';
    }

    public function __invoke(?string $locale = null)
    {
        if ($this->container === null) {
            $this->container = new Container(
                $this->navigationRepository,
                $this->walkRecursive($this->pageRepository->fetchTree(), $locale ?? \Locale::getDefault(), 0)
            );
        }

        return $this->container;
    }

    private function walkRecursive(array $items, string $locale, int $level): array
    {
        $collection = [];
        foreach ($items as $arrayItem) {
            if (!isset($arrayItem['pages'][$locale])) {
                continue;
            }
            $page = $arrayItem['pages'][$locale];
            if ((string)$page->status !== "online") {
                continue;
            }

            $children = $this->walkRecursive($arrayItem['children'], $locale, $level + 1);

            $collection[] = new Item($page, $arrayItem['sitemap'], $level, $children, false);
        }

        return $collection;
    }
}

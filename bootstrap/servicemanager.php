<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms;

use Ixocreate\Application\Service\ServiceManagerConfigurator;
use Ixocreate\Cms\Admin\Container;
use Ixocreate\Cms\Admin\Factory\ContainerFactory;
use Ixocreate\Cms\Block\BlockSubManager;
use Ixocreate\Cms\PageType\PageTypeSubManager;
use Ixocreate\Cms\Router\CmsRouter;
use Ixocreate\Cms\Router\Factory\CmsRouterFactory;
use Ixocreate\Cms\Router\PageRoute;
use Ixocreate\Cms\Router\Replacement\ReplacementManager;
use Ixocreate\Cms\Seo\Sitemap\XmlSitemapProviderSubManager;
use Ixocreate\Cms\Tree\FilterManager;
use Ixocreate\Cms\Tree\Structure\Factory\StructureBuilderFactory;
use Ixocreate\Cms\Tree\Structure\StructureBuilder;

/** @var ServiceManagerConfigurator $serviceManager */
$serviceManager->addSubManager(PageTypeSubManager::class);
$serviceManager->addSubManager(BlockSubManager::class);
$serviceManager->addSubManager(XmlSitemapProviderSubManager::class);
$serviceManager->addSubManager(ReplacementManager::class);
$serviceManager->addSubManager(FilterManager::class);

$serviceManager->addFactory(CmsRouter::class, CmsRouterFactory::class);
$serviceManager->addFactory(PageRoute::class);
$serviceManager->addFactory(StructureBuilder::class, StructureBuilderFactory::class);
$serviceManager->addFactory(Container::class, ContainerFactory::class);

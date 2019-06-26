<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Router\Factory;

use Ixocreate\Application\Http\Middleware\MiddlewareSubManager;
use Ixocreate\Application\Uri\ApplicationUri;
use Ixocreate\Cache\CacheableSubManager;
use Ixocreate\Cache\CacheManager;
use Ixocreate\Cms\Cacheable\CompiledGeneratorRoutesCacheable;
use Ixocreate\Cms\Cacheable\CompiledMatcherRoutesCacheable;
use Ixocreate\Cms\Cacheable\RouteCollectionCacheable;
use Ixocreate\Cms\Router\CmsRouter;
use Ixocreate\ServiceManager\FactoryInterface;
use Ixocreate\ServiceManager\ServiceManagerInterface;
use Zend\Expressive\MiddlewareContainer;
use Zend\Expressive\MiddlewareFactory;

final class CmsRouterFactory implements FactoryInterface
{
    /**
     * @param ServiceManagerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @throws \Psr\Cache\InvalidArgumentException
     * @return CmsRouter|mixed
     */
    public function __invoke(ServiceManagerInterface $container, $requestedName, array $options = null)
    {
        $routeCollection = $container
            ->get(CacheManager::class)
            ->fetch($container->get(CacheableSubManager::class)->get(RouteCollectionCacheable::class));

        $middlewareFactory = new MiddlewareFactory(new MiddlewareContainer($container->get(MiddlewareSubManager::class)));

        return new CmsRouter(
            $middlewareFactory,
            $container->get(ApplicationUri::class),
            $container->get(CacheManager::class),
            $container->get(CacheableSubManager::class)->get(CompiledGeneratorRoutesCacheable::class),
            $container->get(CacheableSubManager::class)->get(CompiledMatcherRoutesCacheable::class)
        );
    }
}

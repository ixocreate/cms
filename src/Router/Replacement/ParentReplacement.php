<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Router\Replacement;

use Ixocreate\Cms\Router\RouteSpecification;
use Ixocreate\Cms\Router\RoutingItem;

final class ParentReplacement implements ReplacementInterface
{
    /**
     * @return int
     */
    public function priority(): int
    {
        return 1;
    }

    /**
     * @param RouteSpecification $routeSpecification
     * @param string $locale
     * @param RoutingItem $routingItem
     * @throws \Exception
     * @return RouteSpecification
     */
    public function replace(
        RouteSpecification $routeSpecification,
        string $locale,
        RoutingItem $routingItem
    ): RouteSpecification {
        if (empty($routingItem->parent())) {
            return $this->updateRouteSpecification($routeSpecification, "");
        }

        $parentRouting = $routingItem->parent()->pageRoute($locale);
        if (empty($parentRouting)) {
            return $this->updateRouteSpecification($routeSpecification, "");
        }

        return $this->updateRouteSpecification($routeSpecification, $parentRouting->uri(RouteSpecification::NAME_INHERITANCE));
    }

    private function updateRouteSpecification(RouteSpecification $routeSpecification, string $replace): RouteSpecification
    {
        foreach ($routeSpecification->uris() as $name => $uri) {
            $routeSpecification = $routeSpecification->withUri(\str_replace('${PARENT}', $replace, $uri), $name);
        }

        return $routeSpecification;
    }
}

<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Loader;

use Ixocreate\Cms\Entity\Sitemap;

interface SitemapLoaderInterface
{
    public function receiveSitemap(string $sitemapId): ?Sitemap;

    public function receiveHandles(): array;
}

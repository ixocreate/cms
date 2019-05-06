<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Action\Page;

use Doctrine\Common\Collections\Criteria;
use Ixocreate\Admin\Response\ApiErrorResponse;
use Ixocreate\Admin\Response\ApiSuccessResponse;
use Ixocreate\Cms\Config\Config;
use Ixocreate\Cms\Entity\PageVersion;
use Ixocreate\Cms\Repository\PageVersionRepository;
use Ixocreate\Cms\Site\Admin\AdminContainer;
use Ixocreate\Cms\Site\Admin\AdminItem;
use Ixocreate\Schema\Builder\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DetailAction implements MiddlewareInterface
{
    /**
     * @var AdminContainer
     */
    private $adminContainer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Builder
     */
    private $schemaBuilder;

    /**
     * @var PageVersionRepository
     */
    private $pageVersionRepository;

    /**
     * DetailAction constructor.
     * @param AdminContainer $adminContainer
     * @param Config $config
     * @param Builder $schemaBuilder
     * @param PageVersionRepository $pageVersionRepository
     */
    public function __construct(
        AdminContainer $adminContainer,
        Config $config,
        Builder $schemaBuilder,
        PageVersionRepository $pageVersionRepository
    ) {
        $this->adminContainer = $adminContainer;
        $this->config = $config;
        $this->schemaBuilder = $schemaBuilder;
        $this->pageVersionRepository = $pageVersionRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageId = $request->getAttribute("id");
        $item = $this->adminContainer->findOneBy(function (AdminItem $item) use ($pageId) {
            $pages = $item->pages();
            foreach ($pages as $pageItem) {
                if ((string) $pageItem['page']->id() === $pageId) {
                    return true;
                }
            }

            return false;
        });

        if (empty($item)) {
            return new ApiErrorResponse("invalid_page_id");
        }

        $result = $item->jsonSerialize();

        $page = null;
        $localizedPages = [];
        foreach ($item->pages() as $locale =>  $pageItem) {
            if ((string) $pageItem['page']->id() === $pageId) {
                $page = $pageItem;
                continue;
            }
            $localizedPages[$locale] = $pageItem;
        }
        unset($result['pages']);
        if (empty($page)) {
            return new ApiErrorResponse("invalid_page_id");
        }

        $page['version'] = [
            'head' => null,
            'approved' => null,
        ];
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('pageId', $page['page']->id()));
        $criteria->andWhere(Criteria::expr()->neq('approvedAt', null));
        $criteria->orderBy(['approvedAt' => 'DESC']);
        $criteria->setMaxResults(1);
        $pageVersion = $this->pageVersionRepository->matching($criteria);
        if ($pageVersion->count() > 0) {
            /** @var PageVersion $pageVersion */
            $pageVersion = $pageVersion->current();
            $page['version']['approved'] = (string) $pageVersion->id();
        }
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('pageId', $page['page']->id()));
        $criteria->orderBy(['createdAt' => 'DESC']);
        $criteria->setMaxResults(1);
        $pageVersion = $this->pageVersionRepository->matching($criteria);
        if ($pageVersion->count() > 0) {
            /** @var PageVersion $pageVersion */
            $pageVersion = $pageVersion->current();
            $page['version']['head'] = (string) $pageVersion->id();
        }

        $result['page'] = $page;
        $result['localizedPages'] = $localizedPages;

        $result['hasChildren'] = (\count($result['children']) > 0);
        unset($result['children'], $result['childrenAllowed']);


        $navigation = $this->config->navigation();
        $navigation = \array_map(function ($value) use ($item) {
            $value['active'] = (\in_array($value['name'], $item->navigation()));
            return $value;
        }, $navigation);
        $result['navigation'] = $navigation;

        $result['schema'] = $item->pageType()->provideSchema('', $this->schemaBuilder);

        return new ApiSuccessResponse($result);
    }
}

<?php

namespace KiwiSuite\Cms\Action\Page;


use KiwiSuite\Admin\Response\ApiErrorResponse;
use KiwiSuite\Admin\Response\ApiSuccessResponse;
use KiwiSuite\Cms\Site\Admin\Builder;
use KiwiSuite\Cms\Site\Admin\Item;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IndexSubSitemapAction implements MiddlewareInterface
{
    /**
     * @var Builder
     */
    private $builder;

    public function __construct(
        Builder $builder
    ) {
        $this->builder = $builder;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $handle = $request->getAttribute("handle");
        $item = $this->builder->build()->findOneBy(function(Item $item) use ($handle) {
            return ($item->sitemap()->handle() === $handle);
        });

        if (empty($item)) {
            return new ApiErrorResponse('invalid_handle');
        }

        return new ApiSuccessResponse([
            'items' => [$item],
            'allowedAddingRoot' => false,
        ]);
    }
}
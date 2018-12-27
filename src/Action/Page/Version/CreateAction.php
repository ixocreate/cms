<?php
namespace Ixocreate\Cms\Action\Page\Version;

use Ixocreate\Admin\Entity\User;
use Ixocreate\Admin\Response\ApiErrorResponse;
use Ixocreate\Admin\Response\ApiSuccessResponse;
use Ixocreate\Cms\Command\Page\CreateVersionCommand;
use Ixocreate\Cms\Site\Admin\Builder;
use Ixocreate\Cms\Site\Admin\Item;
use Ixocreate\CommandBus\CommandBus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateAction implements MiddlewareInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;
    /**
     * @var Builder
     */
    private $builder;

    /**
     * DetailAction constructor.
     * @param CommandBus $commandBus
     * @param Builder $builder
     */
    public function __construct(CommandBus $commandBus, Builder $builder) {
        $this->commandBus = $commandBus;
        $this->builder = $builder;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageId = $request->getAttribute("pageId");
        /** @var Item $item */
        $item = $this->builder->build()->findOneBy(function (Item $item) use ($pageId) {
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

        $page = null;
        foreach ($item->pages() as $pageItem) {
            if ((string) $pageItem['page']->id() === $pageId) {
                $page = $pageItem['page'];
            }
        }

        $content = [];
        if (!empty($request->getParsedBody()['content']) && is_array($request->getParsedBody()['content'])) {
            $content = $request->getParsedBody()['content'];
        }

        $result = $this->commandBus->command(CreateVersionCommand::class, [
            'pageType' => $item->pageType()::serviceName(),
            'pageId' => (string) $page->id(),
            'createdBy' => $request->getAttribute(User::class, null)->id(),
            'content' => $content,
            'approve' => true,
        ]);

        if ($result->isSuccessful()) {
            return new ApiSuccessResponse((string) $result->command()->uuid());
        }

        return new ApiErrorResponse('execution_error', $result->messages());
    }
}

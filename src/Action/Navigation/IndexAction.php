<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Action\Navigation;

use Ixocreate\Admin\Response\ApiSuccessResponse;
use Ixocreate\Cms\Config\Config;
use Ixocreate\Cms\Entity\Navigation;
use Ixocreate\Cms\Repository\NavigationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IndexAction implements MiddlewareInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var NavigationRepository
     */
    private $navigationRepository;

    public function __construct(Config $config, NavigationRepository $navigationRepository)
    {
        $this->config = $config;
        $this->navigationRepository = $navigationRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $navigation = $this->config->navigation();
        $navigation = \array_map(function ($value) {
            $value['active'] = false;
            return $value;
        }, $navigation);

        $result = $this->navigationRepository->findBy(['pageId' => $request->getAttribute("id")]);
        /** @var Navigation $item */
        foreach ($result as $item) {
            foreach ($navigation as &$navigationItem) {
                if ($navigationItem['name'] === $item->navigation()) {
                    $navigationItem['active'] =  true;
                    break;
                }
            }
        }

        return new ApiSuccessResponse($navigation);
    }
}

<?php

/**
 * kiwi-suite/cms (https://github.com/kiwi-suite/cms)
 *
 * @package kiwi-suite/cms
 * @see https://github.com/kiwi-suite/cms
 * @copyright Copyright (c) 2010 - 2018 kiwi suite GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Entity;

use Ixocreate\Database\Tree\NodeInterface;
use Ixocreate\Entity\Entity\Definition;
use Ixocreate\Entity\Entity\DefinitionCollection;
use Ixocreate\Entity\Entity\EntityTrait;
use Ixocreate\CommonTypes\Entity\UuidType;
use Ixocreate\Entity\Type\TypeInterface;

final class Sitemap implements NodeInterface
{
    use EntityTrait;

    private $id;
    private $parentId;
    private $nestedLeft;
    private $nestedRight;
    private $pageType;
    private $handle;

    public function id(): UuidType
    {
        return $this->id;
    }

    public function parentId(): ?UuidType
    {
        return $this->parentId;
    }

    public function nestedLeft(): ?int
    {
        return $this->nestedLeft;
    }

    public function nestedRight(): ?int
    {
        return $this->nestedRight;
    }

    public function pageType(): string
    {
        return $this->pageType;
    }

    public function handle(): ?string
    {
        return $this->handle;
    }

    protected static function createDefinitions(): DefinitionCollection
    {
        return new DefinitionCollection([
            new Definition('id', UuidType::class, false, true),
            new Definition('parentId', UuidType::class, true, true),
            new Definition('nestedLeft', TypeInterface::TYPE_INT, true, true),
            new Definition('nestedRight', TypeInterface::TYPE_INT, true, true),
            new Definition('pageType', TypeInterface::TYPE_STRING, false, true),
            new Definition('handle', TypeInterface::TYPE_STRING, true, true),
        ]);
    }

    public function right(): ?int
    {
        return $this->nestedRight();
    }

    public function left(): ?int
    {
        return $this->nestedLeft();
    }


    public function leftParameterName(): string
    {
        return 'nestedLeft';
    }

    public function rightParameterName(): string
    {
        return 'nestedRight';
    }

    public function parentIdParameterName(): string
    {
        return 'parentId';
    }

    public function idName(): string
    {
        return 'id';
    }
}


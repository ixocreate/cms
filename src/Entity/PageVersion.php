<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Entity;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Ixocreate\CommonTypes\Entity\DateTimeType;
use Ixocreate\CommonTypes\Entity\SchemaType;
use Ixocreate\Contract\Entity\DatabaseEntityInterface;
use Ixocreate\Entity\Entity\Definition;
use Ixocreate\Entity\Entity\DefinitionCollection;
use Ixocreate\Entity\Entity\EntityInterface;
use Ixocreate\Entity\Entity\EntityTrait;
use Ixocreate\CommonTypes\Entity\UuidType;

final class PageVersion implements EntityInterface, DatabaseEntityInterface
{
    use EntityTrait;

    private $id;

    private $pageId;

    private $content;

    private $createdBy;

    private $approvedAt;

    private $createdAt;

    public function id(): UuidType
    {
        return $this->id;
    }

    public function pageId(): UuidType
    {
        return $this->pageId;
    }

    public function content()
    {
        return $this->content;
    }

    public function createdBy(): UuidType
    {
        return $this->createdBy;
    }

    public function approvedAt(): ?DateTimeType
    {
        return $this->approvedAt;
    }

    public function createdAt(): DateTimeType
    {
        return $this->createdAt;
    }

    protected static function createDefinitions(): DefinitionCollection
    {
        return new DefinitionCollection([
            new Definition('id', UuidType::class, false, true),
            new Definition('pageId', UuidType::class, false, true),
            new Definition('content', SchemaType::class, true, true),
            new Definition('createdBy', UuidType::class, false, true),
            new Definition('approvedAt', DateTimeType::class, true, true),
            new Definition('createdAt', DateTimeType::class, false, true),
        ]);
    }

    public static function loadMetadata(ClassMetadataBuilder $builder)
    {
        $builder->setTable('cms_page_version');

        $builder->createField('id', UuidType::class)->makePrimaryKey()->build();
        $builder->createField('pageId', UuidType::class)->nullable(false)->build();
        $builder->createField('content', SchemaType::class)->nullable(true)->build();
        $builder->createField('createdBy', UuidType::class)->nullable(false)->build();
        $builder->createField('approvedAt', DateTimeType::class)->nullable(true)->build();
        $builder->createField('createdAt', DateTimeType::class)->nullable(false)->build();
    }
}

<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Package\Entity;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Ixocreate\Type\Package\Entity\DateTimeType;
use Ixocreate\Type\Package\Entity\SchemaType;
use Ixocreate\Entity\DatabaseEntityInterface;
use Ixocreate\Entity\Package\Definition;
use Ixocreate\Entity\Package\DefinitionCollection;
use Ixocreate\Entity\Package\EntityInterface;
use Ixocreate\Entity\Package\EntityTrait;
use Ixocreate\Type\Package\Entity\UuidType;

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

        $builder->createField('id', UuidType::serviceName())->makePrimaryKey()->build();
        $builder->createField('pageId', UuidType::serviceName())->nullable(false)->build();
        $builder->createField('content', SchemaType::serviceName())->nullable(true)->build();
        $builder->createField('createdBy', UuidType::serviceName())->nullable(false)->build();
        $builder->createField('approvedAt', DateTimeType::serviceName())->nullable(true)->build();
        $builder->createField('createdAt', DateTimeType::serviceName())->nullable(false)->build();
    }
}

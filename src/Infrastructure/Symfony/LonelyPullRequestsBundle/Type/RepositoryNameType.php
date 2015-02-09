<?php

namespace LonelyPullRequests\Infrastructure\Symfony\Type;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use LonelyPullRequests\Domain\RepositoryName;

/**
 * RepositoryName type for Doctrine to map the value-object
 *
 */
class RepositoryNameType extends StringType
{
    const NAME = 'repositoryName';

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) {
        return RepositoryName::fromString($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue(RepositoryName $value) {
        return $value->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return self::NAME;
    }
}
<?php
// api/src/Filter/SearchFilterWithMapping.php

/*
 * This file extends the SearchFilter functionality.
 */

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filter the collection by given properties mapped by Entity class.
 */
class SearchFilterWithMapping extends SearchFilter implements SearchFilterInterface
{

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, IdentifiersExtractorInterface $identifiersExtractor = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $iriConverter, $propertyAccessor, $logger, $properties, $identifiersExtractor, $nameConverter);
    }

    private $mapping = [
        'entry_point' => [
            'App\Entity\Manifests' => 'overpacks.ep.id',
        ],
        'shipping_address.name' => [
            'App\Entity\Shipments' => 'to_name',
            'App\Entity\Orders' => 'to_name',
        ],
        'overpack_id' => [
            'App\Entity\Shipments' => 'carton.id',
        ],
        'product.id' => [
            'App\Entity\Orders' => 'line_item_list.product.id',
        ],
        'shop.order_id' => [
            'App\Entity\Orders' => 'shop_order_id',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        // Lookup mapping for relation dependent fields
        if (isset($this->mapping[$property]) && isset($this->mapping[$property][$resourceClass])) {
            $property = $this->mapping[$property][$resourceClass];
        }

        if (null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $caseSensitive = true;
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            if ('id' === $field) {
                // Parse comma delimited id list to values array for Shipments id filter.
                if ($resourceClass == 'App\Entity\Shipments' && is_array($values) && isset($values[0]) && is_string($values[0]) && strpos($values[0], ',') !== FALSE) {

                    // Limit list to 10 id's.
                    $arr = explode(',', $values[0]);
                    $values = array_slice($arr, 0 , 10);
                }

                $values = array_map([$this, 'getIdFromValue'], $values);
            }

            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
                ]);

                return;
            }

            $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

            // prefixing the strategy with i makes it case insensitive
            if (0 === strpos($strategy, 'i')) {
                $strategy = substr($strategy, 1);
                $caseSensitive = false;
            }

            if (1 === \count($values)) {
                $this->addWhereByStrategy($strategy, $queryBuilder, $queryNameGenerator, $alias, $field, $values[0], $caseSensitive);

                return;
            }

            if (self::STRATEGY_EXACT !== $strategy) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('"%s" strategy selected for "%s" property, but only "%s" strategy supports multiple values', $strategy, $property, self::STRATEGY_EXACT)),
                ]);

                return;
            }

            $wrapCase = $this->createWrapCase($caseSensitive);
            $valueParameter = $queryNameGenerator->generateParameterName($field);

            $queryBuilder
                ->andWhere(sprintf($wrapCase('%s.%s').' IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $caseSensitive ? $values : array_map('strtolower', $values));
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);
        $associationFieldIdentifier = 'id';
        $doctrineTypeField = $this->getDoctrineFieldType($property, $resourceClass);

        if (null !== $this->identifiersExtractor) {
            $associationResourceClass = $metadata->getAssociationTargetClass($field);
            $associationFieldIdentifier = $this->identifiersExtractor->getIdentifiersFromResourceClass($associationResourceClass)[0];
            $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);
        }

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
            ]);

            return;
        }

        $association = $field;
        $valueParameter = $queryNameGenerator->generateParameterName($association);
        if ($metadata->isCollectionValuedAssociation($association)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $association);
            $associationField = $associationFieldIdentifier;
        } else {
            $associationAlias = $alias;
            $associationField = $field;
        }

        if (1 === \count($values)) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values[0]);
        } else {
            $queryBuilder
                ->andWhere(sprintf('%s.%s IN (:%s)', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values);
        }
    }
}

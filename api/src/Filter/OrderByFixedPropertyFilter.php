<?php
// api/src/Filter/OrderByFixedPropertyFilter.php

/*
 * This file overrides Doctrine OrderFilter.
 */

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Order the collection by a single property.
 *
 * The filter orders by Id so that the property [] does not need to be set.
 */
class OrderByFixedPropertyFilter extends OrderFilter
{

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, string $orderParameterName = 'sort', LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $orderParameterName, $logger, $properties, $nameConverter);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (isset($context['filters']) && !isset($context['filters'][$this->orderParameterName])) {
            return;
        }

        // Patch for default id sorting - CH.
        if (isset($context['filters'][$this->orderParameterName]) && !\is_array($context['filters'][$this->orderParameterName])) {
            $context['filters'][$this->orderParameterName] = ['id' => $context['filters'][$this->orderParameterName]];
        }

        if (!isset($context['filters'][$this->orderParameterName]) || !\is_array($context['filters'][$this->orderParameterName])) {
            parent::apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            return;
        }

        foreach ($context['filters'][$this->orderParameterName] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }
    }

}

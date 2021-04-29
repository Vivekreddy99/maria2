<?php
// api/src/Filter/ExistsFilterWithMapping.php

/*
 * This file overrides ExistsFilter.
 */

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filters the collection by whether a property value exists or not.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not one of ( "true" | "false" | "1" | "0" ) the property is ignored.
 *
 * A query parameter with key but no value is treated as `true`, e.g.:
 * Request: GET /products?exists[brand]
 * Interpretation: filter products which have a brand
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class ExistsFilterWithMapping extends ExistsFilter
{
    protected $existsParameterName = 'exists';

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = null, string $existsParameterName = self::QUERY_PARAMETER_KEY, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $existsParameterName, $nameConverter);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                          string $resourceClass, string $operationName = null, array $context = [])
    {
        // Fix for packed and labeled filters.
        $shipping_filters = ['packed', 'labeled'];
        foreach ($shipping_filters as $shipping_filter) {
            if (isset($context['filters']) && isset($context['filters'][$shipping_filter])) {
                switch(strtolower($context['filters'][$shipping_filter])) {
                    case 'yes':
                        $context['filters'][$this->existsParameterName] = $shipping_filter == 'packed' ? ['carton' => 1] : ['labels' => 1];
                        break;
                    case 'no':
                        $context['filters'][$this->existsParameterName] = $shipping_filter == 'packed' ? ['carton' => 0] : ['labels' => 0];
                        break;
                    default:
                        return;
                }
                unset($context['filters'][$shipping_filter]);
            }
        }

        if (!\is_array($context['filters'][$this->existsParameterName] ?? null)) {
            $context['exists_deprecated_syntax'] = true;
            parent::apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

            return;
        }

        foreach ($context['filters'][$this->existsParameterName] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/*, array $context = []*/)
    {
        if (\func_num_args() > 6) {
            $context = func_get_arg(6);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a seventh `$context` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.5.', __FUNCTION__), E_USER_DEPRECATED);
                }
            }
            $context = [];
        }

        if (
            (($context['exists_deprecated_syntax'] ?? false) && !isset($value[self::QUERY_PARAMETER_KEY])) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true) ||
            !$this->isNullableField($property, $resourceClass)
        ) {
            return;
        }

        // Don't have access to private method, use intval for packed instead.
        // $value = $this->normalizeValue($value, $property);
        $value = intval($value);

        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasAssociation($field)) {
            if ($metadata->isCollectionValuedAssociation($field)) {
                $queryBuilder
                    ->andWhere(sprintf('%s.%s %s EMPTY', $alias, $field, $value ? 'IS NOT' : 'IS'));

                return;
            }

            if ($metadata->isAssociationInverseSide($field)) {
                $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $field, Join::LEFT_JOIN);

                $queryBuilder
                    ->andWhere(sprintf('%s %s NULL', $alias, $value ? 'IS NOT' : 'IS'));

                return;
            }

            $queryBuilder
                ->andWhere(sprintf('%s.%s %s NULL', $alias, $field, $value ? 'IS NOT' : 'IS'));

            return;
        }

        if ($metadata->hasField($field)) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s %s NULL', $alias, $field, $value ? 'IS NOT' : 'IS'));
        }
    }

}

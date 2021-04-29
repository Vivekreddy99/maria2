<?php
// api/src/Filter/DateInclusiveFilter.php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

class DateInclusiveFilter extends DateFilter {

    protected $mapping = [
        'start' => ['date_start', 'created.start'],
        'end' => ['date_end', 'created.end'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {

        if (in_array($property, $this->mapping['start']) && !\is_array($values)){
            $values = ['after' => $values];
            $property = 'created';
        } elseif(in_array($property, $this->mapping['end']) && !\is_array($values)) {
            // Make date_end inclusive.
            $new_date = $values;
            $tm = strtotime($values);

            if ($tm) {
                $date_end = strtotime("1 day", $tm);
                if ($date_end) {
                    $new_date = date("Y-m-d", $date_end);
                }
            }

            $values = ['before' => $new_date];

            // If no date_start has been set, then use -90 day default.
            $q =$queryBuilder->getQuery()->getDQL();
            if (strpos($q,'created >=') === FALSE) {
                $minus90 = strtotime("-90 day", time());
                $default_date_start = date("Y-m-d", $minus90);
                $values=['before' => $new_date, 'after' => $default_date_start];
            }

            $property = 'created';
        }

        // Expect $values to be an array having the period as keys and the date value as values
        if (
            !\is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isDateField($property, $resourceClass)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $nullManagement = $this->properties[$property] ?? null;
        $type = (string) $this->getDoctrineFieldType($property, $resourceClass);

        if (self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
        }

        if (isset($values[self::PARAMETER_BEFORE])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_BEFORE,
                $values[self::PARAMETER_BEFORE],
                $nullManagement,
                $type
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_BEFORE])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_STRICTLY_BEFORE,
                $values[self::PARAMETER_STRICTLY_BEFORE],
                $nullManagement,
                $type
            );
        }

        if (isset($values[self::PARAMETER_AFTER])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_AFTER,
                $values[self::PARAMETER_AFTER],
                $nullManagement,
                $type
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_AFTER])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_STRICTLY_AFTER,
                $values[self::PARAMETER_STRICTLY_AFTER],
                $nullManagement,
                $type
            );
        }
    }
}

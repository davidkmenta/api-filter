<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Seq;
use MF\Collection\Immutable\Tuple;

class QueryParametersParser
{
    public function parse(array $queryParameters): FiltersInterface
    {
        return Seq::init(function () use ($queryParameters) {
            foreach ($queryParameters as $column => $values) {
                $columns = $this->parseColumns($column);
                $columnsCount = count($columns);

                foreach ($this->normalizeFilters($values) as $filter => $value) {
                    $this->assertTupleIsAllowed($filter, $columnsCount);
                    $parsedValues = $this->parseValues($value, $columnsCount);

                    foreach ($columns as $column) {
                        yield Tuple::of($column, $filter, new Value(array_shift($parsedValues)));
                    }
                }
            }
        })
            ->reduce(
                function (FiltersInterface $filters, ITuple $tuple): FiltersInterface {
                    return $filters->addFilter($this->createFilter(...$tuple));
                },
                new Filters()
            );
    }

    private function parseColumns(string $column): array
    {
        return mb_substr($column, 0, 1) === '('
            ? Tuple::parse($column)->toArray()
            : [$column];
    }

    private function normalizeFilters($values): array
    {
        return is_array($values)
            ? $values
            : ['eq' => $values];
    }

    private function assertTupleIsAllowed(string $filter, int $columnsCount): void
    {
        Assertion::false($columnsCount > 1 && $filter === 'in', 'Tuples are not allowed in IN filter.');
    }

    private function parseValues($value, int $columnsCount): array
    {
        return $columnsCount > 1
            ? Tuple::parse($value, $columnsCount)->toArray()
            : [$value];
    }

    private function createFilter(string $column, string $filter, Value $value): FilterInterface
    {
        switch (mb_strtolower($filter)) {
            case 'eq':
                return new FilterWithOperator($column, $value, '=', 'eq');
            case 'gt':
                return new FilterWithOperator($column, $value, '>', 'gt');
            case 'lt':
                return new FilterWithOperator($column, $value, '<', 'lt');
            case 'lte':
                return new FilterWithOperator($column, $value, '<=', 'lt');
            case 'gte':
                return new FilterWithOperator($column, $value, '>=', 'gte');
            case 'in':
                return new FilterIn($column, $value);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Filter "%s" is not implemented. For column "%s" with value "%s".',
                $filter,
                $column,
                $value->getValue()
            )
        );
    }
}

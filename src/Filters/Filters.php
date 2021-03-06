<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Service\FilterApplicator;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;

class Filters implements FiltersInterface
{
    /** @var IList|FilterInterface[] */
    private $filters;

    /** @param FilterInterface[] $filters */
    public static function from(array $filters): FiltersInterface
    {
        return new self($filters);
    }

    /** @param FilterInterface[] $filters */
    public function __construct(array $filters = [])
    {
        $this->filters = ListCollection::fromT(FilterInterface::class, $filters);
    }

    /**
     * Apply all filters to given filterable
     */
    public function applyAllTo(Filterable $filterable, FilterApplicator $filterApplicator): Filterable
    {
        return $this->filters->reduce(
            function (Filterable $filterable, FilterInterface $filter) use ($filterApplicator) {
                return $filterApplicator->apply($filter, $filterable);
            },
            $filterable
        );
    }

    /** @return FilterInterface[] */
    public function getIterator(): iterable
    {
        yield from $this->filters;
    }

    public function getPreparedValues(ApplicatorInterface $applicator): array
    {
        $preparedValues = [];
        foreach ($this->filters as $filter) {
            $preparedValues += $applicator->getPreparedValue($filter);
        }

        return $preparedValues;
    }

    public function addFilter(FilterInterface $filter): FiltersInterface
    {
        if ($filter instanceof FilterIn && $this->shouldMergeInFilter($filter)) {
            /** @var FilterIn $inFilter */
            $inFilter = $this->filters
                ->filter($this->findInFilter($filter))
                ->first();

            $inFilter->addValue($filter->getValue());
        } else {
            $this->filters = $this->filters->add($filter);
        }

        return $this;
    }

    private function shouldMergeInFilter(FilterIn $filter): bool
    {
        return $this->filters->containsBy($this->findInFilter($filter));
    }

    private function findInFilter(FilterIn $filter): \Closure
    {
        return function (FilterInterface $item) use ($filter) {
            return $item instanceof FilterIn && $item->getColumn() === $filter->getColumn();
        };
    }
}

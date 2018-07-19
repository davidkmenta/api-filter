<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

interface FilterInterface
{
    public function getColumn(): string;

    public function getOperator(): string;

    public function getValue(): Value;
}
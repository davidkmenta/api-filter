<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

class Filterable
{
    /** @var mixed */
    private $value;

    /** @param mixed $value */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /** @return mixed */
    public function getValue()
    {
        return $this->value;
    }
}

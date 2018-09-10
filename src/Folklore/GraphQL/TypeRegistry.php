<?php

namespace Folklore\GraphQL;

use Folklore\GraphQL\Exception\TypeNotFound;
use Folklore\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Facades\Log;

class TypeRegistry
{
    private $types = [];
    private $typeObjects = [];

    /**
     * @param string $type
     * @param ObjectType $object
     */
    public function set(string $type, $object)
    {
        $this->types[$type] = $object;
    }

    /**
     * @param string $type
     * @return mixed
     * @throws TypeNotFound
     */
    public function get(string $type)
    {
        if (!isset($this->types[$type]))
        {
            throw new TypeNotFound("Type '$type' was not defined in the TypeRegistry.'");
        }

        if (!isset($this->typeObjects[$type]))
        {
            $this->typeObjects[$type] = GraphQL::objectType($this->types[$type]);
        }

        return $this->typeObjects[$type];
    }
}
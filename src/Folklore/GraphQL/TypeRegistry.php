<?php

namespace Folklore\GraphQL;

use Folklore\GraphQL\Exception\TypeNotFound;
use Folklore\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class TypeRegistry
{
    private $types = [];
    private $typeObjects = [];
    private $scalarDirectory;
    private $scalarNamespace;

    public function __construct()
    {
        $this->scalarDirectory = config("graphql.scalar_directory",app_path('GraphQL/Types/Scalars'));
        $this->scalarNamespace = config("graphql.scalar_namespace");
    }

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
        if (!isset($this->typeObjects[$type]))
        {
            //Might be a scalar
            if (file_exists($this->scalarDirectory . "/$type.php"))
            {
                $this->typeObjects[$type] = App::make($this->scalarNamespace. "\\" . $type);
            }
            else
            {
                $this->typeObjects[$type] = GraphQL::objectType($this->types[$type]);
            }
        }

        return $this->typeObjects[$type];
    }
}
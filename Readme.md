# Differences from official package

This version has been optimized for performance, and I keep up with the latest version of the [webonyx](https://github.com/webonyx/graphql-php) library. This version uses a `TypeRegistry` class inside the schema registration method to lazy load types as they are needed. This has increased performance in application with 500+ types registered by over 50%. I also stopped the unused `TypeAdded` event from firing, which also increased performance significantly.

The usage of the `TypeRegistry` complicates the library to a degree - you must now publish the path to your custom scalars directory and namespace in the `graphql.php` configuration file. When creating custom scalars, your `type` function must be configured as follows:

```
static public function type() {
        if(is_null(self::$_instance))
        {
            self::$_instance = new self();
        }
        return GraphQL::type(self::$_instance->name);
    }
``` 

It's important that anywhere you reference a GraphQL, you always call it with `GraphQL::type` rather than instantiating the class, as this will hook into the `TypeRegistry` class. If you don't do this, you'll get schema errors.

# Laravel GraphQL

Use Facebook GraphQL with Laravel 5 or Lumen. It is based on the PHP implementation [here](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) on the [React](http://facebook.github.io/react) blog or you can read the [GraphQL specifications](https://facebook.github.io/graphql/). This is a work in progress.

This package is compatible with Eloquent model (or any other data source). See the example below.

[![Latest Stable Version](https://poser.pugx.org/folklore/graphql/v/stable.svg)](https://packagist.org/packages/folklore/graphql)
[![Build Status](https://travis-ci.org/Folkloreatelier/laravel-graphql.png?branch=master)](https://travis-ci.org/Folkloreatelier/laravel-graphql)
[![Total Downloads](https://poser.pugx.org/folklore/graphql/downloads.svg)](https://packagist.org/packages/folklore/graphql)

----
### To use laravel-graphql with Relay, check the [feature/relay](https://github.com/Folkloreatelier/laravel-graphql/tree/feature/relay) branch.
----

## Installation

Version 1.0 is released. If you are upgrading from older version, you can check [Upgrade to 1.0](docs/upgrade.md).

#### Dependencies:

* [Laravel 5.x](https://github.com/laravel/laravel) or [Lumen](https://github.com/laravel/lumen)
* [GraphQL PHP](https://github.com/webonyx/graphql-php)


**1-** Require the package via Composer in your `composer.json`.
```json
{
  "require": {
    "sonarsoftware/graphql": "~2.0.0"
  }
}
```

**2-** Run Composer to install or update the new requirement.

```bash
$ composer install
```

or

```bash
$ composer update
```

### Laravel >= 5.5.x

**1-** Publish the configuration file

```bash
$ php artisan vendor:publish --provider="Folklore\GraphQL\ServiceProvider"
```

**2-** Review the configuration file

```
config/graphql.php
```

## Usage

- [Schemas](#schemas)
- [Creating a query](#creating-a-query)
- [Creating a mutation](#creating-a-mutation)
- [Adding validation to mutation](#adding-validation-to-mutation)

#### Advanced Usage
- [Query variables](docs/advanced.md#query-variables)
- [Query nested resource](docs/advanced.md#query-nested-resource)
- [Enums](docs/advanced.md#enums)
- [Interfaces](docs/advanced.md#interfaces)
- [Custom field](docs/advanced.md#custom-field)
- [Eager loading relationships](docs/advanced.md#eager-loading-relationships)

### Schemas
Starting from version 1.0, you can define multiple schemas. Having multiple schemas can be useful if, for example, you want an endpoint that is public and another one that needs authentication.

You can define multiple schemas in the config:

```php
'schema' => 'default',

'schemas' => [
    'default' => [
        'query' => [
            //'users' => 'App\GraphQL\Query\UsersQuery'
        ],
        'mutation' => [
            //'updateUserEmail' => 'App\GraphQL\Query\UpdateUserEmailMutation'
        ]
    ],
    'secret' => [
        'query' => [
            //'users' => 'App\GraphQL\Query\UsersQuery'
        ],
        'mutation' => [
            //'updateUserEmail' => 'App\GraphQL\Query\UpdateUserEmailMutation'
        ]
    ]
]
```

Or you can add schema using the facade:

```php
GraphQL::addSchema('secret', [
    'query' => [
        'users' => 'App\GraphQL\Query\UsersQuery'
    ],
    'mutation' => [
        'updateUserEmail' => 'App\GraphQL\Query\UpdateUserEmailMutation'
    ]
]);
```

Afterwards, you can build the schema using the facade:

```php
// Will return the default schema defined by 'schema' in the config
$schema = GraphQL::schema();

// Will return the 'secret' schema
$schema = GraphQL::schema('secret');

// Will build a new schema
$schema = GraphQL::schema([
    'query' => [
        //'users' => 'App\GraphQL\Query\UsersQuery'
    ],
    'mutation' => [
        //'updateUserEmail' => 'App\GraphQL\Query\UpdateUserEmailMutation'
    ]
]);
```

Or you can request the endpoint for a specific schema

```
// Default schema
http://homestead.app/graphql?query=query+FetchUsers{users{id,email}}

// Secret schema
http://homestead.app/graphql/secret?query=query+FetchUsers{users{id,email}}
```

### Creating a query

First you need to create a type.

```php
namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'description' => 'A user'
    ];

    /*
    * Uncomment following line to make the type input object.
    * http://graphql.org/learn/schema/#input-types
    */
    // protected $inputObject = true;

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user'
            ]
        ];
    }

    // If you want to resolve the field yourself, you can declare a method
    // with the following format resolve[FIELD_NAME]Field()
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }
}
```

Add the type to the `config/graphql.php` configuration file

```php
'types' => [
    'User' => 'App\GraphQL\Type\UserType'
]
```

You could also add the type with the `GraphQL` Facade, in a service provider for example.

```php
GraphQL::addType('App\GraphQL\Type\UserType', 'User');
```

Then you need to define a query that returns this type (or a list). You can also specify arguments that you can use in the resolve method.
```php
namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\User;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users'
    ];

    public function type()
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function args()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::string()
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string()
            ]
        ];
    }

    public function resolve($root, $args)
    {
        if (isset($args['id'])) {
            return User::where('id' , $args['id'])->get();
        } else if(isset($args['email'])) {
            return User::where('email', $args['email'])->get();
        } else {
            return User::all();
        }
    }
}
```

If any part of the query has a default value, you can define this in the `args()` function by including `defaultValue` as an array key in one of the arguments, and setting the value to the default value.
For example, if you had an optional argument in your query to `include_deleted_users` and the default value should be `false`, then you could edit the `args()` function as shown below:

```
public function args()
{
    return [
        'id' => [
            'name' => 'id',
            'type' => Type::string()
        ],
        'email' => [
            'name' => 'email',
            'type' => Type::string()
        ],
        'include_deleted_users' => [
            'name' => 'include_deleted_users',
            'type' => Type::boolean(),
            'defaultValue' => false
        ]
    ];
}
```

You can also include descriptions for query fields by adding a `description` array key - this will be shown in the schema, and picked up by tools like GraphiQL.

Add the query to the `config/graphql.php` configuration file

```php
'schemas' => [
    'default' => [
        'query' => [
            'users' => 'App\GraphQL\Query\UsersQuery'
        ],
        // ...
    ]
]
```

And that's it. You should be able to query GraphQL with a request to the url `/graphql` (or anything you choose in your config). Try a GET request with the following `query` input

```
query FetchUsers {
  users {
    id
    email
  }
}
```

For example, if you use homestead:
```
http://homestead.app/graphql?query=query+FetchUsers{users{id,email}}
```

### Creating a mutation

A mutation is like any other query, it accepts arguments (which will be used to do the mutation) and return an object of a certain type.

For example a mutation to update the password of a user. First you need to define the Mutation.

```php
namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\User;

class UpdateUserPasswordMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateUserPassword'
    ];

    public function type()
    {
        return GraphQL::type('User');
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::nonNull(Type::string())],
            'password' => ['name' => 'password', 'type' => Type::nonNull(Type::string())]
        ];
    }

    public function resolve($root, $args)
    {
        $user = User::find($args['id']);

        if (!$user) {
            return null;
        }

        $user->password = bcrypt($args['password']);
        $user->save();

        return $user;
    }
}
```

As you can see in the `resolve` method, you use the arguments to update your model and return it.

You then add the mutation to the `config/graphql.php` configuration file

```php
'schema' => [
    'default' => [
        'mutation' => [
            'updateUserPassword' => 'App\GraphQL\Mutation\UpdateUserPasswordMutation'
        ],
        // ...
    ]
]
```

You should then be able to use the following query on your endpoint to do the mutation.

```
mutation users {
  updateUserPassword(id: "1", password: "newpassword") {
    id
    email
  }
}
```

if you use homestead:
```
http://homestead.app/graphql?query=mutation+users{updateUserPassword(id: "1", password: "newpassword"){id,email}}
```

#### Adding validation to mutation

It is possible to add validation rules to mutation. It uses the laravel `Validator` to performs validation against the `args`.

When creating a mutation, you can add a method to define the validation rules that apply by doing the following:

```php
namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\User;

class UpdateUserEmailMutation extends Mutation
{
    protected $attributes = [
        'name' => 'UpdateUserEmail'
    ];

    public function type()
    {
        return GraphQL::type('User');
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::string()]
        ];
    }

    public function rules()
    {
        return [
            'id' => ['required'],
            'email' => ['required', 'email']
        ];
    }

    public function resolve($root, $args)
    {
        $user = User::find($args['id']);

        if (!$user) {
            return null;
        }

        $user->email = $args['email'];
        $user->save();

        return $user;
    }
}
```

Alternatively you can define rules with each args

```php
class UpdateUserEmailMutation extends Mutation
{
    //...

    public function args()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::string(),
                'rules' => ['required']
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'rules' => ['required', 'email']
            ]
        ];
    }

    //...
}
```

When you execute a mutation, it will returns the validation errors. Since GraphQL specifications define a certain format for errors, the validation errors messages are added to the error object as a extra `validation` attribute. To find the validation error, you should check for the error with a `message` equals to `'validation'`, then the `validation` attribute will contain the normal errors messages returned by the Laravel Validator.

```json
{
  "data": {
    "updateUserEmail": null
  },
  "errors": [
    {
      "message": "validation",
      "locations": [
        {
          "line": 1,
          "column": 20
        }
      ],
      "validation": {
        "email": [
          "The email is invalid."
        ]
      }
    }
  ]
}
```

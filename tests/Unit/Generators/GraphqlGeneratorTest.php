<?php

use Arseno25\LaravelApiMagic\Generators\GraphqlGenerator;

uses()->group('unit', 'graphql-generator');

it('generates graphql types and queries', function () {
    $schema = [
        'title' => 'Test API',
        'version' => '1.0.0',
        'baseUrl' => 'http://localhost',
        'endpoints' => [
            '/api/users' => [
                'get' => [
                    'summary' => 'Get Users',
                    'responses' => [
                        ['status' => 200, 'resource' => 'App\\Http\\Resources\\UserResource', 'is_array' => true]
                    ],
                ]
            ],
            '/api/users/{id}' => [
                'get' => [
                    'summary' => 'Get User',
                    'parameters' => [
                        'path' => [['name' => 'id', 'type' => 'integer']],
                    ],
                    'responses' => [
                        ['status' => 200, 'resource' => 'App\\Http\\Resources\\UserResource', 'is_array' => false]
                    ],
                ],
                'post' => [
                    'summary' => 'Update User',
                    'parameters' => [
                        'path' => [['name' => 'id', 'type' => 'integer']],
                        'body' => [
                            'name' => ['type' => 'string', 'required' => true],
                            'email' => ['type' => 'string', 'required' => false],
                        ]
                    ],
                    'responses' => [
                        ['status' => 200, 'resource' => 'App\\Http\\Resources\\UserResource', 'is_array' => false]
                    ]
                ]
            ]
        ]
    ];

    $resources = [
        'App\\Http\\Resources\\UserResource' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'email' => ['type' => 'string'],
        ]
    ];

    $generator = new GraphqlGenerator();
    $result = $generator->generate($schema, $resources);

    // Assert that the generated schema contains Query and Mutation types
    expect($result)->toContain('type Query {');
    expect($result)->toContain('type Mutation {');

    // Assert that it generated the clean User type
    expect($result)->toContain('type User {');
    expect($result)->toContain('id: ID!');
    expect($result)->toContain('name: String');
    
    // Assert queries (auto-generated based on path/method)
    expect($result)->toContain('users: [User!]!');
    expect($result)->toContain('user(id: ID!): User');

    // Assert mutations
    expect($result)->toContain('createUser(id: ID!, input: UserCreateInput!): User');

    // Assert inputs
    expect($result)->toContain('input UserCreateInput {');
    expect($result)->toContain('name: String!');
    expect($result)->toContain('email: String');
});

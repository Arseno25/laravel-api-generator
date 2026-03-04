<?php

use Arseno25\LaravelApiMagic\Generators\CodeSnippetGenerator;

describe('Code Snippet Generator', function () {
    it('generates curl snippet', function () {
        $generator = new CodeSnippetGenerator;
        $endpoint = [
            'parameters' => [
                'body' => [
                    ['name' => 'title', 'type' => 'string'],
                    ['name' => 'price', 'type' => 'integer'],
                ],
            ],
            'security' => [['type' => 'http', 'scheme' => 'bearer']],
        ];

        $snippets = $generator->generate('post', '/api/products', $endpoint, 'http://localhost');

        expect($snippets)->toHaveKey('curl');
        expect($snippets)->toHaveKey('javascript');
        expect($snippets)->toHaveKey('php');
        expect($snippets)->toHaveKey('python');
        expect($snippets['curl'])->toContain('POST');
        expect($snippets['curl'])->toContain('application/json');
    });

    it('generates javascript snippet with bearer token', function () {
        $generator = new CodeSnippetGenerator;
        $endpoint = [
            'parameters' => ['body' => []],
            'security' => [['type' => 'http', 'scheme' => 'bearer']],
        ];

        $snippets = $generator->generate('get', '/api/users', $endpoint, 'http://localhost');

        expect($snippets['javascript'])->toContain('Authorization');
        expect($snippets['javascript'])->toContain('Bearer');
    });

    it('generates php snippet', function () {
        $generator = new CodeSnippetGenerator;
        $endpoint = [
            'parameters' => [
                'body' => [
                    ['name' => 'email', 'type' => 'email'],
                ],
            ],
            'security' => [],
        ];

        $snippets = $generator->generate('post', '/api/auth/login', $endpoint, 'http://localhost');

        expect($snippets['php'])->toContain('Http::');
        expect($snippets['php'])->toContain('post');
    });

    it('generates python snippet', function () {
        $generator = new CodeSnippetGenerator;
        $endpoint = [
            'parameters' => ['body' => []],
            'security' => [],
        ];

        $snippets = $generator->generate('delete', '/api/users/1', $endpoint, 'http://localhost');

        expect($snippets['python'])->toContain('import requests');
        expect($snippets['python'])->toContain('requests.delete');
    });
});

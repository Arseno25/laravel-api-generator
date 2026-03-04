<?php

use Illuminate\Support\Facades\File;

uses()->group('unit', 'commands', 'graphql-command');

it('generates graphql schema file', function () {
    // Clear potentially cached schema
    $graphqlFile = resource_path('graphql/schema.graphql');
    if (File::exists($graphqlFile)) {
        File::delete($graphqlFile);
    }

    // Define some routes to parse so that the api-magic schema has items
    Illuminate\Support\Facades\Route::middleware('api')->get('/api/test-graphql-route', function () {
        return response()->json(['success' => true]);
    });

    // Run the command
    $this->artisan('api-magic:graphql')
        ->assertExitCode(0)
        ->expectsOutputToContain('✅ Generated GraphQL schema!');

    // Assert file was created
    expect(File::exists($graphqlFile))->toBeTrue();

    // Clean up
    if (File::exists($graphqlFile)) {
        File::delete($graphqlFile);
    }
});

<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Generators\GraphqlGenerator;
use Arseno25\LaravelApiMagic\Services\DocumentationSchemaBuilder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class GenerateGraphqlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-magic:graphql
                            {--output= : Output file path for the GraphQL schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a GraphQL schema from your REST API endpoints';

    /**
     * Execute the console command.
     */
    public function handle(DocumentationSchemaBuilder $schemaBuilder): int
    {
        $this->info('🔧 Generating GraphQL schema...');

        $schema = $schemaBuilder->buildInternalSchema(Request::create('/'));

        $generator = new GraphqlGenerator;
        $graphqlSchema = $generator->generate($schema);

        $output =
            $this->option('output') ?? resource_path('graphql/schema.graphql');

        $dir = dirname($output);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($output, $graphqlSchema);

        $queryCount = substr_count($graphqlSchema, 'type Query');
        $mutationCount = substr_count($graphqlSchema, 'type Mutation');
        $typeMatches = [];
        preg_match_all(
            "/^type (?!Query|Mutation)\w+/m",
            $graphqlSchema,
            $typeMatches,
        );
        $typeCount = count($typeMatches[0]);

        $this->info('✅ Generated GraphQL schema!');
        $this->info(
            "   Types: {$typeCount} | Queries: ".
                ($queryCount > 0 ? 'Yes' : 'No').
                ' | Mutations: '.
                ($mutationCount > 0 ? 'Yes' : 'No'),
        );
        $this->info("   File saved to: {$output}");

        return self::SUCCESS;
    }
}

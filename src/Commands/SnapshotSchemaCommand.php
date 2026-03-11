<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Services\ChangelogService;
use Arseno25\LaravelApiMagic\Services\DocumentationSchemaBuilder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class SnapshotSchemaCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = "api-magic:snapshot";

    /**
     * The console command description.
     */
    protected $description = "Save a snapshot of the current API schema for changelog tracking";

    /**
     * Execute the console command.
     */
    public function handle(DocumentationSchemaBuilder $schemaBuilder): int
    {
        $this->info("📸 Taking API schema snapshot...");

        $request = Request::create("/api/docs/json", "GET");
        $schema = $schemaBuilder->buildInternalSchema($request);

        $changelog = new ChangelogService();
        $savedPath = $changelog->saveSnapshot($schema);

        $endpointCount = 0;
        foreach ($schema["endpoints"] ?? [] as $methods) {
            $endpointCount += count($methods);
        }

        $this->info("✅ Snapshot saved: {$savedPath}");
        $this->info("📊 Endpoints captured: {$endpointCount}");

        // Show diff with previous snapshot if available
        $snapshots = $changelog->getSnapshots();
        if (count($snapshots) > 1) {
            $previousContent = file_get_contents($snapshots[1]["path"]);
            if ($previousContent === false) {
                return self::SUCCESS;
            }

            $previousSchema = json_decode($previousContent, true);

            if ($previousSchema) {
                $diff = $changelog->computeDiff($previousSchema, $schema);

                if (
                    $diff["total_added"] > 0 ||
                    $diff["total_removed"] > 0 ||
                    $diff["total_changed"] > 0
                ) {
                    $this->newLine();
                    $this->info("📝 Changes since last snapshot:");

                    if ($diff["total_added"] > 0) {
                        $this->line(
                            "  <fg=green>+ {$diff["total_added"]} endpoint(s) added</>",
                        );
                        foreach ($diff["added"] as $key => $endpoint) {
                            $this->line("    <fg=green>+ {$key}</>");
                        }
                    }

                    if ($diff["total_removed"] > 0) {
                        $this->line(
                            "  <fg=red>- {$diff["total_removed"]} endpoint(s) removed</>",
                        );
                        foreach ($diff["removed"] as $key => $endpoint) {
                            $this->line("    <fg=red>- {$key}</>");
                        }
                    }

                    if ($diff["total_changed"] > 0) {
                        $this->line(
                            "  <fg=yellow>~ {$diff["total_changed"]} endpoint(s) changed</>",
                        );
                        foreach ($diff["changed"] as $key => $change) {
                            $this->line("    <fg=yellow>~ {$key}</>");
                        }
                    }
                } else {
                    $this->info("✨ No changes detected since last snapshot.");
                }
            }
        }

        return self::SUCCESS;
    }
}

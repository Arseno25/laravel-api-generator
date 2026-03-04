<?php

use Arseno25\LaravelApiMagic\Parsers\EventAnalyzer;
use Illuminate\Support\Facades\File;

uses()->group('parsers', 'event-analyzer');

beforeEach(function () {
    $this->analyzer = new EventAnalyzer();
});

afterEach(function () {
    $tempPath = base_path('app/Events/TestUserCreated.php');
    if (File::exists($tempPath)) {
        File::delete($tempPath);
    }
});

describe('Event analysis', function () {
    it('returns empty array if directory does not exist', function () {
        $result = $this->analyzer->analyze(base_path('app/NonExistentEvents'));
        expect($result)->toBeEmpty();
    });

    it('analyzes ShouldBroadcast events', function () {
        $eventContent = <<<'PHP'
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * @summary A test user created event
 */
class TestUserCreated implements ShouldBroadcast
{
    public int $userId;
    public string $username;

    public function __construct(int $userId, string $username)
    {
        $this->userId = $userId;
        $this->username = $username;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('users.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'user.created';
    }
}
PHP;

        $tempPath = base_path('app/Events/TestUserCreated.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $eventContent);
        
        // Ensure class is required so reflection can find it
        if (!class_exists('App\Events\TestUserCreated')) {
            require_once $tempPath;
        }

        $result = $this->analyzer->analyze(dirname($tempPath));

        expect($result)->not->toBeEmpty();
        expect($result)->toHaveKey('user.created');
        
        $eventData = $result['user.created'];
        expect($eventData['name'])->toBe('user.created');
        expect($eventData['description'])->toBe('A test user created event');
        expect($eventData['channel'])->toContain('users.');
        expect($eventData['payload']['properties'])->toHaveKey('userId');
        expect($eventData['payload']['properties']['userId']['type'])->toBe('integer');
        expect($eventData['payload']['properties'])->toHaveKey('username');
        expect($eventData['payload']['properties']['username']['type'])->toBe('string');
    });
});

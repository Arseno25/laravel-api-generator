<?php

use Arseno25\LaravelApiMagic\Attributes\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;

class ContractProbeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role' => 'nullable|in:admin,editor',
            'avatar' => 'nullable|file',
        ];
    }
}

class ContractProbeController extends Controller
{
    public function store(
        ContractProbeRequest $request,
    ): \Illuminate\Http\JsonResponse {
        return response()->json([
            'data' => [
                'email' => $request->input('email'),
            ],
        ]);
    }
}

class NestedContractProbeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'profile.name' => 'required|string',
            'profile.preferences.newsletter' => 'nullable|boolean',
            'items' => 'required|array',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'nullable|integer',
            'tags' => 'nullable|array',
            'tags.*' => 'required|string',
        ];
    }
}

class NestedContractProbeController extends Controller
{
    public function store(
        NestedContractProbeRequest $request,
    ): \Illuminate\Http\JsonResponse {
        return response()->json(['data' => $request->validated()]);
    }
}

class ProfileProbeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

class ProfileProbeController extends Controller
{
    public function show(): ProfileProbeResource
    {
        return new ProfileProbeResource(
            (object) [
                'id' => 1,
                'name' => 'Seno',
            ],
        );
    }
}

class ApprovalProbeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}

class AttributeResponseProbeController extends Controller
{
    #[
        ApiResponse(
            status: 202,
            resource: ApprovalProbeResource::class,
            description: 'Request accepted for processing',
        ),
    ]
    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['queued' => true], 202);
    }
}

class PhotoVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'url' => $this->url,
        ];
    }
}

class VideoVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'stream_url' => $this->stream_url,
        ];
    }
}

class PolymorphicProbeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'subject' => $this->type === 'photo'
                    ? new PhotoVariantResource(
                        (object) ['url' => 'https://example.com/photo.jpg'],
                    )
                    : new VideoVariantResource(
                        (object) [
                            'stream_url' => 'https://example.com/video.m3u8',
                        ],
                    ),
        ];
    }
}

class PolymorphicProbeController extends Controller
{
    public function show(): PolymorphicProbeResource
    {
        return new PolymorphicProbeResource(
            (object) [
                'type' => 'photo',
            ],
        );
    }
}

uses()->group('feature', 'openapi-schema-builder');

it(
    'extracts request body schemas into reusable OpenAPI components',
    function () {
        Route::middleware('api')->post('/api/contracts', [
            ContractProbeController::class,
            'store',
        ]);

        $response = getJson('/api/docs/export');

        $response
            ->assertOk()
            ->assertJsonPath(
                'paths./api/contracts.post.requestBody.content.multipart/form-data.schema.$ref',
                '#/components/schemas/ContractProbeRequestPayload',
            )
            ->assertJsonPath(
                'components.schemas.ContractProbeRequestPayload.properties.email.format',
                'email',
            )
            ->assertJsonPath(
                'components.schemas.ContractProbeRequestPayload.properties.avatar.format',
                'binary',
            )
            ->assertJsonPath(
                'components.schemas.ContractProbeRequestPayload.properties.role.enum.0',
                'admin',
            )
            ->assertJsonPath(
                'components.schemas.ContractProbeRequestPayload.properties.role.enum.1',
                'editor',
            );

        expect(
            $response->json(
                'components.schemas.ContractProbeRequestPayload.required',
            ),
        )->toContain('email');
    },
);

it(
    'builds nested request body schemas and multiple content types',
    function () {
        Route::middleware('api')->post('/api/nested-contracts', [
            NestedContractProbeController::class,
            'store',
        ]);

        $response = getJson('/api/docs/export');

        $response
            ->assertOk()
            ->assertJsonPath(
                'paths./api/nested-contracts.post.requestBody.content.application/json.schema.$ref',
                '#/components/schemas/NestedContractProbeRequestPayload',
            )
            ->assertJsonPath(
                'paths./api/nested-contracts.post.requestBody.content.application/x-www-form-urlencoded.schema.$ref',
                '#/components/schemas/NestedContractProbeRequestPayload',
            )
            ->assertJsonPath(
                'components.schemas.NestedContractProbeRequestPayload.properties.profile.type',
                'object',
            )
            ->assertJsonPath(
                'components.schemas.NestedContractProbeRequestPayload.properties.profile.properties.name.type',
                'string',
            )
            ->assertJsonPath(
                'components.schemas.NestedContractProbeRequestPayload.properties.items.type',
                'array',
            )
            ->assertJsonPath(
                'components.schemas.NestedContractProbeRequestPayload.properties.items.items.properties.sku.type',
                'string',
            )
            ->assertJsonPath(
                'components.schemas.NestedContractProbeRequestPayload.properties.tags.type',
                'array',
            )
            ->assertJsonPath(
                'components.schemas.NestedContractProbeRequestPayload.properties.tags.items.type',
                'string',
            );
    },
);

it('wraps single resource responses in reusable envelope schemas', function () {
    Route::middleware('api')->get('/api/profile-probe', [
        ProfileProbeController::class,
        'show',
    ]);

    $response = getJson('/api/docs/export');

    $response
        ->assertOk()
        ->assertJsonPath(
            'paths./api/profile-probe.get.responses.200.content.application/json.schema.$ref',
            '#/components/schemas/ProfileProbeResourceResponse',
        )
        ->assertJsonPath(
            'components.schemas.ProfileProbeResourceResponse.properties.data.$ref',
            '#/components/schemas/ProfileProbeResource',
        )
        ->assertJsonPath(
            'components.schemas.ProfileProbeResource.properties.id.type',
            'integer',
        )
        ->assertJsonPath(
            'components.schemas.ProfileProbeResource.properties.name.type',
            'string',
        );
});

it(
    'maps ApiResponse resource metadata into reusable response schemas',
    function () {
        Route::middleware('api')->post('/api/approval-probe', [
            AttributeResponseProbeController::class,
            'store',
        ]);

        $response = getJson('/api/docs/export');

        $response
            ->assertOk()
            ->assertJsonPath(
                'paths./api/approval-probe.post.responses.202.content.application/json.schema.$ref',
                '#/components/schemas/ApprovalProbeResourceResponse',
            )
            ->assertJsonPath(
                'paths./api/approval-probe.post.responses.202.content.application/json.example.data.id',
                1,
            )
            ->assertJsonPath(
                'paths./api/approval-probe.post.responses.202.content.application/json.example.data.status',
                'string',
            );
    },
);

it('emits oneOf metadata for polymorphic resource properties', function () {
    Route::middleware('api')->get('/api/polymorphic-probe', [
        PolymorphicProbeController::class,
        'show',
    ]);

    $response = getJson('/api/docs/export');

    $response
        ->assertOk()
        ->assertJsonPath(
            'components.schemas.PolymorphicProbeResource.properties.subject.oneOf.0.title',
            'PhotoVariantResource',
        )
        ->assertJsonPath(
            'components.schemas.PolymorphicProbeResource.properties.subject.oneOf.1.title',
            'VideoVariantResource',
        );
});

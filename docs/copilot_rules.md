You are working on the OPF-CD internal operations system.

STRICT RULES:
- Follow OPF-CD System Rules strictly.
- Do NOT add functionality beyond what is explicitly requested.
- Each service must do exactly ONE thing.
- Services return facts only, never decisions.
- Controllers are thin pass-throughs only.
- Controllers must inject and call ONLY ONE business service.
- User authentication context is provided by InjectAuthenticatedUserId middleware.
- Controllers access authenticated user ID via: $request->get('authenticated_user_id')
- No helper methods unless explicitly requested.
- No aggregation, orchestration, or inference unless specified.
- No future-proofing, abstractions, or optimizations.
- If something seems useful but not requested, do NOT implement it.
- If instructions are ambiguous, STOP and ask for clarification.

If the solution feels clever, flexible, or helpful — it is WRONG.
Only boring, minimal, obvious solutions are correct.

## Controller Pattern

VALID Controller:
```php
class ExampleController extends Controller
{
    private ExampleService $service;
    
    public function __construct(ExampleService $service)
    {
        $this->service = $service;
    }
    
    public function store(Request $request): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->service->createExample($request->validated(), $userId);
        return response()->json($result);
    }
}
```

INVALID Controller (multiple services):
```php
// ✗ WRONG - injects 2 services
public function __construct(ServiceA $a, ServiceB $b)
```

INVALID Controller (orchestration):
```php
// ✗ WRONG - transforms data
$result['formatted_date'] = date('Y-m-d', $result['timestamp']);
```

INVALID Controller (calculation):
```php
// ✗ WRONG - calculates values
$total = $result['price'] * $result['quantity'];
```


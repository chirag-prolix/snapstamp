# snapstamp — Reusable Skills & Patterns

Recipes for common tasks in this codebase. Reference these when implementing new features.

---

## Skill: Add a New Entity

1. Create `src/Entity/MyEntity.php`
   - UUID id generated in `__construct()` via `Ramsey\Uuid\Uuid::uuid4()`
   - Add `#[ORM\Index]` on columns used in WHERE clauses
   - Use backed enums for status/type fields
   - Add `createdAt`/`updatedAt` as `\DateTimeImmutable`

2. Create `src/Repository/MyEntityRepository.php`
   - Extend `ServiceEntityRepository`
   - Add custom query methods (avoid queries in services)

3. Generate migration:
   ```bash
   php bin/console doctrine:migrations:diff
   php bin/console doctrine:migrations:migrate
   ```

4. Add the entity to `src/Entity/` imports in any service that needs it.

**Template:**
```php
#[ORM\Entity(repositoryClass: MyEntityRepository::class)]
#[ORM\Table(name: 'my_entities', indexes: [
    new ORM\Index(columns: ['status']),
])]
class MyEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', enumType: MyStatusEnum::class)]
    private MyStatusEnum $status = MyStatusEnum::ACTIVE;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }
}
```

---

## Skill: Add a New API Endpoint

1. Create or extend a controller in `src/Controller/Api/`
2. Create a DTO in `src/Dto/` with `#[Assert\*]` annotations
3. Add business logic to the relevant Service (or create a new service)
4. The controller pattern:

```php
#[Route('/api/v1/things', name: 'api_things_')]
#[IsGranted('ROLE_USER')]
class ThingController extends AbstractController
{
    public function __construct(private ThingService $thingService) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new CreateThingDto();
        $dto->name = $data['name'] ?? '';

        try {
            $thing = $this->thingService->create($dto);
            return $this->json(['success' => true, 'data' => ['id' => $thing->getId()]], 201);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
```

**Status code guide:** 201 create, 200 read/update, 400 domain error, 401 unauth, 403 forbidden, 404 not found.

---

## Skill: Add Caching to a Service Method

Use `RedisAdapter::get()` with a TTL. Always invalidate on writes.

```php
public function getThingDetails(string $id): array
{
    return $this->cache->get("thing:{$id}:details", function () use ($id) {
        $thing = $this->thingRepository->find($id);
        return ['id' => $thing->getId(), 'name' => $thing->getName()];
    }, \DateInterval::createFromDateString('1 hour'));
}

public function updateThing(Thing $thing): void
{
    // ... persist ...
    $this->cache->delete("thing:{$thing->getId()}:details");
}
```

Cache key naming convention: `{entity}:{id}:{aspect}` or `{entity}s:{qualifier}:{params}`.

---

## Skill: Add an Async Message (Queue)

For operations that don't need to block the HTTP response (emails, heavy reports).

1. Create the message value object in `src/Message/`:
```php
// src/Message/SendWelcomeEmailMessage.php
readonly class SendWelcomeEmailMessage
{
    public function __construct(public string $customerId) {}
}
```

2. Create the handler in `src/MessageHandler/`:
```php
// src/MessageHandler/SendWelcomeEmailHandler.php
#[AsMessageHandler]
class SendWelcomeEmailHandler
{
    public function __invoke(SendWelcomeEmailMessage $message): void
    {
        // do the slow work here
    }
}
```

3. Dispatch from a service:
```php
$this->messageBus->dispatch(new SendWelcomeEmailMessage($customer->getId()));
```

4. Run workers:
```bash
php bin/console messenger:consume async
```

---

## Skill: Add a Custom Validator

For constraints that need DB lookups (e.g. unique email with custom message).

```php
// src/Validator/UniqueEmail.php
#[\Attribute]
class UniqueEmail extends Constraint {}

// src/Validator/UniqueEmailValidator.php
class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(private UserRepository $userRepository) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->userRepository->findOneBy(['email' => $value])) {
            $this->context->buildViolation('Email already registered')->addViolation();
        }
    }
}
```

---

## Skill: Add a New Enum

```php
// src/Enum/ThingStatusEnum.php
namespace App\Enum;

enum ThingStatusEnum: string
{
    case ACTIVE   = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case EXPIRED  = 'EXPIRED';
}
```

Use in entity: `#[ORM\Column(type: 'string', enumType: ThingStatusEnum::class)]`

---

## Skill: Add a Security Voter

Use when `#[IsGranted]` roles alone aren't enough (e.g. "only the owning merchant can edit this reward").

```php
// src/Security/Voter/RewardVoter.php
class RewardVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'EDIT' && $subject instanceof Reward;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Merchant) return false;
        return $subject->getMerchant()->getId() === $user->getId();
    }
}
```

Call in controller: `$this->denyAccessUnlessGranted('EDIT', $reward);`

---

## Skill: Add a Cron Command

For scheduled tasks like expiring stamps or sending daily digests.

```php
// src/Command/ProcessExpiredStampsCommand.php
#[AsCommand(name: 'app:stamps:expire', description: 'Expire old stamps')]
class ProcessExpiredStampsCommand extends Command
{
    public function __construct(private StampService $stampService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->stampService->expireOldStamps();
        $output->writeln("Expired {$count} stamps.");
        return Command::SUCCESS;
    }
}
```

Schedule via system cron or Symfony Scheduler:
```
0 2 * * * php /var/www/bin/console app:stamps:expire
```

---

## Skill: Write a Unit Test for a Service

```php
class ThingServiceTest extends TestCase
{
    private ThingService $service;

    protected function setUp(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $repo       = $this->createMock(ThingRepository::class);
        $cache      = $this->createMock(RedisAdapter::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $this->service = new ThingService($em, $repo, $cache, $logger);
    }

    public function testCreateThing(): void
    {
        $dto = new CreateThingDto();
        $dto->name = 'Widget';

        $result = $this->service->create($dto);

        $this->assertNotNull($result->getId());
        $this->assertEquals('Widget', $result->getName());
    }
}
```

Run: `php bin/phpunit tests/Unit/Service/ThingServiceTest.php`

---

## Skill: Standard JSON Response Shapes

**Success (list):**
```json
{
  "success": true,
  "data": [{ "id": "...", "name": "..." }],
  "meta": { "total": 50, "page": 1, "per_page": 20 }
}
```

**Success (single):**
```json
{ "success": true, "data": { "id": "...", "name": "..." } }
```

**Created:**
```json
{ "success": true, "message": "Created successfully", "data": { "id": "..." } }
```

**Error:**
```json
{ "success": false, "message": "Human-readable reason" }
```

---

## Common Pitfalls

| Mistake | Fix |
|---|---|
| Calling `password_hash()` directly | Use `UserPasswordHasherInterface::hashPassword()` |
| Storing money as `float` | Use `decimal` type, store as `string` |
| Comparing enum to string | Use `$enum->value === 'ACTIVE'` or `$enum === MyEnum::ACTIVE` |
| Forgetting to flush after persist | Always call `$em->flush()` in the service after all `persist()` calls |
| Cache not invalidated after write | Call `$cache->delete($key)` in every write path |
| Returning raw `\Exception::getMessage()` | Catch and return domain-safe messages only |
| Hard-deleting users | Set `deletedAt` timestamp instead |

Add a new REST API endpoint to snapstamp following the project's conventions.

$ARGUMENTS

---

## Step 1 — Parse the request

From `$ARGUMENTS`, extract:
- HTTP method (GET / POST / PATCH / DELETE)
- Route path (e.g. `/api/v1/stamps/redeem`)
- Role required (ROLE_CUSTOMER / ROLE_MERCHANT / ROLE_ADMIN / public)
- What it does in one sentence
- Does this require a new entity or DB column? (determines whether a migration is needed)

If any of these are unclear, ask before proceeding.

---

## Step 2 — Create the DTO (skip when there are no request body parameters)

Read `src/Dto/Reward/CreateRewardDto.php` as a style reference first.

File: `src/Dto/<Domain>/<ActionName>Dto.php`

Rules:
- Namespace: `App\Dto\<Domain>`
- Public typed properties only — no constructor
- Every required field: `#[Assert\NotBlank]` + a type-appropriate constraint
- Optional fields: `#[Assert\Optional]` wrapping their constraints, typed as `?Type = null`
- String enums: `#[Assert\Choice(choices: [...])]` with a readable message listing valid values
- Decimal money fields: store as `string`, validate with `#[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]`

---

## Step 3 — Add the service method

Locate the relevant service in `src/Service/` (or create one if none fits). Read the existing service file first to check what is already injected in the constructor before adding anything.

Rules:
- Method receives the typed DTO (and the authenticated User entity if needed)
- All DB writes go through `EntityManagerInterface::flush()` here, not in the controller
- After any write, delete affected cache keys per CLAUDE.md caching conventions
- If `LoggerInterface` is not already injected, add it — `$this->logger->info(...)` on success, `$this->logger->error(...)` on failure
- Throw a `\DomainException` (or a specific class from `src/Exception/`) for business-rule violations
- Never throw generic `\Exception` for expected failure cases

---

## Step 4 — Add the controller action

Locate the relevant controller in `src/Controller/Api/` (match by domain — e.g. stamps → `StampController.php`). Read the existing controller file first to check what is already injected and where to insert the new action.

Rules:
- Class-level `#[Route('/api/v1/<resource>')]`, method-level `#[Route('/<sub-path>', methods: ['METHOD'])]`
- Add `#[IsGranted('ROLE_X')]` on the method (or on the class if every action needs the same role)
- If `ValidatorInterface` or the service are not already injected, add them to the constructor
- For POST/PATCH: hydrate the DTO from `$request->toArray()`, run `$this->validate($dto)` before calling the service
- Return shape: `$this->json(['success' => true, 'message' => '...', 'data' => $result], Response::HTTP_OK)` (use `HTTP_CREATED` for POST)
- Catch `\DomainException $e` → `$this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST)`
- Never let a raw `\Exception` message reach the client — catch it, log it, return a generic 500 message
- Status codes follow CLAUDE.md API Shape conventions

---

## Step 5 — Write the unit test

File: `tests/Unit/Service/<ServiceName>Test.php`

Rules:
- Mock all dependencies with `$this->createMock()`
- Happy path: assert the return value and that `flush()` was called
- Failure path: assert the `\DomainException` is thrown with the right message
- Name tests `test<ActionName>_<scenario>` (e.g. `testRedeemReward_throwsWhenExpired`)

---

## Done when

- [ ] DTO exists with all fields validated (if applicable)
- [ ] Service method contains all business logic
- [ ] Controller action is thin: hydrate → validate → call service → return JSON
- [ ] Unit test covers happy path + at least one failure case
- [ ] `php bin/phpunit tests/Unit/Service/<ServiceName>Test.php` passes — fix any failures before finishing
- [ ] If a new entity or column was added: `php bin/console doctrine:schema:validate` passes

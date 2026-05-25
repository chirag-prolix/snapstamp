<?php

namespace App\Controller\Api;

use App\Dto\Stamp\IssueStampDto;
use App\Entity\Merchant;
use App\Service\StampService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/stamps')]
#[IsGranted('ROLE_MERCHANT')]
#[OA\Tag(name: 'Stamps')]
class StampController extends AbstractController
{
    public function __construct(
        private readonly StampService $stampService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/issue', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/stamps/issue',
        summary: 'Issue stamps to a customer (by ID or phone)',
        description: 'Either customerId or customerPhone must be provided.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'customerId', type: 'string', format: 'uuid', nullable: true, example: 'a1b2c3d4-...'),
                    new OA\Property(property: 'customerPhone', type: 'string', nullable: true, example: '+919876543210'),
                    new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 10, example: 1),
                    new OA\Property(property: 'transactionId', type: 'string', nullable: true, maxLength: 100),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 500),
                    new OA\Property(property: 'isBonus', type: 'boolean', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Stamps issued, returns stamp card detail'),
            new OA\Response(response: 400, description: 'Validation error or customer not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function issue(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new IssueStampDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        if ($dto->customerId === null && $dto->customerPhone === null) {
            return $this->json(
                ['success' => false, 'message' => 'Provide customerId or customerPhone.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $result = $this->stampService->issueStamps($merchant, $dto);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Stamps issued.', 'data' => $result], Response::HTTP_CREATED);
    }

    #[Route('/card/{customerId}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/stamps/card/{customerId}',
        summary: "Get a customer's active stamp card for this merchant",
        parameters: [
            new OA\Parameter(name: 'customerId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stamp card detail with stamps'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Customer or card not found'),
        ]
    )]
    public function customerCard(string $customerId): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        try {
            $result = $this->stampService->getCustomerCard($merchant, $customerId);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['success' => true, 'message' => 'OK', 'data' => $result]);
    }

    private function hydrate(object $dto, Request $request): object
    {
        $data = json_decode($request->getContent(), true) ?? [];
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        return $dto;
    }

    private function validate(object $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $field = ltrim($violation->getPropertyPath(), '.');
            $errors[$field] = $violation->getMessage();
        }

        return $this->json(
            ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors],
            Response::HTTP_BAD_REQUEST
        );
    }
}

<?php

namespace App\Controller\Api;

use App\Dto\Admin\RejectMerchantDto;
use App\Entity\Merchant;
use App\Enum\MerchantStatusEnum;
use App\Repository\MerchantRepository;
use App\Service\MerchantOnboardingService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/admin')]
#[OA\Tag(name: 'Admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly MerchantOnboardingService $onboardingService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/merchants', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/admin/merchants',
        summary: 'List all merchants, optionally filtered by onboarding status',
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['PENDING', 'ACTIVE', 'REJECTED']),
                example: 'PENDING'
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of merchants'),
            new OA\Response(response: 400, description: 'Invalid status value'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — admin role required'),
        ]
    )]
    public function listMerchants(Request $request): JsonResponse
    {
        $statusParam = $request->query->get('status');

        if ($statusParam !== null) {
            $status = MerchantStatusEnum::tryFrom(strtoupper($statusParam));
            if ($status === null) {
                return $this->json(
                    ['success' => false, 'message' => 'Invalid status value.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $merchants = $this->merchantRepository->findByStatus($status);
        } else {
            $merchants = $this->merchantRepository->findAll();
        }

        $data = array_map(fn(Merchant $m) => $this->onboardingService->serialize($m), $merchants);

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/merchants/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/admin/merchants/{id}',
        summary: 'Get a single merchant by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Merchant details'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — admin role required'),
            new OA\Response(response: 404, description: 'Merchant not found'),
        ]
    )]
    public function getMerchant(string $id): JsonResponse
    {
        $merchant = $this->merchantRepository->find($id);

        if (!$merchant instanceof Merchant) {
            return $this->json(
                ['success' => false, 'message' => 'Merchant not found.'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json([
            'success' => true,
            'data'    => $this->onboardingService->serialize($merchant),
        ]);
    }

    #[Route('/merchants/{id}/approve', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/admin/merchants/{id}/approve',
        summary: 'Approve a pending merchant application',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Merchant approved'),
            new OA\Response(response: 400, description: 'Merchant is not in PENDING status'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — admin role required'),
            new OA\Response(response: 404, description: 'Merchant not found'),
        ]
    )]
    public function approveMerchant(string $id): JsonResponse
    {
        $merchant = $this->merchantRepository->find($id);

        if (!$merchant instanceof Merchant) {
            return $this->json(
                ['success' => false, 'message' => 'Merchant not found.'],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $this->onboardingService->approveMerchant($merchant);
        } catch (\DomainException $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json([
            'success' => true,
            'message' => 'Merchant approved.',
            'data'    => $this->onboardingService->serialize($merchant),
        ]);
    }

    #[Route('/merchants/{id}/reject', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/admin/merchants/{id}/reject',
        summary: 'Reject a pending merchant application',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Documents are incomplete.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Merchant rejected'),
            new OA\Response(response: 400, description: 'Validation error or merchant not in PENDING status'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — admin role required'),
            new OA\Response(response: 404, description: 'Merchant not found'),
        ]
    )]
    public function rejectMerchant(string $id, Request $request): JsonResponse
    {
        $merchant = $this->merchantRepository->find($id);

        if (!$merchant instanceof Merchant) {
            return $this->json(
                ['success' => false, 'message' => 'Merchant not found.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $dto = $this->hydrate(new RejectMerchantDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $this->onboardingService->rejectMerchant($merchant, $dto->reason);
        } catch (\DomainException $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['success' => true, 'message' => 'Merchant rejected.']);
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
            $errors[ltrim($violation->getPropertyPath(), '.')] = $violation->getMessage();
        }

        return $this->json(
            ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors],
            Response::HTTP_BAD_REQUEST
        );
    }
}

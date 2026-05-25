<?php

namespace App\Controller\Api;

use App\Dto\Reward\CreateRewardDto;
use App\Entity\Merchant;
use App\Service\RewardService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/merchant')]
#[IsGranted('ROLE_MERCHANT')]
#[OA\Tag(name: 'Merchant Rewards')]
class RewardController extends AbstractController
{
    public function __construct(
        private readonly RewardService $rewardService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/rewards', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/merchant/rewards',
        summary: 'List all rewards created by the authenticated merchant',
        responses: [
            new OA\Response(response: 200, description: 'List of rewards'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function list(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->getMerchantRewards($merchant),
        ]);
    }

    #[Route('/rewards', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/merchant/rewards',
        summary: 'Create a new reward',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'rewardType', 'value', 'expiresAt'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', minLength: 5, maxLength: 255, example: '10% Off'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, example: 'Get 10% off your next purchase'),
                    new OA\Property(property: 'rewardType', type: 'string', enum: ['DISCOUNT', 'FREE_ITEM', 'CASHBACK', 'EXPERIENCE'], example: 'DISCOUNT'),
                    new OA\Property(property: 'value', type: 'string', example: '10.00', description: 'Decimal string, e.g. percentage or cash amount'),
                    new OA\Property(property: 'stampRequirement', type: 'integer', minimum: 1, maximum: 100, example: 10),
                    new OA\Property(property: 'maxRedemptions', type: 'integer', nullable: true, example: 100),
                    new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', example: '2026-12-31T23:59:59Z'),
                    new OA\Property(property: 'terms', type: 'string', nullable: true, maxLength: 1000),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Reward created'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new CreateRewardDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->rewardService->createReward($merchant, $dto);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Reward created.', 'data' => $result], Response::HTTP_CREATED);
    }

    #[Route('/redemptions', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/merchant/redemptions',
        summary: 'List all redemptions for the authenticated merchant',
        responses: [
            new OA\Response(response: 200, description: 'List of redemptions'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function redemptions(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->getMerchantRedemptions($merchant),
        ]);
    }

    #[Route('/redemptions/{redeemCode}/approve', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/merchant/redemptions/{redeemCode}/approve',
        summary: 'Approve a pending reward redemption',
        parameters: [
            new OA\Parameter(name: 'redeemCode', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'RDM-ABC123'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Redemption approved'),
            new OA\Response(response: 400, description: 'Redemption cannot be approved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function approve(string $redeemCode): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        try {
            $result = $this->rewardService->approveRedemption($merchant, $redeemCode);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Redemption approved.', 'data' => $result]);
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

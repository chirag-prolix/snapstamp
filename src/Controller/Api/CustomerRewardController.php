<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\RewardService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
#[OA\Tag(name: 'Customer')]
class CustomerRewardController extends AbstractController
{
    public function __construct(private readonly RewardService $rewardService) {}

    #[Route('/api/v1/rewards', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/rewards',
        summary: 'List active rewards (optionally filtered by merchant or location)',
        parameters: [
            new OA\Parameter(name: 'merchantId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'lat', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float'), example: 12.9716),
            new OA\Parameter(name: 'lon', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float'), example: 77.5946),
            new OA\Parameter(name: 'radius', in: 'query', required: false, description: 'Search radius in km (default: 10)', schema: new OA\Schema(type: 'number', format: 'float'), example: 5),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of rewards with customer stamp count and distance'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $merchantId = $request->query->get('merchantId');
        $lat        = $request->query->has('lat') ? (float) $request->query->get('lat') : null;
        $lon        = $request->query->has('lon') ? (float) $request->query->get('lon') : null;
        $radius     = (float) $request->query->get('radius', 10);

        /** @var Customer $customer */
        $customer = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->listActiveRewards($merchantId, $lat, $lon, $radius, $customer),
        ]);
    }

    #[Route('/api/v1/rewards/{rewardId}/redeem', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/rewards/{rewardId}/redeem',
        summary: 'Redeem a reward (creates a pending redemption)',
        parameters: [
            new OA\Parameter(name: 'rewardId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Redemption created with redeemCode for merchant approval'),
            new OA\Response(response: 400, description: 'Insufficient stamps or reward unavailable'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function redeem(string $rewardId): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        try {
            $result = $this->rewardService->redeemReward($customer, $rewardId);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Redemption initiated.', 'data' => $result], Response::HTTP_CREATED);
    }

    #[Route('/api/v1/customer/redemptions', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/customer/redemptions',
        summary: 'List all redemptions for the authenticated customer',
        responses: [
            new OA\Response(response: 200, description: 'List of redemptions'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function redemptions(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->getCustomerRedemptions($customer),
        ]);
    }
}

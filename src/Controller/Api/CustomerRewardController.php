<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\RewardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
class CustomerRewardController extends AbstractController
{
    public function __construct(private readonly RewardService $rewardService) {}

    #[Route('/api/v1/rewards', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $merchantId = $request->query->get('merchantId');
        $lat        = $request->query->has('lat') ? (float) $request->query->get('lat') : null;
        $lon        = $request->query->has('lon') ? (float) $request->query->get('lon') : null;
        $radius     = (float) $request->query->get('radius', 10);

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->listActiveRewards($merchantId, $lat, $lon, $radius),
        ]);
    }

    #[Route('/api/v1/rewards/{rewardId}/redeem', methods: ['POST'])]
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

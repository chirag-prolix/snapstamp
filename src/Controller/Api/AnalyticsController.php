<?php

namespace App\Controller\Api;

use App\Entity\Merchant;
use App\Service\AnalyticsService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1')]
#[OA\Tag(name: 'Analytics')]
class AnalyticsController extends AbstractController
{
    public function __construct(private readonly AnalyticsService $analyticsService) {}

    #[Route('/merchant/stats', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
    #[OA\Get(
        path: '/api/v1/merchant/stats',
        summary: 'Get stamp and redemption stats for the authenticated merchant',
        responses: [
            new OA\Response(response: 200, description: 'totals (stampsIssued, rewardsRedeemed, customers), last30Days, topRewards'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function merchantStats(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->analyticsService->getMerchantStats($merchant),
        ]);
    }

    #[Route('/admin/stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/v1/admin/stats',
        summary: 'Get platform-wide stats (admin only)',
        responses: [
            new OA\Response(response: 200, description: 'totals and last30Days new user counts'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden — admin role required'),
        ]
    )]
    public function adminStats(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->analyticsService->getAdminStats(),
        ]);
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\Merchant;
use App\Service\AnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1')]
class AnalyticsController extends AbstractController
{
    public function __construct(private readonly AnalyticsService $analyticsService) {}

    #[Route('/merchant/stats', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
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
    public function adminStats(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->analyticsService->getAdminStats(),
        ]);
    }
}

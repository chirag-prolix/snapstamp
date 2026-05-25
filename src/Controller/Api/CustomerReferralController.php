<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\ReferralService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
#[OA\Tag(name: 'Customer')]
class CustomerReferralController extends AbstractController
{
    public function __construct(private readonly ReferralService $referralService) {}

    #[Route('/api/v1/customer/referral', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/customer/referral',
        summary: "Get the customer's referral code and referral history",
        responses: [
            new OA\Response(response: 200, description: 'Referral stats with code and list of referred users'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function referral(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->referralService->getReferralStats($customer),
        ]);
    }
}

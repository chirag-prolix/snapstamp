<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\ReferralService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
class CustomerReferralController extends AbstractController
{
    public function __construct(private readonly ReferralService $referralService) {}

    #[Route('/api/v1/customer/referral', methods: ['GET'])]
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

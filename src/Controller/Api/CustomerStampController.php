<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\StampService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/customer/stamp-cards')]
#[IsGranted('ROLE_CUSTOMER')]
class CustomerStampController extends AbstractController
{
    public function __construct(private readonly StampService $stampService) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->stampService->getCustomerCards($customer),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        try {
            $result = $this->stampService->getCustomerCardDetail($customer, $id);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['success' => true, 'message' => 'OK', 'data' => $result]);
    }
}

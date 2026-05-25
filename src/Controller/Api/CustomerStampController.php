<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\StampService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/customer/stamp-cards')]
#[IsGranted('ROLE_CUSTOMER')]
#[OA\Tag(name: 'Customer')]
class CustomerStampController extends AbstractController
{
    public function __construct(private readonly StampService $stampService) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/customer/stamp-cards',
        summary: 'List all stamp cards for the authenticated customer',
        responses: [
            new OA\Response(response: 200, description: 'List of stamp cards'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/v1/customer/stamp-cards/{id}',
        summary: 'Get a single stamp card with its individual stamps',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stamp card detail'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Card not found'),
        ]
    )]
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

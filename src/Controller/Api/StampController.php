<?php

namespace App\Controller\Api;

use App\Dto\Stamp\IssueStampDto;
use App\Entity\Merchant;
use App\Service\StampService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/stamps')]
#[IsGranted('ROLE_MERCHANT')]
class StampController extends AbstractController
{
    public function __construct(
        private readonly StampService $stampService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/issue', methods: ['POST'])]
    public function issue(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new IssueStampDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        if ($dto->customerId === null && $dto->customerPhone === null) {
            return $this->json(
                ['success' => false, 'message' => 'Provide customerId or customerPhone.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $result = $this->stampService->issueStamps($merchant, $dto);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Stamps issued.', 'data' => $result], Response::HTTP_CREATED);
    }

    #[Route('/card/{customerId}', methods: ['GET'])]
    public function customerCard(string $customerId): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        try {
            $result = $this->stampService->getCustomerCard($merchant, $customerId);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['success' => true, 'message' => 'OK', 'data' => $result]);
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

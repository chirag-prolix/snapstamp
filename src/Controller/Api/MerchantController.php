<?php

namespace App\Controller\Api;

use App\Dto\Merchant\UpdateMerchantProfileDto;
use App\Entity\Merchant;
use App\Service\MerchantOnboardingService;
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
#[OA\Tag(name: 'Merchant')]
class MerchantController extends AbstractController
{
    public function __construct(
        private readonly MerchantOnboardingService $onboardingService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/profile', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/merchant/profile',
        summary: 'Get the authenticated merchant profile',
        responses: [
            new OA\Response(response: 200, description: 'Merchant profile including apiKey'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function getProfile(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'data'    => $this->onboardingService->serialize($merchant, includeSecret: true),
        ]);
    }

    #[Route('/profile', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/v1/merchant/profile',
        summary: 'Update merchant profile fields',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'businessDescription', type: 'string', nullable: true),
                    new OA\Property(property: 'city', type: 'string', nullable: true),
                    new OA\Property(property: 'state', type: 'string', nullable: true),
                    new OA\Property(property: 'address', type: 'string', nullable: true),
                    new OA\Property(property: 'postalCode', type: 'string', nullable: true),
                    new OA\Property(property: 'phoneForBusiness', type: 'string', nullable: true),
                    new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true),
                    new OA\Property(property: 'webhookUrl', type: 'string', format: 'uri', nullable: true),
                    new OA\Property(property: 'verificationDocuments', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated merchant profile'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new UpdateMerchantProfileDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        $this->onboardingService->updateProfile($merchant, $dto);

        return $this->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data'    => $this->onboardingService->serialize($merchant, includeSecret: true),
        ]);
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
            $errors[ltrim($violation->getPropertyPath(), '.')] = $violation->getMessage();
        }

        return $this->json(
            ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors],
            Response::HTTP_BAD_REQUEST
        );
    }
}

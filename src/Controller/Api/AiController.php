<?php

namespace App\Controller\Api;

use App\Dto\Ai\AnalyticsInsightsRequestDto;
use App\Dto\Ai\BusinessDescriptionRequestDto;
use App\Dto\Ai\RewardRecommendationsRequestDto;
use App\Dto\Ai\RewardSuggestionRequestDto;
use App\Service\ClaudeAiService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/ai')]
#[OA\Tag(name: 'AI')]
class AiController extends AbstractController
{
    public function __construct(
        private readonly ClaudeAiService $aiService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/reward-suggestion', methods: ['POST'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function suggestReward(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new RewardSuggestionRequestDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $suggestion = $this->aiService->suggestReward(
                $dto->businessName,
                $dto->businessType,
                $dto->businessDescription,
            );
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $this->json(['success' => true, 'data' => $suggestion]);
    }

    #[Route('/business-description', methods: ['POST'])]
    public function generateBusinessDescription(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new BusinessDescriptionRequestDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $description = $this->aiService->generateBusinessDescription(
                $dto->businessName,
                $dto->businessType,
                $dto->city,
            );
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $this->json(['success' => true, 'data' => ['description' => $description]]);
    }

    #[Route('/analytics-insights', methods: ['POST'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function getAnalyticsInsights(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new AnalyticsInsightsRequestDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $insights = $this->aiService->getAnalyticsInsights($dto->stats);
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $this->json(['success' => true, 'data' => ['insights' => $insights]]);
    }

    #[Route('/reward-recommendations', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function getRewardRecommendations(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new RewardRecommendationsRequestDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $recommendations = $this->aiService->getRewardRecommendations(
                $dto->stampCards,
                $dto->availableRewards,
                $dto->tier,
            );
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $this->json(['success' => true, 'data' => $recommendations]);
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

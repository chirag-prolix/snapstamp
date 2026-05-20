<?php

namespace App\Controller\Api;

use App\Dto\Reward\CreateRewardDto;
use App\Entity\Merchant;
use App\Service\RewardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/merchant')]
#[IsGranted('ROLE_MERCHANT')]
class RewardController extends AbstractController
{
    public function __construct(
        private readonly RewardService $rewardService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/rewards', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->getMerchantRewards($merchant),
        ]);
    }

    #[Route('/rewards', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        $dto = $this->hydrate(new CreateRewardDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->rewardService->createReward($merchant, $dto);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Reward created.', 'data' => $result], Response::HTTP_CREATED);
    }

    #[Route('/redemptions', methods: ['GET'])]
    public function redemptions(): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->rewardService->getMerchantRedemptions($merchant),
        ]);
    }

    #[Route('/redemptions/{redeemCode}/approve', methods: ['POST'])]
    public function approve(string $redeemCode): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $this->getUser();

        try {
            $result = $this->rewardService->approveRedemption($merchant, $redeemCode);
        } catch (\DomainException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true, 'message' => 'Redemption approved.', 'data' => $result]);
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

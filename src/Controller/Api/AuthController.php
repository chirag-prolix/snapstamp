<?php

namespace App\Controller\Api;

use App\Dto\Auth\LoginDto;
use App\Dto\Auth\RefreshTokenDto;
use App\Dto\Auth\RegisterCustomerDto;
use App\Dto\Auth\RegisterMerchantDto;
use App\Entity\User;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/register/customer', methods: ['POST'])]
    public function registerCustomer(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new RegisterCustomerDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->authService->registerCustomer($dto);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->tokens($result, Response::HTTP_CREATED);
    }

    #[Route('/register/merchant', methods: ['POST'])]
    public function registerMerchant(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new RegisterMerchantDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->authService->registerMerchant($dto);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->json($result, Response::HTTP_CREATED);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new LoginDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->authService->login($dto);
        } catch (\Symfony\Component\Security\Core\Exception\UserNotFoundException) {
            return $this->fail('Invalid credentials.', Response::HTTP_UNAUTHORIZED);
        } catch (\Symfony\Component\Security\Core\Exception\BadCredentialsException) {
            return $this->fail('Invalid credentials.', Response::HTTP_UNAUTHORIZED);
        } catch (\Symfony\Component\Security\Core\Exception\AuthenticationException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokens($result);
    }

    #[Route('/refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new RefreshTokenDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->authService->refreshToken($dto->refreshToken);
        } catch (\Symfony\Component\Security\Core\Exception\AuthenticationException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokens($result);
    }

    #[Route('/logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(Request $request): JsonResponse
    {
        $rawToken = substr($request->headers->get('Authorization', ''), 7);
        $this->authService->logout($rawToken);

        return $this->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    #[Route('/me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'success' => true,
            'message' => 'Profile retrieved.',
            'data'    => $this->authService->serializeUser($user),
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
            $field = ltrim($violation->getPropertyPath(), '.');
            $errors[$field] = $violation->getMessage();
        }

        return $this->json(
            ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors],
            Response::HTTP_BAD_REQUEST
        );
    }

    private function tokens(array $result, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'accessToken'  => $result['accessToken'],
                'refreshToken' => $result['refreshToken'],
                'user'         => $result['user'],
            ],
        ], $status);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }
}

<?php

namespace App\Controller\Api;

use App\Dto\Auth\GoogleLoginDto;
use App\Dto\Auth\LoginDto;
use App\Dto\Auth\SetPhoneDto;
use App\Dto\Auth\LoginPhoneRequestDto;
use App\Dto\Auth\LoginPhoneVerifyDto;
use App\Dto\Auth\RefreshTokenDto;
use App\Dto\Auth\RegisterCustomerDto;
use App\Dto\Auth\RegisterMerchantDto;
use App\Entity\User;
use App\Service\AuthService;
use App\Service\SocialAuthService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth')]
#[OA\Tag(name: 'Auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly SocialAuthService $socialAuthService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/register/customer', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/auth/register/customer',
        summary: 'Register a new customer',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstName', 'lastName', 'phone'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'secret123'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jane'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'phone', type: 'string', example: '+919876543210'),
                    new OA\Property(property: 'referralCode', type: 'string', nullable: true, example: 'ABC123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Customer registered, tokens returned'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'Email already registered'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/v1/auth/register/merchant',
        summary: 'Register a new merchant',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstName', 'lastName', 'phone', 'businessName', 'city', 'state', 'address', 'phoneForBusiness', 'taxId', 'bankAccountNumber', 'bankIfscCode', 'bankAccountHolderName', 'termsAccepted'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'firstName', type: 'string'),
                    new OA\Property(property: 'lastName', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string', example: '+919876543210'),
                    new OA\Property(property: 'businessName', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'state', type: 'string'),
                    new OA\Property(property: 'address', type: 'string'),
                    new OA\Property(property: 'phoneForBusiness', type: 'string', example: '+919876543210'),
                    new OA\Property(property: 'taxId', type: 'string'),
                    new OA\Property(property: 'bankAccountNumber', type: 'string'),
                    new OA\Property(property: 'bankIfscCode', type: 'string', example: 'HDFC0001234'),
                    new OA\Property(property: 'bankAccountHolderName', type: 'string'),
                    new OA\Property(property: 'termsAccepted', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Merchant registered (pending approval)'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 409, description: 'Email already registered'),
        ]
    )]
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

    #[Route('/phone', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function setPhone(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new SetPhoneDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->authService->setPhoneAndSendOtp($user, $dto->phone);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->json(['success' => true, 'message' => 'OTP sent to your phone number.']);
    }

    #[Route('/google', methods: ['POST'])]
    public function googleLogin(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new GoogleLoginDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->socialAuthService->loginWithGoogle($dto->idToken);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokens($result);
    }

    #[Route('/login/phone', methods: ['POST'])]
    public function loginPhoneRequest(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new LoginPhoneRequestDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $this->authService->requestPhoneLoginOtp($dto->phone);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Symfony\Component\Security\Core\Exception\AuthenticationException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['success' => true, 'message' => 'OTP sent to your phone number.']);
    }

    #[Route('/login/phone/verify', methods: ['POST'])]
    public function loginPhoneVerify(Request $request): JsonResponse
    {
        $dto = $this->hydrate(new LoginPhoneVerifyDto(), $request);

        if ($error = $this->validate($dto)) {
            return $error;
        }

        try {
            $result = $this->authService->loginWithPhoneOtp($dto->phone, $dto->code);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Symfony\Component\Security\Core\Exception\AuthenticationException $e) {
            return $this->fail($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        return $this->tokens($result);
    }

    #[Route('/login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login and receive JWT tokens',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful, tokens returned'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Refresh access token using refresh token',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'New access and refresh tokens'),
            new OA\Response(response: 401, description: 'Invalid or expired refresh token'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Invalidate the current access token',
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $rawToken = substr($request->headers->get('Authorization', ''), 7);
        $this->authService->logout($rawToken);

        return $this->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    #[Route('/me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get the authenticated user profile',
        responses: [
            new OA\Response(response: 200, description: 'User profile'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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

    /**
     * @param array{accessToken: string, refreshToken: string, user: array<string, mixed>} $result
     */
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

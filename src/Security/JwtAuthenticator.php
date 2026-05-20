<?php

namespace App\Security;

use App\Service\JwtService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly UserProvider $userProvider,
    ) {}

    public function supports(Request $request): ?bool
    {
        $header = $request->headers->get('Authorization', '');
        return str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get('Authorization', ''), 7);

        try {
            $payload = $this->jwtService->decode($token);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid or expired token.');
        }

        if (($payload['type'] ?? '') !== 'access') {
            throw new AuthenticationException('Invalid token type.');
        }

        $jti = $payload['jti'] ?? '';
        if ($jti && $this->jwtService->isBlacklisted($jti)) {
            throw new AuthenticationException('Token has been revoked.');
        }

        $userId = $payload['sub'] ?? '';

        return new SelfValidatingPassport(
            new UserBadge($userId, fn(string $id) => $this->userProvider->loadUserByIdentifier($id))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['success' => false, 'message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }
}

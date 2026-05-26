<?php

namespace App\EventSubscriber;

use App\Entity\Merchant;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MerchantAccessSubscriber implements EventSubscriberInterface
{
    private const EXEMPT_PREFIXES = [
        '/api/v1/auth',
        '/api/v1/merchant/payment',
        '/api/v1/merchant/payments',
        '/api/v1/payment',
        '/api/v1/merchant/profile',
    ];

    public function __construct(private readonly TokenStorageInterface $tokenStorage) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 5]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $token = $this->tokenStorage->getToken();
        $user  = $token?->getUser();

        if (!$user instanceof Merchant) {
            return;
        }

        if (!$user->hasActiveAccess()) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'message' => 'Trial expired. Please subscribe to continue.',
                'code'    => 'SUBSCRIPTION_REQUIRED',
            ], Response::HTTP_PAYMENT_REQUIRED));
        }
    }
}

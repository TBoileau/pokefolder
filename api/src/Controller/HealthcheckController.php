<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthcheckController
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(MESSENGER_TRANSPORT_DSN)%')]
        private readonly string $messengerTransportDsn,
    ) {
    }

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $db = $this->checkDatabase() ? 'ok' : 'fail';
        $amqp = $this->checkAmqp() ? 'ok' : 'fail';

        $status = ($db === 'ok' && $amqp === 'ok')
            ? Response::HTTP_OK
            : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse(['db' => $db, 'amqp' => $amqp], $status);
    }

    private function checkDatabase(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkAmqp(): bool
    {
        if (str_starts_with($this->messengerTransportDsn, 'in-memory://')
            || str_starts_with($this->messengerTransportDsn, 'sync://')
        ) {
            return true;
        }

        $parsed = parse_url($this->messengerTransportDsn);
        if (!isset($parsed['host'], $parsed['port'])) {
            return false;
        }

        $sock = @fsockopen($parsed['host'], (int) $parsed['port'], $errno, $errstr, 1.0);
        if ($sock === false) {
            return false;
        }
        fclose($sock);

        return true;
    }
}

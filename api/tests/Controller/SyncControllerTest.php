<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\UseCase\Catalog\SyncAll\Input as SyncAllInput;
use App\UseCase\Catalog\SyncCards\Input as SyncCardsInput;
use App\UseCase\Catalog\SyncSets\Input as SyncSetsInput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class SyncControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testPostSyncAllDispatchesSyncAllInput(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/sync');

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertJsonContains(['scope' => 'all', 'force' => false, 'status' => 'dispatched']);

        $sent = $this->asyncTransport()->getSent();
        self::assertCount(1, $sent);
        $message = $sent[0]->getMessage();
        self::assertInstanceOf(SyncAllInput::class, $message);
        self::assertFalse($message->force);
    }

    public function testPostSyncAllWithForceFlagPropagatesIt(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/sync?force=true');

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertJsonContains(['scope' => 'all', 'force' => true]);

        $message = $this->asyncTransport()->getSent()[0]->getMessage();
        self::assertInstanceOf(SyncAllInput::class, $message);
        self::assertTrue($message->force);
    }

    public function testPostSyncSerieDispatchesSyncSetsPerLanguage(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/sync/series/base');

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertJsonContains(['scope' => 'serie', 'serieId' => 'base']);

        $sent = $this->asyncTransport()->getSent();
        self::assertCount(2, $sent);
        foreach ($sent as $envelope) {
            $message = $envelope->getMessage();
            self::assertInstanceOf(SyncSetsInput::class, $message);
            self::assertSame('base', $message->serieId);
        }
    }

    public function testPostSyncSetDispatchesSyncCardsPerLanguage(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/sync/sets/base1');

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertJsonContains(['scope' => 'set', 'setId' => 'base1']);

        $sent = $this->asyncTransport()->getSent();
        self::assertCount(2, $sent);
        foreach ($sent as $envelope) {
            $message = $envelope->getMessage();
            self::assertInstanceOf(SyncCardsInput::class, $message);
            self::assertSame('base1', $message->setId);
        }
    }

    private function asyncTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');

        return $transport;
    }
}

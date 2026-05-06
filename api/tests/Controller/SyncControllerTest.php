<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\UseCase\Catalog\SyncAll\Input as SyncAllInput;
use App\UseCase\Catalog\SyncSet\Input as SyncSetInput;
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
        self::assertJsonContains(['scope' => 'all', 'status' => 'dispatched']);

        $sent = $this->asyncTransport()->getSent();
        self::assertCount(1, $sent);
        self::assertInstanceOf(SyncAllInput::class, $sent[0]->getMessage());
    }

    public function testPostSyncSetDispatchesSyncSetInputWithTheSetId(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/sync/base1');

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertJsonContains(['scope' => 'set', 'setId' => 'base1', 'status' => 'dispatched']);

        $sent = $this->asyncTransport()->getSent();
        self::assertCount(1, $sent);
        $message = $sent[0]->getMessage();
        self::assertInstanceOf(SyncSetInput::class, $message);
        self::assertSame('base1', $message->setId);
    }

    private function asyncTransport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');

        return $transport;
    }
}

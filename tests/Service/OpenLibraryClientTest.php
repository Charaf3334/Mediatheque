<?php

namespace App\Tests\Service;

use App\Service\OpenLibraryClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

class OpenLibraryClientTest extends TestCase
{
    public function testMapsCompleteAndIncompleteRecords(): void
    {
        $client = new OpenLibraryClient(new MockHttpClient(new JsonMockResponse([
            'ISBN:9780140328721' => [
                'title' => 'Fantastic Mr Fox',
                'authors' => [['name' => 'Roald Dahl'], ['name' => 'Quentin Blake']],
                'publishers' => [['name' => 'Puffin']],
                'cover' => ['medium' => 'https://covers.openlibrary.org/b/id/1-M.jpg'],
            ],
            'ISBN:0000000000' => ['title' => 'Book without author'],
        ])));

        $r = $client->fetch(['9780140328721', '0000000000', '1111111111']);

        $this->assertSame('Roald Dahl, Quentin Blake', $r['9780140328721']['authors']);
        $this->assertSame('Puffin', $r['9780140328721']['publisher']);
        $this->assertNull($r['0000000000']['authors']);
        $this->assertNull($r['0000000000']['coverUrl']);
        $this->assertArrayNotHasKey('1111111111', $r); // unknown ISBN: simply absent
    }

    public function testBatchesRequestsBy50(): void
    {
        $calls = 0;
        $client = new OpenLibraryClient(new MockHttpClient(function () use (&$calls) {
            $calls++;

            return new JsonMockResponse([]);
        }));

        $client->fetch(array_map(fn ($i) => (string) (9780000000000 + $i), range(1, 120)));

        $this->assertSame(3, $calls); // 120 ISBNs = 50 + 50 + 20
    }
}

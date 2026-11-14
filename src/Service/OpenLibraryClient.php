<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenLibraryClient
{
    private const URL = 'https://openlibrary.org/api/books';
    private const USER_AGENT = 'MediathequeChallenge/1.0 (github.com/Charaf3334/Library)';

    public function __construct(private HttpClientInterface $http) {}

    /**
     * One request per 50 ISBNs. Returns only the ISBNs Open Library knows, with null for missing fields.
     *
     * @return array<string, array{title: ?string, authors: ?string, publisher: ?string, coverUrl: ?string}>
     */
    public function fetch(array $isbns): array
    {
        $found = [];
        foreach (array_chunk(array_values(array_unique(array_map('strval', $isbns))), 50) as $chunk) {
            $data = $this->http->request('GET', self::URL, [
                'query' => [
                    'bibkeys' => implode(',', array_map(fn (string $i) => "ISBN:$i", $chunk)),
                    'format' => 'json',
                    'jscmd' => 'data',
                ],
                'headers' => ['User-Agent' => self::USER_AGENT],
                'timeout' => 10,
            ])->toArray();

            foreach ($data as $key => $d) {
                $authors = implode(', ', array_filter(array_column($d['authors'] ?? [], 'name')));
                $found[substr((string) $key, 5)] = [
                    'title' => $d['title'] ?? null,
                    'authors' => $authors ?: null,
                    'publisher' => $d['publishers'][0]['name'] ?? null,
                    'coverUrl' => $d['cover']['medium'] ?? null,
                ];
            }
        }

        return $found;
    }
}

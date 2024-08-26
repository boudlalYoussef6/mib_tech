<?php

namespace App\ServiceApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookService implements BookServiceInterface
{
    private HttpClientInterface $client;
    private string $baseUrl;

    public function __construct(HttpClientInterface $client, string $baseUrl = 'http://localhost:80')
    {
        $this->client = $client;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function createBook(array $data): bool
    {
        try {
            $response = $this->client->request('POST', $this->baseUrl.'/api/books', [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
            ]);

            return 201 === $response->getStatusCode();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAllBooks(): array
    {
        $response = $this->client->request('GET', $this->baseUrl.'/api/books', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $data = $response->toArray();
        $books = $data['hydra:member'] ?? [];

        foreach ($books as &$book) {
            $book = $this->transformBook($book);
        }

        return $books;
    }

    private function transformBook(array $book): array
    {
        $book['author'] = $this->getResourceDetails($book['author']);
        $book['categories'] = array_map([$this, 'getResourceDetails'], $book['categories']);

        return $book;
    }

    private function getResourceDetails(string $iri): array
    {
        $url = 0 === strpos($iri, 'http') ? $iri : $this->baseUrl.$iri;

        $response = $this->client->request('GET', $url, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return $response->toArray();
    }

    public function updateBook(int $id, array $data): bool
    {
        try {
            $response = $this->client->request('PUT', $this->baseUrl.'/api/books/'.$id, [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
            ]);

            return 200 === $response->getStatusCode();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteBook(int $id): bool
    {
        try {
            $response = $this->client->request('DELETE', $this->baseUrl.'/api/books/'.$id);

            return 204 === $response->getStatusCode();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getBookById(int $id): array
    {
        $response = $this->client->request('GET', $this->baseUrl.'/api/books/'.$id, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return $response->toArray();
    }

    public function filterBooksByPublicationDate(?\DateTimeInterface $publicationDate): array
    {
        $books = [];

        if ($publicationDate) {
            $formattedDate = $publicationDate->format('Y-m-d');
            $response = $this->client->request('GET', 'http://localhost:80/api/books', [
                'query' => [
                    'publicationDate' => $formattedDate,
                ],
            ]);

            $bookData = $response->toArray();
            if (isset($bookData['hydra:member'])) {
                $books = $bookData['hydra:member'];
            } else {
                throw new \Exception('Unexpected API response format.');
            }
        }

        return $books;
    }
}

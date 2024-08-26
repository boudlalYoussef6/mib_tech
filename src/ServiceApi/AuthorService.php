<?php

namespace App\ServiceApi;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthorService implements AuthorServiceInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function createAuthor(array $data): int
    {
        $response = $this->httpClient->request('POST', 'http://localhost:80/api/authors', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
            'json' => $data,
        ]);

        return $response->getStatusCode();
    }

    public function getAuthors(): array
    {
        $response = $this->httpClient->request('GET', 'http://localhost:80/api/authors');

        if (200 === $response->getStatusCode()) {
            return $response->toArray()['hydra:member'];
        }

        return [];
    }

    public function deleteAuthor(int $id): bool
    {
        $response = $this->httpClient->request('DELETE', sprintf('http://localhost:80/api/authors/%d', $id));

        return Response::HTTP_NO_CONTENT === $response->getStatusCode();
    }

    public function updateAuthor(int $id, array $data): bool
    {
        $response = $this->httpClient->request('PUT', sprintf('http://localhost:80/api/authors/%d', $id), [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
            'json' => $data,
        ]);

        return Response::HTTP_OK === $response->getStatusCode();
    }
    // src/Service/AuthorService.php

    public function filterAuthorsByName(string $name): array
    {
        $response = $this->httpClient->request('GET', 'http://localhost:80/api/authors', [
            'query' => ['name' => $name],
        ]);

        if (Response::HTTP_OK === $response->getStatusCode()) {
            return $response->toArray()['hydra:member'];
        }

        return [];
    }
}

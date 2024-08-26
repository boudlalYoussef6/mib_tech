<?php

namespace App\ServiceApi;

use App\Entity\Category;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CategoryService implements CategoryServiceInterface
{
    private HttpClientInterface $client;
    private string $baseUrl;

    public function __construct(HttpClientInterface $client, string $baseUrl = 'http://localhost:80')
    {
        $this->client = $client;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function createCategory(Category $category): bool
    {
        $data = [
            'name' => $category->getName(),
            'books' => array_map(fn ($book) => '/api/books/'.$book->getId(), $category->getBooks()->toArray()),
        ];

        $response = $this->client->request('POST', $this->baseUrl.'/api/categories', [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return 201 === $response->getStatusCode();
    }

    public function getBookDetails(string $bookIri): array
    {
        // Prepend the base URL if the IRI is relative
        $url = 0 === strpos($bookIri, 'http') ? $bookIri : $this->baseUrl.$bookIri;

        $response = $this->client->request('GET', $url, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return $response->toArray();
    }

    public function getCategories(): array
    {
        $response = $this->client->request('GET', $this->baseUrl.'/api/categories', [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $data = $response->toArray();
        $categories = $data['hydra:member'] ?? [];

        foreach ($categories as &$category) {
            foreach ($category['books'] as &$bookIri) {
                $bookDetails = $this->getBookDetails($bookIri);
                $bookIri = $bookDetails;  // Replace IRI with book details
            }
        }

        return $categories;
    }

    public function deleteCategory(int $categoryId): bool
    {
        $response = $this->client->request('DELETE', $this->baseUrl.'/api/categories/'.$categoryId, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return 204 === $response->getStatusCode();
    }

    public function getCategoryById(int $id): array
    {
        $response = $this->client->request('GET', $this->baseUrl.'/api/categories/'.$id, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return $response->toArray();
    }

    public function updateCategory(int $id, Category $category): bool
    {
        $data = [
            'name' => $category->getName(),
            'books' => array_map(fn ($book) => '/api/books/'.$book->getId(), $category->getBooks()->toArray()),
        ];

        $response = $this->client->request('PUT', $this->baseUrl.'/api/categories/'.$id, [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        return 200 === $response->getStatusCode();
    }
}

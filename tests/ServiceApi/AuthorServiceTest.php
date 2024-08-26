<?php

namespace App\Tests\ServiceApi;

use App\ServiceApi\AuthorService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AuthorServiceTest extends TestCase
{
    private $httpClient;
    private $authorService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->authorService = new AuthorService($this->httpClient);
    }

    public function testCreateAuthor()
    {
        $authorResponse = $this->createMock(ResponseInterface::class);
        $authorResponse->method('getStatusCode')->willReturn(201);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'http://localhost:80/api/authors', $this->callback(function ($options) {
                return isset($options['json']) && $options['json'] === [
                    'name' => 'Test Author',
                    'birthDate' => '1990-01-01',
                    'biography' => 'A brief biography of the author.',
                ];
            }))
            ->willReturn($authorResponse);

        $authorData = [
            'name' => 'Test Author',
            'birthDate' => '1990-01-01',
            'biography' => 'A brief biography of the author.',
        ];

        $authorStatusCode = $this->createAuthor($authorData);
        $this->assertSame(201, $authorStatusCode, 'Failed to create author');
    }

    private function createAuthor(array $data): int
    {
        $response = $this->httpClient->request('POST', 'http://localhost:80/api/authors', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $data,
        ]);

        return $response->getStatusCode();
    }

    public function testUpdateAuthor()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'http://localhost:80/api/authors/1',
                [
                    'json' => ['name' => 'Updated Author Name'],
                    'headers' => ['Content-Type' => 'application/ld+json'],
                ]
            )
            ->willReturn($mockResponse);

        $result = $this->authorService->updateAuthor(1, ['name' => 'Updated Author Name']);
        $this->assertTrue($result);
    }

    public function testDeleteAuthor()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(204);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', 'http://localhost:80/api/authors/1')
            ->willReturn($mockResponse);

        $deleted = $this->authorService->deleteAuthor(1);
        $this->assertTrue($deleted, 'La suppression de l\'auteur a échoué');
    }

    public function testFilterAuthorsByName()
    {
        $expectedAuthors = [
            ['id' => 1, 'name' => 'Author 1'],
            ['id' => 2, 'name' => 'Author 2'],
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn(['hydra:member' => $expectedAuthors]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://localhost:80/api/authors', [
                'query' => ['name' => 'Author 1'],
            ])
            ->willReturn($mockResponse);

        $result = $this->authorService->filterAuthorsByName('Author 1');
        $this->assertSame($expectedAuthors, $result);
    }
}

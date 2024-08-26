<?php

namespace App\Tests\ServiceApi;

use App\ServiceApi\BookService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BookServiceTest extends TestCase
{
    private $httpClient;
    private $bookService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->bookService = new BookService($this->httpClient, 'http://localhost:80');
    }

    public function testCreateBook()
    {
        $bookResponse = $this->createMock(ResponseInterface::class);
        $bookResponse->method('getStatusCode')->willReturn(201);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'http://localhost:80/api/books', $this->callback(function ($options) {
                return isset($options['json']) && $options['json'] === [
                    'title' => 'Test Book',
                    'description' => 'A book description',
                    'publicationDate' => '2024-01-01',
                    'author' => '/api/authors/1',
                ];
            }))
            ->willReturn($bookResponse);

        $bookData = [
            'title' => 'Test Book',
            'description' => 'A book description',
            'publicationDate' => '2024-01-01',
            'author' => '/api/authors/1',
        ];

        $bookCreated = $this->createBook($bookData);
        $this->assertTrue($bookCreated, 'Failed to create book');
    }

    private function createBook(array $data): bool
    {
        $response = $this->httpClient->request('POST', 'http://localhost:80/api/books', [
            'json' => $data,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return 201 === $response->getStatusCode();
    }

    public function testUpdateBook()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'http://localhost:80/api/books/1',
                [
                    'json' => ['title' => 'Updated Book Title'],
                    'headers' => ['Content-Type' => 'application/ld+json'],
                ]
            )
            ->willReturn($mockResponse);

        $result = $this->bookService->updateBook(1, ['title' => 'Updated Book Title']);
        $this->assertTrue($result);
    }

    public function testDeleteBook()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(204);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', 'http://localhost:80/api/books/1')
            ->willReturn($mockResponse);

        $deleted = $this->bookService->deleteBook(1);
        $this->assertTrue($deleted, 'La suppression du livre a échoué');
    }

    public function testGetBookById()
    {
        $expectedData = [
            'id' => 1,
            'title' => 'Test Book',
            'description' => 'A book description',
            'publicationDate' => '2024-01-01',
            'author' => '/api/authors/1',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn($expectedData);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://localhost:80/api/books/1',
                ['headers' => ['Content-Type' => 'application/ld+json']]
            )
            ->willReturn($mockResponse);

        $result = $this->bookService->getBookById(1);
        $this->assertSame($expectedData, $result);
    }

    public function testFilterBooksByPublicationDate()
    {
        $expectedBooks = [
            ['id' => 1, 'title' => 'Book 1', 'publicationDate' => '2024-01-01'],
            ['id' => 2, 'title' => 'Book 2', 'publicationDate' => '2024-01-01'],
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn(['hydra:member' => $expectedBooks]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://localhost:80/api/books', [
                'query' => ['publicationDate' => '2024-01-01'],
            ])
            ->willReturn($mockResponse);

        $publicationDate = new \DateTime('2024-01-01');
        $result = $this->bookService->filterBooksByPublicationDate($publicationDate);
        $this->assertSame($expectedBooks, $result);
    }
}

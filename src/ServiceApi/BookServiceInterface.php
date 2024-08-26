<?php

namespace App\ServiceApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

interface BookServiceInterface
{
    public function __construct(HttpClientInterface $client, string $baseUrl = 'http://localhost:80');

    public function createBook(array $data): bool;

    public function getAllBooks(): array;

    public function updateBook(int $id, array $data): bool;

    public function deleteBook(int $id): bool;

    public function getBookById(int $id): array;

    public function filterBooksByPublicationDate(?\DateTimeInterface $publicationDate): array;
}

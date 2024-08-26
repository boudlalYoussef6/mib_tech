<?php

namespace App\ServiceApi;

interface AuthorServiceInterface
{
    public function createAuthor(array $data): int;

    public function getAuthors(): array;

    public function deleteAuthor(int $id): bool;

    public function updateAuthor(int $id, array $data): bool;

    public function filterAuthorsByName(string $name): array;
}

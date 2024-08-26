<?php

namespace App\ServiceApi;

use App\Entity\Category;

interface CategoryServiceInterface
{
    public function createCategory(Category $category): bool;

    public function getBookDetails(string $bookIri): array;

    public function getCategories(): array;

    public function deleteCategory(int $categoryId): bool;

    public function getCategoryById(int $id): array;

    public function updateCategory(int $id, Category $category): bool;
}

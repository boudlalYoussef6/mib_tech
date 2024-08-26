<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Category;
use App\Form\CategoryType;
use App\ServiceApi\CategoryService;
use App\ServiceApi\CategoryServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    private CategoryService $categoryService;

    public function __construct(CategoryServiceInterface $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    #[Route('/category', name: 'app_category')]
    public function index(): Response
    {
        $categories = $this->categoryService->getCategories();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/new', name: 'category_new')]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $success = $this->categoryService->createCategory($category);

            if ($success) {
                return $this->redirectToRoute('category_new');
            } else {
                $this->addFlash('error', 'An error occurred while creating the category.');
            }
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/{id}/delete', name: 'category_delete')]
    public function delete(int $id): Response
    {
        $success = $this->categoryService->deleteCategory($id);

        if ($success) {
            $this->addFlash('success', 'Category deleted successfully.');
        } else {
            $this->addFlash('error', 'An error occurred while deleting the category.');
        }

        return $this->redirectToRoute('app_category');
    }

    #[Route('/category/{id}/edit', name: 'category_edit')]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        // Fetch the category data
        $categoryData = $this->categoryService->getCategoryById($id);

        if (!is_array($categoryData)) {
            throw new \Exception('Category data is not an array.');
        }

        $category = new Category();
        $category->setName($categoryData['name']);

        if (isset($categoryData['books']) && is_array($categoryData['books'])) {
            foreach ($categoryData['books'] as $bookData) {
                if (isset($bookData['id'])) {
                    $book = $entityManager->getRepository(Book::class)->find($bookData['id']);
                    if ($book) {
                        $category->addBook($book);
                    }
                }
            }
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $success = $this->categoryService->updateCategory($id, $category);

            if ($success) {
                return $this->redirectToRoute('app_category');
            } else {
                $this->addFlash('error', 'An error occurred while updating the category.');
            }
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

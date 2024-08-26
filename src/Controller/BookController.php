<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookFilterType;
use App\Form\BookType;
use App\ServiceApi\BookService;
use App\ServiceApi\BookServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookController extends AbstractController
{
    private BookService $bookService;
    private EntityManagerInterface $entityManager;

    public function __construct(BookServiceInterface $bookService, EntityManagerInterface $entityManager)
    {
        $this->bookService = $bookService;
        $this->entityManager = $entityManager;
    }

    #[Route('/book/new', name: 'book_new')]
    public function new(Request $request): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'title' => $book->getTitle(),
                'description' => $book->getDescription(),
                'publicationDate' => $book->getPublicationDate()->format('Y-m-d'),
                'author' => '/api/authors/'.$book->getAuthor()->getId(),
                'categories' => array_map(fn ($category) => '/api/categories/'.$category->getId(), $book->getCategories()->toArray()),
            ];

            $response = $this->bookService->createBook($data);

            if ($response) {
                return $this->redirectToRoute('book_list');
            } else {
                $this->addFlash('error', 'An error occurred while creating the book.');
            }
        }

        return $this->render('book/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/books', name: 'book_list')]
    public function list(): Response
    {
        $books = $this->bookService->getAllBooks();

        return $this->render('book/list.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/book/{id}/edit', name: 'book_edit')]
    public function edit(Request $request, int $id): Response
    {
        // Fetch the book data
        $bookData = $this->bookService->getBookById($id);

        if (!is_array($bookData)) {
            throw new \Exception('Book data is not an array.');
        }

        $book = new Book();
        $book->setTitle($bookData['title']);
        $book->setDescription($bookData['description']);
        $book->setPublicationDate(new \DateTime($bookData['publicationDate']));

        if (isset($bookData['author']) && isset($bookData['author']['id'])) {
            $author = $this->entityManager->getRepository(Author::class)->find($bookData['author']['id']);
            if ($author) {
                $book->setAuthor($author);
            }
        }

        if (isset($bookData['categories']) && is_array($bookData['categories'])) {
            foreach ($bookData['categories'] as $categoryData) {
                if (isset($categoryData['id'])) {
                    $category = $this->entityManager->getRepository(Category::class)->find($categoryData['id']);
                    if ($category) {
                        $book->addCategory($category);
                    }
                }
            }
        }

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'title' => $book->getTitle(),
                'description' => $book->getDescription(),
                'publicationDate' => $book->getPublicationDate()->format('Y-m-d'),
                'author' => '/api/authors/'.$book->getAuthor()->getId(),
                'categories' => array_map(fn ($category) => '/api/categories/'.$category->getId(), $book->getCategories()->toArray()),
            ];

            $success = $this->bookService->updateBook($id, $data);

            if ($success) {
                return $this->redirectToRoute('book_list'); // Redirect to book list or any other route
            } else {
                $this->addFlash('error', 'An error occurred while updating the book.');
            }
        }

        return $this->render('book/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/book/{id}/delete', name: 'book_delete')]
    public function delete(int $id): Response
    {
        $success = $this->bookService->deleteBook($id);

        if ($success) {
            $this->addFlash('success', 'Book deleted successfully.');
        } else {
            $this->addFlash('error', 'An error occurred while deleting the book.');
        }

        return $this->redirectToRoute('book_list');
    }

    #[Route('/books/filter', name: 'book_filter')]
    public function filterBooks(Request $request): Response
    {
        $form = $this->createForm(BookFilterType::class);
        $form->handleRequest($request);

        $books = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $publicationDate = $data['publicationDate'];

            $books = $this->bookService->filterBooksByPublicationDate($publicationDate);
        }

        return $this->render('book/filter.html.twig', [
            'form' => $form->createView(),
            'books' => $books,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Author;
use App\Form\AuthorFilterType;
use App\Form\AuthorType;
use App\ServiceApi\AuthorService;
use App\ServiceApi\AuthorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends AbstractController
{
    private AuthorService $authorService;

    public function __construct(AuthorServiceInterface $authorService)
    {
        $this->authorService = $authorService;
    }

    #[Route('/create-author', name: 'create_author', methods: ['POST', 'GET'])]
    public function createAuthor(Request $request): Response
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'name' => $author->getName(),
                'birthDate' => $author->getBirthDate()?->format('Y-m-d'),
                'biography' => $author->getBiography(),
            ];

            $statusCode = $this->authorService->createAuthor($data);

            if (Response::HTTP_CREATED === $statusCode) {
                $this->addFlash('success', 'Author created successfully!');
            } else {
                $this->addFlash('error', 'Failed to create author.');
            }

            return $this->redirectToRoute('author_list');
        }

        return $this->render('author/create_author.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/', name: 'author_list', methods: ['GET'])]
    public function listAuthors(): Response
    {
        $authors = $this->authorService->getAuthors();

        if (empty($authors)) {
            $this->addFlash('error', 'Failed to fetch authors from the API.');
        }

        return $this->render('author/list_authors.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/delete-author/{id}', name: 'delete_author')]
    public function deleteAuthor(int $id): Response
    {
        $success = $this->authorService->deleteAuthor($id);

        if ($success) {
            $this->addFlash('success', 'Author deleted successfully.');
        } else {
            $this->addFlash('error', 'Failed to delete the author.');
        }

        return $this->redirectToRoute('author_list');
    }

    #[Route('/edit-author/{id}', name: 'edit_author', methods: ['GET', 'POST'])]
    public function editAuthor(int $id, Request $request): Response
    {
        // Fetch author data
        $authors = $this->authorService->getAuthors();
        $authorData = null;

        foreach ($authors as $item) {
            if ($item['id'] === $id) {
                $authorData = $item;
                break;
            }
        }

        if (!$authorData) {
            throw $this->createNotFoundException('Author not found.');
        }

        $author = new Author();

        $author->setName($authorData['name']);
        $author->setBirthDate(new \DateTime($authorData['birthDate']));
        $author->setBiography($authorData['biography']);

        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $updatedData = [
                'name' => $data->getName(),
                'birthDate' => $data->getBirthDate()->format('Y-m-d'),
                'biography' => $data->getBiography(),
            ];

            $success = $this->authorService->updateAuthor($id, $updatedData);

            if ($success) {
                $this->addFlash('success', 'Author updated successfully!');
            } else {
                $this->addFlash('error', 'Failed to update author.');
            }

            return $this->redirectToRoute('author_list');
        }

        return $this->render('author/edit_author.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/filter-authors', name: 'filter_authors', methods: ['GET', 'POST'])]
    public function filterAuthors(Request $request): Response
    {
        $form = $this->createForm(AuthorFilterType::class);
        $form->handleRequest($request);

        $authors = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $authors = $this->authorService->filterAuthorsByName($data['name']);
        }

        return $this->render('author/filter_authors.html.twig', [
            'form' => $form,
            'authors' => $authors,
        ]);
    }
}

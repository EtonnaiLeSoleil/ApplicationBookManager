<?php
namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Service\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/', name: 'book_index', methods: ['GET'])]
    public function index(Request $request, BookRepository $bookRepository): Response
    {
        $genre = $request->query->get('genre');
        $q = $request->query->get('q');

        if ($q) {
            $books = $bookRepository->searchByTitle($q);
        } elseif ($genre) {
            $books = $bookRepository->findByGenre($genre);
        } else {
            $books = $bookRepository->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->render('book/index.html.twig', [
            'books' => $books,
            'q' => $q,
            'genre' => $genre,
        ]);
    }

    #[Route('/my', name: 'book_my', methods: ['GET'])]
    public function myBooks(BookRepository $bookRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $books = $bookRepository->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('book/my.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/new', name: 'book_new', methods: ['GET','POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ImageUploader $uploader,
        SluggerInterface $slugger,
        BookRepository $bookRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = strtolower($slugger->slug($book->getTitle()));
            // Vérifie unicité
            if ($bookRepository->findOneBy(['slug' => $slug])) {
                $slug .= '-' . time();
            }
            $book->setSlug($slug);
            $book->setUser($this->getUser());

            /** @var UploadedFile $coverFile */
            $coverFile = $form->get('coverFile')->getData();
            if ($coverFile) {
                $path = $uploader->upload($coverFile, $slug);
                $book->setCoverImage($path);
            }

            $em->persist($book);
            $em->flush();

            $this->addFlash('success', '📚 Livre créé avec succès !');
            return $this->redirectToRoute('book_show', ['slug' => $book->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $this->addFlash('danger', '❌ Erreurs dans le formulaire.');
        }

        return $this->render('book/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}/edit', name: 'book_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        Book $book,
        EntityManagerInterface $em,
        ImageUploader $uploader,
        SluggerInterface $slugger,
        BookRepository $bookRepository
    ): Response {
        $this->denyAccessUnlessGranted('BOOK_EDIT', $book);

        $oldCover = $book->getCoverImage();

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = strtolower($slugger->slug($book->getTitle()));
            // Vérifie unicité
            $existing = $bookRepository->findOneBy(['slug' => $slug]);
            if ($existing && $existing->getId() !== $book->getId()) {
                $slug .= '-' . time();
            }
            $book->setSlug($slug);

            /** @var UploadedFile $coverFile */
            $coverFile = $form->get('coverFile')->getData();
            if ($coverFile) {
                if ($oldCover) {
                    $uploader->deleteIfExists($oldCover);
                }
                $path = $uploader->upload($coverFile, $slug);
                $book->setCoverImage($path);
            }

            $em->flush();

            $this->addFlash('success', '✏️ Livre mis à jour');
            return $this->redirectToRoute('book_show', ['slug' => $book->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $this->addFlash('danger', '❌ Erreurs dans le formulaire.');
        }

        return $this->render('book/edit.html.twig', [
            'form' => $form->createView(),
            'book' => $book,
        ]);
    }

    #[Route('/{slug}/delete', name: 'book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $em, ImageUploader $uploader): Response
    {
        $this->denyAccessUnlessGranted('BOOK_DELETE', $book);

        if ($this->isCsrfTokenValid('delete' . $book->getId(), $request->request->get('_token'))) {
            if ($book->getCoverImage()) {
                $uploader->deleteIfExists($book->getCoverImage());
            }
            $em->remove($book);
            $em->flush();
            $this->addFlash('success', '🗑️ Livre supprimé');
        } else {
            $this->addFlash('danger', '⚠️ Suppression non autorisée (CSRF invalide).');
        }

        return $this->redirectToRoute('book_index');
    }

    #[Route('/{slug}', name: 'book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }
}

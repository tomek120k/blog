<?php

namespace App\Controller;

use App\Blog\CQRS\ArticlesQuery;
use App\Blog\CQRS\ArticlesQueryHandler;
use App\Blog\CQRS\CreateArticleCommand;
use App\Blog\CQRS\DeleteArticleCommand;
use App\Blog\CQRS\UpdateArticleCommand;
use App\Blog\CQRS\UpdateArticleFileCommand;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Shared\CQRS\CommandBus;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/articles')]
class ArticleController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(Request $request, ArticlesQueryHandler $articlesQueryHandler): Response
    {
        return $this->render('article/index.html.twig', [
            'articles' => $articlesQueryHandler(new ArticlesQuery(20, $request->query->get('page', 1))),
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CommandBus $commandBus, SluggerInterface $slugger): Response
    {
        $article = new Article();
        $article->setId(Uuid::uuid4());
        $form = $this->createForm(ArticleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = Uuid::uuid4();
            $data = $form->getData();
            $commandBus->dispatch(new CreateArticleCommand(
                $id,
                $data['title'],
                $data['body'],
            ));
            if ($articleImage = $form->get('image')->getData()) {
                $newFilename = $this->processFile($articleImage, $slugger);
                $commandBus->dispatch(new UpdateArticleFileCommand(
                    $id,
                    $newFilename
                ));
            }

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, CommandBus $commandBus, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ArticleType::class, $article->jsonSerialize());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $commandBus->dispatch(new UpdateArticleCommand(
                $article->getId(),
                $data['title'],
                $data['body'],
            ));
            if ($articleImage = $form->get('image')->getData()) {
                $newFilename = $this->processFile($articleImage, $slugger);
                $commandBus->dispatch(new UpdateArticleFileCommand(
                    $article->getId(),
                    $newFilename
                ));
            }

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, CommandBus $commandBus): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $commandBus->dispatch(new DeleteArticleCommand(
                $article->getId(),
            ));
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }

    public function processFile(mixed $articleImage, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($articleImage->getClientOriginalName(), PATHINFO_FILENAME);
        $sluggedName = $slugger->slug($originalFilename);
        $newFilename = $sluggedName.'-'.uniqid().'.'.$articleImage->guessExtension();

        try {
            $articleImage->move(
                $this->getParameter('img_dir'),
                $newFilename
            );
        } catch (FileException $e) {
            $this->logger->warning($e->getMessage());
        }

        return $newFilename;
    }
}

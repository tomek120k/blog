<?php

namespace App\Controller;

use App\Blog\CQRS\ArticlesQuery;
use App\Blog\CQRS\ArticlesQueryHandler;
use App\Blog\CQRS\CreateArticleCommand;
use App\Blog\CQRS\DeleteArticleCommand;
use App\Blog\CQRS\DeleteArticleFileCommand;
use App\Blog\CQRS\UpdateArticleCommand;
use App\Blog\CQRS\UpdateArticleFileCommand;
use App\Entity\Article;
use App\Form\ArticleType;
use App\FormErrorsTransformer;
use App\Shared\CQRS\CommandBus;
use App\Shared\ProblemBag;
use App\View\ArticleApiView;
use App\View\ArticlesApiView;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(private readonly FormErrorsTransformer $formErrorsTransformer)
    {
    }

    #[Route('/articles', name: 'article_index', methods: ['GET'])]
    public function indexAction(Request $request, ArticlesQueryHandler $articlesQueryHandler, ArticlesApiView $apiView): Response
    {
        try {
            $page = $request->query->get('page', 1);
            $limit = $request->query->get('limit', 25);

            $results = $articlesQueryHandler(new ArticlesQuery($limit, $page));

            $response = new JsonResponse($apiView->normalize($results), Response::HTTP_OK);
            $response->setPublic();
            $response->setMaxAge(3600);

            return $response;
        } catch (\RuntimeException $e) {
            return $this->handleNotFoundResponse();
        }
    }

    #[Route('/articles', name: 'article_create', methods: ['POST'])]
    public function createAction(Request $request, CommandBus $commandBus): Response
    {
        $form = $this->createForm(ArticleType::class, null, [
            'csrf_protection' => false,
        ]);
        $json = \json_decode($request->getContent(), true);
        if (json_last_error()) {
            return $this->handleInvalidJSONResponse();
        }

        $form->submit($json);
        if (!$form->isValid()) {
            return $this->handleValidationResponse($this->getErrorMessages($form));
        }

        $id = Uuid::uuid4();
        $data = $form->getData();
        $commandBus->dispatch(new CreateArticleCommand(
            $id,
            $data['title'],
            $data['body'],
        ));

        $response = new JsonResponse([], Response::HTTP_ACCEPTED);
        $url = $this->generateUrl('article_read', ['article' => $id]);
        $response->headers->set('location', $url);

        return $response;
    }

    #[Route('/articles/{article}', name: 'article_read', methods: ['GET'])]
    public function readAction(Article $article = null, ArticleApiView $apiView): Response
    {
        if (!$article) {
            return $this->handleNotFoundResponse();
        }
        $response = new JsonResponse($apiView->normalize($article->jsonSerialize()), Response::HTTP_CREATED);
        $response->headers->set('location', '/api/article/{uuid}');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }

    #[Route('/articles/{article}', name: 'article_update', methods: ['PUT'])]
    public function updateAction(Request $request, Article $article = null, CommandBus $commandBus): Response
    {
        if (!$article) {
            return $this->handleNotFoundResponse();
        }

        $form = $this->createForm(ArticleType::class, $article->jsonSerialize(), [
            'csrf_protection' => false,
        ]);
        $json = \json_decode($request->getContent(), true);
        if (json_last_error()) {
            return $this->handleInvalidJSONResponse();
        }

        $form->submit($json);
        if (!$form->isValid()) {
            return $this->handleValidationResponse($this->getErrorMessages($form));
        }

        $data = $form->getData();
        $commandBus->dispatch(new UpdateArticleCommand(
            $article->getId(),
            $data['title'],
            $data['body'],
        ));

        $response = new JsonResponse([], Response::HTTP_ACCEPTED);
        $url = $this->generateUrl('article_read', ['article' => $article->getId()]);
        $response->headers->set('location', $url);

        return $response;
    }

    #[Route('/articles/{article}', name: 'article_delete', methods: ['DELETE'])]
    public function deleteAction(Article $article = null, CommandBus $commandBus): Response
    {
        if (!$article) {
            return $this->handleNotFoundResponse();
        }

        $commandBus->dispatch(new DeleteArticleCommand($article->getId()));

        $response = new JsonResponse([], Response::HTTP_NO_CONTENT);
        $url = $this->generateUrl('article_index');
        $response->headers->set('location', $url);

        return $response;
    }

    #[Route('/articles/{article}/file', name: 'article_file_update', methods: ['PUT'])]
    public function updateFileAction(Request $request, Article $article = null, CommandBus $commandBus): Response
    {
        if (!$article) {
            return $this->handleNotFoundResponse();
        }
        $requestData = $request->getContent();

        if (base64_decode($requestData)) {
            /**
             * @todo extract to other method/class
             */
            $extension = explode('/', \mime_content_type($requestData))[1];
            $file = time().'.'.$extension;
            $filePath = $this->getParameter('img_dir').'/'.$file;
            $data = explode(',', $requestData);
            $fileData = base64_decode($data[1]);
            if ((strlen($fileData) / 1024 / 1024) > 8) {
                return $this->handleInvalidFileResponse();
            }

            file_put_contents($filePath, $fileData);
            $commandBus->dispatch(new UpdateArticleFileCommand(
                $article->getId(),
                $file
            ));
            $response = new JsonResponse([], Response::HTTP_ACCEPTED);
            $url = $this->generateUrl('article_read', ['article' => 'uuid']);
            $response->headers->set('location', $url);

            return $response;
        } else {
            return $this->handleInvalidFileResponse();
        }
    }

    #[Route('/articles/{article}/file', name: 'article_file_delete', methods: ['DELETE'])]
    public function deleteFileAction(Article $article = null, CommandBus $commandBus): Response
    {
        if (!$article) {
            return $this->handleNotFoundResponse();
        }

        $commandBus->dispatch(new DeleteArticleFileCommand(
            $article->getId(),
        ));

        $response = new JsonResponse([], Response::HTTP_ACCEPTED);
        $url = $this->generateUrl('article_read', ['article' => 'uuid']);
        $response->headers->set('location', $url);

        return $response;
    }

    private function getErrorMessages(FormInterface $form): array
    {
        return $this->formErrorsTransformer->getErrorMessages($form);
    }

    protected function handleValidationResponse(array $errors): JsonResponse
    {
        $apiProblem = new ProblemBag(
            400,
            ProblemBag::TYPE_VALIDATION_ERROR,
        );
        $apiProblem->set('errors', $errors);

        $response = new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );
        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }

    protected function handleInvalidJSONResponse(): JsonResponse
    {
        $apiProblem = new ProblemBag(
            400,
            ProblemBag::TYPE_INVALID_REQUEST_BODY_FORMAT
        );
        $response = new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );
        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }

    protected function handleInvalidFileResponse(): JsonResponse
    {
        $apiProblem = new ProblemBag(
            400,
            ProblemBag::TYPE_INVALID_FILE
        );
        $response = new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );
        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }

    protected function handleNotFoundResponse(): JsonResponse
    {
        $apiProblem = new ProblemBag(
            400,
            ProblemBag::TYPE_NOT_FOUND
        );
        $response = new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );
        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }
}

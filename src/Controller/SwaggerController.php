<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

class SwaggerController extends AbstractController
{
    private const YAML_SWAGGER_FILE_FULL_PATH = __DIR__.'/../../res/api.yaml';
    private const YAML_INLINE_LEVEL = 128;
    private const YAML_AMOUNT_OF_SPACES = 4;

    #[Route('/api/doc', name: 'app_swagger')]
    public function docAction(): Response
    {
        return $this->render('swagger/index.html.twig', [
            'swagger_configuration' => [
                'url' => $this->generateUrl('app_swagger_doc_file'),
            ],
        ]);
    }

    #[Route('/api/doc/download', name: 'app_swagger_doc_file')]
    public function docDownloadAction(Request $request): Response
    {
        $yaml = new Yaml();

        $configuration = $yaml->parseFile(realpath(self::YAML_SWAGGER_FILE_FULL_PATH));
        $configuration['host'] = $request->getBaseUrl();

        $yamlConfiguration = Yaml::dump($configuration, self::YAML_INLINE_LEVEL, self::YAML_AMOUNT_OF_SPACES);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/yaml');
        $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'api-blog.yaml');
        $response->setContent($yamlConfiguration);

        return $response;
    }
}

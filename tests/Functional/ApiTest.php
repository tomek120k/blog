<?php

namespace App\Tests\Functional;

use App\Factory\ArticleFactory;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTest extends WebTestCase
{
    use PHPMatcherAssertions;

    public function testArticleList(): void
    {
        $client = static::createClient();
        (new ORMPurger(self::$kernel->getContainer()->get('doctrine.orm.entity_manager')))->purge();
        ArticleFactory::createMany(10);
        $client->request('GET', $this->generateUrl('article_index'), ['limit' => 5]);
        $response = $client->getResponse()->getContent();
        $articles = \json_decode($response, true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(5, $articles['_embedded']['articles']);
        $this->assertMatchesPattern(\file_get_contents(__DIR__.'/../Resources/product-list-v1.json'), $response);
    }

    public function testArticleListWrongPage(): void
    {
        $client = static::createClient();
        (new ORMPurger(self::$kernel->getContainer()->get('doctrine.orm.entity_manager')))->purge();
        ArticleFactory::createMany(5);
        $client->request('GET', $this->generateUrl('article_index'), ['limit' => 5, 'page' => 100]);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = $client->getResponse()->getContent();
        $this->assertMatchesPattern(\file_get_contents(__DIR__.'/../Resources/not-found.json'), $response);
    }

    public function testArticleCreate(): void
    {
        $client = static::createClient();
        $client->request('POST', $this->generateUrl('article_create'), [], content: json_encode([
            'title' => 'example title',
            'body' => 'example body lorem ipsum',
        ]));
        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testArticleCreateInvalidJSON(): void
    {
        $client = static::createClient();
        $client->request('POST', $this->generateUrl('article_create'), [], content: 'invalid{json}');

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = $client->getResponse()->getContent();
        $this->assertMatchesPattern(\file_get_contents(__DIR__.'/../Resources/invalid-json.json'), $response);
    }

    public function testArticleCreateValidationError(): void
    {
        $client = static::createClient();
        $client->request('POST', $this->generateUrl('article_create'), [], content: json_encode([
            'title' => 'example title',
            'body' => 'example body',
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = $client->getResponse()->getContent();

        $this->assertMatchesPattern(\file_get_contents(__DIR__.'/../Resources/validation-error.json'), $response);
    }

    public function testArticleUpdate(): void
    {
        $client = static::createClient();
        ArticleFactory::createOne(['id' => $id = Uuid::uuid4()]);
        $client->request('PUT', $this->generateUrl('article_update', ['article' => $id]), [], content: json_encode([
            'title' => 'example title',
            'body' => 'example body lorem ipsum',
        ]));
        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testArticleUpdateEntityNotFound(): void
    {
        $client = static::createClient();
        $client->request('PUT', $this->generateUrl('article_update', ['article' => Uuid::uuid4()]), [], content: json_encode([
            'title' => 'example title',
            'body' => 'example body lorem ipsum',
        ]));
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = $client->getResponse()->getContent();
        $this->assertMatchesPattern(\file_get_contents(__DIR__.'/../Resources/not-found.json'), $response);
    }

    public function testArticleDelete(): void
    {
        $client = static::createClient();
        ArticleFactory::createOne(['id' => $id = Uuid::uuid4()]);
        $client->request('DELETE', $this->generateUrl('article_delete', ['article' => $id]));
        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    public function testArticleFile(): void
    {
        $client = static::createClient();
        ArticleFactory::createOne(['id' => $id = Uuid::uuid4()]);
        $client->request('PUT', $this->generateUrl('article_file_update', ['article' => $id]), content: 'data:image/png;base64,');
        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testArticleFileDelete(): void
    {
        $client = static::createClient();
        ArticleFactory::createOne(['id' => $id = Uuid::uuid4()]);
        $client->request('PUT', $this->generateUrl('article_file_delete', ['article' => $id]), content: 'data:image/png;base64,');
        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    private function generateUrl(string $name, array $params = []): string
    {
        return self::$kernel->getContainer()->get('router')->generate($name, $params);
    }
}

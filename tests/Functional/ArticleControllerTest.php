<?php

namespace App\Tests\Functional;

use App\Entity\Article;
use App\Factory\ArticleFactory;
use App\Repository\ArticleRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArticleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ArticleRepository $repository;
    private string $path = '/article/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = static::getContainer()->get('doctrine')->getRepository(Article::class);

        foreach ($this->repository->findAll() as $object) {
            $this->repository->remove($object, true);
        }
    }

    public function testIndex(): void
    {
        (new ORMPurger(self::$kernel->getContainer()->get('doctrine.orm.entity_manager')))->purge();
        ArticleFactory::createMany(10);
        $this->client->request('GET', $this->generateUrl('app_article_index'), ['limit' => 5]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        self::assertPageTitleContains('Article index');
    }

    public function testNew(): void
    {
        $originalNumObjectsInRepository = count($this->repository->findAll());

        $this->client->request('GET', $this->generateUrl('app_article_new'));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'article[title]' => 'TestingTestingTestingTesting',
            'article[body]' => 'TestingTestingTestingTesting',
        ]);

        self::assertResponseRedirects($this->generateUrl('app_article_index'));

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $fixture = new Article();
        $fixture->setTitle('My Title');
        $fixture->setBody('My Title');
        $fixture->setFile('dummy.png');
        $fixture->setId(Uuid::uuid4());

        $this->repository->save($fixture, true);

        $this->client->request('GET', $this->generateUrl('app_article_show', ['id' => $fixture->getId()]));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Article');
    }

    public function testEdit(): void
    {
        $fixture = new Article();
        $fixture->setTitle('My Title');
        $fixture->setBody('My Title');
        $fixture->setFile('dummy.png');
        $fixture->setId(Uuid::uuid4());

        $this->repository->save($fixture, true);
        $this->client->request('GET', $this->generateUrl('app_article_edit', ['id' => $fixture->getId()]));

        $this->client->submitForm('Update', [
            'article[title]' => $title = 'Est voluptas id quia qui est maxime unde.',
            'article[body]' => $body = 'Distinctio iure vel quia facilis. Saepe dolor eos quia cupiditate animi rem culpa. Reprehenderit sint non non voluptate ipsum. Qui exercitationem adipisci exercitationem omnis.',
        ]);

        self::assertResponseRedirects($this->generateUrl('app_article_index'));

        $fixture = $this->repository->findAll();

        self::assertSame($title, $fixture[0]->getTitle());
        self::assertSame($body, $fixture[0]->getBody());
    }

    public function testRemove(): void
    {
        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Article();
        $fixture->setTitle('My Title');
        $fixture->setBody('My Title');
        $fixture->setFile('dummy.png');
        $fixture->setId(Uuid::uuid4());

        $this->repository->save($fixture, true);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', $this->generateUrl('app_article_delete', ['id' => $fixture->getId()]));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects($this->generateUrl('app_article_index'));
    }

    private function generateUrl(string $name, array $params = []): string
    {
        return self::$kernel->getContainer()->get('router')->generate($name, $params);
    }
}

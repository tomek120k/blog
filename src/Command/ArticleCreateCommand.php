<?php

namespace App\Command;

use App\Blog\CQRS\CreateArticleCommand;
use App\Form\ArticleType;
use App\FormErrorsTransformer;
use App\Shared\CQRS\CommandBus;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Form\FormFactoryInterface;

#[AsCommand(
    name: 'app:article:create',
    description: 'Add acricle command',
)]
class ArticleCreateCommand extends Command
{
    public function __construct(private readonly CommandBus $commandBus, private readonly FormFactoryInterface $formFactory)
    {
        parent::__construct('Add article');
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::REQUIRED, 'Article title')
            ->addArgument('body', InputArgument::REQUIRED, 'Article body')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = $input->getArgument('title');
        $body = $input->getArgument('body');

        $form = $this->formFactory->create(ArticleType::class, null, [
            'csrf_protection' => false,
        ]);

        $form->submit(['title' => $title, 'body' => $body]);
        if (!$form->isValid()) {
            $errors = (new FormErrorsTransformer())->getErrorMessages($form);
            $io->warning(json_encode($errors));

            return Command::FAILURE;
        }

        $this->commandBus->dispatch(new CreateArticleCommand(
            $id = Uuid::uuid4(),
            $title,
            $body
        ));

        $io->success('Article added => '.$id);

        return Command::SUCCESS;
    }
}

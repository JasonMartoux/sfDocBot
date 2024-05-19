<?php

namespace App\Command;

use App\Entity\Place;
use Doctrine\ORM\EntityManagerInterface;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingFormatter\EmbeddingFormatter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3LargeEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'GenerateEmbeddings',
    description: 'Generate Embeddings Datas',
)]
class GenerateEmbeddingsCommand extends Command
{
  private EntityManagerInterface $entityManager;

  public function __construct(EntityManagerInterface $entityManager)
  {
    parent::__construct();
    $this->entityManager = $entityManager;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $io->title('Generate Embeddings started');

    $io->section("Lecture des données");
    $dataReader = new FileDataReader(__DIR__ . '/../../documents/sfdoc/symfony-docs-7.1/',Place::class);
    $documents = $dataReader->getDocuments();
    $io->success("Les données ont été lues avec succès, et ".count($documents)." documents ont été trouvés.");

    $io->section("Découpage des documents");
    $splittedDocuments = DocumentSplitter::splitDocuments($documents, 3072,"\n");
    $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splittedDocuments);
    $io->success("Les documents ont été découpés avec succès en ".count($formattedDocuments)." documents de 3072 mots maximum.");

    $io->section("Génération des embeddings");
    $progressBar = new ProgressBar($output, count($formattedDocuments));

    $embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();
    $embeddedDocuments = [];
    $progressBar->start();
    foreach ($formattedDocuments as $document) {
      $embeddedDocuments[] = $embeddingGenerator->embedDocument($document);
      $progressBar->advance();
    }
    $progressBar->finish();
    $io->success("Les embeddings ont été générés avec succès.");

    $io->section("Sauvegarde des embeddings");
    try {
      $vectorStore = new DoctrineVectorStore($this->entityManager, Place::class);
      $vectorStore->addDocuments($embeddedDocuments);
    }
    catch (\Exception $exception) {

      $io->error($exception->getMessage());
      return Command::FAILURE;
    }
    $io->success("Les embeddings ont été sauvegardés avec succès.");

    $io->success("Les embeddings ont été générés avec succès et stockés en BDD");

    return Command::SUCCESS;
  }
}

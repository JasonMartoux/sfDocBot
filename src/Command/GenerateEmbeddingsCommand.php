<?php

namespace App\Command;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'GenerateEmbeddings',
    description: 'Generate Embeddings Datas',
)]
class GenerateEmbeddingsCommand extends Command
{
  public function __construct()
  {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $io->title('Generate Embeddings started');

    $io->section("Lecture des données");
    $dataReader = new FileDataReader(__DIR__ . '/../../documents/sfdoc/symfony-docs-7.1/');
    $documents = $dataReader->getDocuments();
    $io->success("Les données ont été lues avec succès, et ".count($documents)." documents ont été trouvés.");

    $io->section("Découpage des documents");
    $splitDocuments = DocumentSplitter::splitDocuments($documents);
    $io->success("Les documents ont été découpés avec succès en ".count($splitDocuments)." documents de 1000 mots maximum.");

    $io->section("Génération des embeddings");
    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
    $embeddedDocuments = [];
    foreach ($splitDocuments as $document) {
      $embeddedDocuments[] = $embeddingGenerator->embedDocument($document);
    }
    $io->success("Les embeddings ont été générés avec succès.");

    $io->section("Sauvegarde des embeddings");
    $vectorStore = new FileSystemVectorStore();
    $vectorStore->addDocuments($embeddedDocuments);
    $io->success("Les embeddings ont été sauvegardés avec succès.");

    $io->success("Les embeddings ont été générés avec succès et stockés dans le fichier documents-vectorStore.json");

    return Command::SUCCESS;
  }
}

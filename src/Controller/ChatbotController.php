<?php

namespace App\Controller;

use App\Entity\Place;
use App\Form\ChatbotType;
use Doctrine\ORM\EntityManagerInterface;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3LargeEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
  private EntityManagerInterface $entityManager;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
  }

  #[Route('/', name: 'app_chatbot')]
  public function index(Request $request): Response
  {
    $form = $this->createForm(ChatbotType::class);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $question = $form->getData()['question'];

      $vectorStore = new DoctrineVectorStore($this->entityManager, Place::class);
      $embeddingGenerator = new OpenAI3LargeEmbeddingGenerator();

      $qa = new QuestionAnswering(
          $vectorStore,
          $embeddingGenerator,
          new OpenAIChat()
      );


      //$qa->systemMessageTemplate = "Act as a php developer assistant specialist in symfony, ";
      $answer = $qa->answerQuestion($question);

      return $this->render('chatbot/index.html.twig', [
          'form' => $form,
          'answer' => $answer,
      ]);
    }

    return $this->render('chatbot/index.html.twig', [
        'form' => $form,
    ]);
  }
}

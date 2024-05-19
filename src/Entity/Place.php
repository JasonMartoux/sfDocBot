<?php

namespace App\Entity;

use App\Repository\PlaceRepository;
use Doctrine\ORM\Mapping as ORM;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase;
use LLPhant\Embeddings\VectorStores\Doctrine\VectorType;

#[ORM\Entity(repositoryClass: PlaceRepository::class)]
class Place extends DoctrineEmbeddingEntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column]
    public int $id;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $type = null;

    #[ORM\Column(type: VectorType::VECTOR, length: 3072)]
    public ?array $embedding;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    public function setEmbedding(array $embedding): static
    {
        $this->embedding = $embedding;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\DictionaryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity; 

#[ORM\Entity(repositoryClass: DictionaryRepository::class)]
#[ORM\Table(name: "dictionary")]
#[UniqueEntity(fields: ['name'], message: 'Bu kelime zaten sözlükte kayıtlı!')] 
class Dictionary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: "name", length: 255, unique: true)] 
    private ?string $name = null;

    #[ORM\Column(name: "description", type: "text")]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $example_sentence = null;

    public function getId(): ?int { return $this->id; }
    
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }

    public function getExampleSentence(): ?string
    {
        return $this->example_sentence;
    }

    public function setExampleSentence(?string $example_sentence): static
    {
        $this->example_sentence = $example_sentence;
        return $this;
    }
}
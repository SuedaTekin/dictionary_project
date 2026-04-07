<?php

namespace App\Entity;

use App\Repository\DictionaryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DictionaryRepository::class)]
#[ORM\Table(name: "dictionary")] // Tablo adının küçük harf olduğundan emin olduk
class Dictionary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: "name", length: 255)] // Veritabanındaki 'name' sütunuyla eşledik
    private ?string $name = null;

    #[ORM\Column(name: "description", type: "text")] // Veritabanındaki 'description' ile eşledik
    private ?string $description = null;

    public function getId(): ?int { return $this->id; }
    
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
}
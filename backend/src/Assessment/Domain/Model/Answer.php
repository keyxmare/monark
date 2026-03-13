<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'assessment_answers')]
final class Answer
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'boolean')]
    private bool $isCorrect;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Question $question;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $content,
        bool $isCorrect,
        int $position,
        Question $question,
    ) {
        $this->id = $id;
        $this->content = $content;
        $this->isCorrect = $isCorrect;
        $this->position = $position;
        $this->question = $question;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $content,
        bool $isCorrect,
        int $position,
        Question $question,
    ): self {
        $answer = new self(
            id: Uuid::v7(),
            content: $content,
            isCorrect: $isCorrect,
            position: $position,
            question: $question,
        );

        $question->addAnswer($answer);

        return $answer;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        ?string $content = null,
        ?bool $isCorrect = null,
        ?int $position = null,
    ): void {
        if ($content !== null) {
            $this->content = $content;
        }
        if ($isCorrect !== null) {
            $this->isCorrect = $isCorrect;
        }
        if ($position !== null) {
            $this->position = $position;
        }
        $this->updatedAt = new DateTimeImmutable();
    }
}

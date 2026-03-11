<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'assessment_questions')]
final class Question
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', enumType: QuestionType::class)]
    private QuestionType $type;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', enumType: QuestionLevel::class)]
    private QuestionLevel $level;

    #[ORM\Column(type: 'integer')]
    private int $score;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Quiz $quiz;

    /** @var Collection<int, Answer> */
    #[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $answers;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        QuestionType $type,
        string $content,
        QuestionLevel $level,
        int $score,
        int $position,
        Quiz $quiz,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->content = $content;
        $this->level = $level;
        $this->score = $score;
        $this->position = $position;
        $this->quiz = $quiz;
        $this->answers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        QuestionType $type,
        string $content,
        QuestionLevel $level,
        int $score,
        int $position,
        Quiz $quiz,
    ): self {
        $question = new self(
            id: Uuid::v7(),
            type: $type,
            content: $content,
            level: $level,
            score: $score,
            position: $position,
            quiz: $quiz,
        );

        $quiz->addQuestion($question);

        return $question;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): QuestionType
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getLevel(): QuestionLevel
    {
        return $this->level;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    /** @return Collection<int, Answer> */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function getAnswerCount(): int
    {
        return $this->answers->count();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        ?QuestionType $type = null,
        ?string $content = null,
        ?QuestionLevel $level = null,
        ?int $score = null,
        ?int $position = null,
    ): void {
        if ($type !== null) {
            $this->type = $type;
        }
        if ($content !== null) {
            $this->content = $content;
        }
        if ($level !== null) {
            $this->level = $level;
        }
        if ($score !== null) {
            $this->score = $score;
        }
        if ($position !== null) {
            $this->position = $position;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addAnswer(Answer $answer): void
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
        }
    }

    public function removeAnswer(Answer $answer): void
    {
        $this->answers->removeElement($answer);
    }
}

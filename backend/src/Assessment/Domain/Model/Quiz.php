<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'assessment_quizzes')]
#[ORM\UniqueConstraint(name: 'uniq_quiz_slug', columns: ['slug'])]
final class Quiz
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', enumType: QuizType::class)]
    private QuizType $type;

    #[ORM\Column(type: 'string', enumType: QuizStatus::class)]
    private QuizStatus $status;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $timeLimit;

    #[ORM\Column(length: 36)]
    private string $authorId;

    /** @var Collection<int, Question> */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'quiz', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $questions;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $title,
        string $slug,
        string $description,
        QuizType $type,
        QuizStatus $status,
        ?DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        ?int $timeLimit,
        string $authorId,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
        $this->type = $type;
        $this->status = $status;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->timeLimit = $timeLimit;
        $this->authorId = $authorId;
        $this->questions = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $title,
        string $slug,
        string $description,
        QuizType $type,
        QuizStatus $status = QuizStatus::Draft,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?int $timeLimit = null,
        string $authorId = '',
    ): self {
        return new self(
            id: Uuid::v7(),
            title: $title,
            slug: $slug,
            description: $description,
            type: $type,
            status: $status,
            startsAt: $startsAt,
            endsAt: $endsAt,
            timeLimit: $timeLimit,
            authorId: $authorId,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): QuizType
    {
        return $this->type;
    }

    public function getStatus(): QuizStatus
    {
        return $this->status;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    /** @return Collection<int, Question> */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function getQuestionCount(): int
    {
        return $this->questions->count();
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
        ?string $title = null,
        ?string $slug = null,
        ?string $description = null,
        ?QuizType $type = null,
        ?QuizStatus $status = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?int $timeLimit = null,
    ): void {
        if ($title !== null) {
            $this->title = $title;
        }
        if ($slug !== null) {
            $this->slug = $slug;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($type !== null) {
            $this->type = $type;
        }
        if ($status !== null) {
            $this->status = $status;
        }
        if ($startsAt !== null) {
            $this->startsAt = $startsAt;
        }
        if ($endsAt !== null) {
            $this->endsAt = $endsAt;
        }
        if ($timeLimit !== null) {
            $this->timeLimit = $timeLimit;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addQuestion(Question $question): void
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
        }
    }

    public function removeQuestion(Question $question): void
    {
        $this->questions->removeElement($question);
    }
}

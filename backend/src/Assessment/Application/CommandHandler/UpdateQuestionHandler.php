<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\UpdateQuestionCommand;
use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateQuestionHandler
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
    ) {
    }

    public function __invoke(UpdateQuestionCommand $command): QuestionOutput
    {
        $question = $this->questionRepository->findById(Uuid::fromString($command->questionId));
        if ($question === null) {
            throw NotFoundException::forEntity('Question', $command->questionId);
        }

        $input = $command->input;

        $question->update(
            type: $input->type !== null ? QuestionType::from($input->type) : null,
            content: $input->content,
            level: $input->level !== null ? QuestionLevel::from($input->level) : null,
            score: $input->score,
            position: $input->position,
        );

        $this->questionRepository->save($question);

        return QuestionOutput::fromEntity($question);
    }
}

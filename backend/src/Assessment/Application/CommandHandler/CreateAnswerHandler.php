<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\CreateAnswerCommand;
use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAnswerHandler
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepository,
        private QuestionRepositoryInterface $questionRepository,
    ) {
    }

    public function __invoke(CreateAnswerCommand $command): AnswerOutput
    {
        $input = $command->input;

        $question = $this->questionRepository->findById(Uuid::fromString($input->questionId));
        if ($question === null) {
            throw NotFoundException::forEntity('Question', $input->questionId);
        }

        $answer = Answer::create(
            content: $input->content,
            isCorrect: $input->isCorrect,
            position: $input->position,
            question: $question,
        );

        $this->answerRepository->save($answer);

        return AnswerOutput::fromEntity($answer);
    }
}

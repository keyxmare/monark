<?php

declare(strict_types=1);

use App\Assessment\Application\Command\CreateAnswerCommand;
use App\Assessment\Application\Command\CreateAttemptCommand;
use App\Assessment\Application\Command\CreateQuestionCommand;
use App\Assessment\Application\Command\CreateQuizCommand;
use App\Assessment\Application\Command\DeleteAnswerCommand;
use App\Assessment\Application\Command\DeleteQuestionCommand;
use App\Assessment\Application\Command\DeleteQuizCommand;
use App\Assessment\Application\Command\UpdateAnswerCommand;
use App\Assessment\Application\Command\UpdateQuestionCommand;
use App\Assessment\Application\Command\UpdateQuizCommand;
use App\Assessment\Application\DTO\AnswerListOutput;
use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Application\DTO\AttemptListOutput;
use App\Assessment\Application\DTO\AttemptOutput;
use App\Assessment\Application\DTO\CreateAnswerInput;
use App\Assessment\Application\DTO\CreateAttemptInput;
use App\Assessment\Application\DTO\CreateQuestionInput;
use App\Assessment\Application\DTO\CreateQuizInput;
use App\Assessment\Application\DTO\QuestionListOutput;
use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Application\DTO\QuizListOutput;
use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Application\DTO\UpdateAnswerInput;
use App\Assessment\Application\DTO\UpdateQuestionInput;
use App\Assessment\Application\DTO\UpdateQuizInput;
use App\Assessment\Application\Query\GetAnswerQuery;
use App\Assessment\Application\Query\GetAttemptQuery;
use App\Assessment\Application\Query\GetQuestionQuery;
use App\Assessment\Application\Query\GetQuizQuery;
use App\Assessment\Application\Query\ListAnswersQuery;
use App\Assessment\Application\Query\ListAttemptsQuery;
use App\Assessment\Application\Query\ListQuestionsQuery;
use App\Assessment\Application\Query\ListQuizzesQuery;
use App\Assessment\Presentation\Controller\CreateAnswerController;
use App\Assessment\Presentation\Controller\CreateAttemptController;
use App\Assessment\Presentation\Controller\CreateQuestionController;
use App\Assessment\Presentation\Controller\CreateQuizController;
use App\Assessment\Presentation\Controller\DeleteAnswerController;
use App\Assessment\Presentation\Controller\DeleteQuestionController;
use App\Assessment\Presentation\Controller\DeleteQuizController;
use App\Assessment\Presentation\Controller\GetAnswerController;
use App\Assessment\Presentation\Controller\GetAttemptController;
use App\Assessment\Presentation\Controller\GetQuestionController;
use App\Assessment\Presentation\Controller\GetQuizController;
use App\Assessment\Presentation\Controller\ListAnswersController;
use App\Assessment\Presentation\Controller\ListAttemptsController;
use App\Assessment\Presentation\Controller\ListQuestionsController;
use App\Assessment\Presentation\Controller\ListQuizzesController;
use App\Assessment\Presentation\Controller\UpdateAnswerController;
use App\Assessment\Presentation\Controller\UpdateQuestionController;
use App\Assessment\Presentation\Controller\UpdateQuizController;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubAssessmentBus(mixed $result = null): MessageBusInterface&stdClass
{
    return new class ($result) extends stdClass implements MessageBusInterface {
        public ?object $dispatched = null;

        public function __construct(private readonly mixed $result)
        {
        }

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched = $message;
            $envelope = new Envelope($message);

            if ($this->result !== null) {
                $envelope = $envelope->with(new HandledStamp($this->result, 'handler'));
            }

            return $envelope;
        }
    };
}

$ts = '2026-01-01T00:00:00+00:00';
$uuid = 'a0000000-0000-0000-0000-000000000001';

it('creates a quiz and returns 201', function () use ($ts, $uuid) {
    $output = new QuizOutput('q-1', 'PHP Quiz', 'php-quiz', 'A quiz', 'quiz', 'draft', null, null, null, $uuid, 0, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $controller = new CreateQuizController($bus);

    $response = $controller(new CreateQuizInput(title: 'PHP Quiz', slug: 'php-quiz', description: 'A quiz', type: 'quiz', authorId: $uuid));

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateQuizCommand::class);
});

it('gets a quiz', function () use ($ts, $uuid) {
    $output = new QuizOutput('q-1', 'PHP Quiz', 'php-quiz', 'A quiz', 'quiz', 'draft', null, null, null, $uuid, 0, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new GetQuizController($bus))('q-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetQuizQuery::class);
});

it('updates a quiz', function () use ($ts, $uuid) {
    $output = new QuizOutput('q-1', 'Updated', 'updated', 'A quiz', 'quiz', 'draft', null, null, null, $uuid, 0, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new UpdateQuizController($bus))('q-1', new UpdateQuizInput(title: 'Updated'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateQuizCommand::class);
});

it('deletes a quiz and returns 204', function () {
    $bus = stubAssessmentBus();
    $response = (new DeleteQuizController($bus))('q-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteQuizCommand::class);
});

it('lists quizzes', function () {
    $bus = stubAssessmentBus(new QuizListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListQuizzesController($bus))(Request::create('/api/assessment/quizzes'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListQuizzesQuery::class);
});

it('creates a question and returns 201', function () use ($ts, $uuid) {
    $output = new QuestionOutput('qn-1', 'single_choice', 'What is PHP?', 'easy', 10, 1, $uuid, 0, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $input = new CreateQuestionInput(type: 'single_choice', content: 'What is PHP?', level: 'easy', score: 10, position: 1, quizId: $uuid);
    $response = (new CreateQuestionController($bus))($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateQuestionCommand::class);
});

it('gets a question', function () use ($ts, $uuid) {
    $output = new QuestionOutput('qn-1', 'single_choice', 'What is PHP?', 'easy', 10, 1, $uuid, 0, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new GetQuestionController($bus))('qn-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetQuestionQuery::class);
});

it('updates a question', function () use ($ts, $uuid) {
    $output = new QuestionOutput('qn-1', 'text', 'Updated?', 'medium', 20, 1, $uuid, 0, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new UpdateQuestionController($bus))('qn-1', new UpdateQuestionInput(content: 'Updated?'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateQuestionCommand::class);
});

it('deletes a question and returns 204', function () {
    $bus = stubAssessmentBus();
    $response = (new DeleteQuestionController($bus))('qn-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteQuestionCommand::class);
});

it('lists questions', function () {
    $bus = stubAssessmentBus(new QuestionListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListQuestionsController($bus))(Request::create('/api/assessment/questions', 'GET', ['quiz_id' => 'q-1']));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListQuestionsQuery::class);
});

it('creates an answer and returns 201', function () use ($ts, $uuid) {
    $output = new AnswerOutput('a-1', 'Yes', true, 1, $uuid, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new CreateAnswerController($bus))(new CreateAnswerInput(content: 'Yes', isCorrect: true, position: 1, questionId: $uuid));

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateAnswerCommand::class);
});

it('gets an answer', function () use ($ts, $uuid) {
    $output = new AnswerOutput('a-1', 'Yes', true, 1, $uuid, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new GetAnswerController($bus))('a-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetAnswerQuery::class);
});

it('updates an answer', function () use ($ts, $uuid) {
    $output = new AnswerOutput('a-1', 'No', false, 1, $uuid, $ts, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new UpdateAnswerController($bus))('a-1', new UpdateAnswerInput(content: 'No'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateAnswerCommand::class);
});

it('deletes an answer and returns 204', function () {
    $bus = stubAssessmentBus();
    $response = (new DeleteAnswerController($bus))('a-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteAnswerCommand::class);
});

it('lists answers', function () {
    $bus = stubAssessmentBus(new AnswerListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListAnswersController($bus))(Request::create('/api/assessment/answers', 'GET', ['question_id' => 'qn-1']));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListAnswersQuery::class);
});

it('creates an attempt and returns 201', function () use ($ts, $uuid) {
    $output = new AttemptOutput('att-1', 0, 'started', $ts, null, $uuid, $uuid, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new CreateAttemptController($bus))(new CreateAttemptInput(userId: $uuid, quizId: $uuid));

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateAttemptCommand::class);
});

it('gets an attempt', function () use ($ts, $uuid) {
    $output = new AttemptOutput('att-1', 85, 'graded', $ts, $ts, $uuid, $uuid, $ts);
    $bus = stubAssessmentBus($output);
    $response = (new GetAttemptController($bus))('att-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetAttemptQuery::class);
});

it('lists attempts', function () {
    $bus = stubAssessmentBus(new AttemptListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListAttemptsController($bus))(Request::create('/api/assessment/attempts'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListAttemptsQuery::class);
});

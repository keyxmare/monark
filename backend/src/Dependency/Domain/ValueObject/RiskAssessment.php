<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

use JsonSerializable;

final readonly class RiskAssessment implements JsonSerializable
{
    /** @param list<string> $recommendations */
    public function __construct(
        public RiskLevel $level,
        public float $score,
        public array $recommendations,
    ) {
    }

    public static function none(): self
    {
        return new self(RiskLevel::None, 0.0, []);
    }

    /** @return array{level: string, score: float, recommendations: list<string>} */
    public function jsonSerialize(): array
    {
        return [
            'level' => $this->level->value,
            'score' => $this->score,
            'recommendations' => $this->recommendations,
        ];
    }
}

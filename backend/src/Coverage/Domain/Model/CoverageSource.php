<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Model;

use App\Catalog\Domain\Model\ProviderType;

enum CoverageSource: string
{
    case CiGitlab = 'ci_gitlab';
    case CiGithub = 'ci_github';
    case LocalDocker = 'local_docker';

    public static function fromProviderType(ProviderType $type): self
    {
        return match ($type) {
            ProviderType::GitLab => self::CiGitlab,
            ProviderType::GitHub => self::CiGithub,
            ProviderType::Bitbucket => throw new \LogicException('Bitbucket coverage not supported yet.'),
        };
    }
}

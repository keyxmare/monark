<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Identity\Domain\Model\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        if (isset($_ENV['SEED_GITLAB_URL'], $_ENV['SEED_GITLAB_TOKEN'])
            && \is_string($_ENV['SEED_GITLAB_URL'])
            && \is_string($_ENV['SEED_GITLAB_TOKEN'])
        ) {
            $gitlabName = \is_string($_ENV['SEED_GITLAB_NAME'] ?? null) ? $_ENV['SEED_GITLAB_NAME'] : 'GitLab';
            $gitlab = Provider::create(
                name: $gitlabName,
                type: ProviderType::GitLab,
                url: $_ENV['SEED_GITLAB_URL'],
                apiToken: $_ENV['SEED_GITLAB_TOKEN'],
            );
            $manager->persist($gitlab);
        }

        if (isset($_ENV['SEED_GITHUB_TOKEN']) && \is_string($_ENV['SEED_GITHUB_TOKEN'])) {
            $githubName = \is_string($_ENV['SEED_GITHUB_NAME'] ?? null) ? $_ENV['SEED_GITHUB_NAME'] : 'GitHub';
            $github = Provider::create(
                name: $githubName,
                type: ProviderType::GitHub,
                url: 'https://api.github.com',
                apiToken: $_ENV['SEED_GITHUB_TOKEN'],
            );
            $manager->persist($github);
        }

        $manager->flush();
    }
}

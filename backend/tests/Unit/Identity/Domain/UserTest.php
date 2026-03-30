<?php

declare(strict_types=1);

use App\Identity\Domain\Model\User;

describe('User', function () {
    it('creates with default role', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        expect($user->getEmail())->toBe('test@example.com');
        expect($user->getPassword())->toBe('hashed');
        expect($user->getFirstName())->toBe('John');
        expect($user->getLastName())->toBe('Doe');
        expect($user->getAvatar())->toBeNull();
        expect($user->getRoles())->toContain('ROLE_USER');
        expect($user->getUserIdentifier())->toBe('test@example.com');
        expect($user->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($user->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates with custom avatar and roles', function () {
        $user = User::create(
            email: 'admin@example.com',
            hashedPassword: 'hashed',
            firstName: 'Admin',
            lastName: 'User',
            avatar: 'https://example.com/avatar.png',
            roles: ['ROLE_USER', 'ROLE_ADMIN'],
        );

        expect($user->getAvatar())->toBe('https://example.com/avatar.png');
        expect($user->getRoles())->toContain('ROLE_ADMIN');
        expect($user->getRoles())->toContain('ROLE_USER');
    });

    it('deduplicates ROLE_USER in getRoles', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
            roles: ['ROLE_USER', 'ROLE_ADMIN'],
        );

        $roles = $user->getRoles();
        expect(\count(\array_keys($roles, 'ROLE_USER', true)))->toBe(1);
        expect($roles)->toContain('ROLE_ADMIN');
    });

    it('updates partial fields', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $originalUpdatedAt = $user->getUpdatedAt();
        $user->update(firstName: 'Jane');

        expect($user->getFirstName())->toBe('Jane');
        expect($user->getLastName())->toBe('Doe');
        expect($user->getEmail())->toBe('test@example.com');
        expect($user->getAvatar())->toBeNull();
    });

    it('updates all fields', function () {
        $user = User::create(
            email: 'old@example.com',
            hashedPassword: 'hashed',
            firstName: 'Old',
            lastName: 'Name',
        );

        $user->update(
            firstName: 'New',
            lastName: 'Person',
            avatar: 'https://example.com/new.png',
            email: 'new@example.com',
        );

        expect($user->getFirstName())->toBe('New');
        expect($user->getLastName())->toBe('Person');
        expect($user->getAvatar())->toBe('https://example.com/new.png');
        expect($user->getEmail())->toBe('new@example.com');
    });

    it('updates password', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'old-hash',
            firstName: 'John',
            lastName: 'Doe',
        );

        $user->updatePassword('new-hash');

        expect($user->getPassword())->toBe('new-hash');
    });

    it('eraseCredentials does nothing', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $user->eraseCredentials();

        expect($user->getPassword())->toBe('hashed');
    });

    it('has a UUID v7 id', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        expect($user->getId())->toBeInstanceOf(\Symfony\Component\Uid\Uuid::class);
    });

    it('rejects blank first name', function () {
        expect(fn () => User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: '',
            lastName: 'Doe',
        ))->toThrow(\InvalidArgumentException::class, 'first name must not be blank');
    });

    it('rejects whitespace-only first name', function () {
        expect(fn () => User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: '   ',
            lastName: 'Doe',
        ))->toThrow(\InvalidArgumentException::class, 'first name must not be blank');
    });

    it('rejects blank last name', function () {
        expect(fn () => User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: '',
        ))->toThrow(\InvalidArgumentException::class, 'last name must not be blank');
    });

    it('rejects blank password', function () {
        expect(fn () => User::create(
            email: 'test@example.com',
            hashedPassword: '',
            firstName: 'John',
            lastName: 'Doe',
        ))->toThrow(\InvalidArgumentException::class, 'password must not be blank');
    });

    it('rejects roles without ROLE_USER', function () {
        expect(fn () => User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
            roles: ['ROLE_ADMIN'],
        ))->toThrow(\InvalidArgumentException::class, 'roles must contain at least ROLE_USER');
    });
});

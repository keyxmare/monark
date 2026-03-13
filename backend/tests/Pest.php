<?php

declare(strict_types=1);

if (\is_dir(__DIR__ . '/Integration')) {
    pest()
        ->extends(\Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::class)
        ->in('Integration');
}

if (\is_dir(__DIR__ . '/Functional')) {
    pest()
        ->extends(\Symfony\Bundle\FrameworkBundle\Test\WebTestCase::class)
        ->in('Functional');
}

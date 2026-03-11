<?php

declare(strict_types=1);

pest()
    ->extends(\Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::class)
    ->in('Integration');

pest()
    ->extends(\Symfony\Bundle\FrameworkBundle\Test\WebTestCase::class)
    ->in('Functional');

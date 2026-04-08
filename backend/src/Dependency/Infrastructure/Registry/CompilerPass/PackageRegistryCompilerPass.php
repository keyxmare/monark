<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Registry\CompilerPass;

use App\Dependency\Infrastructure\Registry\Attribute\AsPackageRegistry;
use App\Dependency\Infrastructure\Registry\PackageRegistryFactory;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PackageRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(PackageRegistryFactory::class)) {
            return;
        }

        $factoryDefinition = $container->getDefinition(PackageRegistryFactory::class);
        $adapters = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if ($class === null || !\str_starts_with($class, 'App\\')) {
                continue;
            }

            /** @var class-string $class */
            $reflectionClass = new ReflectionClass($class);

            $attributes = $reflectionClass->getAttributes(AsPackageRegistry::class);
            if ($attributes === []) {
                continue;
            }

            $adapters[] = new Reference($id);
        }

        $factoryDefinition->setArgument(0, $adapters);
    }
}

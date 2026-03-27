<?php
declare(strict_types=1);

namespace SuperKernel\Bootstrap\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SuperKernel\Annotator\AnnotationCollector;
use SuperKernel\Annotator\Factory\AnnotationCollectorFactory;
use SuperKernel\Annotator\Provider\AnnotationCollectorProvider;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\ClassLoader\Provider\ClassLoaderProvider;
use SuperKernel\ComposerResolver\Provider\ComposerJsonReaderProvider;
use SuperKernel\ComposerResolver\Provider\ComposerLockReaderProvider;
use SuperKernel\ComposerResolver\Provider\PackageCollectorProvider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definer\ObjectDefiner;
use SuperKernel\Di\Definer\ProviderDefiner;
use SuperKernel\Di\Factory\DefinitionFactory;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Resolver\ObjectResolver;
use SuperKernel\Di\Resolver\PropertyResolver;
use SuperKernel\PathResolver\PathResolver;
use SuperKernel\PathResolver\Provider\PathResolveAdapterProvider;
use SuperKernel\ProcessHandler\Provider\ProcessHandlerProvider;
use SuperKernel\Reflector\Provider\ReflectionCollectorProvider;
use Throwable;
use function array_merge;

#[
	Provider(ContainerInterface::class),
	Factory,
]
final class ContainerProvider
{
	private static array $attributes = [
		self::class,
		PathResolver::class,
		ObjectDefiner::class,
		MethodResolver::class,
		ObjectResolver::class,
		FactoryResolver::class,
		ProviderDefiner::class,
		PropertyResolver::class,
		DefinitionFactory::class,
		ClassLoaderProvider::class,
		ProcessHandlerProvider::class,
		PackageCollectorProvider::class,
		ComposerJsonReaderProvider::class,
		ComposerLockReaderProvider::class,
		PathResolveAdapterProvider::class,
		AnnotationCollectorProvider::class,
		ReflectionCollectorProvider::class,
	];

	/**
	 * @param PsrContainerInterface|null $container
	 *
	 * @return ContainerInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __invoke(?PsrContainerInterface $container = null): ContainerInterface
	{
		if (null === $container) {
			$reflectionCollector = new ReflectionCollectorProvider()();
			$annotationCollector = $this->getTransientAnnotationCollector($reflectionCollector);

			return new Container($annotationCollector, $reflectionCollector)->get(ContainerInterface::class);
		}

		$definitionFactory = $container->get(DefinitionFactoryInterface::class);
		$resolverFactory = $container->get(ResolverFactoryInterface::class);

		$annotationCollectorDefinition = $definitionFactory->getDefinition(AnnotationCollectorInterface::class);
		$annotationCollector = $resolverFactory->getResolver($annotationCollectorDefinition)->resolve($annotationCollectorDefinition);

		$reflectionCollectorDefinition = $definitionFactory->getDefinition(ReflectionCollectorInterface::class);
		$reflectionCollector = $resolverFactory->getResolver($reflectionCollectorDefinition)->resolve($reflectionCollectorDefinition);

		return new Container($annotationCollector, $reflectionCollector);
	}

	private function getTransientAnnotationCollector(ReflectionCollectorInterface $reflectionCollector): AnnotationCollectorInterface
	{
		$annotations = [];
		foreach (self::$attributes as $attribute) {
			try {
				$reflectClass = $reflectionCollector->reflectClass($attribute);
				$annotations = array_merge($annotations, AnnotationCollectorFactory::addAttribute($reflectClass));
				$annotations = array_merge($annotations, AnnotationCollectorFactory::addAttribute($reflectClass->getMethods()));
				$annotations = array_merge($annotations, AnnotationCollectorFactory::addAttribute($reflectClass->getProperties()));
				$annotations = array_merge($annotations, AnnotationCollectorFactory::addAttribute($reflectClass->getReflectionConstants()));
			}
			catch (Throwable $throwable) {
				throw new RuntimeException($throwable->getMessage(), $throwable->getCode(), $throwable);
			}
		}
		return new AnnotationCollector(...$annotations);
	}
}
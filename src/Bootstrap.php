<?php
declare(strict_types=1);

namespace SuperKernel\Bootstrap;

use SuperKernel\Bootstrap\Provider\ContainerProvider;
use SuperKernel\Context\ApplicationContext;
use SuperKernel\Contract\ApplicationInterface;

final class Bootstrap
{
	public static function run(): void
	{
		(static function (): void {
			$container = ApplicationContext::setContainer(new ContainerProvider()());

			$application = $container->get(ApplicationInterface::class);
			$application->run();
		})();
	}
}
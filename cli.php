<?php

require('vendor/autoload.php');

use Rose\Main;
use Rose\Ext\Wind;
use Rose\Arry;
use Rose\IO\Path;
use Rose\Text;
use Rose\Map;
use Rose\Gateway;

Main::cli(dirname(__FILE__));

$args = new Arry($argv);
if ($args->length < 2)
{
	echo "Use:\n";
	echo "    rose <fn-file> [arguments...]              Runs the specified file.\n";
	echo "    rose version|--version|-v                  Shows the rose-core and CLI versions.\n";
	echo "    rose list                                  Shows a list of all installed packages.\n";
	echo "    rose add <package-name>                    Installs a package using composer.\n";
	echo "    rose remove|rm <package-name>              Removes a package.\n";
	echo "    rose update|up [<package-name>]            Updates all packages or an specific package.\n";
	return;
}

try {

	switch($args->get(1))
	{
		case '--version':
		case '-v':
		case 'version':
			echo "\x1B[97mcore:\x1B[0m v".Main::version()."\n";
			echo "\x1B[97mcli:\x1B[0m v".(json_decode(file_get_contents(dirname(__FILE__).'/composer.json'))->version)."\n";
			break;

		case 'list':
			echo "Installed packages:\n\n";
			foreach (json_decode(file_get_contents(dirname(__FILE__).'/composer.lock'))->packages as $package)
				echo "    \x1B[97m".$package->name.":\x1B[0m \x1B[92mv".$package->version."\x1B[0m\n";

			break;

		case 'add':
			if (!$args->{2})
			{
				echo "\x1B[91mError:\x1B[0m " . 'Parameter <package-name> is missing.' . "\n";
				break;
			}

			Path::chdir(Path::fsroot());

			system ("composer --ansi require " . $args->get(2));
			break;

		case 'remove':
		case 'rm':
			if (!$args->{2})
			{
				echo "\x1B[91mError:\x1B[0m " . 'Parameter <package-name> is missing.' . "\n";
				break;
			}

			Path::chdir(Path::fsroot());

			system ("composer --ansi remove " . $args->get(2));
			break;

		case 'update':
		case 'up':
			Path::chdir(Path::fsroot());

			if (!$args->{2})
				system ("composer --ansi update");
			else
				system ("composer --ansi update " . $args->get(2));

			break;

		default:
			Rose\Ext\Wind::run($args->get(1), new Map ([ 'args' => $args->slice(2) ]));
			break;
	}
}
catch (Throwable $e) {
	echo "\x1B[91mError:\x1B[0m " . $e->getMessage() . "\n";
}

<?php

require(__DIR__.'/vendor/autoload.php');

use Rose\Main;
use Rose\Ext\Wind;
use Rose\Arry;
use Rose\IO\Path;
use Rose\IO\Directory;
use Rose\Text;
use Rose\Map;
use Rose\Gateway;
use Rose\Expr;

use Rose\Errors\FalseError;
use Rose\Ext\Wind\SubReturn;

function cli_error_handler ($errno, $message, $file, $line) {
    echo "\x1B[93mWarn (".$file." ".$line."):\x1B[0m " . $message . "\n";
}

Main::defs(true);

$dir = Path::resolve(Path::dirname('.'));
while ($dir)
{
    if (Path::exists(Path::append($dir, 'rcore'))) {
        Main::$CORE_DIR = Path::append($dir, 'rcore');
        break;
    }

    $n_dir = Path::dirname($dir);
    if (!$n_dir || $n_dir == $dir) $n_dir = null;
    $dir = $n_dir;
}

Main::cli(Path::dirname(__FILE__));

set_error_handler ('cli_error_handler', E_STRICT | E_USER_ERROR | E_WARNING | E_USER_WARNING);

$args = new Arry($argv);
if ($args->length < 2)
{
    echo "Use:\n";
    echo "    rose new [<target-folder>]                 Creates a new API project in the specified folder.\n";
    echo "\n";
    echo "    rose <fn-file> [arguments...]              Runs the specified file.\n";
    echo "    rose -i <string>                           Executes the given string immediately.\n";
    echo "    rose version|--version|-v                  Shows the rose-core and CLI versions.\n";
    echo "    rose list                                  Shows a list of all installed packages.\n";
    echo "    rose add <package-name>                    Installs a package using composer.\n";
    echo "    rose remove|rm <package-name>              Removes a package.\n";
    echo "    rose update|up [<package-name>]            Updates all packages or an specific package.\n";
    echo "    rose get <repo-url> [<mod-name>]           Installs a module from a repository.\n";
    echo "    rose del <mod-name>                        Removes a module.\n";
    echo "    rose :mod-name [args..]                    Executes a module.\n";
    echo "\n";
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

        case '-i':
            if (!$args->{2})
                throw new Error('Parameter <string> is missing.');

            try {
                Wind::$data = new Map();
                $response = Expr::eval($args->{2}, Wind::$data);
    
                if ($response != null)
                    Wind::reply ($response);
            }
            catch (SubReturn $e)
            {
                if (!Wind::$contentFlushed)
                    echo Wind::$response;
            }
            catch (FalseError $e) {
            }
    
            break;

        case 'list':
            echo "Installed packages:\n\n";
            foreach (json_decode(file_get_contents(dirname(__FILE__).'/composer.lock'))->packages as $package)
                echo "    \x1B[97m".$package->name.":\x1B[0m \x1B[92mv".$package->version."\x1B[0m\n";

            break;

        case 'add':
            if (!$args->{2})
                throw new Error('Parameter <package-name> is missing.');

            Path::chdir(Path::fsroot());

            system ("composer --ansi require " . $args->get(2));
            break;

        case 'remove':
        case 'rm':
            if (!$args->{2})
                throw new Error('Parameter <package-name> is missing.');

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

        case 'get':
            if (!$args->{2})
                throw new Error('Parameter <repo-url> is missing.');

            $tmp = Path::append(Path::fsroot(), 'tmp');

            $url = $args->{2};
            if (!Text::indexOf($url, '//'))
                $url = 'https://github.com/' . $url . '.git';

            Directory::remove($tmp, true);

            echo "\x1B[97mFetching repository ...\x1B[0m\n";
            system ("git clone " . $url . " " . $tmp, $code);
            if ($code) throw new Error('git clone exited with error ' . $code);

            echo "\n\n";

            if ($args->{3})
            {
                $dir = Path::append($tmp, $args->{3});

                if (!Path::exists($dir))
                    throw new Error('Directory ' . $args->{3} . ' not found');

                echo "\x1B[97mInstalling module `" . $args->{3} . "` ...\x1B[0m";
                Directory::copy ($dir, Path::append(Path::fsroot(), 'mods', $args->{3}), true, true);
            }
            else
            {
                foreach (Directory::readDirs ($tmp, false)->dirs->__nativeArray as $dir)
                {
                    if ($dir->name[0] === '.')
                        continue;

                    echo "\x1B[97mInstalling module `" . $dir->name . "` ...\x1B[0m";
                    Directory::copy ($dir->path, Path::append(Path::fsroot(), 'mods', $dir->name), true, true);
                }
            }

            Directory::remove($tmp, true);

            echo "\n\x1B[92mCompleted\x1B[0m\n";
            break;

        case 'del':
            if (!$args->{2})
                throw new Error('Parameter <mod-name> is missing.');

            $path = Path::append(Path::fsroot(), 'mods', $args->{2});

            if (!Path::exists($path))
                throw new Error ("Module \x1B[97m'" . $args->{2} . "'\x1B[0m not installed.");

            echo "\x1B[97mRemoving module `" . $args->{2} . "` ...";
            Directory::remove ($path, true);

            echo "\n\x1B[92mCompleted\x1B[0m\n";
            break;

        case 'new':
            if (!$args->{2}) $args->{2} = 'api';
            system('composer --ansi create-project rsthn/rose-api '.$args->get(2));
            break;
        
        default:

            $path = $args->get(1);

            if ($path[0] === ':')
            {
                $name = Text::substring($path, 1);

                $path = Path::append(Path::fsroot(), 'mods', $name);
                if (!Path::exists($path))
                    throw new Error ("Module \x1B[97m'" . $name . "'\x1B[0m not installed.");

                Rose\Ext\Wind::run($path.'/main.fn', new Map ([ 'args' => $args->slice(2)->unshift(Path::resolve($path.'/main.fn')) ]));
            }
            else
            {
                if (Text::endsWith($path, '.fn'))
                    Rose\Ext\Wind::run($path, new Map ([ 'args' => $args->slice(2)->unshift(Path::resolve($path)) ]));
                else
                    Rose\Ext\Wind::run($path.'.fn', new Map ([ 'args' => $args->slice(2)->unshift(Path::resolve($path.'.fn')) ]));
            }

            break;
    }
}
catch (Throwable $e) {
    echo "\x1B[91mError:\x1B[0m " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

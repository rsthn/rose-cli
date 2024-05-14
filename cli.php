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
Wind::$data = new Map();

set_error_handler ('cli_error_handler', E_STRICT | E_USER_ERROR | E_WARNING | E_USER_WARNING);

$args = new Arry($argv);
if ($args->length < 2)
{
    echo "\n";
    echo "  rose new [target-folder]                  Creates a new API project in the specified folder.\n";
    echo "\n";
    echo "  rose <fn-file> [args...]                  Runs the specified file.\n";
    echo "  rose -i <code>                            Executes the given code immediately.\n";
    echo "  rose version op target-version            Checks if the rose-core version matches the condition.\n";
    echo "  rose version                              Shows the rose-core and CLI versions.\n";
    echo "\n";
    echo "  rose ls                                   Shows a list of all installed packages.\n";
    echo "  rose add <package-name>                   Installs a package using composer.\n";
    echo "  rose rm <package-name>                    Removes a package.\n";
    echo "  rose up [package-name]                    Updates all packages or an specific package.\n";
    echo "\n";
    echo "  rose mod-pull [repo-path|repo-url]        Pulls a repository of CLI modules in the module cache.\n";
    echo "  rose mod-search                           Shows all available modules in the module cache.\n";
    echo "  rose mod-ls                               Shows a list of installed modules.\n";
    echo "  rose mod-add <mod-name>                   Installs a module.\n";
    echo "  rose mod-rm <mod-name>                    Removes a module.\n";
    echo "  rose :mod-name [args..]                   Executes a module with the specified parameters.\n";
    return;
}

try {

    switch($args->get(1))
    {
        /**
         * `version`
         * `version op value`
         */
        case 'version':
            if ($args->length > 2)
            {
                if (!$args->{3})
                    throw new Error("Parameter \e[97mtarget-version\e[0m is missing.");

                $tmp = "(def-fn version value ".
                         "(reduce val in (split '.' (value)) ".
                            "(+ (* 1000 (s)) (val))".
                         ")".
                       ")".
                       "(".$args->get(2)." '".Main::version()."' '".$args->get(3)."')"
                    ;

                if (!Expr::eval($tmp, Wind::$data))
                    throw new Error("Version check failed: \e[97m" . Main::version() . " " . $args->get(2) . " " . $args->get(3) . "\e[0m");
                break;
            }

            echo "\n  \e[90mcore:\e[0m \e[97mv".Main::version() . "\e[0m";
            echo "\n  \e[90mcli:\e[0m v".(json_decode(file_get_contents(dirname(__FILE__).'/composer.json'))->version) . "\e[0m";
            break;

        /**
         * `-i <code>`
         */
        case '-i':
            if (!$args->{2})
                throw new Error("Parameter \e[97mcode\e[0m is missing.");

            try {
                $response = Expr::eval($args->{2}, Wind::$data);
                if ($response != null)
                    Wind::reply ($response);
            }
            catch (SubReturn $e) {
                if (!Wind::$contentFlushed)
                    echo Wind::$response;
            }
            catch (FalseError $e) {
            }
            break;

        /**
         * `ls`
         */
        case 'ls':
            foreach (json_decode(file_get_contents(dirname(__FILE__).'/composer.lock'))->packages as $package)
                echo "\n  \e[90m".$package->name.":\e[0m \e[97m".$package->version."\e[0m";
            break;

        /**
         * `add <package-name>`
         */
        case 'add':
            if (!$args->{2})
                throw new Error("Parameter \e[97mpackage-name\e[0m is missing.");

            Path::chdir(Path::fsroot());
            system ("composer --ansi require " . $args->get(2));
            break;

        /**
         * `rm <package-name>`
         */
        case 'rm':
            if (!$args->{2})
                throw new Error("Parameter \e[97mpackage-name\e[0m is missing.");

            Path::chdir(Path::fsroot());
            system ("composer --ansi remove " . $args->get(2));
            break;

        /**
         * `up [package-name]`
         */
        case 'up':
            Path::chdir(Path::fsroot());
            if (!!$args->{2})
                system ("composer --ansi update " . $args->get(2));
            else
                system ("composer --ansi update");
            break;

        /**
         * `mod-ls`
         */
        case 'mod-ls':
            $tmp = Path::append(Path::fsroot(), 'mods');
            if (!Path::exists($tmp)) break;

            foreach (Directory::readDirs($tmp, false)->dirs->__nativeArray as $dir)
                echo "\n  \e[90m".$dir->name.":\e[0m \e[97m". json_decode(file_get_contents(Path::append($dir->path, 'meta.json')))->version . "\e[0m";
            break;

        /**
         * `mod-pull [repo-url]`
         */
        case 'mod-pull':
            $url = $args->{2} ?? 'rsthn/rose-cli-mods';
            if (!Text::indexOf($url, '//'))
                $url = 'https://github.com/' . $url . '.git';

            $tmp = Path::append(Path::fsroot(), 'mod-cache', md5($url));
            if (Path::exists($tmp)) Directory::remove($tmp, true);

            system("git clone " . $url . " " . $tmp, $code);
            if ($code) throw new Error('git clone exited with error ' . $code);

            echo "\n\e[92mCompleted\e[0m\n";
            break;

        /**
         * `mod-search`
         */
        case 'mod-search':
            $regex = $args->{2};

            $tmp = Path::append(Path::fsroot(), 'mod-cache');
            if (!Path::exists($tmp))
                throw new Error("Module cache not found, run \e[96mmod-pull\e[0m first.");

            foreach (Directory::readDirs ($tmp, false)->dirs->__nativeArray as $dir)
            {
                if ($dir->name[0] === '.') continue;

                foreach (Directory::readDirs($dir->path, false)->dirs->__nativeArray as $mod_dir)
                {
                    if ($mod_dir->name[0] === '.') continue;

                    $meta_path = Path::append($mod_dir->path, 'meta.json');
                    if (!Path::exists($meta_path)) continue;

                    $meta = json_decode(file_get_contents($meta_path), true);
                    echo "\n  \e[90m" . $mod_dir->name . ":\e[0m \e[97m" . $meta['version'] . "\e[0m";
                }
            }

            break;

        /**
         * `mod-add <mod-name|*> [repo-url]`
         */
        case 'mod-add':
            if (!$args->{2})
                throw new Error("Parameter \e[97mmod-name\e[0m is missing.");

            $url = $args->{3} ?? 'rsthn/rose-cli-mods';
            if (!Text::indexOf($url, '//'))
                $url = 'https://github.com/' . $url . '.git';

            $tmp = Path::append(Path::fsroot(), 'mod-cache', md5($url));
            if (!Path::exists($tmp))
                throw new Error("Repository \e[97m" . $url . "\e[0m not in the module cache, run \e[96mmod-pull\e[0m first.");
    
            if ($args->get(2) !== '*')
            {
                $dir = Path::append($tmp, $args->get(2));
                if (!Path::exists($dir) || !Path::exists(Path::append($dir, 'meta.json')))
                    throw new Error("Module source folder \e[97m" . $args->get(2) . "\e[0m not found");

                $meta = json_decode(file_get_contents(Path::append($dir, 'meta.json')), true);
                if ($meta['preinstall'])
                {
                    foreach ($meta['preinstall'] as $cmd) {
                        system($cmd, $code);
                        if ($code)
                            throw new Error("Failed with error code " . $code);
                    }
                }

                echo "\nAdding module \e[96m" . $args->get(2) . "\e[0m ...";
                Directory::copy ($dir, Path::append(Path::fsroot(), 'mods', $args->get(2)), true, true);
            }
            else
            {
                foreach (Directory::readDirs ($tmp, false)->dirs->__nativeArray as $dir)
                {
                    if ($dir->name[0] === '.') continue;
                    if (!Path::exists(Path::append($dir->path, 'meta.json'))) continue;

                    $meta = json_decode(file_get_contents(Path::append($dir->path, 'meta.json')), true);
                    if ($meta['preinstall'])
                    {
                        foreach ($meta['preinstall'] as $cmd) {
                            system($cmd, $code);
                            if ($code)
                                throw new Error("Failed with error code " . $code);
                        }
                    }
    
                    echo "\nAdding module \e[96m" . $dir->name . "\e[0m ...";
                    Directory::copy ($dir->path, Path::append(Path::fsroot(), 'mods', $dir->name), true, true);
                }
            }

            echo "\nDone.";
            break;

        /**
         * `mod-rm <mod-name>`
         */
        case 'mod-rm':
            if (!$args->{2})
                throw new Error("Parameter \e[97mmod-name\e[0m is missing.");

            $path = Path::append(Path::fsroot(), 'mods', $args->{2});
            if (!Path::exists($path))
                throw new Error ("Module \e[97m'" . $args->{2} . "'\e[0m not installed.");

            echo "Removing module \e[97m" . $args->{2} . "\e[0m ...";
            Directory::remove ($path, true);
            echo "\n\e[92mCompleted\e[0m\n";
            break;

        /**
         * `new [target-folder]`
         * Default target-folder is "api"
         */
        case 'new':
            if (!$args->{2}) $args->{2} = 'api';
            system('composer --ansi create-project rsthn/rose-api '.$args->get(2));
            break;
        
        default:
            $path = $args->get(1);
            if ($path[0] === ':') {
                $name = Text::substring($path, 1);
                $path = Path::append(Path::fsroot(), 'mods', $name);
                if (!Path::exists($path))
                    throw new Error ("Module \e[97m'" . $name . "'\e[0m not installed.");
                Rose\Ext\Wind::run($path.'/main.fn', new Map ([ 'args' => $args->slice(2)->unshift(Path::resolve($path.'/main.fn')) ]));
            }
            else {
                if (Text::endsWith($path, '.fn'))
                    Rose\Ext\Wind::run($path, new Map ([ 'args' => $args->slice(2)->unshift(Path::resolve($path)) ]));
                else
                    Rose\Ext\Wind::run($path.'.fn', new Map ([ 'args' => $args->slice(2)->unshift(Path::resolve($path.'.fn')) ]));
            }
            break;
    }
}
catch (Throwable $e) {
    echo "\e[91mError:\e[0m " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

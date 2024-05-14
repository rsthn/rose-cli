# Rose CLI Utility

This repository contains a composer project to setup a rose-core command line interface to run your `.fn` files, create projects, and more stuff! Right from the comfort of your terminal.

## Installation

First and foremost, ensure you have PHP 8.1+ and Composer 2+ installed in your system and that those are reachable via PATH.

Open a terminal and navigate to the folder where you want `rose-cli` to be installed and execute the following commands:
```sh
composer create-project rsthn/rose-cli
cd rose-cli
composer update
./install
```

After that, the command `rose` should be globally accessible.

## Removal

Automatic removal is currently not supported, but to remove it manually simply execute `where rose` / `whereis rose` / `which rose` to locate the `rose.sh` or `rose.bat` file that was created in the PHP folder and remove it. Then remove the entire `rose-cli` folder and that is all.

## Commant Line Interface

<br/>

Create a new API project in the specified folder.
```sh
rose new [target-folder]
```

Runs the specified file.
```sh
rose example.fn [args...]
```

Executes the given code immediately.
```sh
rose -i "(echo 'Hello')"
```

Throws an error if the operator returns `false` when evaluated with the rose-core version.
```sh
rose version ge? 1.0.0
```

Shows the rose-core and CLI versions.
```sh
rose version
```

Shows a list of all installed packages.
```sh
rose ls
```

Installs a package using composer.
```sh
rose add <package-name>
```

Removes a package.
```sh
rose rm <package-name>
```

Updates all packages or an specific package.
```sh
rose up [package-name]
```

Pulls a repository of CLI modules in the module cache. If no repository is specified, the default one ([rose-cli-mods](https://github.com/rsthn/rose-cli-mods)) will be used
```sh
rose mod-pull [repo-path|repo-url]
```

Shows all available modules in the module cache.
```sh
rose mod-search
```

Shows a list of installed modules.
```sh
rose mod-ls
```

Installs a module.
```sh
rose mod-add <mod-name>
```

Removes a module.
```sh
rose mod-rm <mod-name>
```

Executes a module with the specified parameters.
```sh
rose :mod-name [args..]
```

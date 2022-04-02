# Rose CLI Utility

Welcome! This repository contains the project to setup a rose-core command line interface to run your `.fn` files right from your console.

<br/>

## Installation

First and foremost, ensure you have PHP 8.1+ and Composer 2+ installed in your system and that those are reachable via PATH.

Open a console and navigate to the folder where you want `rose-cli` to be installed and execute the following commands:
```sh
composer create-project rsthn/rose-cli
cd rose-cli
php cli.php install.fn
```

After that, the command `rose` should be globally accessible.

<br/>

## Removal

Automatic removal is currently not supported, but to remove it manually simply execute `where rose` or `whereis rose` to locate the `rose.sh` or `rose.bat` file that was created in the PHP folder and remove it. Then remove the entire `rose-cli` folder and that is all.

<br/>

## Commant Line Interface

<br/>

Runs the specified file. Arguments are passed via the global variable. `args`.
```sh
rose <fn-file> [arguments...]
```

Shows the rose-core and CLI versions.
```sh
rose version
rose --version
rose -v
```

Shows a list of all installed packages.
```sh
rose list
```

Installs a package using composer.
```sh
rose add <package-name>
```

Removes a package.
```sh
rose remove <package-name>
rose rm <package-name>
```

Updates all installed packages.
```sh
rose update
rose up
```

Updates an specific package.
```sh
rose update <package-name>
rose up <package-name>
```

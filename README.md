Check24ShellCommandBundle
=========================

[![Build Status](https://travis-ci.org/CHECK24/Check24ShellCommandBundle.svg?branch=master)](https://travis-ci.org/CHECK24/Check24ShellCommandBundle)

Symfony bundle for the [shell command library](https://github.com/sp4ceb4r/shell-command)

Usage
-----

### Configuration

```yaml
check24_shell_command:
    commands:
        wget:
            name: 'wget'
            args:
                url: '%base_url%${file_name}.gz'
            options:
                - '--no-verbose'
                - '-O-'
                - '--quiet'
        md5:
            name: 'md5sum'
        gunzip:
            name: 'gunzip'
            options:
                - ['-c']
            output:
                path: '%kernel.project_dir%/var/${file_name}'
    pipes:
        download-unzip:
          - ['wget', 'md5']
          - ['gunzip']
```

### Simple command

```php
$command = $container->get('check24_shell_command.commands.wget');
$process = new \Shell\Process($command);
$process->runAsync();
$process->wait();
```

### Pipe

```php
$pipe = $container->get('check24_shell_command.pipes.download_unzip');
$pipe->addParameter('file_name', $filename);
$outputs = $pipe->exec();
$md5Sum = substr($outputs['md5'][1], 0, 32);
```

Installation
------------

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require check24/shell-command-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require check24/shell-command-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \Check24\ShellCommandBundle\Check24ShellCommandBundle(),
        );

        // ...
    }

    // ...
}
```

-------------------------------------------------------------------
## Build-in commands
### !!! Important: Before using this commands, be sure that these commands are installed in your dev-box.

```yaml
wget_streaming:
    name: 'wget'
    args:
        url: '${url}'
    options:
        - '--no-verbose'
        - '-O-'
        - '--dns-timeout=10'
        - '--connect-timeout=10'
        - '--read-timeout=180'
        - '--no-check-certificate'
        - '--tries=1'
        - '--waitretry=1'
        - '--user=${username}'
        - '--password=${password}'
        - '--header="accept-encoding:gzip"'
        - '--header="User-Agent: Check24/S2/COP-MOI"'
        - '--quiet'

sponge:
    name: 'sponge'

backup:
    name: 'gzip'
    options:
        - '-n'
    output:
        path: 'path' => '%backup_tmp_destination%/${backupName}.csv.gz'
        
zgrep:
    name: 'zgrep'
    options:
        - '--binary-files=text'
    args:
        - '""'
    expectedExitCodes:
        - 0
        - 1
        - 2

funzip:
    name: 'funzip'

remove_empty_lines:
    name: 'awk'
    args:
        - '"NR==1{sub(/^\xef\xbb\xbf/,\"\")}NF" RS="\r|\n|\r\n"'

head:
    name: 'head'
    options:
        - '-n 11'
```
# Filename Hashing Revisions & Automatic Cleanup

[![Continuous Integration](https://github.com/netglue/revs/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/netglue/revs/actions/workflows/continuous-integration.yml)

Probably re-inventing the wheel here, but I wanted to be able to rev front-end resource file names in a predictable way with options to automatically delete old revisions keeping either none of them or a specific number of the most recent ones. There are javascript tools that do this but at the time of writing, I couldn't find anything I liked in JS land that you could just fire off from an npm package script easily. There's plenty for Grunt and Gulp if you're using those.

The main use-case for this lib is to be triggered by a script in your `package.json` something like this:
```json
{
    "scripts": {
        "css:rev": "vendor/bin/rev netglue:rev -s \"build/css/*.css\" -t \"public/assets/css\" -d -c 1 -r \"module/layouts/*.phtml\""
    }
}
```

## Install with composer:
```bash
composer require netglue/revs
```
In order to use the tools, make sure that you've `require`d composers `vendor/autoload.php`, or [use the shipped binary](#symfony-command) in `vendor/bin/rev`

## Operation

Say you have a file in `/my/frontend/whatever.css`, and you want a hashed filename in `/my/public/assets/{hashed-file-name}.css`, you'd do something like this:

```php
use Netglue\Revs\Revver;
use Netglue\Revs\RevverOptions;
use Netglue\Revs\RevvedFile;

$options = new RevverOptions();
$options->setDestinationDirectory('/my/public/assets');
$revver = new Revver($options);
/** @var RevvedFile $result */
$result = $rever->revFile('/my/frontend/whatever.css');
var_dump($result->destination()); // Yields the full path and filename of the copied file.
```

The options object can be fed an array like this:
```php
use Netglue\Revs\RevverOptions;
$options = RevverOptions::fromArray([
    'destinationDirectory' => '/my/public/assets',
    'clean_up' => true,
    'revision_count' => 10,
]);
```

By default, no old revisions will be deleted, so the revision count parameter is irrelevant. You must explicitly enable it.

## Resulting File Names

Yeah, they’re pretty long because they are composed of `original-filename-{md5 hash}-{UUID1}.extension` - the file names include a time-based UUID so that an accurate time can be computed for the time the file was generated, hence being able to sort them and make sure we're deleting the oldest revisions when appropriate. It might make sense to get rid of the md5 hash, but that's what's used to determine whether the file has actually changed or not. If the hash hasn't changed between operations, the process is halted.

## Removing old versions

Specifying a 'revision count' in options and setting cleanup to true will keep that many old versions in the target directory. So with a revision count of 1, you'd have the current and next most recent versions.

The return value of `Revver::revFile()` can tell you the paths of any deleted files if you're interested.

## Replacement in Files…

Assuming you have some file that needs references to these 'revved' file names updating, you can use the return value of `\Netglue\Revs\Revver::revFile()` to perform replacement on a string, or in another file on disk, for example:

```php
use Netglue\Revs\Revver;
use Netglue\Revs\RevvedFile;
use Netglue\Revs\Replacer;

/** @var RevvedFile $result */
$result = $rever->revFile('/my/frontend/whatever.css');
$replacementCount = Replacer::replaceInFile('/my/layout.html', $result);
// …or…
$resultingString = Replacer::replaceInString($someString, $result, $count);
// $count is an int with the number of replacements
```

## Putting it together

Assuming you have somehow received notification that your JS is built to a file located in `/build/index.js` and you want that copied to `/public/assets` with a revved file name and all the html files in `/public` to be updated with the new file name:

```php

$options = RevverOptions::fromArray([
    'destinationDirectory' => '/public/assets',
    'cleanUp' => true,
    'revisionCount' => 1,
]);
$revver = new Revver($options);
$info = $revver->revFile('/build/index.js');
printf(
    "New revision of %s copied to %s. %d old revisions deleted\n",
     basename($info->source()),
     dirname($info->destination()),
     count($info->deletedRevisions())
);
$count = 0;
$files = glob('/public/*.html');
foreach ($files as $html) {
    $count += Replacer::replaceInFile(sprintf('/public/%s', $html), $info);
}
printf('Replaced %d references over %d files', $count, count($files));

```

## Symfony Command

Composer will create a binary in `vendor/bin/rev` after installation which you can call from build scripts. There’s straightforward help in the command itself so just issue `vendor/bin/rev netglue:rev --help` to get an overview of the options.

## Test

`cd` to wherever the module is installed, issue a `composer install` followed by a `composer check`.

## Contributions

PR's are welcomed. Please write tests for new features.

## Support

You're welcome to file issues, but please understand that finding the time to answer support requests is very limited
so there might be a long wait for an answer.


## About

[Netglue makes websites and apps in Devon, England](https://netglue.uk).
We hope this is useful to you and we’d appreciate feedback either way :)

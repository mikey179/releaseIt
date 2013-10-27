# ReleaseIt

A tool to create releases of Composer packages from checkout. It supports both
Git and SVN repositories.

What it does: determining what the version number of the release should be and
create the according tag in Git or SVN.

What it doesn't do: ensure the release is listed on packagist.org or any other
Composer repository. It is assumed there are mechanisms in place which automatically
detect new version tags and add them to the according repository.


## Usage

1. cd into the directory with a checkout of the composer package you want to release
1. ensure there are no uncommitted changes (ReleaseIt will deny creating a release then)
1. type `releaseIt` and follow instructions

Done! You created a new release of your composer package. Congratulations!


## Determing the version for the release

To determine the version of the next release there are several possibilies:

### Retrieve next version by branch-alias

Composer allows to define the `branch-alias` for the current branch:

```json
"extra": {
    "branch-alias": {
        "dev-master": "0.2.x-dev"
    },
}
```

ReleaseIt will check the branch of the current checkout and look it up in the
list of branch-alias definitions. If it finds an entry it checks for the last
release in the series. For the example above, if no tag which starts with `v0.2`
exists it will assume no release in this series has been done and suggest using
`v0.2.0` as version number for the release you are about to create.

In case a release (i.e., a tag) in this series already exists it will take the
highest version and increment the patch level. For example, if the highest release
is tagged with `v0.2.3` it will suggest using `v0.2.4` as version number for the
release you are about to create.

In case no `branch-alias` is defined for the current branch, or you deny using
the suggested version number it will fall back to simply ask you for the next
version.


### Asking for the next version

If none of the above ways yielded a version number for the release you will be
asked to enter the version number you want to use for this release. To give you
a little help ReleaseIt will display the last five releases.


## Installation

### As a phar (recommended)

Download a ready-to-use version:

```bash
$ wget http://releaseit.bovigo.org/releaseIt.phar
$ chmod +x releaseIt.phar
```

We recommend moving the file into a directory which is in `$PATH`.

### Manual Installation from Source

This project requires PHP 5.3+ and Composer:

```bash
$ git clone https://github.com/mikey179/releaseIt.git
$ cd releaseIt
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```



# PHP Pathinfo

[![build](https://github.com/timostamm/pathinfo/workflows/CI/badge.svg)](https://github.com/timostamm/pathinfo/actions?query=workflow:"CI")
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/timostamm/pathinfo/php)
[![GitHub tag](https://img.shields.io/github/tag/timostamm/pathinfo?include_prereleases=&sort=semver&color=blue)](https://github.com/timostamm/pathinfo/releases/)
[![License](https://img.shields.io/badge/License-MIT-blue)](#license)

An object to manipulate filesystem paths with a fluid interface.


#### Example

```PHP

$root = Path::info('/var/wwwroot/index.html');

$root->equals('/foo/../var/root/index.html'); // -> true
$root->isIn('/var/wwwroot/'); // -> true
$root->isAbsolute(); // -> true

$root->dirname(); // -> "/var/wwwroot/"
$root->filename(); // -> "index.html"
$root->basename(); // -> "index"
$root->extension(); // -> "html"

Path::info('../log.txt')
	->abs($root)
	->get(); // -> "/var/log.txt"

Path::info('/foo/../var/root/index.html')
	->normalize()
	->get(); // -> "/var/wwwroot/index.html"

```

# PHP Pathinfo

[![Build Status](https://api.travis-ci.org/timostamm/pathinfo.png)](https://travis-ci.org/timostamm/pathinfo)

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

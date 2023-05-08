<?php

namespace TS\Filesystem;


use PHPUnit\Framework\TestCase;



class PathTest extends TestCase
{

	public function testRelativeTo()
	{
		$tests = [
			['/var/wwwroot/js/script.js', '/var/wwwroot/', 'js/script.js'],
			['/dir-a/file', '/dir-b/', '../dir-a/file'],
			['/dir/', '/dir/', './'],
			['/dir/.', '/dir/', './'],
			['/dir/', '/dir/./', './'],
			['/dir/file', '/dir/', 'file'],
			['/file', '/', 'file'],
			['/a/dir/file', '/a/', 'dir/file'],
			['/a/dir/dir2/', '/a/', 'dir/dir2/'],
			['/a/file', '/a/dir/foo/', '../../file'],
			[ '/foo/bar/', '/foo/bar/baz/', '../' ],
			['/a/b/file', '/a/b/', 'file'],
			['/a/b/file', '/a/b', 'file'],
			['/a/dir/', '/a/', 'dir/'],
			['/a/', '/a/b', '../'],
			['/a/b', '/a/', 'b'],
		];

		foreach ($tests as $test) {
			list($path, $start, $expected) = $test;

			$relative = Path::info($path)->relativeTo($start);

			$this->assertEquals($expected, $relative->__toString(), sprintf('Wrong relative path %s (%s relative to dir %s).', $relative, $path, $start));

			$resolved = Path::info($start)->resolve($relative);
			$ok = $resolved->equals( $path );
			$this->assertTrue($ok, sprintf('Expected relative path %s (%s relative to dir %s) to be original path again after joining with %s again, but got: %s ', $relative, $path, $start, $start, $resolved));
		}
	}

	public function testRelativeTo_cwd()
	{
		$this->assertEquals('../../../assets/img/ping.png', Path::info('assets/img/ping.png')->relativeTo('site/foo/bar/', '/var/www/'));
		$this->assertEquals('ping.png', Path::info('assets/img/ping.png')->relativeTo('assets/img', '/var/www/')->get());
		$this->assertEquals('js/script.js', Path::info('assets/js/script.js')->relativeTo('assets', '/var/wwwroot/')->get());
		$this->assertEquals('js/script.js', Path::info('/var/wwwroot/js/script.js')->relativeTo('/var/wwwroot/.')->get());
		$this->assertEquals('js/script.js', Path::info('js/script.js')->relativeTo('.', '/var/wwwroot/')->get());
	}

	public function testIsIn()
	{
		$tests = [
			['/var/wwwroot/index.html', '/var/wwwroot/', true],
			['/var/../var/wwwroot/foo/../index.html', '/var/wwwroot/', true],
			['/var/wwwroot/index.html', '/usr/bin/', false],
		];
		foreach ($tests as $test) {
			list($path, $dir, $expected) = $test;
			$this->assertEquals($expected, Path::info($path)->isIn($dir));
		}
	}

	public function testIsIn_cwd()
	{
		$this->assertTrue(Path::info('assets/js/script.js')->isIn('assets/', '/wwwroot/'));
		$this->assertFalse(Path::info('/assets/js/script.js')->isIn('assets/', '/wwwroot/'));
	}


	public function testAbs()
	{
		$this->assertEquals('/wwwroot/', Path::info('.')->abs('/wwwroot')->normalize());
		$this->assertEquals('/wwwroot/img/ping.png', Path::info('img/ping.png')->abs('/wwwroot'));
		$this->assertEquals('/wwwroot/img/ping.png', Path::info('img/ping.png')->abs('/wwwroot/'));
		$this->assertEquals('/wwwroot/img/', Path::info('img/')->abs('/wwwroot/'));
		$this->assertEquals('/wwwroot/index.html', Path::info('index.html')->abs('/wwwroot/'));
		$this->assertEquals('/wwwroot/index.html', Path::info('index.html')->abs('/wwwroot'));
		$this->assertEquals('/var/ping.png', Path::info('/var/ping.png')->abs('/wwwroot'));
	}


	public function testNormalize()
	{
		$tests = [
			'/a/../' => '/',
			'a/b/c/../../b2' => 'a/b2',
			'a/.' => 'a/',
			'a/./x/..' => 'a/',
			'/.' => '/',
			'.' => './',
			'/./' => '/',
			'dir/..' => '', // XXX should './'
		    'dir/../' => '', // XXX should './'
			'/../' => '/../',
		];
		foreach ($tests as $test => $expected) {
			$p = new Path($test);
			$this->assertEquals($expected, $p->normalize()
				->get());
		}
	}


	public function testEquals()
	{
		$tests = [
			'/a/../' => '/b/..',
			'a/b/c/../../b2' => 'a/b2',
		];
		foreach ($tests as $a => $b) {
			$equal = Path::info($a)->equals($b);
			$this->assertTrue($equal);
		}
	}

	public function testResolve()
	{
		$tests = [
			['/a/', 'b', '/a/b'],
			['/a', 'b', '/a/b'],
			['/a/', '/b', '/b'],
			['', 'b', 'b'],
			['a/', '', 'a/'],
			['a', '', 'a/'],
			['dir/', '..', 'dir/..'],
			['dir', '..', 'dir/..'],
			[ '/foo/bar', './baz', '/foo/bar/./baz' ],
		];
		foreach ($tests as $test) {
			list($a, $b, $expected) = $test;
			$this->assertEquals($expected, Path::info($a)->resolve($b)->get());
		}
	}


	public function testIsAbsolute()
	{
		$tests = [
			'/a/../' => true,
			'a/.' => false,
			'a/./x/..' => false,
			'/.' => true,
			'/./' => true,
			'' => false,
			'php://' => true,
			'php:///' => true
		];
		foreach ($tests as $test => $expected) {
			$this->assertEquals($expected, Path::info($test)->isAbsolute(), $test);
		}
	}

	public function testIsEmpty()
	{
		$tests = [
			'/' => false,
			'.' => false,
			'' => true,
			'php://' => true
		];
		foreach ($tests as $test => $expected) {
			$this->assertEquals($expected, Path::info($test)->isEmpty(), $test);
		}
	}

	public function testFilename()
	{
		$tests = [
			'/' => '',
			'/a/../' => '',
			'/a/..' => '',
			'a/.' => '',
			'a/b' => 'b',
			'a' => 'a',
			'' => ''
		];
		foreach ($tests as $test => $expected) {
			$this->assertEquals($expected, Path::info($test)->filename());
		}
	}

	public function testBasename()
	{
		$tests = [
			'/a/../' => '',
			'/a/..' => '',
			'a/.' => '',
			'/' => '',
			'a/b.e' => 'b',
			'a/b.e/..' => '',
			'a.e' => 'a'
		];
		foreach ($tests as $test => $expected) {
			$this->assertEquals($expected, Path::info($test)->basename());
		}
	}

	public function testExtension()
	{
		$tests = [
			'/a/../' => '',
			'/a/..' => '',
			'a/.' => '',
			'/' => '',
			'a/b.php.inc' => 'inc',
			'a/b.php.inc/..' => '',
			'a.php.inc' => 'inc'
		];
		foreach ($tests as $test => $expected) {
			$this->assertEquals($expected, Path::info($test)->extension());
		}
	}

	public function testDir()
	{
		$tests = [
			'dir/file' => 'dir/',
			'/file' => '/',
			'file' => '',
			'/a/../' => '/a/../',
			'/a/..' => '/a/../',
			'a/.' => 'a/./',
			'.' => './',
			'..' => '../',
			'/' => '/',
			'/var/wwwroot/.' => '/var/wwwroot/./',
			'/var/wwwroot/./' => '/var/wwwroot/./',
			'a/b' => 'a/',
			'a' => '',
			'' => '',
			'a/../a1' => 'a/../'
		];
		foreach ($tests as $test => $expected) {
			$p = new Path($test);
			$this->assertEquals($expected, $p->dirname(), $test);
			$this->assertEquals($expected, $p->dir()
				->get(), $test);
		}
	}

	public function testParent()
	{
		$tests = [
			'dir/file' => 'dir/../',
			'dir/dir/' => 'dir/dir/../',
			'/' => '/../',
			'dir/' => 'dir/../'
		];
		foreach ($tests as $test => $expected) {
			$this->assertEquals($expected, Path::info($test)->parent()
				->get(), $test);
		}
	}
}


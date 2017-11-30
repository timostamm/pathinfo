<?php

namespace TS\Filesystem;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
class Path
{

	private $parts = [];

	private $partCount = 0;

	private $lastPart = null;

	private $firstPart = null;

	public static function info($path)
	{
		if ($path instanceof Path) {
			return $path;
		}
		if (is_string($path)) {
			return new Path($path);
		}
		if (is_object($path) && method_exists($path, '__toString')) {
			return new Path(strval($path));
		}
		throw new \InvalidArgumentException();
	}

	/**
	 * Tests whether the path is within the given directory.
	 *
	 * @param string|Path $directory
	 * @param string|Path|NULL $cwd
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function isIn($directory, $cwd = null)
	{
		$dir = Path::info($directory);
		if ($dir->isEmpty()) {
			throw new \InvalidArgumentException('Directory is empty.');
		}
		$dir = $dir->normalize()->assumeDirectory();
		$self = $this->normalize();
		
		if (! $dir->isAbsolute() && is_null($cwd)) {
			throw new \InvalidArgumentException(sprintf('Directory "%s" is not absolute, you have to provide a working directory.', $directory));
		}
		if (! $self->isAbsolute() && is_null($cwd)) {
			throw new \InvalidArgumentException(sprintf('Path "%s" is not absolute, you have to provide a working directory.', $this));
		}
		if (! is_null($cwd)) {
			$cwd = Path::info($cwd)->normalize()->assumeDirectory();
			if (! $cwd->isAbsolute()) {
				throw new \InvalidArgumentException(sprintf('Working directory "%s" must be absolute.', $cwd));
			}
			if ($cwd->isEmpty()) {
				throw new \InvalidArgumentException('Working directory is empty.');
			}
			$dir = $dir->abs($cwd);
			$self = $self->abs($cwd);
		}
		
		if (strpos($self->__toString(), $dir->__toString()) === 0) {
			return true;
		}
		return false;
	}

	/**
	 * Gets the relative path to the given directory.
	 *
	 * Example:
	 * new Path('/var/wwwroot/js/script.js')->relativeTo('/var/wwwroot/') => js/script.js
	 *
	 * If you provide a working directory, you can use relative paths.
	 * new Path('js/script.js')->relativeTo('.', '/var/wwwroot/') => js/script.js
	 *
	 * If the current path is a directory, it must end with a slash.
	 *
	 *
	 * @param string|Path $directory
	 * @param string|Path|NULL $cwd
	 * @throws \InvalidArgumentException
	 * @return static a new Path object
	 */
	public function relativeTo($directory, $cwd = null)
	{
		$dir = Path::info($directory);
		if ($dir->isEmpty()) {
			throw new \InvalidArgumentException('Directory is empty.');
		}
		$dir = $dir->normalize()->assumeDirectory();
		$self = $this->normalize()->dir();
		
		if (! $dir->isAbsolute() && is_null($cwd)) {
			throw new \InvalidArgumentException(sprintf('Directory "%s" is not absolute, you have to provide a working directory.', $directory));
		}
		if (! $self->isAbsolute() && is_null($cwd)) {
			throw new \InvalidArgumentException(sprintf('Path "%s" is not absolute, you have to provide a working directory.', $this));
		}
		if (! is_null($cwd)) {
			$cwd = Path::info($cwd)->normalize()->assumeDirectory();
			if (! $cwd->isAbsolute()) {
				throw new \InvalidArgumentException(sprintf('Working directory "%s" must be absolute.', $cwd));
			}
			if ($cwd->isEmpty()) {
				throw new \InvalidArgumentException('Working directory is empty.');
			}
			$dir = $dir->abs($cwd)->normalize();
			$self = $self->abs($cwd)->normalize();
		}
		
		$i = 0;
		while ($i < $dir->partCount && $i < $self->partCount && $dir->parts[$i] === $self->parts[$i]) {
			$i ++;
		}
		$remainder = new Path('');
		$remainder->setParts(array_slice($self->parts, $i));
		$outsideDepth = $dir->partCount - $i - 1;
		if ($outsideDepth > 0) {
			$traversal = array_fill(0, $outsideDepth, '..');
			$remainder->setParts(array_merge($traversal, $remainder->parts));
		}
		$result = $remainder->resolve($this->filename());
		return $result->isEmpty() ? new Path('./') : $result;
	}

	/**
	 * Create a path.
	 *
	 * @param string $path
	 * @throws \InvalidArgumentException
	 */
	public function __construct($path)
	{
		$this->set($path);
	}

	private function setParts(array $parts)
	{
		$this->parts = $parts;
		$this->partCount = count($parts);
		if ($this->partCount > 0) {
			$this->firstPart = $parts[0];
			$this->lastPart = $parts[$this->partCount - 1];
		} else {
			$this->firstPart = null;
			$this->lastPart = null;
		}
	}

	public function assumeDirectory()
	{
		if ($this->lastPart !== '') {
			$this->parts[] = '';
			$this->setParts($this->parts);
		}
		return $this;
	}

	/**
	 *
	 * Make the current path absolute, based on the given working directory.
	 *
	 * new Path('img/ping.png')->abs('/wwwroot/') => '/wwwroot/img/ping.png'
	 *
	 * Absolute pathes are not modified.
	 *
	 * new Path('/var/ping.png')->abs('/wwwroot/') => '/var/ping.png'
	 *
	 * @param string|Path $cwd
	 *        	The working directory.
	 * @throws \InvalidArgumentException if the path to the working directory is not absolute.
	 */
	public function abs($cwd)
	{
		if ($this->isAbsolute()) {
			return $this;
		}
		$cwd = Path::info($cwd);
		if (! $cwd->isAbsolute()) {
			throw new \InvalidArgumentException(sprintf('Current working dir "%s" must be an absolute path.', $cwd));
		}
		return $cwd->assumeDirectory()->resolve($this);
	}

	/**
	 * Set the path.
	 *
	 * @param string $path
	 * @throws \InvalidArgumentException
	 * @return self
	 */
	public function set($path)
	{
		if (! is_string($path)) {
			throw new \InvalidArgumentException();
		}
		if ($path === '') {
			$this->setParts([]);
		} else {
			$this->setParts(explode('/', $path));
		}
		return $this;
	}

	/**
	 * Return the path as a string, same as __toString().
	 *
	 * @return string
	 */
	public function get()
	{
		return join('/', $this->parts);
	}

	/**
	 * Is the this path empty?
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return $this->partCount === 0;
	}

	/**
	 * Does this path start with a /?
	 *
	 * @return boolean
	 */
	public function isAbsolute()
	{
		return $this->firstPart === '';
	}

	/**
	 * Returns true if both parts are equal after normalization.
	 *
	 * @param string|Path $path
	 * @return boolean
	 */
	public function equals($path)
	{
		$p = Path::info($path);
		return $p->normalize()->get() === $this->normalize()->get();
	}

	/**
	 * Returns a new path object that points to the dirname part.
	 *
	 * @return static a new Path object
	 */
	public function dir()
	{
		$dir = clone $this;
		if ($this->lastPart === '') {
			// already dir
		} else if ($this->lastPart === '..' || $this->lastPart === '.') {
			$dir->parts[] = '';
		} else {
			array_pop($dir->parts);
			$dir->parts[] = '';
		}
		$dir->setParts($dir->parts);
		return $dir;
	}

	/**
	 * Returns a new path object that points to the parent.
	 *
	 * This is just a shortcut for join('../')
	 *
	 * @return static a new Path object
	 */
	public function parent()
	{
		return $this->dir()->resolve('../');
	}

	/**
	 * Get the directory name, for example '/foo/' for the path '/foo/file'.
	 *
	 * Noteworthy special cases:
	 * - Always returns a string, but it is empty if the path contains just a filename.
	 * - The path '/file' does have a directory name, so '/' is returned.
	 * - If the path ends with '.' or '..', it must refer to a directory, and a slash is appended.
	 *
	 * @return string
	 */
	public function dirname()
	{
		return $this->dir()->get();
	}

	/**
	 * Return just the filename portion of the path.
	 * If no filename is present, an empty string is returned.
	 *
	 * @return string
	 */
	public function filename()
	{
		if ($this->lastPart === '' || $this->lastPart === '..' || $this->lastPart === '.') {
			return '';
		}
		return $this->lastPart;
	}

	/**
	 * Return the filename without extension.
	 *
	 * @return string
	 */
	public function basename()
	{
		$f = $this->filename();
		return pathinfo($f, PATHINFO_FILENAME);
	}

	/**
	 * Return just the extension of the filename.
	 *
	 * @return string
	 */
	public function extension()
	{
		$f = $this->filename();
		return pathinfo($f, PATHINFO_EXTENSION);
	}

	/**
	 * Adds a path to the current path and returns the new path object.
	 *
	 * Example: Resolving 'foo/bar' from the path '/dir' produces '/dir/foo/bar'.
	 *
	 * Noteworthy special cases:
	 * - If the path to resolve is absolute, this absolute path is returned and our current base is dropped.
	 * - If the path to resolve is empty, a slash is added to the current path if not already present.
	 * - We do not know if our path is a directory or not. Resolving 'dir' from '/file' results in '/file/dir'.
	 *
	 * @param string|Path $path
	 * @return static a new Path object
	 */
	public function resolve($path)
	{
		if ($path instanceof Path) {
			$add = clone $path;
		} else if (is_string($path)) {
			$add = new Path($path);
		} else {
			throw new \InvalidArgumentException();
		}
		
		$base = clone $this;
		
		if ($base->partCount === 0) {
			return $add;
		}
		if ($add->isAbsolute()) {
			return $add;
		}
		if ($base->lastPart === '') {
			array_pop($base->parts);
		}
		if ($add->firstPart === '') {
			array_shift($add->parts);
		}
		foreach ($add->parts as $part) {
			$base->parts[] = $part;
		}
		if ($add->partCount === 0) {
			$base->parts[] = '';
		}
		$base->setParts($base->parts);
		return $base;
	}

	/**
	 * Normalizes the path, resolving parent-references (..) and droppping current-references (.) where
	 * sensible.
	 *
	 * In case the path cannot be normalized, the input is returned. Example: /../
	 *
	 * @return static a new Path object
	 */
	public function normalize()
	{
		$path = clone $this;
		$path->parts = [];
		foreach ($this->parts as $part) {
			if ($part === '..') {
				if (empty($path->parts) || count($path->parts) === 1 && $path->parts[0] === '') {
					$path->parts[] = $part;
				} else {
					array_pop($path->parts);
				}
			} else if ($part === '.' && $this->partCount > 1) {
				// pass
			} else {
				$path->parts[] = $part;
			}
		}
		if ($this->lastPart === '..' || $this->lastPart === '.') {
			$path->parts[] = '';
		}
		$path->setParts($path->parts);
		return $path;
	}

	public function __toString()
	{
		return $this->get();
	}

}


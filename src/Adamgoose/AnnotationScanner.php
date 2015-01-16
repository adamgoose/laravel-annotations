<?php namespace Adamgoose;

use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

abstract class AnnotationScanner {

	/**
	 * The namespace to check for annotation reader annotations
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * The path to scan for annotations.
	 *
	 * @var array
	 */
	protected $scan;

	/**
	 * Create a new scanner instance.
	 *
	 * @param  array  $scan
	 * @return void
	 */
	public function __construct(array $scan)
	{
		$this->scan = $scan;

		foreach (Finder::create()->files()->in(__DIR__.'/Annotations') as $file)
		{
			AnnotationRegistry::registerFile($file->getRealPath());
		}
	}

	/**
	 * Create a new scanner instance.
	 *
	 * @param  array  $scan
	 * @return static
	 */
	public static function create(array $scan)
	{
		return new static($scan);
	}

	/**
	 * Get all of the ReflectionClass instances in the scan array.
	 *
	 * @return array
	 */
	protected function getClassesToScan()
	{
		$classes = [];

		foreach ($this->scan as $scan)
		{
			try
			{
				$classes[] = new ReflectionClass($scan);
			}
			catch (Exception $e)
			{
				//
			}
		}

		return $classes;
	}

	/**
	 * Get an annotation reader instance.
	 *
	 * @return \Doctrine\Common\Annotations\SimpleAnnotationReader
	 */
	protected function getReader()
	{
		with($reader = new SimpleAnnotationReader)
				->addNamespace($this->namespace);

		return $reader;
	}

}

<?php namespace Adamgoose;

use Adamgoose\NamespaceToPathConverterTrait;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

abstract class AnnotationScanner {

    use NamespaceToPathConverterTrait;

	/**
	 * The namespace to check for annotation reader annotations
	 *
	 * @var string
	 */
	protected $namespaces = [];

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
	 * Set the classes to scan
	 *
	 * @param array $scans
	 */
	public function setClassesToScan( array $scans )
	{
		$this->scan = $scans;
	}

	/**
	 * Add an annotation namespace for the SimpleAnnotationReader instance.
	 *
	 * If the second parameter is null, it will assume the namespace is PSR-4'd
	 * inside your app folder.
	 *
	 * @param string $namespace
	 * @param string $path
	 */
	public function addAnnotationNamespace($namespace, $path = null)
	{
		$this->namespaces[] = $namespace;

		return $this->registerAnnotationsPathWithRegistry(
			$path ?: $this->getPathFromNamespace( $namespace )
		);
	}

	/**
	 * Register the annotator files with the annotation registry
	 *
	 * @param  string $path
	 * @return $this
	 */
	public function registerAnnotationsPathWithRegistry( $path )
	{
		foreach (Finder::create()->files()->in( $path ) as $file)
        {
            AnnotationRegistry::registerFile($file->getRealPath());
        }

        return $this;
	}

	/**
	 * Get an annotation reader instance.
	 *
	 * @return \Doctrine\Common\Annotations\SimpleAnnotationReader
	 */
	protected function getReader()
	{
		$reader = new SimpleAnnotationReader;

		foreach ($this->namespaces as $namespace)
			$reader->addNamespace($namespace);

		return $reader;
	}

}

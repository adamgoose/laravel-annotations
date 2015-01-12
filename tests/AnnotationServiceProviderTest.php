<?php

use Mockery as m;
use Adamgoose\AnnotationsServiceProvider;

class AnnotationsServiceProviderTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->app = m::mock('Illuminate\Contracts\Foundation\Application');
		$this->provider = new AnnotationsServiceProvider( $this->app );
	}

	public function testPrefixClasses()
	{
		$prefix = 'Prefix';
		$classes = ['Foo', 'Bar', 'Foo\\Bar'];

		$this->assertEquals(
			['Prefix\\Foo', 'Prefix\\Bar', 'Prefix\\Foo\\Bar'],
			$this->provider->prefixClasses($prefix, $classes)
		);
	}

	public function testPrefixClassesWithNoPrefix()
	{
		$prefix = '';
		$classes = ['Foo', 'Bar'];

		$this->assertEquals(
			['Foo', 'Bar'],
			$this->provider->prefixClasses($prefix, $classes)
		);
	}

	public function testPrefixClassesWithTrimsWhitespaceAndDereferencers()
	{
		$prefix = '\\Prefix ';
		$classes = ['\\ Foo  \\', ' \\Bar\\ '];

		$this->assertEquals(
			['Prefix\\Foo', 'Prefix\\Bar'],
			$this->provider->prefixClasses($prefix, $classes)
		);
	}

	public function testConvertNamespaceToDirectory()
	{
		$this->provider = new AnnotationsServiceProviderAppNamespaceStub( $this->app );
		$class = 'App\\Foo';

		$result = $this->provider->convertNamespaceToPath($class);

		$this->assertEquals('Foo', $result);
	}

	public function testConvertNamespaceToDirectoryWithoutRootNamespace()
	{
		$this->provider = new AnnotationsServiceProviderAppNamespaceStub( $this->app );
		$this->provider->appNamespace = 'Foo';
		$class = 'App\\Foo';

		$result = $this->provider->convertNamespaceToPath($class);

		$this->assertEquals('App/Foo', $result);
	}

	public function testGetClassesFromNamespace()
	{
		$this->provider = new AnnotationsServiceProviderAppNamespaceStub( $this->app );
		$this->provider->appNamespace = 'App';

		$this->app->shouldReceive( 'make' )->once()->with('files')->andReturn(
			$filesystem = m::mock('Illuminate\Filesystem\Filesystem')
		);

		$filesystem->shouldReceive( 'allFiles' )->with('path/to/app/Base')->andReturn( [
			new \Symfony\Component\Finder\SplFileInfo( 'Foo.php', '', 'Foo.php' ),
			new \Symfony\Component\Finder\SplFileInfo( 'FooBar.txt', '', 'FooBar.txt' ),
			new \Symfony\Component\Finder\SplFileInfo( 'Baz.php', 'Foo/Bar', 'Foo/Bar/Baz.php' ),
		] );

		$results = $this->provider->getClassesFromNamespace( 'App\\Base', 'path/to/app' );

		$this->assertEquals( [
			'App\\Base\\Foo',
			'App\\Base\\Foo\\Bar\\Baz',
		], $results );
	}
}


class AnnotationsServiceProviderAppNamespaceStub extends AnnotationsServiceProvider {
	public $appNamespace = 'App';

	public function getAppNamespace()
	{
		return $this->appNamespace;
	}
}

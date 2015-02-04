<?php

use Mockery as m;
use Adamgoose\AnnotationsServiceProvider;

class AnnotationsServiceProviderTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->app = m::mock('Illuminate\Contracts\Foundation\Application');
		$this->provider = new AnnotationsServiceProvider( $this->app );
	}

	public function tearDown()
	{
		m::close();
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

		$this->app->shouldReceive( 'make' )
			->with( 'Illuminate\Filesystem\ClassFinder' )->once()
			->andReturn( $classFinder = m::mock() );

		$classFinder->shouldReceive('findClasses')
			->with('path/to/app/Base')->once()
			->andReturn( ['classes'] );

		$results = $this->provider->getClassesFromNamespace( 'App\\Base', 'path/to/app' );

		$this->assertEquals( ['classes'], $results );
	}

	public function testParseNamespaceScansBasic()
	{
		$this->provider = new AnnotationsServiceProviderAppNamespaceStub( $this->app );

		$namespaces = [
			'Foo', 'Bar'
		];
		$this->app->shouldReceive( 'make' )->with('path')->times(2)->andReturn( 'path/to/app' );
		$this->app->shouldReceive( 'make' )
			->with('Illuminate\Filesystem\ClassFinder')->times(2)
			->andReturn( $classfinder = m::mock('Illuminate\Filesystem\ClassFinder') );

		$classfinder->shouldReceive('findClasses')->with('path/to/app/Foo')->andReturn([ 'Foo\Foo', 'Foo\Bar' ]);
		$classfinder->shouldReceive('findClasses')->with('path/to/app/Bar')->andReturn([ 'Bar\Foo', 'Bar\Bar' ]);


		$results = $this->provider->parseNamespaceScans( $namespaces );

		$this->assertEquals([
			'Foo\Foo', 'Foo\Bar',
			'Bar\Foo', 'Bar\Bar',
		], $results);
	}

	public function testParseNamespaceScansFiltered()
	{
		$this->provider = new AnnotationsServiceProviderAppNamespaceStub( $this->app );

		$namespaces = [
			'Foo', 'Bar',
			'except' => ['Foo\Bar', 'Baz']
		];
		$this->app->shouldReceive( 'make' )->with('path')->times(2)->andReturn( 'path/to/app' );
		$this->app->shouldReceive( 'make' )
			->with('Illuminate\Filesystem\ClassFinder')->times(2)
			->andReturn( $classfinder = m::mock('Illuminate\Filesystem\ClassFinder') );

		$classfinder->shouldReceive('findClasses')->with('path/to/app/Foo')->andReturn([ 'Foo\Foo', 'Foo\Bar' ]);
		$classfinder->shouldReceive('findClasses')->with('path/to/app/Bar')->andReturn([ 'Bar\Foo', 'Bar\Bar' ]);


		$results = $this->provider->parseNamespaceScans( $namespaces );

		$this->assertEquals([
			'Foo\Foo',
			'Bar\Foo', 'Bar\Bar',
		], $results);
	}
}


class AnnotationsServiceProviderAppNamespaceStub extends AnnotationsServiceProvider {
	public $appNamespace = 'App';

	public function getAppNamespace()
	{
		return $this->appNamespace;
	}
}

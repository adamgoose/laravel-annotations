<?php

use Mockery as m;
use Adamgoose\AnnotationsServiceProvider;

class AnnotationsServiceProviderTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->app = m::mock('Illuminate\Contracts\Foundation\Application');
		$this->provider = new AnnotationsServiceProvider( $this->app );
	}

	public function testConvertNamespaceToDirectory()
	{
		$this->provider = new AnnotationsServiceProviderStub( $this->app );
		$this->provider->appNamespace = 'Foo';
		$class = 'Foo\\Bar\\Baz';

		$result = $this->provider->convertNamespaceToPath($class);

		$this->assertEquals('Bar/Baz', $result);
	}

	public function testConvertNamespaceToDirectoryWithoutRootNamespace()
	{
		$this->provider = new AnnotationsServiceProviderStub( $this->app );
		$this->provider->appNamespace = 'Bar';
		$class = 'Foo\\Bar\\Baz';

		$result = $this->provider->convertNamespaceToPath($class);

		$this->assertEquals('Foo/Bar/Baz', $result);
	}

}

class AnnotationsServiceProviderStub extends AnnotationsServiceProvider {
	public $appNamespace = null;

	public function getAppNamespace()
	{
		return $this->appNamespace;
	}
}

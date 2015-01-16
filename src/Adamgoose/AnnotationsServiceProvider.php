<?php namespace Adamgoose;

use Adamgoose\Console\EventScanCommand;
use Adamgoose\Console\RouteScanCommand;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Adamgoose\Events\Annotations\Scanner as EventScanner;
use Adamgoose\Routing\Annotations\Scanner as RouteScanner;

class AnnotationsServiceProvider extends ServiceProvider {

    use AppNamespaceDetectorTrait;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
      'EventScan' => 'command.event.scan',
      'RouteScan' => 'command.route.scan',
    ];

    /**
     * The classes to scan for event annotations.
     *
     * @var array
     */
    protected $scanEvents = [];

    /**
     * The classes to scan for route annotations.
     *
     * @var array
     */
    protected $scanRoutes = [];

    /**
     * The namespaces to scan for route annotations.
     *
     * @var array
     */
    protected $scanRoutesNamespaces = [];

    /**
     * A prefix to apply to all event scan classes
     * @var string
     */
    protected $eventsClassNamespace = '';

    /**
     * A prefix to apply to all route scan classes
     * @var string
     */
    protected $routesClassNamespace = '';

    /**
     * Determines if we will auto-scan in the local environment.
     *
     * @var bool
     */
    protected $scanWhenLocal = false;

    /**
     * Determines whether or not to automatically scan the controllers
     * directory (App\Http\Controllers) for routes
     * @var boolean
     */
    protected $scanControllers = false;

    /**
     * File finder for annotations.
     *
     * @var AnnotationFinder
     */
    private $finder;

    /**
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->finder = new AnnotationFinder($app);
        parent::__construct($app);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register the application's annotated event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadAnnotatedEvents();

        if ( ! $this->app->routesAreCached())
        {
            $this->loadAnnotatedRoutes();
        }
    }

    /**
     * Register the commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        foreach (array_keys($this->commands) as $command)
        {
            $method = "register{$command}Command";
            call_user_func_array([$this, $method], []);
        }
        $this->commands(array_values($this->commands));
    }


    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerEventScanCommand()
    {
        $this->app->singleton('command.event.scan', function ($app)
        {
            return new EventScanCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerRouteScanCommand()
    {
        $this->app->singleton('command.route.scan', function ($app)
        {
            return new RouteScanCommand($app['files']);
        });
    }

    /**
     * Load the annotated events.
     *
     * @return void
     */
    public function loadAnnotatedEvents()
    {
        if ($this->app->environment('local') && $this->scanWhenLocal)
        {
            $this->scanEvents();
        }

        $scans = $this->eventScans();

        if ( ! empty( $scans ) && $this->finder->eventsAreScanned())
        {
            $this->loadScannedEvents();
        }
    }

    /**
     * Scan the events for the application.
     *
     * @return void
     */
    protected function scanEvents()
    {
        $scans = $this->eventScans();

        if (empty( $scans ))
        {
            return;
        }

        $scanner = new EventScanner( $scans );

        file_put_contents(
          $this->finder->getScannedEventsPath(), '<?php ' . $scanner->getEventDefinitions()
        );
    }

    /**
     * Load the scanned events for the application.
     *
     * @return void
     */
    protected function loadScannedEvents()
    {
        $events = $this->app['Illuminate\Contracts\Events\Dispatcher'];

        require $this->finder->getScannedEventsPath();
    }

    /**
     * Load the annotated routes
     *
     * @return void
     */
    protected function loadAnnotatedRoutes()
    {
        if ($this->app->environment('local') && $this->scanWhenLocal)
        {
            $this->scanRoutes();
        }

        $scans = $this->routeScans();

        if ( ! empty( $scans ) && $this->finder->routesAreScanned())
        {
            $this->loadScannedRoutes();
        }
    }

    /**
     * Scan the routes and write the scanned routes file.
     *
     * @return void
     */
    protected function scanRoutes()
    {
        $scans = $this->routeScans();

        if (empty( $scans ))
        {
            return;
        }

        $scanner = new RouteScanner( $scans );

        file_put_contents(
          $this->finder->getScannedRoutesPath(), '<?php ' . $scanner->getRouteDefinitions()
        );
    }

    /**
     * Load the scanned application routes.
     *
     * @return void
     */
    protected function loadScannedRoutes()
    {
        $this->app->booted(function ()
        {
            $router = $this->app['Illuminate\Contracts\Routing\Registrar'];

            require $this->finder->getScannedRoutesPath();
        });
    }

    /**
     * Apply the given prefix to the given routes
     *
     * @param  string $prefix The prefix to apply
     * @param  array  $routes The routes to apply the prefix to
     * @return array
     */
    public function prefixClasses( $prefix, $routes )
    {
        // trim the namespace segments for safety
        $prefix = trim( $prefix, ' \\' );

        return array_map(function($item) use ( $prefix ) {
            $item = trim( $item, ' \\' );

            // concat the strings if there is a prefix, otherwise return the given classname
            return empty($prefix) ? $item : "{$prefix}\\{$item}";
        }, (array)$routes);
    }

    /**
     * Get the classes to be scanned by the provider.
     *
     * @return array
     */
    public function eventScans()
    {
        return $this->prefixClasses( $this->eventsClassNamespace, $this->scanEvents );
    }

    /**
     * Get the classes to be scanned by the provider.
     *
     * @return array
     */
    public function routeScans()
    {
        $classes = $this->prefixClasses( $this->routesClassNamespace, $this->scanRoutes );

        // scan the controllers namespace if the flag is set
        if ( $this->scanControllers )
        {
            $classes = array_merge(
                $classes,
                $this->getClassesFromNamespace( $this->getAppNamespace() . 'Http\\Controllers' )
            );
        }

        $classes = array_merge(
            $classes,
            $this->parseNamespaceScans( $this->scanRoutesNamespaces )
        );

        return $classes;
    }

    /**
     * Scan the given namespaces, then parse using the nested 'only' and
     * 'exclude' options applied
     *
     * @param  array $input
     * @return array
     */
    public function parseNamespaceScans( $namespaces )
    {
        // rip out the 'only' and 'exclude' options from the input array
        $options = [
            'only' => (array) array_pull( $namespaces, 'only', [] ),
            'except' => (array) array_pull( $namespaces, 'except', [] ),
        ];

        $classes = [];

        // loop through the namespaces, and add the classes to the $classes array
        foreach ($namespaces as $namespace)
            $classes = array_merge($classes, $this->getClassesFromNamespace( $namespace ));

        // filter out items in the 'exclude' array
        $classes = array_filter($classes, function($item) use ($options)
        {
            return ! $this->itemExcludedByOptions( $item, $options );
        });

        return $classes;
    }

    /**
     * Convert the given namespace to a file path
     *
     * @param  string $namespace the namespace to convert
     * @return string
     */
    public function convertNamespaceToPath( $namespace )
    {
        // remove the app namespace from the namespace if it is there
        $appNamespace = $this->getAppNamespace();

        if (substr($namespace, 0, strlen($appNamespace)) == $appNamespace)
        {
            $namespace = substr($namespace, strlen($appNamespace));
        }

        // trim and return the path
        return str_replace('\\', '/', trim($namespace, ' \\') );
    }

    /**
     * Get a list of the classes in a namespace. Leaving the second argument
     * will scan for classes within the project's app directory
     *
     * @param  string $namespace the namespace to search
     * @return array
     */
    public function getClassesFromNamespace( $namespace, $base = null )
    {
        $directory = ( $base ?: $this->app->make('path') ) . '/' . $this->convertNamespaceToPath( $namespace );

        return $this->app->make('Illuminate\Filesystem\ClassFinder')->findClasses( $directory );
    }

    /**
     * Determine if the given options exclude a particular item
     * @param  string $method
     * @param  array  $options
     * @return boolean
     */
    protected function itemExcludedByOptions($item, array $options)
    {
        return (( ! empty($options['only']) && ! in_array($item, (array) $options['only'])) ||
            ( ! empty($options['except']) && in_array($item, (array) $options['except'])));
    }
}

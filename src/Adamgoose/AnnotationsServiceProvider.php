<?php namespace Adamgoose;

use Adamgoose\Console\EventScanCommand;
use Adamgoose\Console\RouteScanCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Adamgoose\Events\Annotations\Scanner as EventScanner;
use Adamgoose\Routing\Annotations\Scanner as RouteScanner;

class AnnotationsServiceProvider extends ServiceProvider {

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
     * Determines if we will auto-scan in the local environment.
     *
     * @var bool
     */
    protected $scanWhenLocal = false;

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
        $this->addEventAnnotations( $this->app->make('annotations.event.scanner') );

        $this->loadAnnotatedEvents();

        $this->addRoutingAnnotations( $this->app->make('annotations.route.scanner') );

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
     * Register the scanner.
     *
     * @return void
     */
    protected function registerRouteScanner()
    {
        $this->app->bindShared('annotations.route.scanner', function ($app)
        {
            $scanner = new RouteScanner([]);

            $scanner->addAnnotationNamespace(
                'Adamgoose\Routing\Annotations\Annotations',
                __DIR__.'/Routing/Annotations/Annotations'
            );

            return $scanner;
        });
    }

    /**
     * Register the scanner.
     *
     * @return void
     */
    protected function registerEventScanner()
    {
        $this->app->bindShared('annotations.event.scanner', function ($app)
        {
            $scanner = new EventScanner([]);

            $scanner->addAnnotationNamespace(
                'Adamgoose\Events\Annotations\Annotations',
                __DIR__.'/Events/Annotations/Annotations'
            );

            return $scanner;
        });
    }

    /**
     * Add an annotations to the route scanner
     *
     * @param RouteScanner $namespace
     */
    public function addRoutingAnnotations( RouteScanner $scanner ) {}

    /**
     * Add an annotations to the route scanner
     *
     * @param RouteScanner $namespace
     */
    public function addEventAnnotations( EventScanner $scanner ) {}

    /**
     * Add an annotation namespace to the event scanner
     *
     * @param string $namespace
     * @param string $path
     */
    public function addEventAnnotations( $namespace, $path = null )
    {
        $scanner = $this->app->make('annotations.event.scanner');

        $scanner->addAnnotationNamespace($namespace, $path);

        return $this;
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

        if ( ! empty($this->scanEvents) && $this->finder->eventsAreScanned())
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
        if (empty($this->scanEvents))
        {
            return;
        }

        $scanner = $this->app->make('annotations.event.scanner');

        $scanner->setClassesToScan($this->scanEvents);

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

        if ( ! empty($this->scanRoutes) && $this->finder->routesAreScanned())
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
        if (empty($this->scanRoutes))
        {
            return;
        }

        $scanner = $this->app->make('annotations.route.scanner');

        $scanner->setClassesToScan($this->scanRoutes);

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
     * Get the classes to be scanned by the provider.
     *
     * @return array
     */
    public function eventScans()
    {
        return $this->scanEvents;
    }

    /**
     * Get the classes to be scanned by the provider.
     *
     * @return array
     */
    public function routeScans()
    {
        return $this->scanRoutes;
    }
}

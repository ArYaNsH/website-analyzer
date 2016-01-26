<?php
namespace Front;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Silex\ServiceControllerResolver;



class FrontServiceProvider implements ServiceProviderInterface, ControllerProviderInterface {

    public function register(Application $app) { 
        $app['front'] = $app->share(function($app) { return new FrontController(); });
    }

    

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registers
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app) {
        // Add twig template path.
        if ($app->offsetExists('twig.loader.filesystem')) {
            $app['twig.loader.filesystem']->addPath(__DIR__ . '/views/', 'front');
        }
    }

    

    

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     * @throws \LogicException if ServiceController service provider is not registered.
     */
    public function connect(Application $app) {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use these routes.');
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        //Homepage
        $controllers->method('GET|POST')->match('/', 'front:homepage')
            ->bind('homepage');
        //Register
        $controllers->method('GET|POST')->match('/register', 'front:register')
            ->bind('register');  
        //Login
        $controllers->method('GET|POST')->match('/login', 'front:login')
            ->bind('login');

        //Assets
        $controllers->method('GET|POST')->match('/css_path', 'front:css')
            ->bind('front.css');

        $controllers->method('GET|POST')->match('/js_path', 'front:js')
            ->bind('front.js'); 

       

        return $controllers;


       
    }

}
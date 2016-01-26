<?php
require_once __DIR__ . '/bootstrap.php';

use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;



$app = new Silex\Application();
//<-----------------------------FOR DEBUGGING PURPOSES--------------------------->
$app['debug'] = true;
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'localefallback' => 'fr',
));
$app->register(new Silex\Provider\SwiftmailerServiceProvider());    

$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(
        __DIR__ . '/../views/app'
    )
));


//db
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'db.options' => array(
		'driver' => 'pdo_mysql',
		'dbhost' => 'localhost',
		'dbname' => 'master',
		'user' => 'root',
		'password' => '',
	),
));

// $app['session.db_options'] = array(
//     'db_table'      => 'session',
//     'db_id_col'     => 'session_id',
//     'db_data_col'   => 'session_value',
//     'db_time_col'   => 'session_time'
// );


// $app['session.storage.handler'] = $app->share(function () use ($app) {
//     return new PdoSessionHandler(
//         $app['db']->getWrappedConnection(),
//         $app['session.db_options'],
//         $app['session.storage.options']
//     );
// });


// Front
$frontServiceProvider = new Front\FrontServiceProvider();
$app->register($frontServiceProvider);
$app->mount(null, $frontServiceProvider);

$app->before(function (Request $request ) use ($app) {


});

$app->boot();


return $app;

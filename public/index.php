<?php

use Mattford\WsmOpenScoresheet\Handlers\HttpErrorHandler;
use Mattford\WsmOpenScoresheet\Http\Controllers\GenerateScoresheetController;
use Mattford\WsmOpenScoresheet\Http\Controllers\MatchController;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

define('BASE_PATH', realpath(__DIR__ . '/../'));

$app = AppFactory::create();

$app->post('/generate', [GenerateScoresheetController::class, 'generate']);
$app->post('/results', [MatchController::class, 'generateResult']);
$app->post('/match', [MatchController::class, 'view']);
$app->get('/{path:.*}', function (\Slim\Psr7\Request $req, \Slim\Psr7\Response $res) {
    $targetPath = realpath(__DIR__ . $req->getUri()->getPath());
    if (str_starts_with($targetPath, __DIR__) && is_file($targetPath) && is_readable($targetPath)) {
        $res = $res->withHeader('Content-Type', 'text/css');
        $res->getBody()->write(file_get_contents($targetPath));
        return $res;
    }
    $view = Twig::fromRequest($req);
    return $view->render($res, 'default.twig');
});

// Add Routing Middleware
$app->addRoutingMiddleware();

//// Add Error Handling Middleware
$httpErrorHandler = new HttpErrorHandler($app);
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler([$httpErrorHandler, 'handle']);

// Create Twig
$twig = Twig::create(__DIR__ . '/../resources/views', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$app->run();
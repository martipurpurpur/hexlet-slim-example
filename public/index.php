<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use function Stringy\create as s;

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
   return $this->get('renderer')->render($response, "hello.phtml");
});
$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $users = collect($users)->filter(function ($user) use ($term) {
        return s($user)->startsWith($term, false);
    })->all();
    $params = [
        'users' => $users,
        'term' => $term
    ];
    return $this->get('renderer')->render($response, "users/show.phtml", $params);
});

$app->run();
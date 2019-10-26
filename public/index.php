<?php

use Slim\Factory\AppFactory;
use DI\Container;
use function Stringy\create as s;
use App\Validator;

require __DIR__ . '/../vendor/autoload.php';

$repo = new App\Repository();

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

$app->get('/users', function ($request, $response) use ($repo) {
    $term = $request->getQueryParam('term');
    $repo = $repo->all();
    $users = collect($repo)->filter(function ($user) use ($term) {
        return s($user['name'])->startsWith($term, false);
    })->all();
    $params = [
       'users' => $users,
        'term' => $term
    ];
    return $this->get('renderer')->render($response, "users/show.phtml", $params);
 });

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => [],
        'errors' => [],
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->post('/users', function ($request, $response) use ($repo) {
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    if (empty($errors)) {
        $repo->save($user);
        return $response->withHeader('Location', '/users')
            ->withStatus(302);
    }
    return $this->get('renderer')->render($response->withStatus(422), "users/new.phtml", $params);
});

$app->run();
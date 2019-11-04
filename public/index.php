<?php

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(\Slim\Middleware\MethodOverrideMiddleware::class);

$repo = new App\Repository();
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
    $params = [
        'currentUser'=> $_SESSION['user'] ?? null,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, "hello.phtml", $params);
});

$app->post('/session', function ($request, $response) use ($repo) {
    $userData = $request->getParsedBodyParam('user');
    $user = collect($repo->all())->first(function ($user) use ($userData) {
        return $user['name'] == $userData['name']
            && hash('sha256', $userData['password']) == $user['password'];
    });
    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        $this->get('flash')->addMessage('error', 'Wrong password or name');
    }
    return $response->withRedirect('/');
});

$app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect('/');
});

$app->get('/users', function ($request, $response) use ($repo) {
    $term = $request->getQueryParam('term');
    $users = $repo->findByName($term);
    $flash = $this->get('flash')->getMessages();
    $params = [
       'users' => $users,
        'term' => $term,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, "users/index.phtml", $params);
 })->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'userData' => [],
        'errors' => [],
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->post('/users/new', function ($request, $response) use ($router) {
    $this->get('flash')->addMessage('success', 'User Added');
    return $response->withRedirect($router->urlFor('users'));
});

 $app->get('/users/{id}', function ($request, $response, $args) use ($repo) {
    $id = $args['id'];
    $user = $repo->findById($id);
    if(!$user) {
        $this->get('flash')->addMessage('error', 'Not Found');
        return $response->withRedirect('/users');
    }
    $params = [
        'user' => $user
    ];
    return $this->get('renderer')->render($response, "users/show.phtml", $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($repo, $router) {
    $validator = new Validator();
    $userData = $request->getParsedBodyParam('user');
    $errors = $validator->validate($userData);
    $this->get('flash')->addMessage('success', 'User has been created');
    $params = [
        'userData' => $userData,
        'errors' => $errors,
    ];
    if (empty($errors)) {
        $repo->save($userData);
        return $response->withRedirect($router->urlFor('users'))
            ->withStatus(302);
    }
    return $this->get('renderer')->render($response->withStatus(422), "users/new.phtml", $params);
});

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($repo) {
    $user = $repo->findById($args['id']);
    $params = [
        'user' => $user,
        'userData' => $user
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->patch('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $user = $repo->findById($args['id']);
    $userData = $request->getParsedBodyParam('user');

    $validator = new App\Validator();
    $errors = $validator->validate($userData);

    if (empty($errors)) {
        $user['name'] = $userData['name'];
        $user['email'] = $userData['email'];
        $user['password'] = $userData['password'];
        $user['passwordConfirmation'] = $userData['passwordConfirmation'];
        $repo->save($user);
        $this->get('flash')->addMessage('success', 'User has been updated');
        return $response->withRedirect($router->urlFor('users'));
    }

    $params = [
        'user' => $user,
        'userData' => $userData,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $repo->destroy($args['id']);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});

$app->run();

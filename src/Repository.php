<?php

namespace App;

class Repository
{
    public function __construct()
    {
        session_start();
    }

    public function all()
    {
        return array_values($_SESSION);
    }

    public function find(int $id)
    {
        return $_SESSION[$id];
    }

    public function save(array $user)
    {
        if (empty($user['name']) || empty($user['email']) || empty($user['password']) || (($user['password'] !== $user['passwordConfirmation']))) {
            $json = json_encode($user);
            throw new \Exception("Wrong data: {$json}");
        }
        $user['id'] = uniqid();
        $_SESSION[$user['id']] = $user;
        file_put_contents("src/user.json", json_encode($user));
    }
}

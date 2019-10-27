<?php

namespace App;

use function Stringy\create as s;

class Repository
{
    public function all()
    {
        $file = json_decode(file_get_contents('src/user.json'), TRUE);
        $users = collect($file)->values()->all();
        return $users;
    }

    public function findById(int $id)
    {
        $file = json_decode(file_get_contents('src/user.json'), TRUE);
        $findUser = collect($file)->firstWhere($id)->all();
        return $findUser;
    }
    public function findByName($name)
    {
        $users = $this->all();
        $findUsers = collect($users)->filter(function ($user) use ($name) {
            return s($user['name'])->startsWith($name, false);
        })->all();
        return $findUsers;
    }

    public function save(array $user)
    {
        if (empty($user['name']) ||
            empty($user['email']) ||
            empty($user['password']) ||
            ($user['password'] !== $user['passwordConfirmation']))
        {
            $json = json_encode($user);
            throw new \Exception("Wrong data: {$json}");
        }

        $id = uniqid();
        $file = file_get_contents('src/user.json');
        $json = json_decode(($file), TRUE);
        unset($file);
        $json[$id] = $user;
        file_put_contents('src/user.json', json_encode($json,
            JSON_PRETTY_PRINT));
    }
}

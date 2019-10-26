<?php
namespace App;

class Validator
{
    public function validate(array $user)
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "can't be blank";
        }
        if (empty($user['email'])) {
            $errors['email'] = "can't be blank";
        }
        if (empty($user['password'])) {
            $errors['password'] = "can't be blank";
        }
        if ($user['passwordConfirmation'] !== $user['password']) {
            $errors['passwordConfirmation'] = "passwords don't match";
        }
        return $errors;
    }
}
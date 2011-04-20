<?php

/**
 * Anonymous User
 * @package Authentication
 */
class AnonymousUser extends User
{
    public function __construct()
    {
    }

    public function getUserHash() {
        return '';
    }
 
    public function setUserData($key, $value) {
        return false;
    }

    public function getUserData($key) {
        return null;
    }
 
}


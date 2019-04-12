<?php

namespace App\Service;

use Cake\Utility\Security;
use Firebase\JWT\JWT;

class UserService
{
    /**
     * Get auth token
     *
     * @param string $subject The id
     * @param int $days The days token is valid
     * @return string
     */
    public function getToken($subject, $days = 7)
    {
        return JWT::encode(
            [
                'sub' => $subject,
                'exp' => time() + ($days * 24 * 60 * 60)
            ],
            Security::getSalt()
        );
    }
}
<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\Time;
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
    public function getToken(string $subject, int $days = 7): string
    {
        $time = new Time();
        $time->addDays($days);

        return JWT::encode(
            [
                'sub' => $subject,
                'exp' => $time->getTimestamp(),
            ],
            Security::getSalt()
        );
    }
}

<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Stream;

/**
 * Description of StreamSingleActivity
 *
 * @author Stefano
 */
abstract class StreamSingleActivity extends StreamActivity
{
    public function getTime()
    {
        return $this->activity->offsetGet('time');
    }
}

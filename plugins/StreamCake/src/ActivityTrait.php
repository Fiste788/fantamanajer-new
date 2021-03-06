<?php
declare(strict_types=1);

namespace StreamCake;

trait ActivityTrait
{
    abstract protected function activityId();

    /**
     * @return string
     */
    public function activityObject()
    {
        return static::class . ':' . $this->activityId();
    }

    /**
     * @return string
     */
    public function activityForeignId()
    {
        return $this->activityObject();
    }

    /**
     * @return array
     */
    public function activityNotify()
    {
        return [];
    }

    /**
     * @return array
     */
    public function activityExtraData()
    {
        return [];
    }
}

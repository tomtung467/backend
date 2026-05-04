<?php

namespace App\Traits;

trait Timestampable
{
    /**
     * Get human readable created_at
     */
    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get human readable updated_at
     */
    public function getUpdatedAtHumanAttribute()
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Check if model was recently created
     */
    public function wasRecentlyCreated()
    {
        return $this->created_at->diffInSeconds(now()) < 60;
    }

    /**
     * Check if model was recently updated
     */
    public function wasRecentlyUpdated()
    {
        return $this->updated_at->diffInSeconds(now()) < 60;
    }
}

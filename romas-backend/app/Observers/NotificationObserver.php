<?php

namespace App\Observers;

use App\Events\NewNotification;
use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification)
    {
        event(new NewNotification($notification));
    }
}
<?php

namespace App\ScheduledJobs;

use App\Models\User;
use App\Notifications\NoOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class NoOrderForTodayNotification
{
    public function __invoke()
    {
        $users = User::query()
            ->where(function(Builder $query) {
                $query->where('settings->noOrderNotification', '1')
                    ->orWhere('settings->noOrderNotification', true);
            })
            ->whereDoesntHave('orderItems.meal', function (Builder $q) {
                return $q->whereDate('date', today());
            })
            ->whereDoesntHave('disabledNotifications', function(Builder $q) {
                return $q->where('date', today());
            })
            ->get();

        Notification::send($users, new NoOrder(__('calendar.today')));
    }
}

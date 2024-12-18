<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NewOrderPossibilities extends Notification
{
    use Queueable;

    /**
     * @var Carbon[]|null
     */
    private $dates;

    /**
     * Create a new notification instance.
     *
     * @param  iterable  $dates
     */
    public function __construct(iterable $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'dates' => $this->dates,
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(User $notifiable)
    {
        $message = (new MailMessage)->subject(__('New order possibilities'));

        $locale = $notifiable->settings->language ?? config('app.locale');

        foreach ($this->dates as $date) {
            $formatted = Carbon::parse($date)->locale($locale)->isoFormat('ddd MMM DD YYYY');
            $message->line(__('New order possibility for :day', ['day' => $formatted]));
        }

        return $message->action(__('Click here for more details'), route('meals.index'));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
}

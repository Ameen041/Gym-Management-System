<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * One in-app notification. The message is stored as an English translation
 * key + parameters, so it is shown in the language the reader is using.
 */
class AppNotification extends Notification
{
    public function __construct(
        public string $message,
        public array $params = [],
        public ?string $url = null,
        public string $icon = 'fa-bell',
        public ?string $tag = null,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->message,
            'params'  => $this->params,
            'url'     => $this->url,
            'icon'    => $this->icon,
            'tag'     => $this->tag,
        ];
    }
}

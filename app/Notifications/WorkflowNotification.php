<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    public function __construct(
        protected string $message,
        protected ?Model $model = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $url = null;
        if ($this->model) {
            if ($this->model instanceof \App\Models\Lead) {
                $url = url("/leads/{$this->model->id}");
            } elseif ($this->model instanceof \App\Models\Deal) {
                $url = url("/pipeline/{$this->model->id}");
            }
        }

        return [
            'type' => 'workflow',
            'icon' => 'git-branch',
            'color' => 'purple',
            'title' => __('Workflow Notification'),
            'body' => $this->message,
            'url' => $url,
        ];
    }
}

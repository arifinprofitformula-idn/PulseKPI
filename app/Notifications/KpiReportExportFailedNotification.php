<?php

namespace App\Notifications;

use App\Models\KpiReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KpiReportExportFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly KpiReportExport $export,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'KPI export failed',
            'message' => sprintf('%s export could not be completed.', $this->export->type->label()),
            'export_id' => $this->export->getKey(),
            'type' => $this->export->type->value,
            'format' => $this->export->format->value,
        ];
    }
}

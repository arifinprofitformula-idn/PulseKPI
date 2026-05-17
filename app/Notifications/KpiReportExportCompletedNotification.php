<?php

namespace App\Notifications;

use App\Models\KpiReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KpiReportExportCompletedNotification extends Notification
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
            'title' => 'KPI export completed',
            'message' => sprintf('%s export is ready to download.', $this->export->type->label()),
            'export_id' => $this->export->getKey(),
            'type' => $this->export->type->value,
            'format' => $this->export->format->value,
            'download_url' => route('kpi-report-exports.download', $this->export),
        ];
    }
}

<?php

namespace App\Modules\Core\Exports;

use App\Modules\Core\Models\LogActivity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LogActivitiesExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        $logs = LogActivity::with(['user', 'organization'])
            ->filter($this->filters)
            ->get();

        return $logs->map(fn (LogActivity $log) => [
            'id' => $log->id,
            'description' => $log->description,
            'user_type' => $log->user_type,
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name ?? 'Guest',
            'organization_id' => $log->organization_id,
            'route' => $log->route,
            'method_type' => $log->method_type,
            'status_code' => $log->status_code,
            'ip_address' => $log->ip_address,
            'country' => $log->country,
            'user_agent' => $log->user_agent,
            'request_data' => $log->request_data ? json_encode($log->request_data, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => $log->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $log->updated_at?->format('H:i:s d/m/Y'),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Description',
            'User Type',
            'User ID',
            'User Name',
            'Organization ID',
            'Route',
            'Method Type',
            'Status Code',
            'IP Address',
            'Country',
            'User Agent',
            'Request Data',
            'Created At',
            'Updated At',
        ];
    }
}

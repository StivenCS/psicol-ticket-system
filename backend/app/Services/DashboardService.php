<?php

namespace App\Services;

use App\Models\Ticket;

class DashboardService
{
    public function getStats(): array
    {
        $row = Ticket::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
            SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_count,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count,
            SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_count,
            SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as overdue
        ", [now()->toDateString()])->first();

        return [
            'total'       => (int) $row->total,
            'overdue'     => (int) $row->overdue,
            'by_status'   => [
                'open'        => (int) $row->open_count,
                'in_progress' => (int) $row->in_progress_count,
                'resolved'    => (int) $row->resolved_count,
                'closed'      => (int) $row->closed_count,
            ],
            'by_priority' => [
                'low'      => (int) $row->low_count,
                'medium'   => (int) $row->medium_count,
                'high'     => (int) $row->high_count,
                'critical' => (int) $row->critical_count,
            ],
        ];
    }
}

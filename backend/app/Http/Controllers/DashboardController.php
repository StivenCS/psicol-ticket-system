<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $total = Ticket::count();

        $byStatus = Ticket::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byPriority = Ticket::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $overdue = Ticket::whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        return response()->json([
            'total'       => $total,
            'overdue'     => $overdue,
            'by_status'   => [
                'open'        => (int) ($byStatus->get('open') ?? 0),
                'in_progress' => (int) ($byStatus->get('in_progress') ?? 0),
                'resolved'    => (int) ($byStatus->get('resolved') ?? 0),
                'closed'      => (int) ($byStatus->get('closed') ?? 0),
            ],
            'by_priority' => [
                'low'      => (int) ($byPriority->get('low') ?? 0),
                'medium'   => (int) ($byPriority->get('medium') ?? 0),
                'high'     => (int) ($byPriority->get('high') ?? 0),
                'critical' => (int) ($byPriority->get('critical') ?? 0),
            ],
        ]);
    }
}

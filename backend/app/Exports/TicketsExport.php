<?php

namespace App\Exports;

use App\Models\User;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private array $priorityMap = [
        'low'      => 'Baja',
        'medium'   => 'Media',
        'high'     => 'Alta',
        'critical' => 'Crítica',
    ];

    private array $statusMap = [
        'open'        => 'Abierto',
        'in_progress' => 'En progreso',
        'resolved'    => 'Resuelto',
        'closed'      => 'Cerrado',
    ];

    public function __construct(
        private TicketService $ticketService,
        private array $filters,
        private ?User $user = null,
    ) {}

    public function query(): Builder
    {
        return $this->ticketService
            ->buildQuery($this->filters, $this->user)
            ->with(['creator:id,name', 'assignee:id,name'])
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return ['#', 'Título', 'Descripción', 'Prioridad', 'Estado', 'Creador', 'Asignado a', 'Vencimiento', 'Creado'];
    }

    public function map($ticket): array
    {
        return [
            $ticket->id,
            $ticket->title,
            $ticket->description,
            $this->priorityMap[$ticket->priority] ?? $ticket->priority,
            $this->statusMap[$ticket->status]     ?? $ticket->status,
            $ticket->creator?->name  ?? '-',
            $ticket->assignee?->name ?? 'Sin asignar',
            $ticket->due_date        ?? '-',
            $ticket->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF5B6AF0']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}

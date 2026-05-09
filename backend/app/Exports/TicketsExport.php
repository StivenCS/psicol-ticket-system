<?php

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        private array $filters,
        private ?int $userId = null,
        private bool $isCliente = false
    ) {}

    public function collection(): Collection
    {
        $query = Ticket::with(['creator:id,name', 'assignee:id,name']);

        if ($this->isCliente && $this->userId) {
            $query->where('creator_id', $this->userId);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if (!empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['assigned_to'])) {
            $query->where('assigned_to', $this->filters['assigned_to']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('due_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('due_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
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

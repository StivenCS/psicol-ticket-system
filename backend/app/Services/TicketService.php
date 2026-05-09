<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TicketService
{
    public function buildQuery(array $filters = [], ?User $user = null): Builder
    {
        $query = Ticket::query();

        if ($user?->hasRole('cliente')) {
            $query->where('creator_id', $user->id);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('due_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('due_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function create(array $data, int $creatorId): Ticket
    {
        $ticket = Ticket::create([...$data, 'creator_id' => $creatorId]);
        $ticket->load(['creator:id,name,email', 'assignee:id,name,email']);
        return $ticket;
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);
        $ticket->load(['creator:id,name,email', 'assignee:id,name,email']);
        return $ticket;
    }
}

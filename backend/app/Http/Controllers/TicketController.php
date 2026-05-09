<?php

namespace App\Http\Controllers;

use App\Exports\TicketsExport;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketController extends Controller
{
    private const ALLOWED_SORTS = ['id', 'title', 'priority', 'status', 'due_date', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $user  = Auth::guard('api')->user();
        $query = Ticket::with([
            'creator:id,name,email',
            'assignee:id,name,email',
        ]);

        if ($user->hasRole('cliente')) {
            $query->where('creator_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('due_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('due_date', '<=', $dateTo);
        }

        $sortBy  = in_array($request->input('sort_by'), self::ALLOWED_SORTS)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'required|in:low,medium,high,critical',
            'status'      => 'sometimes|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date'    => 'nullable|date',
        ]);

        $data['creator_id'] = Auth::guard('api')->id();

        $ticket = Ticket::create($data);
        $ticket->load(['creator:id,name,email', 'assignee:id,name,email']);

        return response()->json($ticket, 201);
    }

    public function show(string $id): JsonResponse
    {
        $user   = Auth::guard('api')->user();
        $ticket = Ticket::with(['creator:id,name,email', 'assignee:id,name,email'])
            ->findOrFail($id);

        if ($user->hasRole('cliente') && $ticket->creator_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($ticket);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority'    => 'sometimes|in:low,medium,high,critical',
            'status'      => 'sometimes|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date'    => 'nullable|date',
        ]);

        $ticket->update($data);
        $ticket->load(['creator:id,name,email', 'assignee:id,name,email']);

        return response()->json($ticket);
    }

    public function destroy(string $id): JsonResponse
    {
        Ticket::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user    = Auth::guard('api')->user();
        $filters = $request->only(['search', 'priority', 'status', 'assigned_to', 'date_from', 'date_to']);
        $format  = $request->input('format') === 'csv' ? 'csv' : 'xlsx';

        $export   = new TicketsExport($filters, $user->id, $user->hasRole('cliente'));
        $filename = 'tickets_' . now()->format('Ymd_His') . '.' . $format;

        return $format === 'csv'
            ? Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV)
            : Excel::download($export, $filename);
    }
}

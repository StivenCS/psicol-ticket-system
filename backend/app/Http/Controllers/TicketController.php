<?php

namespace App\Http\Controllers;

use App\Exports\TicketsExport;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketController extends Controller
{
    private const ALLOWED_SORTS = ['id', 'title', 'priority', 'status', 'due_date', 'created_at'];

    public function __construct(private TicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $user    = Auth::guard('api')->user();
        $filters = $request->only(['search', 'priority', 'status', 'assigned_to', 'date_from', 'date_to']);

        $sortBy  = in_array($request->input('sort_by'), self::ALLOWED_SORTS)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 15), 100);

        $tickets = $this->ticketService
            ->buildQuery($filters, $user)
            ->with(['creator:id,name,email', 'assignee:id,name,email'])
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return response()->json($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->create($request->validated(), Auth::guard('api')->id());

        return response()->json($ticket, 201);
    }

    public function show(string $id): JsonResponse
    {
        $user   = Auth::guard('api')->user();
        $ticket = Ticket::with(['creator:id,name,email', 'assignee:id,name,email'])->findOrFail($id);

        if ($user->hasRole('cliente') && $ticket->creator_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($ticket);
    }

    public function update(UpdateTicketRequest $request, string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        return response()->json($this->ticketService->update($ticket, $request->validated()));
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

        $export   = new TicketsExport($this->ticketService, $filters, $user);
        $filename = 'tickets_' . now()->format('Ymd_His') . '.' . $format;

        return $format === 'csv'
            ? Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV)
            : Excel::download($export, $filename);
    }
}

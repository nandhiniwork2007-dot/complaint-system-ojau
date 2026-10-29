<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageUsers(), 403, 'You do not have permission to view the audit history.');

        $paginator = AuditLog::query()
            ->with('user:id,name')
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', $request->string('action')->value().'%'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->value();
                $query->where(function ($nested) use ($search) {
                    $nested->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $paginator->getCollection()->transform(fn (AuditLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'user' => $log->user?->name,
            'target_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
            'target_id' => $log->auditable_id,
            'created_at' => $log->created_at?->toIso8601String(),
        ]);

        return response()->json($paginator);
    }
}

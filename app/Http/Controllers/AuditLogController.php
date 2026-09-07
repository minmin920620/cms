<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('user_role')) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('role', $request->user_role));
        }

        if ($request->filled('record_type')) {
            $query->where('auditable_type', 'App\\Models\\' . $request->record_type);
        }

        if ($request->filled('case_number')) {
            $caseNumber = $request->case_number;
            $query->where(function ($auditQuery) use ($caseNumber) {
                $auditQuery
                    ->where('old_values->case_number', 'like', "%{$caseNumber}%")
                    ->orWhere('new_values->case_number', 'like', "%{$caseNumber}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->paginate(15)->withQueryString();
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $roles = User::query()->select('role')->distinct()->orderBy('role')->pluck('role');
        $recordTypes = AuditLog::query()
            ->whereNotNull('auditable_type')
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(fn ($type) => class_basename($type));

        return view('audit-logs.index', compact('auditLogs', 'actions', 'users', 'roles', 'recordTypes'));
    }
}

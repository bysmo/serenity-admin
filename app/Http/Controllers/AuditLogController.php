<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Afficher la liste des logs d'audit
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('actor')->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $logs = $query->paginate($perPage);
        $users = User::orderBy('name')->get();
        $actions = AuditLog::distinct()->pluck('action')->sort();
        $models = AuditLog::distinct()->pluck('model')->sort();

        return view('audit-logs.index', compact('logs', 'users', 'actions', 'models'));
    }

    /**
     * Afficher les détails d'un log
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('actor');
        return view('audit-logs.show', compact('auditLog'));
    }
}

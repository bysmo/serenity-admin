<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Helpers\SecurityHelper;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Afficher la liste des logs d'audit
     */
    public function index(Request $request)
    {
        // Validation des filtres
        $request->validate([
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin'   => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
            'action'     => 'nullable|string|in:created,updated,deleted,login,logout,backup_create,backup_download,backup_restore,backup_delete',
            'model'      => 'nullable|string|max:150',
            'actor_id'   => 'nullable|integer|min:1',
        ]);

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
                $q->where('action', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('model', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('description', 'like', SecurityHelper::likeSearch($search));
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

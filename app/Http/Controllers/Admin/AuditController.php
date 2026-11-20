<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('audit.view');

        $logs = DocumentAuditLog::with(['user', 'document'])
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->input('user'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->input('from'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->input('to'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->input('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('description', 'like', "%{$v}%")
                ->orWhereHas('document', fn ($d) => $d->where('reference', 'like', "%{$v}%"))))
            ->latest('id')->paginate(50)->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'actions' => DocumentAuditLog::distinct()->orderBy('action')->pluck('action'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Support\Facades\Gate;
use Throwable;

class BackupController extends Controller
{
    public function index(BackupService $backups)
    {
        Gate::authorize('backups.manage');

        return view('admin.backups.index', ['backups' => $backups->list()]);
    }

    public function store(BackupService $backups)
    {
        Gate::authorize('backups.manage');
        try {
            $file = $backups->create();
        } catch (Throwable $e) {
            return back()->withErrors(['backup' => 'Sauvegarde impossible : '.$e->getMessage()]);
        }
        AuditLogger::log('sauvegarde', 'Sauvegarde manuelle : '.basename($file));

        return back()->with('status', 'Sauvegarde créée : '.basename($file));
    }

    public function download(string $name, BackupService $backups)
    {
        Gate::authorize('backups.manage');
        $path = $backups->directory().'/'.basename($name);
        abort_unless(is_file($path), 404);
        AuditLogger::log('sauvegarde_telechargee', 'Sauvegarde téléchargée : '.basename($name));

        return response()->download($path);
    }
}

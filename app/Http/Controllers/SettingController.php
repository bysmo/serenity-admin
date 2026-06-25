<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $settings = AppSetting::orderBy('groupe')->orderBy('cle')->get();
        $settingsByGroup = $settings->groupBy('groupe');
        
        // Vérifier le statut du scheduler
        $schedulerStatus = $this->checkSchedulerStatus();
        
        // Vérifier/créer le lien symbolique storage si nécessaire
        $this->ensureStorageLink();
        
        return view('settings.index', compact('settings', 'settingsByGroup', 'schedulerStatus'));
    }
    
    /**
     * S'assurer que le lien symbolique storage existe
     */
    private function ensureStorageLink()
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        
        // Sur Windows, on crée une copie ou on vérifie le lien
        if (!File::exists($link)) {
            try {
                // Essayer de créer le lien symbolique
                if (PHP_OS_FAMILY === 'Windows') {
                    // Sur Windows, créer le lien s'il n'existe pas
                    if (!File::isDirectory($link)) {
                        // Créer un lien symbolique (nécessite des permissions admin)
                        exec('mklink /D "' . $link . '" "' . $target . '"');
                    }
                } else {
                    // Sur Linux/Unix
                    symlink($target, $link);
                }
            } catch (\Exception $e) {
                // En cas d'erreur, on continue quand même
            }
        }
    }
    
    /**
     * Vérifier si le scheduler cron est configuré
     */
    private function checkSchedulerStatus()
    {
        $status = [
            'configured' => false,
            'last_run' => null,
            'command' => 'php artisan schedule:run',
            'cron_command' => '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
            'windows_command' => null,
        ];
        
        // Pour Windows, créer une tâche planifiée
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $status['windows_command'] = 'schtasks /Create /SC MINUTE /MO 1 /TN "Laravel Scheduler" /TR "' . base_path() . '\\artisan schedule:run" /F';
            // Sur Windows, on ne peut pas vraiment vérifier automatiquement
            $status['configured'] = false; // Par défaut, on considère qu'il n'est pas configuré
            $status['check_method'] = 'manual';
        } else {
            // Sur Linux/Unix, on peut vérifier le crontab
            $cronContent = shell_exec('crontab -l 2>/dev/null');
            
            if ($cronContent && str_contains($cronContent, 'schedule:run')) {
                $status['configured'] = true;
                $status['check_method'] = 'crontab';
                
                // Essayer de récupérer la dernière exécution depuis les logs
                $logPath = storage_path('logs/laravel.log');
                if (file_exists($logPath)) {
                    $lastLine = shell_exec("tail -n 100 {$logPath} | grep 'Running scheduled command' | tail -n 1");
                    if ($lastLine) {
                        // Extraire la date si possible
                        preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $lastLine, $matches);
                        if (isset($matches[1])) {
                            $status['last_run'] = $matches[1];
                        }
                    }
                }
            } else {
                $status['configured'] = false;
                $status['check_method'] = 'crontab';
            }
        }
        
        return $status;
    }

    /**
     * Mettre à jour les paramètres
     */
    public function update(Request $request)
    {
        $settings = $request->except(['_token', '_method', 'entreprise_logo_upload']);

        // Gestion de l'upload du logo
        if ($request->hasFile('entreprise_logo_upload')) {
            $file = $request->file('entreprise_logo_upload');
            
            // Valider le fichier
            $request->validate([
                'entreprise_logo_upload' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            // Créer le dossier si nécessaire
            $logoDir = storage_path('app/public/logos');
            if (!File::exists($logoDir)) {
                File::makeDirectory($logoDir, 0755, true);
            }
            
            // Supprimer l'ancien logo s'il existe
            $oldLogoSetting = AppSetting::where('cle', 'entreprise_logo')->first();
            if ($oldLogoSetting && $oldLogoSetting->valeur) {
                $oldLogoPath = storage_path('app/public/' . $oldLogoSetting->valeur);
                if (File::exists($oldLogoPath)) {
                    File::delete($oldLogoPath);
                }
            }
            
            // Générer un nom unique
            $filename = 'logo_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Déplacer le fichier
            $file->move($logoDir, $filename);
            
            // Enregistrer le chemin relatif (logos/filename)
            $logoSetting = AppSetting::where('cle', 'entreprise_logo')->first();
            if ($logoSetting) {
                $logoSetting->update(['valeur' => 'logos/' . $filename]);
            } else {
                AppSetting::create([
                    'cle' => 'entreprise_logo',
                    'valeur' => 'logos/' . $filename,
                    'type' => 'string',
                    'description' => 'Logo de l\'entreprise (chemin du fichier)',
                    'groupe' => 'entreprise',
                ]);
            }
        }

        foreach ($settings as $cle => $valeur) {
            $setting = AppSetting::where('cle', $cle)->first();
            
            if ($setting) {
                $formattedValue = match ($setting->type) {
                    'integer' => (string) $valeur,
                    'boolean' => filter_var($valeur, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                    'json' => json_encode($valeur),
                    default => $valeur,
                };

                $setting->update(['valeur' => $formattedValue]);

                // Enregistrer dans le journal d'audit
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'update',
                    'model' => 'AppSetting',
                    'model_id' => $setting->id,
                    'new_values' => ['cle' => $cle, 'valeur' => $formattedValue],
                    'description' => "Paramètre '{$cle}' modifié",
                ]);
            }
        }

        return redirect()->route('settings.index')
            ->with('success', 'Paramètres mis à jour avec succès');
    }

    /**
     * Créer un nouveau paramètre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cle' => 'required|string|max:255|unique:app_settings,cle',
            'valeur' => 'nullable|string',
            'type' => 'required|in:string,integer,boolean,json',
            'description' => 'nullable|string|max:2000',
            'groupe' => 'required|string|max:255',
        ]);

        $setting = AppSetting::create($validated);

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'model' => 'AppSetting',
            'model_id' => $setting->id,
            'new_values' => $setting->toArray(),
            'description' => "Paramètre '{$setting->cle}' créé",
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Paramètre créé avec succès');
    }
}

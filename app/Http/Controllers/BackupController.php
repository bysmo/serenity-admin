<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    /**
     * Afficher la page de gestion des backups
     */
    public function index()
    {
        $backups = [];
        $backupPath = storage_path('app/backups');
        
        if (File::exists($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $file) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }
        
        // Trier par date (plus récent en premier)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return view('backups.index', compact('backups'));
    }

    /**
     * Créer un backup de la base de données
     */
    public function create()
    {
        try {
            $timestamp = date('Y-m-d_His');
            $backupDir = storage_path('app/backups');
            
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            if (config('database.default') === 'sqlite') {
                // Backup SQLite
                $dbName = config('database.connections.sqlite.database', 'database.sqlite');
                $dbPath = database_path($dbName);
                $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'backup_' . $timestamp . '.sqlite';
                
                if (!File::exists($dbPath)) {
                    return redirect()->route('backups.index')
                        ->with('error', 'Fichier de base de données SQLite introuvable: ' . $dbPath);
                }
                
                File::copy($dbPath, $backupFile);
                
                if (!File::exists($backupFile) || File::size($backupFile) == 0) {
                    return redirect()->route('backups.index')
                        ->with('error', 'Échec de la copie du fichier de backup SQLite');
                }
            } else {
                // Backup MySQL avec mysqldump
                $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'backup_' . $timestamp . '.sql';
                
                $username = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');
                $host = config('database.connections.mysql.host', 'localhost');
                $port = config('database.connections.mysql.port', '3306');
                $database = config('database.connections.mysql.database');
                
                // Chercher mysqldump dans le chemin WAMP (Windows)
                $mysqldumpPath = '';
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Chemins possibles pour WAMP
                    $possiblePaths = [
                        'C:\\wamp64\\bin\\mysql\\mysql8.0.37\\bin\\mysqldump.exe',
                        'C:\\wamp64\\bin\\mysql\\mysql8.0.36\\bin\\mysqldump.exe',
                        'C:\\wamp64\\bin\\mysql\\mysql8.0.35\\bin\\mysqldump.exe',
                        'C:\\wamp64\\bin\\mysql\\mysql8.0.34\\bin\\mysqldump.exe',
                        'C:\\wamp\\bin\\mysql\\mysql8.0.37\\bin\\mysqldump.exe',
                        'C:\\wamp\\bin\\mysql\\mysql8.0.36\\bin\\mysqldump.exe',
                    ];
                    
                    foreach ($possiblePaths as $path) {
                        if (File::exists($path)) {
                            $mysqldumpPath = $path;
                            break;
                        }
                    }
                    
                    // Si non trouvé, essayer de trouver dans le PATH
                    if (empty($mysqldumpPath)) {
                        $mysqldumpPath = 'mysqldump';
                    }
                } else {
                    $mysqldumpPath = 'mysqldump';
                }
                
                // Construire la commande mysqldump pour Windows
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Sur Windows, utiliser shell_exec avec redirection
                    $command = sprintf(
                        '"%s" --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s',
                        $mysqldumpPath,
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($database)
                    );
                    
                    // Exécuter et rediriger vers le fichier
                    $output = shell_exec($command . ' 2>&1');
                    
                    // Écrire la sortie dans le fichier
                    if ($output !== null) {
                        File::put($backupFile, $output);
                    } else {
                        // Essayer avec exec et redirection
                        $commandWithRedirect = $command . ' > ' . escapeshellarg($backupFile) . ' 2>&1';
                        exec($commandWithRedirect, $execOutput, $returnVar);
                        
                        if ($returnVar !== 0) {
                            $errorMsg = implode("\n", $execOutput);
                            return redirect()->route('backups.index')
                                ->with('error', 'Erreur mysqldump: ' . ($errorMsg ?: 'Commande échouée'));
                        }
                    }
                } else {
                    // Sur Linux/Unix
                    $command = sprintf(
                        '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s > %s 2>&1',
                        $mysqldumpPath,
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($database),
                        escapeshellarg($backupFile)
                    );
                    
                    exec($command, $output, $returnVar);
                    
                    if ($returnVar !== 0) {
                        $errorMsg = implode("\n", $output);
                        if (File::exists($backupFile) && File::size($backupFile) == 0) {
                            File::delete($backupFile);
                        }
                        return redirect()->route('backups.index')
                            ->with('error', 'Erreur mysqldump: ' . $errorMsg);
                    }
                }
                
                // Vérifier que le fichier a été créé et n'est pas vide
                if (!File::exists($backupFile)) {
                    return redirect()->route('backups.index')
                        ->with('error', 'Échec de la création du backup. Le fichier n\'a pas été créé. Vérifiez que mysqldump est accessible.');
                }
                
                if (File::size($backupFile) == 0) {
                    File::delete($backupFile);
                    return redirect()->route('backups.index')
                        ->with('error', 'Le fichier de backup est vide. Vérifiez les paramètres de connexion MySQL et que mysqldump fonctionne correctement.');
                }
            }

            // Enregistrer dans le journal d'audit
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'backup_create',
                'model' => 'Backup',
                'new_values' => ['file' => basename($backupFile)],
                'description' => "Backup créé: " . basename($backupFile),
            ]);

            return redirect()->route('backups.index')
                ->with('success', 'Backup créé avec succès: ' . basename($backupFile));
        } catch (\Exception $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Erreur lors de la création du backup: ' . $e->getMessage());
        }
    }

    /**
     * Valider un nom de fichier de backup (protection contre le path traversal)
     */
    private function validateBackupFilename(string $filename): string
    {
        // Nettoyer le nom de fichier : uniquement le basename, pas de répertoire
        $filename = basename($filename);

        // Refuser les tentatives de traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(400, 'Nom de fichier invalide.');
        }

        // Vérifier que le fichier résolu est bien dans le répertoire backups
        $resolvedPath = realpath(storage_path('app/backups/' . $filename));
        $backupDir = realpath(storage_path('app/backups'));

        if ($resolvedPath === false || $backupDir === false || !str_starts_with($resolvedPath, $backupDir)) {
            abort(400, 'Chemin de fichier invalide.');
        }

        return $filename;
    }

    /**
     * Télécharger un backup
     */
    public function download($filename)
    {
        $filename = $this->validateBackupFilename($filename);
        $filePath = storage_path('app/backups/' . $filename);
        
        if (!File::exists($filePath)) {
            return redirect()->route('backups.index')
                ->with('error', 'Fichier de backup introuvable');
        }

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'backup_download',
            'model' => 'Backup',
            'new_values' => ['file' => $filename],
            'description' => "Backup téléchargé: {$filename}",
        ]);

        return response()->download($filePath);
    }

    /**
     * Restaurer un backup
     */
    public function restore($filename)
    {
        try {
            $filename = $this->validateBackupFilename($filename);
            $backupFile = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupFile)) {
                return redirect()->route('backups.index')
                    ->with('error', 'Fichier de backup introuvable');
            }

            if (config('database.default') === 'sqlite') {
                // Restauration SQLite
                $dbPath = database_path(config('database.connections.sqlite.database'));
                File::copy($backupFile, $dbPath);
            } else {
                // Restauration MySQL — utiliser escapeshellarg() sur tous les paramètres
                $command = sprintf(
                    'mysql --user=%s --password=%s --host=%s %s < %s',
                    escapeshellarg(config('database.connections.mysql.username')),
                    escapeshellarg(config('database.connections.mysql.password')),
                    escapeshellarg(config('database.connections.mysql.host')),
                    escapeshellarg(config('database.connections.mysql.database')),
                    escapeshellarg($backupFile)
                );
                
                exec($command);
            }

            // Enregistrer dans le journal d'audit
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'backup_restore',
                'model' => 'Backup',
                'new_values' => ['file' => $filename],
                'description' => "Backup restauré: {$filename}",
            ]);

            return redirect()->route('backups.index')
                ->with('success', 'Backup restauré avec succès');
        } catch (\Exception $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Erreur lors de la restauration: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un backup
     */
    public function destroy($filename)
    {
        $filename = $this->validateBackupFilename($filename);
        $filePath = storage_path('app/backups/' . $filename);
        
        if (File::exists($filePath)) {
            File::delete($filePath);

            // Enregistrer dans le journal d'audit
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'backup_delete',
                'model' => 'Backup',
                'old_values' => ['file' => $filename],
                'description' => "Backup supprimé: {$filename}",
            ]);

            return redirect()->route('backups.index')
                ->with('success', 'Backup supprimé avec succès');
        }

        return redirect()->route('backups.index')
            ->with('error', 'Fichier de backup introuvable');
    }
}

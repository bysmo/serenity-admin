<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Exception;

class InstallController extends Controller
{
    /**
     * Constructeur - Configurer les sessions en mode fichier pendant l'installation
     */
    public function __construct()
    {
        // Utiliser le driver 'file' pour les sessions pendant l'installation
        // car la table 'sessions' n'existe pas encore en base de données
        config(['session.driver' => 'file']);
    }
    
    /**
     * Afficher la page d'installation
     */
    public function index()
    {
        // Vérifier si l'application est déjà installée
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('admin.login')->with('info', 'L\'application est déjà installée.');
        }
        
        // S'assurer que SESSION_DRIVER=file est défini dans .env pendant l'installation
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (!preg_match('/^SESSION_DRIVER=/m', $envContent)) {
                $envContent .= "\nSESSION_DRIVER=file\n";
                file_put_contents($envPath, $envContent);
            } else {
                // Forcer SESSION_DRIVER=file si l'application n'est pas installée
                $envContent = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $envContent);
                file_put_contents($envPath, $envContent);
            }
        } else {
            // Créer un .env minimal avec SESSION_DRIVER=file
            $minimalEnv = "APP_NAME=Serenity\n";
            $minimalEnv .= "APP_ENV=local\n";
            $minimalEnv .= "APP_KEY=\n";
            $minimalEnv .= "APP_DEBUG=true\n";
            $minimalEnv .= "APP_URL=http://localhost\n";
            $minimalEnv .= "SESSION_DRIVER=file\n";
            file_put_contents($envPath, $minimalEnv);
        }
        
        // Forcer la configuration des sessions en 'file' pour cette requête
        config(['session.driver' => 'file']);
        
        return view('install.index');
    }
    
    /**
     * Vérifier les prérequis
     */
    public function checkRequirements()
    {
        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extension_pdo' => extension_loaded('pdo'),
            'extension_mbstring' => extension_loaded('mbstring'),
            'extension_openssl' => extension_loaded('openssl'),
            'extension_tokenizer' => extension_loaded('tokenizer'),
            'extension_xml' => extension_loaded('xml'),
            'extension_ctype' => extension_loaded('ctype'),
            'extension_json' => extension_loaded('json'),
            'extension_fileinfo' => extension_loaded('fileinfo'),
            'extension_curl' => extension_loaded('curl'),
            'extension_gd' => extension_loaded('gd'),
            'writable_storage' => is_writable(storage_path()),
            'writable_bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
            'env_exists' => file_exists(base_path('.env')) || file_exists(base_path('.env.example')),
        ];
        
        $allPassed = !in_array(false, $requirements, true);
        
        return response()->json([
            'requirements' => $requirements,
            'all_passed' => $allPassed,
            'php_version' => PHP_VERSION,
        ]);
    }
    
    /**
     * Vérifier si l'application est déjà installée (sécurité)
     */
    private function ensureNotInstalled(): void
    {
        if (file_exists(storage_path('installed'))) {
            abort(403, 'L\'application est déjà installée. Cette action n\'est pas autorisée.');
        }
    }

    /**
     * Vérifier la connexion à la base de données
     */
    public function checkDatabase(Request $request)
    {
        // Sécurité : empêcher la réinstallation
        $this->ensureNotInstalled();

        $validated = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_name' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'db_connection' => 'required|in:mysql,sqlite',
        ]);

        try {
            // Mettre à jour le fichier .env d'abord
            $this->updateEnvFile($validated);
            
            // Recharger la configuration
            config(['database.default' => $validated['db_connection']]);
            
            if ($validated['db_connection'] === 'mysql') {
                config([
                    'database.connections.mysql.host' => $validated['db_host'],
                    'database.connections.mysql.port' => $validated['db_port'],
                    'database.connections.mysql.database' => $validated['db_name'],
                    'database.connections.mysql.username' => $validated['db_username'],
                    'database.connections.mysql.password' => $validated['db_password'],
                ]);
            }
            
            // Tester la connexion
            DB::purge('mysql');
            DB::connection($validated['db_connection'])->getPdo();
            
            return response()->json([
                'success' => true,
                'message' => 'Connexion à la base de données réussie.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de se connecter à la base de données: ' . $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Mettre à jour le fichier .env
     */
    private function updateEnvFile($dbConfig)
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');
        
        // Créer .env depuis .env.example si nécessaire
        if (!file_exists($envPath) && file_exists($envExamplePath)) {
            copy($envExamplePath, $envPath);
        } elseif (!file_exists($envPath)) {
            // Créer un fichier .env basique
            $defaultEnv = "APP_NAME=Serenity\n";
            $defaultEnv .= "APP_ENV=local\n";
            $defaultEnv .= "APP_KEY=\n";
            $defaultEnv .= "APP_DEBUG=true\n";
            $defaultEnv .= "APP_URL=http://localhost\n\n";
            $defaultEnv .= "SESSION_DRIVER=file\n\n";
            $defaultEnv .= "DB_CONNECTION={$dbConfig['db_connection']}\n";
            if ($dbConfig['db_connection'] === 'mysql') {
                $defaultEnv .= "DB_HOST={$dbConfig['db_host']}\n";
                $defaultEnv .= "DB_PORT={$dbConfig['db_port']}\n";
                $defaultEnv .= "DB_DATABASE={$dbConfig['db_name']}\n";
                $defaultEnv .= "DB_USERNAME={$dbConfig['db_username']}\n";
                $defaultEnv .= "DB_PASSWORD={$dbConfig['db_password']}\n";
            } else {
                $defaultEnv .= "DB_DATABASE=" . database_path('database.sqlite') . "\n";
            }
            
            file_put_contents($envPath, $defaultEnv);
        }
        
        // Mettre à jour les valeurs de la base de données
        $envContent = file_get_contents($envPath);
        
        // S'assurer que SESSION_DRIVER=file est défini pendant l'installation
        if (!preg_match('/^SESSION_DRIVER=/m', $envContent)) {
            $envContent .= "\nSESSION_DRIVER=file\n";
        } else {
            // Remplacer SESSION_DRIVER par 'file' si l'application n'est pas installée
            if (!file_exists(storage_path('installed'))) {
                $envContent = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $envContent);
            }
        }
        
        $envContent = preg_replace('/^DB_CONNECTION=.*/m', "DB_CONNECTION={$dbConfig['db_connection']}", $envContent);
        
        if ($dbConfig['db_connection'] === 'mysql') {
            $envContent = preg_replace('/^DB_HOST=.*/m', "DB_HOST={$dbConfig['db_host']}", $envContent);
            $envContent = preg_replace('/^DB_PORT=.*/m', "DB_PORT={$dbConfig['db_port']}", $envContent);
            $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$dbConfig['db_name']}", $envContent);
            $envContent = preg_replace('/^DB_USERNAME=.*/m', "DB_USERNAME={$dbConfig['db_username']}", $envContent);
            $envContent = preg_replace('/^DB_PASSWORD=.*/m', "DB_PASSWORD={$dbConfig['db_password']}", $envContent);
        } else {
            $dbPath = database_path('database.sqlite');
            $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$dbPath}", $envContent);
            // Créer le fichier SQLite s'il n'existe pas
            if (!file_exists($dbPath)) {
                touch($dbPath);
            }
        }
        
        file_put_contents($envPath, $envContent);
    }
    
    /**
     * Exécuter les migrations
     */
    public function runMigrations()
    {
        // Sécurité : empêcher la réinstallation
        $this->ensureNotInstalled();

        try {
            Artisan::call('migrate', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Migrations exécutées avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors des migrations: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Générer la clé d'application
     */
    public function generateKey()
    {
        // Sécurité : empêcher la régénération de clé après installation
        $this->ensureNotInstalled();

        try {
            $envPath = base_path('.env');
            
            // Vérifier si le fichier .env existe
            if (!file_exists($envPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier .env n\'existe pas. Veuillez configurer la base de données d\'abord.',
                ], 500);
            }
            
            // Lire le contenu du fichier .env
            $envContent = file_get_contents($envPath);
            
            // Vérifier si APP_KEY est déjà défini et non vide
            if (preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches)) {
                $existingKey = trim($matches[1]);
                if (!empty($existingKey) && $existingKey !== 'base64:') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Clé d\'application déjà présente.',
                    ]);
                }
            }
            
            // Essayer d'utiliser la commande Artisan
            try {
                Artisan::call('key:generate', ['--force' => true]);
            } catch (Exception $artisanException) {
                // Si Artisan échoue, générer la clé manuellement
                $key = 'base64:' . base64_encode(random_bytes(32));
                
                // Mettre à jour le fichier .env
                if (preg_match('/^APP_KEY=.*/m', $envContent)) {
                    $envContent = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, $envContent);
                } else {
                    // Ajouter APP_KEY si elle n'existe pas
                    $envContent = preg_replace('/(^APP_NAME=.*)/m', '$1' . "\nAPP_KEY=" . $key, $envContent, 1);
                }
                
                file_put_contents($envPath, $envContent);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Clé d\'application générée avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la clé: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Exécuter les seeders
     */
    public function runSeeders()
    {
        // Sécurité : empêcher le re-seeding après installation
        $this->ensureNotInstalled();

        try {
            Artisan::call('db:seed', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Base de données initialisée avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Créer le lien symbolique storage
     */
    public function createStorageLink()
    {
        try {
            $link = public_path('storage');
            $target = storage_path('app/public');
            
            // Créer le répertoire cible s'il n'existe pas
            if (!File::exists($target)) {
                File::makeDirectory($target, 0755, true);
            }
            
            // Vérifier si le lien existe déjà et fonctionne
            if (File::exists($link) || is_link($link)) {
                // Vérifier si le lien pointe vers la bonne cible
                $realPath = realpath($link);
                $targetPath = realpath($target);
                if ($realPath === $targetPath) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Lien symbolique existe déjà.',
                    ]);
                } else {
                    // Supprimer le lien incorrect
                    if (is_link($link)) {
                        unlink($link);
                    } elseif (is_dir($link)) {
                        File::deleteDirectory($link);
                    }
                }
            }
            
            // Créer le lien symbolique
            if (PHP_OS_FAMILY === 'Windows') {
                // Sur Windows - mklink nécessite des privilèges administrateur
                // Si mklink échoue, créer une copie des fichiers ou un raccourci
                $linkWindows = str_replace('/', '\\', $link);
                $targetWindows = str_replace('/', '\\', $target);
                
                // Supprimer le lien/dossier existant s'il existe
                if (File::exists($link)) {
                    if (is_dir($link) && !is_link($link)) {
                        File::deleteDirectory($link);
                    } else {
                        @unlink($link);
                    }
                }
                
                // Essayer de créer le lien symbolique
                $output = [];
                $returnVar = 0;
                $command = 'mklink /D "' . $linkWindows . '" "' . $targetWindows . '" 2>&1';
                exec($command, $output, $returnVar);
                
                // Si mklink échoue (souvent à cause des privilèges), créer une copie du répertoire
                if ($returnVar !== 0 || !File::exists($link)) {
                    // Créer le répertoire directement dans public/storage
                    if (!File::exists($link)) {
                        File::makeDirectory($link, 0755, true);
                    }
                    return response()->json([
                        'success' => true,
                        'message' => 'Répertoire storage créé (lien symbolique non disponible sur Windows sans privilèges administrateur).',
                    ]);
                }
            } else {
                // Sur Linux/Unix
                if (!symlink($target, $link)) {
                    throw new Exception('Impossible de créer le lien symbolique.');
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Lien symbolique créé avec succès.',
            ]);
        } catch (Exception $e) {
            // En cas d'erreur, essayer de créer au moins le répertoire
            try {
                $link = public_path('storage');
                if (!File::exists($link)) {
                    File::makeDirectory($link, 0755, true);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Répertoire storage créé (lien symbolique non disponible).',
                ]);
            } catch (Exception $e2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du lien: ' . $e->getMessage(),
                ], 500);
            }
        }
    }
    
    /**
     * Finaliser l'installation
     */
    public function finish()
    {
        // Sécurité : empêcher la finalisation multiple
        $this->ensureNotInstalled();

        try {
            // Remettre le driver de session en 'database' maintenant que les migrations sont exécutées
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                // Ajouter ou mettre à jour SESSION_DRIVER
                if (preg_match('/^SESSION_DRIVER=.*/m', $envContent)) {
                    $envContent = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=database', $envContent);
                } else {
                    $envContent .= "\nSESSION_DRIVER=database\n";
                }
                file_put_contents($envPath, $envContent);
            }
            
            // Créer un fichier pour indiquer que l'installation est terminée
            file_put_contents(storage_path('installed'), date('Y-m-d H:i:s'));
            
            // Optimiser l'application
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            
            return response()->json([
                'success' => true,
                'message' => 'Installation terminée avec succès.',
                'redirect' => route('admin.login'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation: ' . $e->getMessage(),
            ], 500);
        }
    }
}
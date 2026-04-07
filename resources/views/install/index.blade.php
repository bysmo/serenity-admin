<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appNomComplet = \App\Models\AppSetting::get('app_nom', 'Serenity');
    @endphp
    <title>{{ $appNomComplet }} - Installation</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts - Ubuntu Light -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-image: url('{{ asset('images/background.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
            padding: 2rem 0;
        }
        
        .install-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .install-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 1.5rem;
            width: 100%;
            max-width: 420px;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .install-header h1 {
            color: #1e3a5f;
            font-weight: 300;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .install-header h1 i {
            font-size: 1.1rem;
        }
        
        .install-header p {
            color: #666;
            font-size: 0.75rem;
            font-weight: 300;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 400;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: #667eea;
            color: white;
        }
        
        .step.completed .step-circle {
            background: #28a745;
            color: white;
        }
        
        .step.completed .step-circle::before {
            content: '\2713';
        }
        
        .step-label {
            font-size: 0.65rem;
            color: #999;
            font-weight: 300;
        }
        
        .step.active .step-label {
            color: #667eea;
            font-weight: 400;
        }
        
        .step-content {
            min-height: 350px;
        }
        
        .step-content h3 {
            font-size: 1rem;
            font-weight: 400;
            color: #1e3a5f;
        }
        
        .step-content p {
            font-size: 0.75rem;
            font-weight: 300;
        }
        
        .text-muted {
            font-size: 0.75rem !important;
        }
        
        #requirements-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.4rem 0.5rem;
            margin-bottom: 0;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.65rem;
        }
        
        .requirement-item.passed {
            background: #d4edda;
        }
        
        .requirement-item.failed {
            background: #f8d7da;
        }
        
        .requirement-name {
            flex: 1;
            font-size: 0.65rem;
            font-weight: 300;
        }
        
        .requirement-status {
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }
        
        .requirement-status i {
            font-size: 0.7rem;
        }
        
        .form-label {
            font-weight: 300;
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 0.35rem;
        }
        
        .btn-primary {
            background: #1e3a5f;
            border: none;
            padding: 0.35rem 1rem;
            font-weight: 300;
            font-size: 0.7rem;
        }
        
        .btn-primary:hover {
            background: #2c5282;
        }
        
        .btn-primary i {
            font-size: 0.7rem;
        }
        
        .btn-success {
            background: #28a745;
            border: none;
            padding: 0.35rem 1rem;
            font-weight: 300;
            font-size: 0.7rem;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-success i {
            font-size: 0.7rem;
        }
        
        .btn-secondary {
            font-weight: 300;
            font-size: 0.7rem;
            padding: 0.35rem 1rem;
        }
        
        .btn-secondary i {
            font-size: 0.7rem;
        }
        
        .alert {
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 300;
        }
        
        .alert i {
            font-size: 0.75rem;
        }
        
        .form-control, .form-select {
            font-size: 0.8rem;
            font-weight: 300;
            padding: 0.4rem 0.6rem;
        }
        
        .progress-container {
            margin: 1rem 0;
        }
        
        .progress-step {
            padding: 0.6rem;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
        }
        
        .progress-step i {
            font-size: 0.85rem;
        }
        
        .progress-step.success {
            background: #d4edda;
            color: #155724;
        }
        
        .progress-step.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .progress-step.processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <h1><i class="bi bi-cash-coin"></i> Serenity</h1>
                <p>Procédure d'installation étape par étape</p>
            </div>
            
            <!-- Indicateur d'étapes -->
            <div class="step-indicator">
                <div class="step active" id="step-1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Prérequis</div>
                </div>
                <div class="step" id="step-2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Base de données</div>
                </div>
                <div class="step" id="step-3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Installation</div>
                </div>
                <div class="step" id="step-4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Finalisation</div>
                </div>
            </div>
            
            <!-- Contenu des étapes -->
            <div class="step-content">
                <!-- Étape 1: Prérequis -->
                <div id="content-step-1" class="step-panel">
                    <h3 class="mb-3" style="font-size: 1rem; font-weight: 400;">Vérification des prérequis</h3>
                    <p class="text-muted mb-3" style="font-size: 0.75rem;">Vérifions que votre environnement répond aux exigences du système.</p>
                    
                    <div id="requirements-list"></div>
                    
                    <div class="mt-4 text-end">
                        <button class="btn btn-primary" onclick="checkRequirements()">
                            <i class="bi bi-check-circle"></i> Vérifier les prérequis
                        </button>
                        <button class="btn btn-success d-none" id="btn-next-1" onclick="nextStep(2)">
                            <i class="bi bi-arrow-right"></i> Continuer
                        </button>
                    </div>
                </div>
                
                <!-- Étape 2: Base de données -->
                <div id="content-step-2" class="step-panel d-none">
                    <h3 class="mb-3" style="font-size: 1rem; font-weight: 400;">Configuration de la base de données</h3>
                    <p class="text-muted mb-3" style="font-size: 0.75rem;">Configurez la connexion à votre base de données.</p>
                    
                    <form id="db-form">
                        <div class="mb-3">
                            <label class="form-label">Type de base de données</label>
                            <select class="form-select" id="db_connection" name="db_connection" onchange="toggleDbFields()">
                                <option value="sqlite">SQLite (Simple)</option>
                                <option value="mysql" selected>MySQL / MariaDB</option>
                            </select>
                        </div>
                        
                        <div id="mysql-fields">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hôte</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="127.0.0.1" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Port</label>
                                    <input type="number" class="form-control" id="db_port" name="db_port" value="3306" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom de la base de données</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom d'utilisateur</label>
                                    <input type="text" class="form-control" id="db_username" name="db_username" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mot de passe</label>
                                    <input type="password" class="form-control" id="db_password" name="db_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info d-none" id="sqlite-info">
                            <i class="bi bi-info-circle"></i> SQLite sera utilisé. Le fichier de base de données sera créé automatiquement.
                        </div>
                        
                        <div id="db-status"></div>
                        
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-secondary" onclick="previousStep(2)">
                                <i class="bi bi-arrow-left"></i> Précédent
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Tester la connexion
                            </button>
                            <button class="btn btn-success d-none" id="btn-next-2" onclick="nextStep(3)">
                                <i class="bi bi-arrow-right"></i> Continuer
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Étape 3: Installation -->
                <div id="content-step-3" class="step-panel d-none">
                    <h3 class="mb-3" style="font-size: 1rem; font-weight: 400;">Installation en cours</h3>
                    <p class="text-muted mb-3" style="font-size: 0.75rem;">L'application est en cours d'installation. Veuillez patienter...</p>
                    
                    <div id="progress-steps"></div>
                    
                    <div class="mt-4 text-end d-none" id="btn-next-3-container">
                        <button class="btn btn-success" id="btn-next-3" onclick="nextStep(4)">
                            <i class="bi bi-arrow-right"></i> Continuer
                        </button>
                    </div>
                </div>
                
                <!-- Étape 4: Finalisation -->
                <div id="content-step-4" class="step-panel d-none">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="mb-2" style="font-size: 1rem; font-weight: 400;">Installation terminée !</h3>
                        <p class="text-muted mb-3" style="font-size: 0.75rem;">L'application E-Cotisations a été installée avec succès.</p>
                        <p class="text-muted mb-3" style="font-size: 0.75rem;">
                            <strong>Compte administrateur par défaut :</strong><br>
                            Email: admin@ecotisations.com<br>
                            Mot de passe: password
                        </p>
                        <p class="alert alert-warning" style="font-size: 0.75rem; padding: 0.5rem 0.75rem;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 0.75rem;"></i> 
                            <strong>Important :</strong> Changez le mot de passe après votre première connexion.
                        </p>
                        <a href="{{ route('admin.login') }}" class="btn btn-success">
                            <i class="bi bi-box-arrow-in-right"></i> Aller à la page de connexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        
        function toggleDbFields() {
            const connection = document.getElementById('db_connection').value;
            const mysqlFields = document.getElementById('mysql-fields');
            const sqliteInfo = document.getElementById('sqlite-info');
            
            if (connection === 'sqlite') {
                mysqlFields.classList.add('d-none');
                sqliteInfo.classList.remove('d-none');
                document.getElementById('db_host').removeAttribute('required');
                document.getElementById('db_port').removeAttribute('required');
                document.getElementById('db_name').removeAttribute('required');
                document.getElementById('db_username').removeAttribute('required');
            } else {
                mysqlFields.classList.remove('d-none');
                sqliteInfo.classList.add('d-none');
                document.getElementById('db_host').setAttribute('required', 'required');
                document.getElementById('db_port').setAttribute('required', 'required');
                document.getElementById('db_name').setAttribute('required', 'required');
                document.getElementById('db_username').setAttribute('required', 'required');
            }
        }
        
        function nextStep(step) {
            if (step > currentStep) {
                currentStep = step;
                updateStepIndicator();
            }
        }
        
        function previousStep(step) {
            if (step < currentStep) {
                currentStep = step;
                updateStepIndicator();
            }
        }
        
        function updateStepIndicator() {
            for (let i = 1; i <= 4; i++) {
                const stepEl = document.getElementById(`step-${i}`);
                const contentEl = document.getElementById(`content-step-${i}`);
                
                stepEl.classList.remove('active', 'completed');
                if (contentEl) contentEl.classList.add('d-none');
                
                if (i < currentStep) {
                    stepEl.classList.add('completed');
                } else if (i === currentStep) {
                    stepEl.classList.add('active');
                    if (contentEl) contentEl.classList.remove('d-none');
                }
            }
        }
        
        function checkRequirements() {
            fetch('{{ route("install.check-requirements") }}')
                .then(response => response.json())
                .then(data => {
                    const listEl = document.getElementById('requirements-list');
                    listEl.innerHTML = '';
                    
                    const requirements = {
                        'php_version': 'PHP Version >= 8.2.0 (' + data.php_version + ')',
                        'extension_pdo': 'Extension PDO',
                        'extension_mbstring': 'Extension MBString',
                        'extension_openssl': 'Extension OpenSSL',
                        'extension_tokenizer': 'Extension Tokenizer',
                        'extension_xml': 'Extension XML',
                        'extension_ctype': 'Extension Ctype',
                        'extension_json': 'Extension JSON',
                        'extension_fileinfo': 'Extension Fileinfo',
                        'extension_curl': 'Extension cURL',
                        'extension_gd': 'Extension GD',
                        'writable_storage': 'Dossier storage accessible en écriture',
                        'writable_bootstrap_cache': 'Dossier bootstrap/cache accessible en écriture',
                        'env_exists': 'Fichier .env ou .env.example existe',
                    };
                    
                    let allPassed = true;
                    
                    for (const [key, label] of Object.entries(requirements)) {
                        const passed = data.requirements[key];
                        if (!passed) allPassed = false;
                        
                        const item = document.createElement('div');
                        item.className = `requirement-item ${passed ? 'passed' : 'failed'}`;
                        item.innerHTML = `
                            <div class="requirement-name">${label}</div>
                            <div class="requirement-status">
                                ${passed ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>'}
                            </div>
                        `;
                        listEl.appendChild(item);
                    }
                    
                    if (allPassed) {
                        document.getElementById('btn-next-1').classList.remove('d-none');
                    } else {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger mt-3';
                        alert.style.gridColumn = '1 / -1';
                        alert.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Certains prérequis ne sont pas remplis. Veuillez les corriger avant de continuer.';
                        listEl.appendChild(alert);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la vérification des prérequis.');
                });
        }
        
        document.getElementById('db-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            const statusEl = document.getElementById('db-status');
            statusEl.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Test de connexion en cours...</div>';
            
            fetch('{{ route("install.check-database") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusEl.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle"></i> ${data.message}</div>`;
                    document.getElementById('btn-next-2').classList.remove('d-none');
                } else {
                    statusEl.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                statusEl.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Erreur lors du test de connexion.</div>';
            });
        });
        
        function runInstallation() {
            const progressEl = document.getElementById('progress-steps');
            const steps = [
                { name: 'Génération de la clé d\'application', route: '{{ route("install.generate-key") }}' },
                { name: 'Création du lien symbolique storage', route: '{{ route("install.create-storage-link") }}' },
                { name: 'Exécution des migrations', route: '{{ route("install.run-migrations") }}' },
                { name: 'Initialisation de la base de données', route: '{{ route("install.run-seeders") }}' },
            ];
            
            progressEl.innerHTML = '';
            
            let currentIndex = 0;
            
            function executeStep(index) {
                if (index >= steps.length) {
                    // Après l'installation, passer à la finalisation
                    setTimeout(() => {
                        fetch('{{ route("install.finish") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('btn-next-3-container').classList.remove('d-none');
                            } else {
                                const errorEl = document.createElement('div');
                                errorEl.className = 'alert alert-danger mt-3';
                                errorEl.innerHTML = `<i class="bi bi-x-circle"></i> Erreur lors de la finalisation: ${data.message}`;
                                progressEl.appendChild(errorEl);
                                // Afficher le bouton quand même pour permettre de continuer
                                document.getElementById('btn-next-3-container').classList.remove('d-none');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            // Afficher le bouton même en cas d'erreur pour permettre de continuer
                            document.getElementById('btn-next-3-container').classList.remove('d-none');
                        });
                    }, 500);
                    return;
                }
                
                const step = steps[index];
                const stepEl = document.createElement('div');
                stepEl.className = 'progress-step processing';
                stepEl.innerHTML = `
                    <div>
                        <i class="bi bi-hourglass-split"></i> ${step.name}...
                    </div>
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                `;
                progressEl.appendChild(stepEl);
                
                fetch(step.route, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    stepEl.classList.remove('processing');
                    if (data.success) {
                        stepEl.classList.add('success');
                        stepEl.innerHTML = `
                            <div>
                                <i class="bi bi-check-circle"></i> ${step.name} - Terminé
                            </div>
                        `;
                        setTimeout(() => executeStep(index + 1), 500);
                    } else {
                        stepEl.classList.add('error');
                        stepEl.innerHTML = `
                            <div>
                                <i class="bi bi-x-circle"></i> ${step.name} - Erreur: ${data.message}
                            </div>
                        `;
                        // Continuer quand même vers la finalisation
                        setTimeout(() => executeStep(index + 1), 1000);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    stepEl.classList.remove('processing');
                    stepEl.classList.add('error');
                    stepEl.innerHTML = `
                        <div>
                            <i class="bi bi-x-circle"></i> ${step.name} - Erreur
                        </div>
                    `;
                    // Continuer quand même vers la finalisation
                    setTimeout(() => executeStep(index + 1), 1000);
                });
            }
            
            executeStep(0);
        }
        
        // Lors du passage à l'étape 3, lancer l'installation automatiquement
        const originalNextStep = nextStep;
        nextStep = function(step) {
            originalNextStep(step);
            if (step === 3) {
                setTimeout(runInstallation, 300);
            }
        };
        
        // Initialiser
        updateStepIndicator();
    </script>
</body>
</html>
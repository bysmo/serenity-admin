<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $appNomComplet = \App\Models\AppSetting::get('app_nom', 'Serenity');
        $logoPath = \App\Models\AppSetting::get('entreprise_logo');
        $faviconUrl = null;
        
        if ($logoPath) {
            $logoFullPath = storage_path('app/public/' . $logoPath);
            $publicStorageExists = \Illuminate\Support\Facades\File::exists(public_path('storage'));
            
            if ($publicStorageExists && \Illuminate\Support\Facades\File::exists($logoFullPath)) {
                $faviconUrl = asset('storage/' . $logoPath);
            } else {
                $filename = basename($logoPath);
                $faviconUrl = route('storage.logo', ['filename' => $filename]);
            }
        }
    @endphp
    
    @if($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif
    
    <title>{{ $appNomComplet }} - Connexion Administrateur</title>
    
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
        }
        
        
        .login-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 1.5rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 1.25rem;
        }
        
        .login-header h2 {
            color: var(--primary-dark-blue, #1e3a5f);
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.75rem;
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
        }
        
        .form-label {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 0.35rem;
        }
        
        .form-control {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.4rem 0.6rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-dark-blue, #1e3a5f);
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-dark-blue, #1e3a5f);
            border-color: var(--primary-dark-blue, #1e3a5f);
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.8rem;
            padding: 0.4rem;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-blue, #2c5282);
            border-color: var(--primary-blue, #2c5282);
        }
        
        .alert {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.75rem;
            border-radius: 5px;
            padding: 0.5rem 0.75rem;
        }
        
        .form-check-label {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.75rem;
        }
        
        .mb-3 {
            margin-bottom: 0.75rem !important;
        }
        
        .invalid-feedback {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.7rem;
        }
        
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="bi bi-cash-coin"></i> Serenity - Administrateur</h2>
            </div>
            
            
            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Se souvenir de moi
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="{{ route('membre.login') }}" class="text-decoration-none" style="font-size: 0.75rem; color: #666; font-weight: 300;">
                    <i class="bi bi-person-circle"></i> Accès Client
                </a>
            </div>
        </div>
    </div>
    
    <!-- Toast Container (en haut à droite) -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="toastContainer"></div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fonction pour afficher un toast
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-info';
            const icon = type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-x-circle' : type === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle';
            
            const toastHTML = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.875rem;">
                            <i class="bi ${icon} me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 5000
            });
            toast.show();
            
            // Supprimer l'élément après la fermeture
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Afficher les messages de session comme toasts
        @if(session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif
        
        @if(session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
        
        @if($errors->any())
            @foreach($errors->all() as $error)
                showToast('{{ $error }}', 'error');
            @endforeach
        @endif
        
        @if(session('warning'))
            showToast('{{ session('warning') }}', 'warning');
        @endif
        
        @if(session('info'))
            showToast('{{ session('info') }}', 'info');
        @endif
    </script>
</body>
</html>

@php
    // Récupérer le statut du cache (par défaut on suppose que rien n'a tourné)
    $auditStatus = \Illuminate\Support\Facades\Cache::get('audit_checksums_status', [
        'is_valid'           => true,
        'rows_checked_count' => 0,
        'corrupted_count'    => 0,
        'corrupted_data'     => null,
        'last_check_time'    => now()->timestamp,
        'next_check_time'    => now()->addMinutes(10)->timestamp,
    ]);

    $isValid = $auditStatus['is_valid'] ?? true;
    $rowsCount = $auditStatus['rows_checked_count'] ?? 0;
    $corruptedCount = $auditStatus['corrupted_count'] ?? 0;
    
    // Déterminer la couleur globale
    $colorClass = $isValid ? 'text-success' : 'text-danger';
    $bgClass = $isValid ? 'bg-success-subtle' : 'bg-danger-subtle';
    $icon = $isValid ? 'bi-shield-check' : 'bi-shield-exclamation';
@endphp

<!-- Audit Gadget in Navbar -->
<div class="nav-item dropdown me-3 d-flex align-items-center" id="audit-gadget-container">
    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3 py-1 rounded-pill border {{ $bgClass }} {{ $colorClass }}" 
       href="#" 
       role="button" 
       data-bs-toggle="dropdown" 
       aria-expanded="false" 
       style="border-color: currentColor !important; font-size: 0.85rem;"
       title="{{ $rowsCount }} lignes vérifiées">
        
        <i class="bi {{ $icon }} fs-5"></i>
        <span class="fw-medium d-none d-md-inline">
            {{ $isValid ? 'Audit: Intègre' : 'Corruption (' . $corruptedCount . ')' }}
        </span>

        <!-- Jauge circulaire / Compte à rebours -->
        <div class="audit-timer ms-1" 
             data-next-time="{{ $auditStatus['next_check_time'] }}" 
             style="height: 24px; width: 24px; position: relative;">
            <svg viewBox="0 0 36 36" style="width:100%; height:100%; transform: rotate(-90deg);">
                <!-- Cercle de fond -->
                <path class="circle-bg"
                      d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="{{ $isValid ? 'rgba(25, 135, 84, 0.2)' : 'rgba(220, 53, 69, 0.2)' }}"
                      stroke-width="3"
                />
                <!-- Cercle de progression -->
                <path class="circle-progress"
                      id="audit-timer-progress"
                      stroke-dasharray="100, 100"
                      d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                      fill="none"
                      stroke="{{ $isValid ? '#198754' : '#dc3545' }}"
                      stroke-width="3"
                />
            </svg>
            <div id="audit-timer-text" style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size: 0.55rem; font-weight: bold; color: currentColor;">
                --
            </div>
        </div>
    </a>

    <!-- Menu déroulant (Détails) -->
    <div class="dropdown-menu dropdown-menu-end shadow p-0" style="width: 320px; border-radius: 12px; overflow: hidden; font-family: 'Ubuntu', sans-serif;">
        <div class="p-3 {{ $isValid ? 'bg-success' : 'bg-danger' }} text-white text-center">
            <i class="bi {{ $icon }} mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-0">Statut de l'Audit Continu</h6>
            <small class="opacity-75">Dernière vérification: {{ \Carbon\Carbon::createFromTimestamp($auditStatus['last_check_time'])->diffForHumans() }}</small>
        </div>
        
        <div class="p-3 text-center" style="font-size: 0.85rem;">
            <div class="row text-center mb-3">
                <div class="col-6 border-end">
                    <strong class="fs-5">{{ number_format($rowsCount, 0, ',', ' ') }}</strong><br>
                    <span class="text-muted" style="font-size: 0.7rem;">Lignes Scannées</span>
                </div>
                <div class="col-6">
                    <strong class="fs-5 {{ $isValid ? 'text-success' : 'text-danger' }}">{{ $corruptedCount }}</strong><br>
                    <span class="text-muted" style="font-size: 0.7rem;">Anomalies</span>
                </div>
            </div>

            @if(!$isValid && !empty($auditStatus['corrupted_data']))
                <div class="alert alert-danger p-2 text-start mb-0" style="font-size: 0.75rem; max-height: 150px; overflow-y: auto;">
                    <strong>Détail des corruptions :</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        @foreach(array_slice($auditStatus['corrupted_data'], 0, 5) as $error)
                            <li>Table <code>{{ $error['table'] }}</code> (ID: {{ $error['id'] }})</li>
                        @endforeach
                    </ul>
                    @if(count($auditStatus['corrupted_data']) > 5)
                        <div class="text-center mt-2">
                            <span class="badge bg-danger rounded-pill">+{{ count($auditStatus['corrupted_data']) - 5 }} autres</span>
                        </div>
                    @endif
                </div>
                <div class="mt-2 text-center">
                     <a href="{{ url('logs/security') }}" class="btn btn-sm btn-outline-danger" style="font-size: 0.7rem;">Voir le journal complet</a>
                </div>
            @else
                <div class="text-success fw-medium">
                    <i class="bi bi-shield-lock-fill"></i> Tous les checksums sont valides.
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const timerContainer = document.querySelector('.audit-timer');
    if (!timerContainer) return;

    const nextTimeStr = timerContainer.getAttribute('data-next-time');
    if (!nextTimeStr) return;

    const nextTime = parseInt(nextTimeStr, 10) * 1000; // ms
    const path = document.getElementById('audit-timer-progress');
    const text = document.getElementById('audit-timer-text');
    
    // Le chronomètre total est de 10 minutes (600 000 ms)
    const totalDuration = 10 * 60 * 1000;

    function updateTimer() {
        const now = new Date().getTime();
        let remaining = nextTime - now;
        
        if (remaining <= 0) {
            remaining = 0;
            text.innerText = "0s";
            path.setAttribute('stroke-dasharray', `0, 100`);
            // Pour rafraichir dynamiquement, on recharge ou on relance un appel Ajax
            return;
        }

        // Pourcentage restant
        const percent = Math.max(0, Math.min(100, (remaining / totalDuration) * 100));
        
        // Mettre à jour l'anneau SVG (cercle de 100 de circonférence)
        path.setAttribute('stroke-dasharray', `${percent}, 100`);

        // Afficher l'intérieur
        const secondsLeft = Math.floor(remaining / 1000);
        let display = "";
        
        if (secondsLeft > 60) {
            display = Math.floor(secondsLeft / 60) + "m";
        } else {
            display = secondsLeft + "s";
        }
        text.innerText = display;
    }

    setInterval(updateTimer, 1000);
    updateTimer();
});
</script>

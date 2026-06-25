<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Services\PinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class MembrePinWebController extends Controller
{
    public function __construct(protected PinService $pinService) {}

    /**
     * Affiche la vue de configuration du code PIN, intégrée au profil.
     * Cette méthode peut être appelée directement via une route GET
     * ou bien la vue peut être incluse dans profil.blade.php.
     */
    public function settings(Request $request)
    {
        return view('membres.pin.settings', [
            'membre' => $request->user('membre')
        ]);
    }

    /**
     * Définit le PIN pour la première fois.
     */
    public function setup(Request $request)
    {
        /** @var Membre $membre */
        $membre = $request->user('membre');

        if ($membre->hasPin()) {
            return back()->with('error', 'Vous avez déjà configuré un code PIN.');
        }

        $request->validate([
            'pin'              => 'required|string|size:' . PinService::PIN_LENGTH . '|regex:/^\d+$/',
            'pin_confirmation' => 'required|string|same:pin',
        ], [
            'pin.required'              => 'Le code PIN est obligatoire.',
            'pin.size'                  => 'Le code PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
            'pin.regex'                 => 'Le code PIN ne doit contenir que des chiffres.',
            'pin_confirmation.required' => 'La confirmation du PIN est obligatoire.',
            'pin_confirmation.same'     => 'Les deux codes PIN ne correspondent pas.',
        ]);

        $membre->setPin($request->input('pin'));

        return back()->with('success', 'Code PIN défini avec succès. Vous pouvez maintenant l\'activer.');
    }

    /**
     * Active le PIN ou modifie le mode.
     */
    public function enable(Request $request)
    {
        /** @var Membre $membre */
        $membre = $request->user('membre');

        if (!$membre->hasPin()) {
            return back()->with('error', 'Veuillez d\'abord définir un code PIN.');
        }

        if ($membre->isPinLocked()) {
            return back()->with('error', 'Compte temporairement verrouillé suite à de trop nombreuses tentatives.');
        }

        $request->validate([
            'pin'  => 'required|string|size:' . PinService::PIN_LENGTH . '|regex:/^\d+$/',
            'mode' => 'required|in:each_time,session',
        ]);

        if (!$membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return back()->with('error', 'Trop de tentatives. Code PIN verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.');
            }
            return back()->with('error', 'Code PIN incorrect. Tentative ' . ($membre->pin_attempts) . '/' . Membre::PIN_MAX_ATTEMPTS);
        }

        // Fermer l'ancienne session en cas de changement vers each_time
        if ($request->input('mode') === 'each_time') {
            $this->pinService->closeSession($membre->id);
        }

        if (!$membre->isPinEnabled()) {
            $membre->enablePin($request->input('mode'));
            return back()->with('success', 'L\'utilisation du code PIN a été activée.');
        } else {
            $membre->setPinMode($request->input('mode'));
            return back()->with('success', 'Le mode d\'utilisation du code PIN a été mis à jour.');
        }
    }

    /**
     * Désactive le code PIN.
     */
    public function disable(Request $request)
    {
        /** @var Membre $membre */
        $membre = $request->user('membre');

        if (!$membre->isPinEnabled()) {
            return back()->with('error', 'Le code PIN est déjà désactivé.');
        }

        if ($membre->isPinLocked()) {
            return back()->with('error', 'Compte temporairement verrouillé.');
        }

        $request->validate([
            'pin' => 'required|string|size:' . PinService::PIN_LENGTH . '|regex:/^\d+$/',
        ]);

        if (!$membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return back()->with('error', 'Trop de tentatives échouées. Compte verrouillé pour ' . Membre::PIN_LOCK_MINUTES . ' minutes.');
            }
            return back()->with('error', 'Code PIN incorrect.');
        }

        $this->pinService->closeSession($membre->id);
        $membre->disablePin();

        return back()->with('success', 'L\'utilisation du code PIN a été désactivée.');
    }

    /**
     * Change le code PIN existant.
     */
    public function change(Request $request)
    {
         /** @var Membre $membre */
         $membre = $request->user('membre');

         if (!$membre->hasPin()) {
             return back()->with('error', 'Veuillez d\'abord définir un code PIN.');
         }
 
         if ($membre->isPinLocked()) {
             return back()->with('error', 'Compte temporairement verrouillé.');
         }
 
         $request->validate([
             'old_pin'          => 'required|string|size:' . PinService::PIN_LENGTH . '|regex:/^\d+$/',
             'pin'              => 'required|string|size:' . PinService::PIN_LENGTH . '|regex:/^\d+$/|different:old_pin',
             'pin_confirmation' => 'required|string|same:pin',
         ]);
 
         if (!$membre->verifyPin($request->input('old_pin'))) {
             $membre->refresh();
             if ($membre->isPinLocked()) {
                 return back()->with('error', 'Trop de tentatives. Code PIN verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.');
             }
             return back()->with('error', 'Ancien code PIN incorrect.');
         }
 
         $this->pinService->closeSession($membre->id);
         $membre->setPin($request->input('pin'));
 
         return back()->with('success', 'Votre code PIN a été modifié avec succès.');
    }

    // ─── PARTIE INTERCEPTION WEB ──────────────────────────────────────────────

    /**
     * Affiche le formulaire demandant de saisir le PIN
     * pour autoriser une action bloquée.
     */
    public function showVerifyActionForm(Request $request)
    {
        // On s'assure qu'une action est bien en attente dans la session
        if (!Session::has('pin_intended_action')) {
            return redirect()->route('membre.dashboard')->with('error', 'Aucune action en attente.');
        }

        return view('membres.pin.verify-action', [
            'membre' => $request->user('membre')
        ]);
    }

    /**
     * Valide le PIN saisi, et si OK, affiche un formulaire auto-submit
     * pour rejouer l'action interceptée d'origine sans perdre de données.
     */
    public function verifyAction(Request $request)
    {
        if (!Session::has('pin_intended_action')) {
            return redirect()->route('membre.dashboard')->with('error', 'La session d\'action a expiré.');
        }

        /** @var Membre $membre */
        $membre = $request->user('membre');

        if ($membre->isPinLocked()) {
            return back()->with('error', 'Compte temporairement verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.');
        }

        $request->validate([
            'pin' => 'required|string|size:' . PinService::PIN_LENGTH . '|regex:/^\d+$/',
        ]);

        if (!$membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return back()->with('error', 'Code PIN incorrect. Compte verrouillé pour ' . Membre::PIN_LOCK_MINUTES . ' minutes.');
            }
            return back()->with('error', 'Code PIN incorrect. Tentative ' . ($membre->pin_attempts) . '/' . Membre::PIN_MAX_ATTEMPTS);
        }

        // --- Le code PIN est correct ---

        // Cas Option B (Session) : On ouvre une session Cache pour 5 minutes
        if ($membre->isPinEnabled() && $membre->isPinModeSession()) {
            $this->pinService->openSession($membre->id);
        }

        // On récupère les données de la session
        $intended = Session::get('pin_intended_action');
        Session::forget('pin_intended_action');

        // On marque la session courante pour permettre le "bypass" dans le middleware
        // Ce flag sera supprimé par le middleware dès que la requête sera restaurée.
        Session::put('pin_validated_for_request', true);

        // Afficher la page avec le formulaire caché qui s'auto-soumet
        return view('membres.pin.auto-submit', [
            'action_url'    => $intended['url'],
            'action_method' => $intended['method'],
            'action_data'   => $intended['data'],
        ]);
    }
}

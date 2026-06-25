<?php

namespace App\Http\Controllers;

use App\Models\AutoNumberingConfig;
use App\Services\AutoNumberingService;
use Illuminate\Http\Request;

class AutoNumberingConfigController extends Controller
{
    protected $autoNumberingService;

    public function __construct(AutoNumberingService $autoNumberingService)
    {
        $this->autoNumberingService = $autoNumberingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $configs = AutoNumberingConfig::all();
        
        // Types d'objets suggérés
        $objectTypes = [
            'client' => 'Numéro de Client',
            'compte' => 'Numéro de Compte',
            'transaction' => 'Numéro de Transaction',
            'piece_comptable' => 'Numéro de Pièce Comptable',
        ];

        return view('settings.auto-numbering', compact('configs', 'objectTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'object_type' => 'required|string|unique:auto_numbering_configs,object_type',
            'description' => 'nullable|string|max:2000',
            'definition' => 'required|array',
            'current_value' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        AutoNumberingConfig::create($validated);

        return redirect()->route('admin.auto-numbering.index')
            ->with('success', 'Configuration de numérotation créée avec succès');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AutoNumberingConfig $config)
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:2000',
            'definition' => 'required|array',
            'current_value' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $config->update($validated);

        return redirect()->route('admin.auto-numbering.index')
            ->with('success', 'Configuration de numérotation mise à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AutoNumberingConfig $config)
    {
        $config->delete();

        return redirect()->route('admin.auto-numbering.index')
            ->with('success', 'Configuration de numérotation supprimée avec succès');
    }

    /**
     * Preview the generated number.
     */
    public function preview(string $objectType)
    {
        $preview = $this->autoNumberingService->preview($objectType);
        return response()->json(['preview' => $preview]);
    }
}

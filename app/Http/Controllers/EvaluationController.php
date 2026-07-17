<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationIndicator;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index()
    {
        $evaluations = Evaluation::with(['indicators.user', 'indicators.documents'])->get();
        // Ambil semua dokumen yang ada di gudang untuk opsi penautan
        $allDocuments = Document::all();

        return view('evaluations.index', compact('evaluations', 'allDocuments'));
    }

    // Tautkan dokumen ke indikator
    public function linkDocument(Request $request, EvaluationIndicator $indicator)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id'
        ]);

        // BYPASS
        $user = User::first();

        $indicator->documents()->syncWithoutDetaching([
            $request->document_id => ['ditautkan_oleh' => $user->id]
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil ditautkan ke indikator!');
    }

    // Lepas kaitan dokumen dari indikator
    public function unlinkDocument(EvaluationIndicator $indicator, Document $document)
    {
        $indicator->documents()->detach($document->id);
        return redirect()->back()->with('success', 'Tautan dokumen berhasil dilepas!');
    }
}

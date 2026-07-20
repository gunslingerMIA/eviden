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
        return view('evaluations.index', compact('evaluations'));
    }

    // Tambah Evaluasi baru
    public function store(Request $request)
    {
        $request->validate([
            'nama_evaluasi' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'instansi_penilai' => 'nullable|string|max:255',
            'tahun' => 'required|integer',
        ]);

        Evaluation::create([
            'nama_evaluasi' => $request->nama_evaluasi,
            'deskripsi' => $request->deskripsi,
            'instansi_penilai' => $request->instansi_penilai,
            'tahun' => $request->tahun,
        ]);

        return redirect()->back()->with('success', 'Evaluasi berhasil ditambahkan!');
    }

    // Detail Evaluasi (menampilkan indikator, dokumen terhubung, dll)
    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['indicators.user', 'indicators.documents.uploader', 'indicators.folders']);
        $activeUserId = session('active_user_id', User::first()->id);
        $activeUser = User::find($activeUserId);
        $allDocuments = Document::all();
        $allUsers = User::all();

        return view('evaluations.show', compact('evaluation', 'allDocuments', 'allUsers', 'activeUser'));
    }

    // Ubah Evaluasi
    public function update(Request $request, Evaluation $evaluation)
    {
        $request->validate([
            'nama_evaluasi' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'instansi_penilai' => 'nullable|string|max:255',
            'tahun' => 'required|integer',
        ]);

        $evaluation->update([
            'nama_evaluasi' => $request->nama_evaluasi,
            'deskripsi' => $request->deskripsi,
            'instansi_penilai' => $request->instansi_penilai,
            'tahun' => $request->tahun,
        ]);

        return redirect()->back()->with('success', 'Evaluasi berhasil diperbarui!');
    }

    // Hapus Evaluasi
    public function destroy(Evaluation $evaluation)
    {
        $evaluation->delete();
        return redirect()->route('evaluations.index')->with('success', 'Evaluasi berhasil dihapus!');
    }

    // Tambah Indikator ke Evaluasi
    public function storeIndicator(Request $request, Evaluation $evaluation)
    {
        $request->validate([
            'nama_indikator' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'pic_user_id' => 'nullable|exists:users,id',
        ]);

        $evaluation->indicators()->create([
            'nama_indikator' => $request->nama_indikator,
            'deskripsi' => $request->deskripsi,
            'pic_user_id' => $request->pic_user_id,
        ]);

        return redirect()->back()->with('success', 'Indikator berhasil ditambahkan!');
    }

    // Ubah Indikator
    public function updateIndicator(Request $request, EvaluationIndicator $indicator)
    {
        $request->validate([
            'nama_indikator' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'pic_user_id' => 'nullable|exists:users,id',
        ]);

        $indicator->update([
            'nama_indikator' => $request->nama_indikator,
            'deskripsi' => $request->deskripsi,
            'pic_user_id' => $request->pic_user_id,
        ]);

        return redirect()->back()->with('success', 'Indikator berhasil diperbarui!');
    }

    // Hapus Indikator
    public function destroyIndicator(EvaluationIndicator $indicator)
    {
        $indicator->delete();
        return redirect()->back()->with('success', 'Indikator berhasil dihapus!');
    }

    // Tautkan dokumen ke indikator
    public function linkDocument(Request $request, EvaluationIndicator $indicator)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id'
        ]);

        $activeUserId = session('active_user_id', User::first()->id);
        $user = User::find($activeUserId);

        // Security check
        if ($indicator->pic_user_id != $user->id) {
            abort(403, 'Hanya PIC penanggung jawab yang diperbolehkan menautkan berkas.');
        }

        $indicator->documents()->syncWithoutDetaching([
            $request->document_id => ['ditautkan_oleh' => $user->id]
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil ditautkan ke indikator!');
    }

    // Lepas kaitan dokumen dari indikator
    public function unlinkDocument(EvaluationIndicator $indicator, Document $document)
    {
        $activeUserId = session('active_user_id', User::first()->id);
        $user = User::find($activeUserId);

        // Security check
        if ($indicator->pic_user_id != $user->id) {
            abort(403, 'Hanya PIC penanggung jawab yang diperbolehkan melepas kaitan berkas.');
        }

        $indicator->documents()->detach($document->id);
        return redirect()->back()->with('success', 'Tautan dokumen berhasil dilepas!');
    }

    // Unggah Dokumen langsung via Indikator (khusus PIC)
    public function uploadDocument(Request $request, EvaluationIndicator $indicator)
    {
        $request->validate([
            'judul_dokumen' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // Max 10MB
            'tahun_mulai' => 'nullable|integer',
            'tahun_selesai' => 'nullable|integer',
        ]);

        $activeUserId = session('active_user_id', User::first()->id);
        $user = User::find($activeUserId);

        // Security check: must be indicator PIC to upload
        if ($indicator->pic_user_id != $user->id) {
            abort(403, 'Hanya PIC penanggung jawab yang diperbolehkan mengunggah berkas untuk komponen ini.');
        }

        // Check if root folder for user exists, otherwise create it
        $userFolder = \App\Models\Folder::firstOrCreate([
            'nama_folder' => 'Eviden - ' . $user->name,
            'parent_id' => null,
            'dibuat_oleh' => $user->id,
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('documents', 'public');

            $doc = Document::create([
                'judul_dokumen' => $request->judul_dokumen,
                'file_path' => $path,
                'ekstensi' => $file->getClientOriginalExtension(),
                'ukuran_file' => $file->getSize(),
                'folder_id' => $userFolder->id,
                'tahun_mulai' => $request->tahun_mulai,
                'tahun_selesai' => $request->tahun_selesai,
                'uploader_id' => $user->id,
            ]);

            // Link to indicator
            $indicator->documents()->syncWithoutDetaching([
                $doc->id => ['ditautkan_oleh' => $user->id]
            ]);

            return redirect()->back()->with('success', 'Berkas berhasil diunggah langsung ke folder Anda dan ditautkan!');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah berkas.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Document;
use App\Models\User;
use App\Models\Evaluation;
use App\Models\EvaluationIndicator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{
    // Helper rekursif untuk membuat daftar folder terindentasi
    private function buildIndentedFolders($folders, $parentId = null, $prefix = '')
    {
        $options = [];
        foreach ($folders->where('parent_id', $parentId) as $folder) {
            $options[] = [
                'id' => $folder->id,
                'nama_folder' => $prefix . $folder->nama_folder
            ];
            $options = array_merge($options, $this->buildIndentedFolders($folders, $folder->id, $prefix . '— '));
        }
        return $options;
    }

    // Tampilkan berkas dan folder di halaman root (Gudang Utama)
    public function index()
    {
        $folders = Folder::whereNull('parent_id')->with(['user', 'indicators.evaluation'])->get();
        $documents = Document::whereNull('folder_id')->with(['uploader', 'indicators.evaluation'])->get();
        $evaluations = Evaluation::with('indicators')->get();
        
        $foldersRaw = Folder::all();
        $moveFolderOptions = $this->buildIndentedFolders($foldersRaw);
        $trashedDocuments = Document::onlyTrashed()->with(['uploader', 'indicators.evaluation'])->get();
        
        return view('folders.index', [
            'folders' => $folders,
            'documents' => $documents,
            'evaluations' => $evaluations,
            'moveFolderOptions' => $moveFolderOptions,
            'trashedDocuments' => $trashedDocuments,
            'currentFolder' => null
        ]);
    }

    // Tampilkan subfolder
    public function show(Folder $folder)
    {
        $folder->load(['children.user', 'children.indicators.evaluation', 'documents.uploader', 'documents.indicators.evaluation', 'indicators.evaluation']);
        $evaluations = Evaluation::with('indicators')->get();
        
        $foldersRaw = Folder::all();
        $moveFolderOptions = $this->buildIndentedFolders($foldersRaw);
        $trashedDocuments = Document::onlyTrashed()->with(['uploader', 'indicators.evaluation'])->get();
        
        return view('folders.index', [
            'folders' => $folder->children,
            'documents' => $folder->documents,
            'evaluations' => $evaluations,
            'moveFolderOptions' => $moveFolderOptions,
            'trashedDocuments' => $trashedDocuments,
            'currentFolder' => $folder
        ]);
    }

    // Membuat folder baru
    public function store(Request $request)
    {
        $request->validate([
            'nama_folder' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id'
        ]);

        $user = User::first(); // Bypass Auth

        Folder::create([
            'nama_folder' => $request->nama_folder,
            'parent_id' => $request->parent_id,
            'dibuat_oleh' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Folder berhasil dibuat!');
    }

    // Unggah Dokumen langsung di halaman utama (root)
    public function storeDocumentRoot(Request $request)
    {
        $request->validate([
            'judul_dokumen' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // Max 10MB
            'tahun_mulai' => 'nullable|integer',
            'tahun_selesai' => 'nullable|integer',
        ]);

        $user = User::first(); // Bypass Auth

        $relativeFolderId = null;
        if ($request->has('relative_path') && !empty($request->relative_path)) {
            $parts = explode('/', $request->relative_path);
            if (count($parts) > 1) {
                array_pop($parts);
                foreach ($parts as $folderName) {
                    $existingFolder = Folder::where('nama_folder', $folderName)
                        ->where('parent_id', $relativeFolderId)
                        ->first();
                    if ($existingFolder) {
                        $relativeFolderId = $existingFolder->id;
                    } else {
                        $newFolder = Folder::create([
                            'nama_folder' => $folderName,
                            'parent_id' => $relativeFolderId,
                            'dibuat_oleh' => $user->id,
                        ]);
                        $relativeFolderId = $newFolder->id;
                    }
                }
            }
        }

        $doc = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('documents', 'public');

            $doc = Document::create([
                'judul_dokumen' => $request->judul_dokumen,
                'file_path' => $path,
                'ekstensi' => $file->getClientOriginalExtension(),
                'ukuran_file' => $file->getSize(),
                'folder_id' => $relativeFolderId,
                'tahun_mulai' => $request->tahun_mulai,
                'tahun_selesai' => $request->tahun_selesai,
                'uploader_id' => $user->id,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diunggah di File Manager!',
                'document' => $doc ? $doc->load('uploader') : null
            ]);
        }

        return redirect()->back()->with('success', 'Dokumen berhasil diunggah di File Manager!');
    }

    // Unggah Dokumen ke dalam subfolder
    public function storeDocument(Request $request, Folder $folder)
    {
        $request->validate([
            'judul_dokumen' => 'required|string|max:255',
            'file' => 'required|file|max:10240',
            'tahun_mulai' => 'nullable|integer',
            'tahun_selesai' => 'nullable|integer',
        ]);

        $user = User::first(); // Bypass Auth

        $relativeFolderId = $folder->id;
        if ($request->has('relative_path') && !empty($request->relative_path)) {
            $parts = explode('/', $request->relative_path);
            if (count($parts) > 1) {
                array_pop($parts);
                foreach ($parts as $folderName) {
                    $existingFolder = Folder::where('nama_folder', $folderName)
                        ->where('parent_id', $relativeFolderId)
                        ->first();
                    if ($existingFolder) {
                        $relativeFolderId = $existingFolder->id;
                    } else {
                        $newFolder = Folder::create([
                            'nama_folder' => $folderName,
                            'parent_id' => $relativeFolderId,
                            'dibuat_oleh' => $user->id,
                        ]);
                        $relativeFolderId = $newFolder->id;
                    }
                }
            }
        }

        $doc = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('documents', 'public');

            $doc = Document::create([
                'judul_dokumen' => $request->judul_dokumen,
                'file_path' => $path,
                'ekstensi' => $file->getClientOriginalExtension(),
                'ukuran_file' => $file->getSize(),
                'folder_id' => $relativeFolderId,
                'tahun_mulai' => $request->tahun_mulai,
                'tahun_selesai' => $request->tahun_selesai,
                'uploader_id' => $user->id,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diunggah!',
                'document' => $doc ? $doc->load('uploader') : null
            ]);
        }

        return redirect()->back()->with('success', 'Dokumen berhasil diunggah!');
    }

    // Aktifkan / matikan sharing link
    public function toggleShare(Document $document)
    {
        if ($document->is_shared) {
            $document->update([
                'is_shared' => false,
                'share_token' => null
            ]);
            return redirect()->back()->with('success', 'Tautan berbagi dinonaktifkan.');
        } else {
            $document->update([
                'is_shared' => true,
                'share_token' => Str::random(32) // Generate token unik acak
            ]);
            return redirect()->back()->with('success', 'Tautan berbagi berhasil diaktifkan.');
        }
    }

    // Tampilan Publik (Halaman Luar) untuk dokumen yang dibagikan
    public function viewShared($token)
    {
        // Cari dokumen berdasarkan token yang valid dan status dibagikan
        $document = Document::where('share_token', $token)->where('is_shared', true)->firstOrFail();
        return view('folders.shared', compact('document'));
    }

    // Ubah nama Folder
    public function updateFolder(Request $request, Folder $folder)
    {
        $request->validate([
            'nama_folder' => 'required|string|max:255'
        ]);

        $folder->update([
            'nama_folder' => $request->nama_folder
        ]);

        return redirect()->back()->with('success', 'Folder berhasil diubah!');
    }

    // Hapus Folder
    public function destroyFolder(Folder $folder)
    {
        // Pindahkan seluruh file di dalam folder & subfolder ke Recycle Bin secara asinkron/aman
        $this->softDeleteFolderContents($folder);

        $folder->delete();
        return redirect()->route('folders.index')->with('success', 'Folder berhasil dihapus dan semua berkas di dalamnya dipindahkan ke Tempat Sampah!');
    }

    // Helper untuk memindahkan isi folder ke Recycle Bin secara rekursif
    private function softDeleteFolderContents(Folder $folder)
    {
        // 1. Soft-delete seluruh dokumen di folder ini dan kosongkan folder_id agar tidak terhapus cascade di DB
        foreach ($folder->documents as $doc) {
            $doc->update(['folder_id' => null]);
            $doc->delete();
        }

        // 2. Lakukan secara rekursif untuk subfolder anak
        foreach ($folder->children as $child) {
            $this->softDeleteFolderContents($child);
        }
    }

    // Edit Berkas Dokumen
    public function updateDocument(Request $request, Document $document)
    {
        $request->validate([
            'judul_dokumen' => 'required|string|max:255',
            'tahun_mulai' => 'nullable|integer',
            'tahun_selesai' => 'nullable|integer',
            'file' => 'nullable|file|max:10240'
        ]);

        $data = [
            'judul_dokumen' => $request->judul_dokumen,
            'tahun_mulai' => $request->tahun_mulai,
            'tahun_selesai' => $request->tahun_selesai,
        ];

        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            $file = $request->file('file');
            $data['file_path'] = $file->store('documents', 'public');
            $data['ekstensi'] = $file->getClientOriginalExtension();
            $data['ukuran_file'] = $file->getSize();
        }

        $document->update($data);

        return redirect()->back()->with('success', 'Metadata dokumen berhasil diperbarui!');
    }

    // Hapus Berkas Dokumen (Soft Delete)
    public function destroyDocument(Document $document)
    {
        $document->delete();
        return redirect()->back()->with('success', 'Dokumen berhasil dipindahkan ke Recycle Bin (Soft Delete)!');
    }

    // Kaitkan Dokumen ke Indikator Evaluasi
    public function linkIndicator(Request $request, Document $document)
    {
        $request->validate([
            'indicator_ids' => 'nullable|array',
            'indicator_ids.*' => 'exists:evaluation_indicators,id'
        ]);

        $user = User::first(); // Bypass Auth
        $indicatorIds = $request->input('indicator_ids', []);

        $syncData = [];
        foreach ($indicatorIds as $id) {
            $syncData[$id] = ['ditautkan_oleh' => $user->id];
        }

        $document->indicators()->sync($syncData);

        return redirect()->back()->with('success', 'Tautan dokumen dengan penilaian berhasil diperbarui!');
    }

    // Lepas Tautan Dokumen dari Indikator Evaluasi
    public function unlinkIndicator(Document $document, EvaluationIndicator $indicator)
    {
        $document->indicators()->detach($indicator->id);
        return redirect()->back()->with('success', 'Tautan dokumen dengan penilaian berhasil dilepas!');
    }

    // Pulihkan Dokumen dari Soft Delete
    public function restoreDocument($id)
    {
        $document = Document::onlyTrashed()->findOrFail($id);
        $document->restore();
        return redirect()->back()->with('success', 'Dokumen berhasil dipulihkan!');
    }

    // Hapus Dokumen secara Permanen
    public function forceDeleteDocument($id)
    {
        $document = Document::onlyTrashed()->findOrFail($id);
        
        // Hapus file fisik dari disk public
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        $document->forceDelete();
        return redirect()->back()->with('success', 'Dokumen berhasil dihapus secara permanen!');
    }

    // Pindahkan Folder ke Folder Induk Baru
    public function moveFolder(Request $request, Folder $folder)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:folders,id'
        ]);

        $parentId = $request->parent_id;

        // Validasi agar folder tidak dipindahkan ke dirinya sendiri
        if ($parentId == $folder->id) {
            return redirect()->back()->with('error', 'Folder tidak dapat dipindahkan ke dirinya sendiri!');
        }

        // Validasi agar folder tidak dipindahkan ke subfolder miliknya sendiri (mencegah siklus tak terbatas)
        if ($parentId) {
            $targetParent = Folder::find($parentId);
            $parentIds = [];
            while ($targetParent) {
                $parentIds[] = $targetParent->id;
                $targetParent = $targetParent->parent;
            }
            if (in_array($folder->id, $parentIds)) {
                return redirect()->back()->with('error', 'Folder tidak dapat dipindahkan ke subfolder miliknya sendiri!');
            }
        }

        $folder->update([
            'parent_id' => $parentId
        ]);

        return redirect()->back()->with('success', 'Folder berhasil dipindahkan!');
    }

    // Pindahkan Dokumen ke Folder Baru
    public function moveDocument(Request $request, Document $document)
    {
        $request->validate([
            'folder_id' => 'nullable|exists:folders,id'
        ]);

        $document->update([
            'folder_id' => $request->folder_id
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil dipindahkan!');
    }

    // Kosongkan Tempat Sampah
    public function emptyTrash()
    {
        $trashed = Document::onlyTrashed()->get();
        foreach ($trashed as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
            $doc->forceDelete();
        }
        return redirect()->back()->with('success', 'Tempat sampah berhasil dikosongkan!');
    }

    // Unduh Seluruh Folder sebagai ZIP
    public function downloadFolderZip(Folder $folder)
    {
        $zip = new \ZipArchive();
        $zipFileName = str_replace(' ', '_', $folder->nama_folder) . '.zip';
        
        // Buat file ZIP sementara di folder storage/app/public/temp/
        if (!Storage::disk('public')->exists('temp')) {
            Storage::disk('public')->makeDirectory('temp');
        }
        
        $zipFilePath = storage_path('app/public/temp/' . $zipFileName);
        
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFolderToZip($folder, $zip, '');
            $zip->close();
            
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }
        
        return redirect()->back()->with('error', 'Gagal mengompresi folder ke berkas ZIP!');
    }

    // Helper rekursif untuk menambahkan isi folder ke ZIP
    private function addFolderToZip(Folder $folder, \ZipArchive $zip, $subpath = '')
    {
        $folderSubpath = $subpath . $folder->nama_folder . '/';
        $zip->addEmptyDir($folderSubpath);
        
        // Tambahkan semua dokumen di folder ini
        foreach ($folder->documents as $doc) {
            $realPath = storage_path('app/public/' . $doc->file_path);
            if (file_exists($realPath)) {
                // Bersihkan nama agar aman untuk format file di zip
                $cleanTitle = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $doc->judul_dokumen);
                if (empty($cleanTitle)) {
                    $cleanTitle = 'document_' . $doc->id;
                }
                $fileNameInZip = $cleanTitle . '.' . $doc->ekstensi;
                $zip->addFile($realPath, $folderSubpath . $fileNameInZip);
            }
        }
        
        // Tambahkan semua subfolder secara rekursif
        foreach ($folder->children as $child) {
            $this->addFolderToZip($child, $zip, $folderSubpath);
        }
    }

    // Hubungkan Folder ke Indikator Penilaian
    public function linkFolderIndicator(Request $request, Folder $folder)
    {
        $request->validate([
            'indicator_id' => 'required|exists:evaluation_indicators,id'
        ]);

        $user = User::first(); // Bypass Auth

        $folder->indicators()->syncWithoutDetaching([
            $request->indicator_id => ['ditautkan_oleh' => $user->id]
        ]);

        return redirect()->back()->with('success', 'Folder berhasil dikaitkan ke penilaian!');
    }

    // Lepas Hubungan Folder dari Indikator Penilaian
    public function unlinkFolderIndicator(Folder $folder, EvaluationIndicator $indicator)
    {
        $folder->indicators()->detach($indicator->id);
        return redirect()->back()->with('success', 'Tautan folder dengan penilaian berhasil dilepas!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Document;
use App\Models\Folder;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [
            'total_evaluasi' => Evaluation::count(),
            'total_dokumen' => Document::count(),
            'total_folder' => Folder::count(),
        ];
        return view('dashboard', $data);
    }
}

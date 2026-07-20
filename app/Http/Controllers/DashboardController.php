<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Document;
use App\Models\Folder;

use App\Models\User;

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

    public function switchUser(User $user)
    {
        session(['active_user_id' => $user->id]);
        return redirect()->back()->with('success', "Beralih ke pengguna: {$user->name}");
    }
}

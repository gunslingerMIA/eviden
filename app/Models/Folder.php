<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Folder extends Model
{
    //
    protected $guarded = [];

    //mengambil folder induknya
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    //mengambil folder anak-anaknya
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    //mengambil semua file/dokumen di dalam folder ini
    public function documents(){
        return $this->hasMany(Document::class);
    }

    //mendapatkan user pemilik folder ini
    public function user()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    //relasi many to many ke indikator evaluasi
    public function indicators()
    {
        return $this->belongsToMany(EvaluationIndicator::class, 'folder_indicator', 'folder_id', 'indicator_id')
                ->withPivot('ditautkan_oleh')
                ->withTimestamps();
    }
}

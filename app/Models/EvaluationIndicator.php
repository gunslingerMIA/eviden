<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Evaluation;

class EvaluationIndicator extends Model
{
    //
    protected $guarded = [];
    
    // Indikator ini milik evaluasi apa?

    public function evaluation(){
        return $this->belongsTo(Evaluation::class);

    }

    //siapa pegawai (pic) yang bertugas memenuhi indikator ini?

    public function user(){
        return $this->belongsTo(User::class, 'pic_user_id');

    }   
    // relasi many to many ke gudang dokumen
    public function documents(){
        return $this->belongsToMany(Document::class, 'document_indicator', 'indicator_id', 'document_id')
                ->withPivot('ditautkan_oleh')
                ->withTimestamps();
    }

    // relasi many to many ke gudang folder
    public function folders(){
        return $this->belongsToMany(Folder::class, 'folder_indicator', 'indicator_id', 'folder_id')
                ->withPivot('ditautkan_oleh')
                ->withTimestamps();
    }
}

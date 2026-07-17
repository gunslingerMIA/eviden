<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes; //aktifkan fitur recycle bin

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use SoftDeletes;
    
    //

    

    protected $guarded = [];

    //dokumen ini ada di folder mana?

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
    
    //relasi many to many ke indikator evaluasi 
    public function indicators()
    {
        return $this->belongsToMany(EvaluationIndicator::class, 'document_indicator', 'document_id', 'indicator_id')
                ->withPivot('ditautkan_oleh')
                ->withTimestamps();
    }   

    //siapa yang mengunggah folder ini?

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
    

    
}

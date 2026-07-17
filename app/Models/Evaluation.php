<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    //
    protected $guarded = [];

    // satu evaluasi memiliki banyak indicator
    public function indicators()
    {
        return $this->hasMany(EvaluationIndicator::class);
    }
    
}

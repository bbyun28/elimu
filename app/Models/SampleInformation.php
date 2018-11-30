<?php

namespace App\Models;

use App\Scopes\OnlyCurrentStudy;
use Illuminate\Database\Eloquent\Model;

class SampleInformation extends DependsOnStudy
{
    protected $dates = [
        'created_at',
        'updated_at',
        'collected_at',
        'birthdate'
    ];

    protected $fillable = [
        'sample_id',
        'subject_id',
        'collected_at',
        'visit_id',
        'study_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new OnlyCurrentStudy);
    }

    public function study()
    {
        return $this->belongsTo(Study::class);
    }

    public function samples()
    {
        return $this->hasMany(Sample::class);
    }
}

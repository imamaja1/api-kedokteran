<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssessmentNodeIndex extends Model
{
    use HasUuids;

    protected $table = 'assessment_node_indexes';

    protected $fillable = [
        'template_id',
        'node_key',
        'parent_key',
        'node_name',
        'path',
        'level',
        'weight',
        'is_input',
        'type',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'is_input' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }

    public function childNodes()
    {
        return $this->hasMany(AssessmentNodeIndex::class, 'parent_key', 'node_key')
            ->where('template_id', $this->template_id);
    }

    public function scores()
    {
        return $this->hasMany(StudentScore::class, 'node_key', 'node_key')
            ->where('template_id', $this->template_id);
    }

    public function scopeInputNodes($query)
    {
        return $query->where('is_input', true);
    }

    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['user_id', 'action', 'model', 'model_id', 'changes', 'description', 'is_success', 'timestamp'];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dynamically resolve the related model instance
     */
    public function relatedModel()
    {
        if (!class_exists($this->model)) {
            return null;
        }

        return $this->model::find($this->model_id);
    }
}

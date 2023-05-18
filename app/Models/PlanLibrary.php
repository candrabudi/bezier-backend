<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanLibrary extends Model
{
    use HasFactory;

    public function member()
    {
        return $this->hasOne(User::class, 'id', 'member_user_id')
            ->select('id', 'full_name');
    }

    public function client()
    {
        return $this->hasOne(User::class, 'id', 'client_user_id')
            ->select('id', 'full_name');
    }
    
    public function approvedBy()
    {
        return $this->hasOne(User::class, 'id', 'approved_by')
            ->select('id', 'full_name');
    }

    public function getImagePathAttribute($value)
    {
        return !empty($value) ? url(\Storage::url($value)) : null;
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d H:i:s', strtotime($value));
    }

    public function getUpdatedAtAttribute($value)
    {
        return date('Y-m-d H:i:s', strtotime($value));
    }
}

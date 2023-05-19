<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskClient extends Model
{
    use HasFactory;

    public function memberAssign()
    {
        return $this->hasMany(MemberAssign::class, 'client_user_id', 'id')
            ->join('users', 'users.id', '=', 'member_assigns.member_user_id')
            ->select('client_user_id', 'users.full_name', 'photo_profile_url');
    }
}

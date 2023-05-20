<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
class TaskClient extends Model
{
    use HasFactory;

    public static function customPaginate($perPage = 10)
    {
        $page = Paginator::resolveCurrentPage('page');

        $results = self::query()
            ->forPage($page, $perPage)
            ->with('memberAssign')
            ->get();

        $total = self::query()->count();

        return new Paginator($results, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'total' => $total,
        ]);
    }
    
    public function memberAssign()
    {
        return $this->hasMany(MemberAssign::class, 'client_user_id', 'id')
            ->join('users', 'users.id', '=', 'member_assigns.member_user_id')
            ->select('client_user_id', 'users.full_name', 'photo_profile_url');
    }
}

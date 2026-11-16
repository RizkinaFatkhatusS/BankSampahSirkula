<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'permissions';

    public $timestamps = false;

    protected $guarded = [];

    public const DAPAT_DIBERIKAN = 'dapat_diberikan';
    public const SENSITIF = 'sensitif';
    public const KHUSUS_SUPER_ADMIN = 'khusus_super_admin';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions', 'permission_id', 'role_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tbl_user';

    // If your primary key is not `id`, add this too:
    protected $primaryKey = 'user_id';

    // If your primary key is not auto-incrementing or not integer:
    protected $keyType = 'int';

    // If you don’t use Laravel’s timestamps (`created_at`, `updated_at`)
    public $timestamps = false;    
    protected $fillable = [
        'user_name',
        'user_email',
        'user_password',
        'user_contactNum',
        'user_licenseNum',
        'user_role',
        'user_status',
        'branch_id',
        'registered_by',
         'last_login_at',
    ];

    // Automatically hash password when creating/updating
    public function setUserPasswordAttribute($value)
    {
        $this->attributes['user_password'] = bcrypt($value);
    }

    

    // Default attributes for super admin
    protected $attributes = [
        'user_role'   => 'superadmin',
        'user_status' => 'active',
        'branch_id'   => NULL,
        'registered_by' => NULL,
    ];

    protected $hidden = ['user_password', 'remember_token'];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

     public function getIdAttribute()
{
    return $this->user_id;
}


    public function getAuthPassword()
    {
        return $this->user_password;
    }

     public function pets()
    {
        return $this->hasMany(Pet::class, 'user_id', 'user_id');
    }
    
    public function owners()
    {
        return $this->hasMany(Owner::class, 'user_id', 'user_id');
    }
    
    public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class, 'user_id', 'user_id');
    }
    

}



<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'institution',
        'gender',
        'phone_number',
        'tujuan_permohonan',
        'role',
        'approval_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['managed_diseases'];

    public function managedDiseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_operator', 'user_id', 'disease_id');
    }

    public function getManagedDiseasesAttribute()
    {
        if ($this->role === 'operator') {
            return $this->managedDiseases()
                ->select(['diseases.id as disease_id', 'diseases.name'])
                ->get()
                ->map(function ($disease) {
                    return [
                        'disease_id' => $disease->disease_id,
                        'name' => $disease->name,
                        'pivot' => $disease->pivot
                    ];
                })
                ->toArray();
        }
        return null;
    }
}

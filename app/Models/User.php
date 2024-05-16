<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Termwind\Components\Li;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'firstName',
        'lastName',
        'password',
        'image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'username_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    //Ein User kann mehrere Todos erstellt haben
    public function createdTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'user_id');
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ListModel::class, 'list_user', 'list_id', 'user_id')->withTimestamps();
    }

    public function todos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class)->withTimestamps()->withPivot('assignedTo');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['user' =>['id'=>$this->id]];
    }

}

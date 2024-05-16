<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ListModel entspricht der 'lists'-Tabelle in der Datenbank.
class ListModel extends Model
{
    use HasFactory;

    // Eloquent wird diese Tabelle verwenden, wenn wir mit ListModel arbeiten.
    protected $table = 'lists';

    protected $fillable = ['name', 'public'];

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'list_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'list_user', 'list_id', 'user_id')->withTimestamps();
    }
}

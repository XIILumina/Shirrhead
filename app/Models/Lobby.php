<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Lobby extends Model
{
    protected $fillable = [
    'invite_code',
    'status',
    'players'];

    // Accessor to decode players JSON
    public function getPlayersAttribute($value)
    {
        return json_decode($value, true) ?: [];  // Ensure empty array if null or invalid
    }

    // Mutator to encode players JSON
    public function setPlayersAttribute($value)
    {
        $this->attributes['players'] = json_encode($value);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'hand',
        'visible_cards',
        'hidden_cards',
        'position',
    ];

    protected $casts = [
        'hand' => 'array',
        'visible_cards' => 'array',
        'hidden_cards' => 'array',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

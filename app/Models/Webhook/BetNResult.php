<?php

namespace App\Models\Webhook;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BetNResult extends Model
{
    use HasFactory;

    protected $table = 'bet_n_results';

    protected $fillable = [
        'user_id',
        'operator_id',
        'request_date_time',
        'signature',
        'player_id',
        'currency',
        'tran_id',
        'game_code',
        'game_name',
        'bet_amount',
        'win_amount',
        'net_win',
        'tran_date_time',
        'provider_code',
        'auth_token',
        'status',
        'cancelled_at',
        'old_balance',
        'new_balance'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
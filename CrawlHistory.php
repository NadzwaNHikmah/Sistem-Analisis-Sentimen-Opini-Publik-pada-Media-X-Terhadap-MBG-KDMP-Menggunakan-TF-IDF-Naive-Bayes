<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CrawlHistory extends Model
{
    protected $fillable = [
        'batch_code',
        'status',
        'total_tweets',
        'mbg_count',
        'kdmp_count',
        'positif_count',
        'negatif_count',
        'netral_count',
        'started_at',
        'completed_at',
        'duration_seconds',
        'notes',
        'log_output',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tweets()
    {
        return $this->hasMany(Tweet::class, 'crawl_history_id');
    }

    public function getFormattedDurationAttribute(): string
    {
        if ($this->status === 'running') {
            return 'Berjalan...';
        }

        $seconds = 0;
        if ($this->duration_seconds !== null && $this->duration_seconds >= 0) {
            $seconds = $this->duration_seconds;
        } elseif ($this->started_at && $this->completed_at) {
            $seconds = abs($this->completed_at->diffInSeconds($this->started_at));
        } else {
            return '-';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
    }

    public function getFormattedStartedAtAttribute(): string
    {
        return $this->started_at ? $this->started_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' : '-';
    }

    public function getFormattedCompletedAtAttribute(): string
    {
        return $this->completed_at ? $this->completed_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' : '-';
    }
}

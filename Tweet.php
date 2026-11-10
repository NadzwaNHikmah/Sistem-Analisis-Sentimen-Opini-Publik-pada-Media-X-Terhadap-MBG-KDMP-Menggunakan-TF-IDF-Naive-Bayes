<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Tweet extends Model
{
    use HasFactory;

    protected $table = 'tweets';

    protected $fillable = [
        'crawl_history_id',
        'tweet_hash',
        'tweet_id_str',
        'program',
        'username',
        'tweet',
        'clean_tweet',
        'sentimen',
        'provinsi',
        'tweet_date',
        'crawled_at',
    ];

    protected $casts = [
        'tweet_date' => 'datetime',
        'crawled_at' => 'datetime',
    ];

    public function crawlHistory()
    {
        return $this->belongsTo(CrawlHistory::class, 'crawl_history_id');
    }

    public function scopeByCrawlHistory($query, $historyId)
    {
        if ($historyId) {
            return $query->where('crawl_history_id', $historyId);
        }
        return $query;
    }

    /**
     * Scope filter berdasarkan program (MBG / KDMP)
     */
    public function scopeProgram($query, string $program)
    {
        return $query->where('program', strtoupper($program));
    }

    /**
     * Scope filter berdasarkan rentang tanggal
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            return $query->whereBetween('tweet_date', [$start, $end]);
        }
        return $query;
    }

    /**
     * Scope filter berdasarkan provinsi
     */
    public function scopeProvinsi($query, $provinsi)
    {
        if ($provinsi && $provinsi !== 'semua' && $provinsi !== '') {
            return $query->where('provinsi', $provinsi);
        }
        return $query;
    }

    /**
     * Scope filter berdasarkan sentimen
     */
    public function scopeSentimen($query, $sentimen)
    {
        if ($sentimen && in_array(strtolower($sentimen), ['positif', 'negatif', 'netral'])) {
            return $query->where('sentimen', strtolower($sentimen));
        }
        return $query;
    }
}

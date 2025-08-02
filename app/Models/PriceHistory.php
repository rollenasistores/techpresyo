<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'price_history';

    public $timestamps = false; // Using custom recorded_at

    protected $fillable = [
        'product_id',
        'store_id',
        'price',
        'recorded_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'recorded_at' => 'datetime'
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the product for this price history
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the store for this price history
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope for specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent history
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }

    // ========== ACCESSORS ==========

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format($this->price, 2);
    }

    // ========== METHODS ==========

    /**
     * Get price trend data for charts
     */
    public static function getPriceTrendData($productId, $storeId = null, $days = 30)
    {
        $query = self::where('product_id', $productId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->get()->map(function ($history) {
            return [
                'date' => $history->recorded_at->format('Y-m-d'),
                'price' => $history->price,
                'store' => $history->store->name
            ];
        });
    }
}
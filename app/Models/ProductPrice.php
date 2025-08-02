<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'price',
        'original_price',
        'currency',
        'availability',
        'stock_quantity',
        'product_url',
        'last_scraped',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'last_scraped' => 'datetime',
        'is_active' => 'boolean'
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the product for this price
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the store for this price
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope for active prices only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for in-stock items
     */
    public function scopeInStock($query)
    {
        return $query->whereIn('availability', ['in_stock', 'limited']);
    }

    /**
     * Scope for specific store
     */
    public function scopeFromStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // ========== ACCESSORS ==========

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Get discount percentage if original price exists
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->original_price || $this->original_price <= $this->price) {
            return null;
        }

        return round((($this->original_price - $this->price) / $this->original_price) * 100, 1);
    }

    /**
     * Check if price is on sale
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->original_price && $this->original_price > $this->price;
    }

    /**
     * Get availability status with color
     */
    public function getAvailabilityStatusAttribute(): array
    {
        $statuses = [
            'in_stock' => ['label' => 'In Stock', 'color' => 'green'],
            'out_of_stock' => ['label' => 'Out of Stock', 'color' => 'red'],
            'limited' => ['label' => 'Limited Stock', 'color' => 'orange'],
            'pre_order' => ['label' => 'Pre-order', 'color' => 'blue']
        ];

        return $statuses[$this->availability] ?? ['label' => 'Unknown', 'color' => 'gray'];
    }

    // ========== METHODS ==========

    /**
     * Check if price needs update (based on last_scraped)
     */
    public function needsUpdate($hours = 24): bool
    {
        return $this->last_scraped->diffInHours(now()) >= $hours;
    }
}
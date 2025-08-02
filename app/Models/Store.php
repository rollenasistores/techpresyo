<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'website',
        'description',
        'contact_info',
        'address',
        'payment_methods',
        'shipping_info',
        'rating',
        'total_reviews',
        'is_verified',
        'is_active'
    ];

    protected $casts = [
        'contact_info' => 'array',
        'address' => 'array',
        'payment_methods' => 'array',
        'shipping_info' => 'array',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'is_verified' => 'boolean',
        'is_active' => 'boolean'
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get all product prices for this store
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get price history for this store
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * Get store reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope for active stores only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified stores
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for stores with products
     */
    public function scopeWithProducts($query)
    {
        return $query->whereHas('productPrices');
    }

    // ========== ACCESSORS ==========

    /**
     * Get the store logo or default
     */
    public function getLogoAttribute(): string
    {
        return $this->logo_url ?? '/images/stores/default.png';
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute(): string
    {
        $address = $this->address;
        if (!$address)
            return '';

        return collect([
            $address['street'] ?? '',
            $address['city'] ?? '',
            $address['region'] ?? ''
        ])->filter()->implode(', ');
    }

    /**
     * Get products count for this store
     */
    public function getProductsCountAttribute(): int
    {
        return $this->productPrices()->distinct('product_id')->count();
    }

    // ========== METHODS ==========

    /**
     * Get cheapest products from this store
     */
    public function getCheapestProducts($limit = 10)
    {
        return $this->productPrices()
            ->with(['product.brand', 'product.category'])
            ->where('availability', 'in_stock')
            ->orderBy('price')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if store has specific payment method
     */
    public function hasPaymentMethod(string $method): bool
    {
        $methods = $this->payment_methods ?? [];
        return in_array($method, $methods);
    }
}
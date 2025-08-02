<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'website',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get all products for this brand
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope for active brands only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for brands with products
     */
    public function scopeWithProducts($query)
    {
        return $query->whereHas('products');
    }

    // ========== ACCESSORS ==========

    /**
     * Get products count for this brand
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get the logo URL or default
     */
    public function getLogoAttribute(): string
    {
        return $this->logo_url ?? '/images/brands/default.png';
    }

    // ========== METHODS ==========

    /**
     * Get popular products for this brand
     */
    public function getPopularProducts($limit = 10)
    {
        return $this->products()
            ->with(['category', 'lowestPrice'])
            ->where('status', 'active')
            ->limit($limit)
            ->get();
    }
}
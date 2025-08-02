<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'slug',
        'model',
        'sku',
        'brand_id',
        'category_id',
        'description',
        'specifications',
        'images',
        'status'
    ];

    protected $casts = [
        'specifications' => 'array',
        'images' => 'array'
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the brand for this product
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category for this product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all prices for this product
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get active prices only
     */
    public function activePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('is_active', true)
            ->whereHas('store', function ($query) {
                $query->where('is_active', true);
            });
    }

    /**
     * Get the lowest price for this product
     */
    public function lowestPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class)
            ->where('is_active', true)
            ->whereHas('store', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('price');
    }

    /**
     * Get price history for this product
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * Get product reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope for active products only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for products with prices
     */
    public function scopeWithPrices($query)
    {
        return $query->whereHas('activePrices');
    }

    /**
     * Scope for products by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for products by brand
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope for price range filtering
     */
    public function scopePriceRange($query, $minPrice = null, $maxPrice = null)
    {
        return $query->whereHas('activePrices', function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice) {
                $q->where('price', '>=', $minPrice);
            }
            if ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            }
        });
    }

    // ========== ACCESSORS ==========

    /**
     * Get the main product image
     */
    public function getMainImageAttribute(): string
    {
        $images = $this->images ?? [];
        return $images[0] ?? '/images/products/no-image.png';
    }

    /**
     * Get product full name (brand + name)
     */
    public function getFullNameAttribute(): string
    {
        return $this->brand->name . ' ' . $this->name;
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    /**
     * Get reviews count
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Get current lowest price
     */
    public function getCurrentLowestPriceAttribute(): ?float
    {
        return $this->lowestPrice?->price;
    }

    // ========== SEARCH CONFIGURATION ==========

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'brand' => $this->brand->name,
            'category' => $this->category->name,
            'description' => $this->description,
            'specifications' => $this->specifications,
            'status' => $this->status
        ];
    }

    // ========== METHODS ==========

    /**
     * Get price comparison across all stores
     */
    public function getPriceComparison()
    {
        return $this->activePrices()
            ->with('store')
            ->orderBy('price')
            ->get()
            ->map(function ($price) {
                return [
                    'store' => $price->store,
                    'price' => $price->price,
                    'original_price' => $price->original_price,
                    'availability' => $price->availability,
                    'url' => $price->product_url,
                    'last_updated' => $price->updated_at
                ];
            });
    }

    /**
     * Get price trend data
     */
    public function getPriceTrend($days = 30)
    {
        return $this->priceHistory()
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('store_id')
            ->map(function ($prices) {
                return $prices->map(function ($price) {
                    return [
                        'date' => $price->recorded_at->format('Y-m-d'),
                        'price' => $price->price
                    ];
                });
            });
    }

    /**
     * Check if product is available
     */
    public function isAvailable(): bool
    {
        return $this->activePrices()
            ->whereIn('availability', ['in_stock', 'limited'])
            ->exists();
    }

    /**
     * Get similar products
     */
    public function getSimilarProducts($limit = 5)
    {
        return self::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->with(['brand', 'lowestPrice'])
            ->limit($limit)
            ->get();
    }
}
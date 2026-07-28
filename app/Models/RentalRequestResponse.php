<?php
// app/Models/RentalRequestResponse.php - ОБНОВЛЕННАЯ

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalRequestResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_request_id', 'lessor_id', 'equipment_id', 'proposed_price',
        'proposed_quantity', 'price_breakdown', 'message', 'availability_dates',
        'additional_terms', 'status', 'expires_at', 'is_bulk_main', 'is_bulk_item',
        'bulk_parent_id', 'order_id', 'counter_price', 'rejection_reason'
    ];

    protected $casts = [
        'price_breakdown' => 'array',
        'availability_dates' => 'array',
        'additional_terms' => 'array',
        'expires_at' => 'datetime',
        'proposed_price' => 'decimal:2',
        'counter_price' => 'decimal:2',
        'proposed_quantity' => 'integer',
        'is_bulk_main' => 'boolean',
        'is_bulk_item' => 'boolean'
    ];

    // 🔥 ДОБАВЛЯЕМ СТАТУС ДЛЯ КОММЕНТАРИЕВ
    public const STATUS_COMMENT = 'comment';

    // Отношения
    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function lessor()
    {
        return $this->belongsTo(User::class, 'lessor_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function bulkParent()
    {
        return $this->belongsTo(RentalRequestResponse::class, 'bulk_parent_id');
    }

    public function bulkItems()
    {
        return $this->hasMany(RentalRequestResponse::class, 'bulk_parent_id')
                    ->where('is_bulk_item', true);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getAllEquipmentAttribute()
    {
        if ($this->is_bulk_main) {
            return $this->bulkItems->map->equipment;
        }
        return collect([$this->equipment]);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCounterOffer($query)
    {
        return $query->where('status', 'counter_offer');
    }

    // 🔥 НОВЫЙ SCOPE: только комментарии
    public function scopeComments($query)
    {
        return $query->where('status', self::STATUS_COMMENT);
    }

    // 🔥 НОВЫЙ SCOPE: только предложения (исключаем комментарии)
    public function scopeProposals($query)
    {
        return $query->where('status', '!=', self::STATUS_COMMENT);
    }

    public function scopeBulkMain($query)
    {
        return $query->where('is_bulk_main', true);
    }

    public function scopeBulkItems($query)
    {
        return $query->where('is_bulk_item', true);
    }

    // Методы
    public function isBulk()
    {
        return $this->is_bulk_main || $this->is_bulk_item;
    }

    // 🔥 НОВЫЙ МЕТОД: проверка является ли комментарием
    public function isComment()
    {
        return $this->status === self::STATUS_COMMENT;
    }

    public function getTotalPriceAttribute()
    {
        // 🔥 Для комментариев возвращаем 0
        if ($this->isComment()) {
            return 0;
        }

        if ($this->is_bulk_main) {
            return $this->bulkItems->sum('proposed_price');
        }

        return $this->proposed_price * $this->proposed_quantity;
    }

    public function getPricePerUnitAttribute()
    {
        // 🔥 Для комментариев возвращаем 0
        if ($this->isComment()) {
            return 0;
        }

        if ($this->is_bulk_main) {
            $totalQuantity = $this->bulkItems->sum('proposed_quantity');
            return $totalQuantity > 0 ? $this->total_price / $totalQuantity : 0;
        }

        return $this->proposed_quantity > 0 ? $this->proposed_price / $this->proposed_quantity : $this->proposed_price;
    }

    public function canBeAccepted()
    {
        // 🔥 Комментарии не могут быть приняты
        if ($this->isComment()) {
            return false;
        }

        return $this->status === 'pending' || $this->status === 'counter_offer';
    }

    public function markAsAccepted()
    {
        // 🔥 Комментарии не могут быть приняты
        if ($this->isComment()) {
            return false;
        }

        $this->update(['status' => 'accepted']);
    }

    public function markAsRejected()
    {
        // 🔥 Комментарии не могут быть отклонены
        if ($this->isComment()) {
            return false;
        }

        $this->update(['status' => 'rejected']);
    }

    public function createCounterOffer($counterPrice, $message = null)
    {
        // 🔥 Комментарии не могут быть контроффером
        if ($this->isComment()) {
            return false;
        }

        return $this->update([
            'status' => 'counter_offer',
            'counter_price' => $counterPrice,
            'message' => $message ?? $this->message
        ]);
    }
}

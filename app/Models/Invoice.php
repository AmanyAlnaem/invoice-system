<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_number',
        'type',
        'date',
        'user_id',
        'total_amount',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'invoice_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}

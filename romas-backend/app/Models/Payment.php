<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Payment extends Model
{
    use Auditable;
    protected $fillable = [
        'invoice_id', 'montant', 'date_paiement', 'methode', 'reference_fedapay'
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
}
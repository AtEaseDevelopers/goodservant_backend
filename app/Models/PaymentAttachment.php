<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAttachment extends Model
{
    use HasFactory;

    public $table = 'payment_attachments';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_path',
    ];

    protected $appends = ['url'];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute()
    {
        // The "public" disk's root is public_path() itself in this app
        // (see config/filesystems.php), not the default storage/app/public,
        // so a plain url() - not Storage::disk('public')->url() - is what
        // actually matches where file_path was written to.
        return url($this->file_path);
    }
}

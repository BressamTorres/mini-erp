<?php

namespace App\Models;

use Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'document', 'phone', 'email', 'photo_path'])]
class Seller extends Model
{
    /** @use HasFactory<SellerFactory> */
    use HasFactory;

    public function formattedDocument(): ?string
    {
        if ($this->document === null) {
            return null;
        }

        if (strlen($this->document) === 11) {
            return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $this->document) ?? $this->document;
        }

        if (strlen($this->document) === 14) {
            return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $this->document) ?? $this->document;
        }

        return $this->document;
    }

    public function formattedPhone(): string
    {
        if (strlen($this->phone) === 10) {
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $this->phone) ?? $this->phone;
        }

        if (strlen($this->phone) === 11) {
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $this->phone) ?? $this->phone;
        }

        return $this->phone;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path === null
            ? null
            : '/storage/'.ltrim($this->photo_path, '/');
    }
}

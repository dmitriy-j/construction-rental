<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    // Указываем, какие поля могут быть массово присвоены
    protected $fillable = [
        'name',
        'type',
        'description',
        'file_path',
        'mapping',
        'is_active',
    ];

    // Указываем, что поле 'mapping' должно быть преобразовано в массив
    protected $casts = [
        'mapping' => 'array',
        'is_active' => 'boolean',
    ];
}

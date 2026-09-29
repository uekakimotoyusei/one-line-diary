<?php

namespace App\Models;

use Database\Factories\DiaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'body', 'image_path'])]
class Diary extends Model
{
    /** @use HasFactory<DiaryFactory> */
    use HasFactory;
}

<?php
namespace AcmeCorp\CartoonGenerator\Models;

use Illuminate\Database\Eloquent\Model;

class CartoonTask extends Model
{
    protected $fillable = ['user_id', 'mode', 'source_file', 'output'];
}

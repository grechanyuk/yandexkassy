<?php
namespace Grechanyuk\YandexKassy\Models;

use Illuminate\Database\Eloquent\Model;

class YandexKassy extends Model {
    protected $table = 'yandexkassy_payments';
    protected $fillable = ['payment_id', 'order_id', 'amount', 'status', 'idempotenceKey'];
}
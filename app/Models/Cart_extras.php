<?php

namespace App\Models;

use Eloquent as Model;

/**
 * Class Cart
 * @package App\Models
 * @version September 4, 2019, 3:38 pm UTC
 *
 * @property \App\Models\Food food
 * @property \App\Models\User user
 * @property \Illuminate\Database\Eloquent\Collection extra
 * @property integer food_id
 * @property integer user_id
 * @property integer quantity
 */
class Cart_extras extends Model
{

    public $table = 'cart_extras';
    


    public $fillable = [
        'extra_id',
        'cart_id',
        'extra_qty',
        'extra_price'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'extra_id' => 'integer',
        'cart_id' => 'integer',
        'extra_qty' => 'integer',
        'extra_price' => 'integer',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'food_id' => 'required|exists:foods,id',
        'user_id' => 'required|exists:users,id'
    ];

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields'
    ];

    public function customFieldsValues()
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }

    public function getCustomFieldsAttribute()
    {
        $hasCustomField = in_array(static::class,setting('custom_field_models',[]));
        if (!$hasCustomField){
            return [];
        }
        $array = $this->customFieldsValues()
            ->join('custom_fields','custom_fields.id','=','custom_field_values.custom_field_id')
            ->where('custom_fields.in_table','=',true)
            ->get()->toArray();

        return convertToAssoc($array,'name');
    }

}

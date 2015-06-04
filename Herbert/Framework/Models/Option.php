<?php namespace Herbert\Framework\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @see http://getherbert.com
 */
class Option extends Model {

    /**
     * Disable timestamps.
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'options';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'option_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'option_name', 'option_value', 'autoload'
    ];

}

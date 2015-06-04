<?php namespace Herbert\Framework\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @see http://getherbert.com
 */
class Term extends Model {

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
    protected $table = 'terms';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'term_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'slug'
    ];

    /**
     * Taxonomy relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxonomies()
    {
        return $this->hasMany(__NAMESPACE__ . '\Taxonomy', 'term_id');
    }

}

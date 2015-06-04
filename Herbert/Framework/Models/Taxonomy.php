<?php namespace Herbert\Framework\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @see http://getherbert.com
 */
class Taxonomy extends Model {

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
    protected $table = 'term_taxonomy';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'term_taxonomy_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'taxonomy', 'description', 'count'
    ];

    /**
     * Post relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->belongsToMany(__NAMESPACE__ . '\Post', 'term_relationships', 'term_taxonomy_id', 'object_id');
    }

    /**
     * Term relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo(__NAMESPACE__ . '\Term', 'term_id');
    }

}

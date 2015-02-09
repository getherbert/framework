<?php namespace Herbert\Framework\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @see http://getherbert.com
 */
class Post extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post_date', 'post_date_gmt',
        'post_content', 'post_title', 'post_excerpt',
        'post_status', 'comment_status', 'ping_status',
        'post_password', 'post_name',
        'to_ping', 'pinged',
        'post_modified', 'post_modified_gmt',
        'post_content_filtered', 'guid', 'menu_order',
        'post_type', 'post_mime_type',
        'comment_count'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'post_date', 'post_date_gmt',
        'post_modified', 'post_modified_gmt'
    ];

    /**
     * Comment relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(__NAMESPACE__ . '\Comment', 'comment_post_ID');
    }

    /**
     * Taxonomy relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxonomies()
    {
        return $this->belongsToMany(__NAMESPACE__ . '\Taxonomy', 'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    /**
     * PostMeta relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meta()
    {
        return $this->hasMany(__NAMESPACE__ . '\PostMeta', 'post_id');
    }

    /**
     * Get a specific type of post.
     *
     * @param $type
     * @return $this
     */
    public static function type($type)
    {
        return static::query()
            ->where('post_type', $type);
    }

}

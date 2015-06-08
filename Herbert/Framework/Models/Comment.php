<?php namespace Herbert\Framework\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @see http://getherbert.com
 */
class Comment extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comments';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'comment_ID';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'comment_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'comment_author', 'comment_author_email', 'comment_author_url', 'comment_author_IP',
        'comment_date', 'comment_date_gmt',
        'comment_content',
        'comment_karma', 'comment_approved',
        'comment_agent', 'comment_type'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'comment_date', 'comment_date_gmt'
    ];

    /**
     * Get this comment's post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(__NAMESPACE__ . '/Post', 'comment_post_ID');
    }

    /**
     * CommentMeta relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meta()
    {
        return $this->hasMany(__NAMESPACE__ . '\CommentMeta', 'comment_id');
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;

        if ( ! $value instanceof Carbon)
        {
            $value = new Carbon($value);
        }

        $this->{static::CREATED_AT . '_gmt'} = $value->timezone('GMT');
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setUpdatedAt($value)
    {
        //
    }

}

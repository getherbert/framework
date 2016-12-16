<?php namespace Herbert\Framework;

use Closure;
/*
 * This is wrapper for WordPress transient functions
 * It uses anonymous functions so 
 * 
 * For Example turn this ugly code:
 *
    if (false === ( $events = get_transient( 'my-events' ) ) ) {
        $events = Event::all($start,$end);
        set_transient( 'my-events', $result );
    }

 * Into:
 *
    $events = Cache::store('my-events',function() {
        return Event::all($start,$end);
    });
 */
    
class Cache {

    /**
     * Returns whatever is cached into $key.
     * Runs $closure and stores it into $key when $closure returns non-empty response
     *
     * @param String $key - Key to store the result of the operation
     *
     * @param Closure $closure - Anonymous function which is run only when needed
     *
     * @return mixed - Returns the result of $closure from cache
     */
    public static function store($key,Closure $closure)
    {
        if ( self::bypassCache() || false === ( $result = get_transient( $key ) ) ) {
          $result = $closure();
          if (!empty($result)) {
            set_transient( $key, $result );
          }
        }
        return $result;
    }

    /**
     * Delete something from the cache
     *
     * @param String $key - Key to delete the from cache
     *
     * @return mixed - Returns the result of $closure from cache
     */
    public static function delete($key)
    {
        return delete_transient($key);
    }

    /**
     * Check if the request has PRAGMA header set to no-cache
     *
     * @return bool
     */
    public static function bypassCache()
    {
         return (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] === 'no-cache');
    }
}
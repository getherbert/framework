<?php namespace Herbert\Framework;

/*
 * This is wrapper for WordPress transient functions
 * It uses anonymous functions so 
 * 
 * For Example turn this ugly code:
 *
    if (false === ( $events = get_transient( $transient ) ) ) {
        $events = Event::all($start,$end);
        set_transient( $transient, $result );
    }
    return $events;

 * Into:
 *
    $events = Cache::store($transient,function() {
        return Event::all($start,$end);
    });
    return $events;
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
    public static function store($key,$closure) {
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
    public static function delete($key) {
        return delete_transient($key);
    }

    /**
     * Check if the request has PRAGMA header set to no-cache
     *
     * @return Boolean
     */
    public static function bypassCache() {
         return (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] === 'no-cache');
    }
}
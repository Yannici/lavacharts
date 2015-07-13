<?php

namespace Khill\Lavacharts\Events;

/**
 * StateChange Event Object
 *
 * The base class for the individual event objects, providing common
 * functions to the child objects.
 *
 *
 * @package    Lavacharts
 * @subpackage Events
 * @since      2.0.0
 * @author     Kevin Hill <kevinkhill@gmail.com>
 * @copyright  (c) 2015, KHill Designs
 * @link       http://github.com/kevinkhill/lavacharts GitHub Repository Page
 * @link       http://lavacharts.com                   Official Docs Site
 * @license    http://opensource.org/licenses/MIT MIT
 */
class StateChange extends Event
{
    const TYPE = 'statechange';

    /**
     * Builds the StateChange Event object when passed an array of configuration options.
     *
     * @param  array $c Options for the Event
     * @throws \Khill\Lavacharts\Exceptions\InvalidConfigValue
     * @throws \Khill\Lavacharts\Exceptions\InvalidConfigProperty
     * @return self
     */
    public function __construct($c)
    {
        parent::__construct($c);
    }
}
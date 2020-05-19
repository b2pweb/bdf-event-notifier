<?php

/*
Copyright (C) 2013-2016 fruux GmbH (https://fruux.com/)

All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name Sabre nor the names of its contributors
      may be used to endorse or promote products derived from this software
      without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
    ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
    LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
    CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
 */

namespace Bdf\Event;

/**
 * Event notifier Trait.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait EventNotifier
{
    /**
     * The registered listeners by event
     *
     * @var callable[]
     */
    private $listeners = [];

    /**
     * The sorted listeners by their priority
     *
     * @var callable[]
     */
    private $sorted = [];

    /**
     * Flag to enable / disable the notifier
     *
     * @var bool
     */
    private $enableEventNotifier = true;

    /**
     * Enable the event dispatcher
     * 
     * @return $this
     */
    public function enableEventNotifier()
    {
        $this->enableEventNotifier = true;

        return $this;
    }

    /**
     * Enable the event dispatcher
     *
     * @return $this
     */
    public function disableEventNotifier()
    {
        $this->enableEventNotifier = false;

        return $this;
    }

    /**
     * Register listener on event
     *
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     *
     * @return $this
     */
    public function listen($eventName, callable $listener, $priority = 0)
    {
        $this->listeners[$eventName][$priority][] = $listener;
        unset($this->sorted[$eventName]);

        return $this;
    }
    
    /**
     * Register listener.
     * 
     * Will be remove on first event call
     * 
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     * 
     * @return $this
     */
    public function once($eventName, callable $listener, $priority = 0)
    {
        $wrapper = null;
        $wrapper = function(...$args) use ($eventName, $listener, &$wrapper) {
            $this->detach($eventName, $wrapper);

            return $listener(...$args);
        };
        
        $this->listen($eventName, $wrapper, $priority);
        
        return $this;
    }

    /**
     * Detach callable listener from event
     * 
     * @param string   $eventName
     * @param callable $listener
     * 
     * @return $this
     */
    public function detach($eventName, callable $listener)
    {
        if (!$this->hasListeners($eventName)) {
            return $this;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $index => $item) {
                if ($listener === $item) {
                    // remove the listener
                    unset($this->listeners[$eventName][$priority][$index], $this->sorted[$eventName]);

                    // remove empty collections
                    if (empty($this->listeners[$eventName][$priority])) {
                        unset($this->listeners[$eventName][$priority]);

                        if (empty($this->listeners[$eventName])) {
                            unset($this->listeners[$eventName]);
                        }
                    }

                    break;
                }
            }
        }

        return $this;
    }
    
    /**
     * Remove all listeners
     * 
     * Remove listeners of event name. If the event name is null, 
     * every listeners of every events will be detached
     * 
     * @param string $eventName
     * 
     * @return $this
     */
    public function detachAll($eventName = null)
    {
        if ($eventName !== null) {
            unset($this->listeners[$eventName], $this->sorted[$eventName]);
        } else {
            $this->listeners = [];
        }
        
        return $this;
    }
    
    /**
     * Check if there are listeners on this event
     * 
     * @param string $eventName
     *
     * @return bool
     */
    public function hasListeners($eventName): bool
    {
        return isset($this->listeners[$eventName]);
    }
    
    /**
     * Get all event listeners
     * 
     * @param string $eventName
     *
     * @return array
     */
    public function listeners($eventName): array
    {
        if (!$this->hasListeners($eventName)) {
            return [];
        }
        
        if (!isset($this->sorted[$eventName])) {
            ksort($this->listeners[$eventName]);

            foreach ($this->listeners[$eventName] as $listeners) {
                foreach ($listeners as $listener) {
                    $this->sorted[$eventName][] = $listener;
                }
            }
        }

        return $this->sorted[$eventName];
    }
    
    /**
     * Notify event
     * 
     * notify event name on listener. The event can be stopped
     * if a listener return 'false'. The notify will return the event status
     * 
     * true: ok
     * false: interrupted
     * 
     * @param string $eventName
     * @param mixed  $args
     * 
     * @return bool
     */
    public function notify($eventName, array $args = []): bool
    {
        if ($this->enableEventNotifier === false) {
            return true;
        }

        foreach ($this->listeners($eventName) as $listener) {
            if ($listener(...$args) === false) {
                return false;
            }
        }
        
        return true;
    }
}

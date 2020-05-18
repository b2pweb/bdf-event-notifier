<?php

namespace Bdf\Event;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class EventNotifierTest extends TestCase
{
    /**
     * 
     */
    public function test_listen()
    {
        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", function() {});
        
        $this->assertTrue($dispatcher->hasListeners("event.test"));
        $this->assertFalse($dispatcher->hasListeners("event.unknown"));
    }

    /**
     *
     */
    public function test_listeners()
    {
        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", function() {});

        $this->assertCount(1, $dispatcher->listeners("event.test"));
        $this->assertCount(0, $dispatcher->listeners("event.unknown"));
    }

    /**
     *
     */
    public function test_listeners_sort_listeners()
    {
        $listener1 = function() use(&$number) {
            $number = 1;
        };
        $listener2 = function() use(&$number) {
            $number = 2;
        };

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", $listener1, 1);
        $dispatcher->listen("event.test", $listener2, -1);

        $listeners = $dispatcher->listeners("event.test");

        $this->assertSame($listener2, $listeners[0]);
        $this->assertSame($listener1, $listeners[1]);
    }

    /**
     *
     */
    public function test_simple_notify()
    {
        $wasCalled = false;

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", function() use(&$wasCalled) {
            $wasCalled = true;
        });

        $dispatcher->notify("event.test");

        $this->assertTrue($wasCalled);
    }

    /**
     *
     */
    public function test_priority()
    {
        $number = 0;

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", function() use(&$number) {
            $number = 1;
        });
        $dispatcher->listen("event.test", function() use(&$number) {
            $number = 2;
        }, -1);

        $dispatcher->notify("event.test");

        $this->assertEquals(1, $number);
    }

    /**
     *
     */
    public function test_notify_stop_propagation()
    {
        $number = 0;

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", function() {
            return false;
        });
        $dispatcher->listen("event.test", function() use(&$number) {
            $number = 1;
        });

        $dispatcher->notify("event.test");

        $this->assertEquals(0, $number);
    }

    /**
     * @group seb
     */
    public function test_once()
    {
        $number = 0;

        $dispatcher = new MyDispatcher();
        $dispatcher->once("event.test", function() use(&$number) {
            $number++;
        });

        $dispatcher->notify("event.test");
        $dispatcher->notify("event.test");

        $this->assertEquals(1, $number);
    }

    /**
     *
     */
    public function test_disable_notify()
    {
        $number = 0;

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", function() use(&$number) {
            $number++;
        });

        $dispatcher->disableEventNotifier();
        $dispatcher->notify("event.test");
        $this->assertEquals(0, $number);

        $dispatcher->enableEventNotifier();
        $dispatcher->notify("event.test");
        $this->assertEquals(1, $number);
    }

    /**
     *
     */
    public function test_detach()
    {
        $number = 0;

        $listener1 = function() use(&$number) {
            $number = 1;
        };
        $listener2 = function() use(&$number) {
            $number = 2;
        };

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", $listener1);
        $dispatcher->listen("event.test", $listener2);
        $dispatcher->detach("event.test", $listener2);
        $dispatcher->notify("event.test");

        $this->assertEquals(1, $number);
        $this->assertCount(1, $dispatcher->listeners("event.test"));
    }

    /**
     *
     */
    public function test_detach_unknown_listener()
    {
        $listener1 = function() {};

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", $listener1);
        $dispatcher->detach("event.test", function(){});

        $this->assertTrue($dispatcher->hasListeners("event.test"));
    }

    /**
     *
     */
    public function test_detach_unknown_event()
    {
        $listener1 = function() {};

        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.test", $listener1);
        $dispatcher->detach("event.unknown", function(){});

        $this->assertTrue($dispatcher->hasListeners("event.test"));
    }

    /**
     *
     */
    public function test_detach_all()
    {
        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.foo", function() {});
        $dispatcher->listen("event.bar", function(){});

        $dispatcher->detachAll();

        $this->assertFalse($dispatcher->hasListeners("event.foo"));
        $this->assertFalse($dispatcher->hasListeners("event.bar"));
    }

    /**
     *
     */
    public function test_detach_all_event()
    {
        $dispatcher = new MyDispatcher();
        $dispatcher->listen("event.foo", function() {});
        $dispatcher->listen("event.bar", function(){});

        $dispatcher->detachAll("event.foo");

        $this->assertFalse($dispatcher->hasListeners("event.foo"));
        $this->assertTrue($dispatcher->hasListeners("event.bar"));
    }
}

//-----------

class MyDispatcher
{
    use EventNotifier;
}
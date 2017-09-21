<?php
namespace Cora;

class EventManager
{
    protected $eventMapping;
    
    /*
        Cora system events to fire:
        Event\\App\\Start
        Event\\Route\\Found
        Event\\Route\\NotFound
        Event\\Auth\\Login
        Event\\Auth\\Logout
        Event\\Cache\\Hit
        Event\\Cache\\Miss
        Event\\Cache\\Delete
        Event\\AmBlend\\PreSave
        Event\\AmBlend\\PostSave
        Event\\AmBlend\\PreFetch
        Event\\AmBlend\\PostFetch
        Event\\Database\\Connected
        Event\\Database\\Delete
        Event\\Database\\Insert
        Event\\Database\\Update
        Event\\Database\\Select
    */
    
    public function __construct(EventMapping $evm)
    {
        $this->eventMapping = $evm;
    }
    
    public function fire(Event $ev)
    {
        $continueEventChain = true;
        $listeners = $this->eventMapping->getListeners($ev);
        
        // If there's more than one listener for this event,
        // sort them according to their priority. (higher priority gets
        // executed first). Default priority is 0.
        if (count($listeners) > 1) {
            $listenerPriorityList = [];
            $listenerOrderList = [];
            $i = 0;
            foreach ($listeners as $listener) {
                if (isset($listener[1])) {
                    $listenerPriorityList[] = $listener[1];
                }
                else {
                    $listenerPriorityList[] = 0;
                }
                $listenerOrderList[] = $i;
                $i -= 1;
            }
            array_multisort($listenerPriorityList, SORT_DESC, $listenerOrderList, SORT_DESC, $listeners);
        }
        
        // Loop through the event listeners and call them.
        foreach ($listeners as $listener) {
            $objOrClosure = $listener[0];
            
            // If the listener is inside a container class, grab it.
            if ($objOrClosure instanceof \Cora\Listener) {
                $listenerObj = $objOrClosure;
                $handleResult = $listenerObj->handle($ev);
            }
            
            // If the listener is a closure function, execute it.
            else if (is_callable($objOrClosure)) {
                $listenerObj = $objOrClosure($this->eventMapping->app);
                $handleResult = $listenerObj->handle($ev);
            }
            
            // If the listener is a listener class name (string), call it.
            else {
                $listenerClass = $listener[0];
                $listenerObj = new $listenerClass;
                $handleResult = $listenerObj->handle($ev);
            }
            
            $continueEventChain = isset($handleResult) ? $handleResult : true;
            // If last called listener returns false, break event chain.
            if ($continueEventChain == false) {
                break;
            }
        }
    }
    
    public function listenFor($eventName, $callback, $priority = 0)
    {
        $this->eventMapping->setListener($eventName, $callback, $priority);
    }
}
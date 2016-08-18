<?php
namespace Cora;

class EventMapping extends Framework
{
    protected $listeners = [];
    
    public function getListeners(Event $ev)
    {
        // Grab event's namespace+name;
        $evName = get_class($ev);
        
        // If event is a default Cora event, use the defined $name field for event name.
        if ($evName == 'Cora\Event') {
            $evName = $ev->name;
        }
        
        return isset($this->listeners[$evName]) ? $this->listeners[$evName] : [];
    }
    
    public function setListener($eventName, $action, $priority = 0)
    {
        $this->listeners[$eventName][] = [$action, $priority];
    }
}
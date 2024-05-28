<?php
namespace Cora;

class EventMapping extends Framework
{
    protected $listeners = [];
    public $app;
    
    public function __construct($container = false)
    {
        parent::__construct();
        $this->app = $container;
        $this->listeners = $this->setListeners();
    }
    
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
    
    public function setListeners()
    {
        // To optionally be defined by children of EventMapping.
    }
}
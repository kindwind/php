<?php

class myQueue {
    private $MAXQUEUE = 1024;
    private $queue = array();
    private $front = -1;
    private $rear = -1;

    public function enqueue($item)
    {
        if($this->front==($this->rear = ($this->rear +1 )%$this->MAXQUEUE))
        {
            die("Queue is full");
        }
        else
        {
            $this->queue[$this->rear] = $item;
        }
    }

    public function dequeue()
    {
        if($this->front==$this->rear)
        {
            return "";
            //die("Queue is empty");
        }
        else
        {   $this->front = ($this->front +1)%$this->MAXQUEUE;
            $temp = $this->queue[$this->front];
            unset($this->queue[$this->front]);
            return $temp;
        }
    }
    
    public function isQueueEmpty()
    {
        if($this->front==$this->rear)
            return true;
        else
            return false;
    }
    
    public function isQueueFull()
    {
        if($this->rear == (($this->front+1)%$this->MAXQUEUE))
            return true;
        else
            return false;
    }
}

class myStack {
    private $MAXSTACK = 1024;
    private $stack = array();
    private $top = -1;
    
    public function push($item)
    {
        if ($this->top == $this->MAXSTACK-1)
        {
            die("Stack is full");
        }
        else
        {
            $this->top++;
            $this->stack[$this->top]=$item;
            //echo "($this->top: $item)\n";
        }
    }
    
    public function pop()
    {
        if ($this->top == -1)
        {
            die("Stack is empty");
        }
        else
        {
            $temp = $this->stack[$this->top];
            unset($this->stack[$this->top]);
            $this->top--;
            return $temp;
        }
    }
    
    public function isStackEmpty()
    {
        if ($this->top == -1)
            return true;
        else
            return false;
    }
    
    public function isStackFull()
    {
        if ($this->top == $this->MAXSTACK-1)
            return true;
        else
            return false;
    }
}


class XmlListNode{
    /* Data to hold */
    public $data;
 
    /* Link to next node */
    public $next;    
    public $lastChild;
    public $parent;
    public $name;
    public $brother;
    public $lineInFile;
 
    /* Node constructor */
    function __construct($data)
    {
        $this->data = $data;
        $this->next = NULL;
        $this->lastChild = NULL;
        $this->parent = NULL;
        $this->name = NULL;
        $this->brother = NULL;
        $this->lineInFile = 0;
        
    }
}
?>
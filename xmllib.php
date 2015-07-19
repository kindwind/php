<?
include("commonlib.php");

class mySimpleXml{
    
    private $file;
    private $xmlTree = array();
    public $xmlHeaderPattern = '/^<\?xml.*>$/';
    public $xmlTagStartPattern = '/^<[^!\s]+.*>$/';
    public $xmlTagEndPattern = '/^<\/.+>$/';
    public $xmlCommentPattern = '/^<!--.*-->$/';
    private $xmlTreeHead;
    private $backTrace;
    private $xmlElement = array();
    private $classMethod;
    
    function __construct($file) {
        $this->file = $file;
        $this->backTrace = debug_backtrace();
        $this->classMethod = get_class_methods(get_class($this));
        //echo $this->backTrace[0]["file"]."\n";
        //echo $this->backTrace[0]["line"]."\n";
    }

    public function mySimpleXml_load_file(){
        $contentQueue = new myQueue();
        $handle = fopen($this->file, "r");
        $contents = '';
        $message = '';
        $name = '';
        $messageArray = array();
        $messageCnt = 0;
        $LessThanSymbolAppear = false;
        $CommentStart = false;
        $HyphenSymbolCnt = 0;
    
        $xmlCollection = new myStack();
        $this->xmlTreeHead = new XmlListNode("");
        $this->xmlTreeHead->name = "XML Tree Head";
        $head = $this->xmlTreeHead;
        $linenum = 1;
        $xmlTagStartAppear = false;
        $errorMessage = array();
        $xmlElementPairsCheck = -1;
        $xmlEnd = false;

        while (!feof($handle))
        {
            $contents = fread($handle, 1);
            if($contents=="\r")
            {
                $contents = fread($handle, 1);
                if($contents=="\n");
                {
                    $linenum++;
                    continue;
                }
            }
            $message = '';
            //echo $contents;
            if($contents=="<")
            {
                if(!$CommentStart)
                {
                    $LessThanSymbolAppear = true;
                    while(!$contentQueue->isQueueEmpty())
                    {
                        $message.= $contentQueue->dequeue();
                    }
                }
                //echo $message;
                $contentQueue->enqueue($contents);
            }
            else if ($contents==">")
            {
                $contentQueue->enqueue($contents);
                if($LessThanSymbolAppear) //dequeue all contents
                {   
                    while(!$contentQueue->isQueueEmpty())
                    {
                        $message.= $contentQueue->dequeue();
                    }
                    $LessThanSymbolAppear = false;
                }
                else if($CommentStart&&$HyphenSymbolCnt>=4)
                {  
                    while(!$contentQueue->isQueueEmpty())
                    {
                        $message.= $contentQueue->dequeue();
                    }
                    $CommentStart = false;
                    $HyphenSymbolCnt = 0;
                }
                //echo $message;
            }
            else if($contents=='-'&&$CommentStart)
            {
                $HyphenSymbolCnt++;
                $contentQueue->enqueue($contents);
            }
            else
            {
                $contentQueue->enqueue($contents);
                if($contents=='!'&&$LessThanSymbolAppear)
                {
                    $LessThanSymbolAppear = false;
                    $CommentStart = true;
                }
            }

            $message = trim ($message, "\t\n\r\0\x0B");
            if($message!='')
            {
                //echo $linenum.":".$message."\n";
                if(!preg_match($this->xmlHeaderPattern,$message)&&!preg_match($this->xmlCommentPattern,$message))
                {
                    //echo "++++".$linenum.":".$message."+++++\n";
                    $messageArray[$messageCnt++] = $message;
                    if($xmlEnd)
                    {
                        $errorMessage[] = "<b>Waring</b>: ".$this->classMethod[1]."(): ".$this->file.":".$linenum." parser error :  Extra content at the end of the document in <b>".$this->backTrace[0]["file"]."</b> on line <b>".$this->backTrace[0]["line"]."</b>";
                        $errorMessage[] = "<b>Waring</b>: ".$this->classMethod[1]."(): ".str_replace(">","&gt;",str_replace("<","&lt;",$message))." in <b>".$this->backTrace[0]["file"]."</b> on line <b>".$this->backTrace[0]["line"]."</b>";
                        $errorMessage[] = "<b>Waring</b>: ".$this->classMethod[1]."(): ^ in <b>".$this->backTrace[0]["file"]."</b> on line <b>".$this->backTrace[0]["line"]."</b>";
                        break;
                    }
                    
                    if(preg_match($this->xmlTagEndPattern,$message)) //meet xml end tag
                    {
                        $xmlElementPairsCheck--;
                        $endTagNameTemp = preg_split("/[\s<\/>]+/",$message);
                        $endTagName = '';
                        $endTagName = $endTagNameTemp[1];
                        /*$this->xmlElement[$name]["coupled"] = 1;
                        $this->xmlElement[$name]["lineEnd"] = $linenum;*/
                        
                        //echo "end:+++++".$head->name.":".$message."+++++\n";
                        if($head->name!=$endTagName)
                        {
                            if($head->parent!=NULL)// It is tree head
                            {
                                $errorMessage[] = "<b>Waring</b>: ".$this->classMethod[1]."(): ".$this->file.":".$linenum." parser error :  Opening and ending tag mismatch: ".$head->name." line ".$head->lineInFile." and ".$endTagName." in <b>".$this->backTrace[0]["file"]."</b> on line <b>".$this->backTrace[0]["line"]."</b>";
                                $errorMessage[] = "<b>Waring</b>: ".$this->classMethod[1]."(): ".str_replace(">","&gt;",str_replace("<","&lt;",$message))." in <b>".$this->backTrace[0]["file"]."</b> on line <b>".$this->backTrace[0]["line"]."</b>";
                                $errorMessage[] = "<b>Waring</b>: ".$this->classMethod[1]."(): ^ in <b>".$this->backTrace[0]["file"]."</b> on line <b>".$this->backTrace[0]["line"]."</b>";
                            }
                        }
                        if($head->parent)
                        {
                            $head = $head->parent;
                        }
                        //echo $head->name."\n";
                    }
                    else if(preg_match($this->xmlTagStartPattern,$message)) //meet xml start tag, pop all from stack first and then push xml start tag to stack
                    {
                        $xmlElementPairsCheck++;
                        $xmlTagStartAppear = true;
                        $startTagNametemp = preg_split("/[\s<>]+/",$message);
                        $startTagName = '';
                        $startTagName = $startTagNametemp[1];
                        /*$this->xmlElement[$name]["coupled"] = 0;
                        $this->xmlElement[$name]["lineStart"] = $linenum;
                        $this->xmlElement[$name]["lineEnd"] = $linenum;*/
                        $item = new XmlListNode("");
                        $item->name = $startTagName;
                        $item->lineInFile = $linenum;
                        $item->parent = $head;
                        //echo "start-1:+++++".$head->name.":".$message."+++++\n";
                        if($head->next!=NULL)
                        {
                            $head = $head->next;
                            while($head->youngerBrother)
                            {
                                $head = $head->youngerBrother;
                            }
                            $head->youngerBrother = $item;
                            $item->olderBrother = $head;
                        }
                        else
                        {
                            $head->next = $item;
                        }
                        $head = $item;
                        //echo "start-2:+++++".$head->name.":".$message."+++++\n";
                        
                        $this->xmlTreeHead->lastChild = $item;
                    }
                    else
                    {
                        $head->data = $message;
                        //echo "data:+++++".$head->name.":".$message."+++++\n";
                    }
                    if($xmlElementPairsCheck==-1)
                    {
                        $xmlEnd = true;
                    }
                    //echo $xmlElementPairsCheck.":".$message."\n";
                }
            }
        }
        
        if(count($errorMessage)>0)
        {
            foreach($errorMessage as $key => $value)
            {
                echo "<br />\n";
                echo $value."<br />\n";
            }
            //die();
        }
        /*foreach($this->xmlElement as $key => $value)
        {
            echo $key.":".$value["coupled"].",".$value["lineStart"]."-".$value["lineEnd"]."\n";
        }*/
        fclose($handle);
        for($i=0;$i<count($messageArray);$i++)
        {
            //echo $messageArray[$i]."\n";
            /*if(preg_match($this->xmlTagEnd,$messageArray[$i])) //pop until start tag
            {
            }
            else if(preg_match($this->xmlTagStart,$messageArray[$i]))
            {
                $name = preg_split("/[\s<>]+/",$messageArray[$i])[1];
                $item = new XmlListNode("");
                $item->name = $name;
                $head->next = $item;
                $this->xmlTreeHead->lastChild = $item;
                $item->parent = $head;
                $head = $item;
            }
            else
            {
                //echo $name."\n";
                /*$item = new XmlListNode($message);
                $item->name = $name;
                if(!$xmlCollection->isStackFull())
                {
                    $xmlCollection->push($item);
                }
                $i++;*/
            //}
        }
        $this->xmlTree["root"] = "";
        //$this->depth_first_trace($this->xmlTreeHead->next, $this->xmlTreeHead, $this->xmlTree);
        $this->breadth_first_trace($this->xmlTreeHead->next, $this->xmlTreeHead, $this->xmlTree);
        //echo $head->name;
        print_r($this->xmlTree);
    }
    
    public function depth_first_trace($root, $xmlParentNode, &$parentArray)
    {
        if($root)
        {
            if($root->data!='')
            {
                $childArray[$root->name] = $root->data;
            }
            else
            {
                $childArray = array();
            }
            if($root->next)
            {
                $this->breadth_first_trace($root->next, $root, $childArray);
            }
            if($root->youngerBrother)
            {
                $this->breadth_first_trace($root->youngerBrother, $xmlParentNode, $parentArray);
            }
            /*echo "parent = ".$xmlParentNode->name.", child = ".$root->name."\n";
            echo "+++++ child array +++++\n";
            print_r($childArray);
            echo "----- child array -----\n";
            echo "+++++ parent array +++++\n";
            print_r($parentArray);
            echo "----- parent array -----\n\n";*/
            //echo $root->name.":".$root->data."<br/>\n";
        }
    }
    
    public function breadth_first_trace($root, $xmlParentNode, &$parentArray)
    {
        if($root)
        {
            if($root->data!='')
            {
                $childArray[$root->name] = $root->data;
            }
            else
            {
                $childArray = array();
            }
            //$parentArray[$xmlParentNode->name][$root->name] = $root->data;
            /*if($root->data!='')
            {
                $parentArray[$xmlParentNode->name][$root->name] = $root->data;
                //$this->xmlTree[$xmlParentNode->name][$root->name] = $root->data;
            }
            else
            {
                $parentArray[$xmlParentNode->name][$root->name] = array();
                //$this->xmlTree[$xmlParentNode->name][$root->name] = array();
            }*/
            echo "parent = ".$xmlParentNode->name.", child = ".$root->name."\n";
            echo "+++++ child array +++++\n";
            print_r($childArray);
            echo "----- child array -----\n";
            echo "+++++ parent array +++++\n";
            print_r($parentArray);
            echo "----- parent array -----\n\n";
            if($root->youngerBrother)
            {
                $this->breadth_first_trace($root->youngerBrother, $xmlParentNode, $parentArray);
            }
            /*echo "+++++++++++++++++++++++++++++++++No Brothers:+++++++++++++++++++++++++++++++++\n";
            echo "I am ".$root->name.", my parent is ".$xmlParentNode->name."\n";
            echo "---------------------------------No Brothers:---------------------------------\n\n";*/
            if($root->next)
            {           
                $this->breadth_first_trace($root->next, $root, $childArray);
            }
            if($root->data!='')
            {
                $parentArray[$root->name] = $root->data;
            }
            else
            {
                $parentArray[$root->name] = array_reverse($childArray);
            }            
            /*echo "parent = ".$xmlParentNode->name.", child = ".$root->name."\n";
            echo "+++++ child array +++++\n";
            print_r($childArray);
            echo "----- child array -----\n";
            echo "+++++ parent array +++++\n";
            print_r($parentArray);
            echo "----- parent array -----\n\n";*/
            /*echo "+++++++++++++++++++++++++++++++++No Children:+++++++++++++++++++++++++++++++++\n";
            echo "I am ".$root->name.", my parent is ".$xmlParentNode->name."\n";
            echo "---------------------------------No Children:---------------------------------\n\n";*/
            //print_r($item);
            //echo "<br/>\n";
            //echo $root->name.":".$root->data."<br/>\n";
        }
    }
}
?>
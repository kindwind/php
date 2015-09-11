<?php
include("commonlib.php");

class mySimpleXml{
    
    private $file;
    private $xmlTreeArray = array();
    public $xmlHeaderPattern = '/^<\?xml.*>$/';
    public $xmlTagStartPattern = '/^<[^!\s]+.*>$/';
    public $xmlTagEndPattern = '/^<\/.+>$/';
    public $xmlCommentPattern = '/^<!--.*-->$/';
    private $xmlTreeHead = NULL;
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

    public function MySimpleXml_load_file() {
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
        $this->xmlTreeHead = new xmlListNode("");
        $this->xmlTreeHead->name = "XML Tree Head";
        $head = $this->xmlTreeHead;
        $linenum = 1;
        $xmlTagStartAppear = false;
        $errorMessage = array();
        $xmlElementPairsCheck = -1;
        $xmlEnd = false;
        $depth = -1;

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
                        $depth --;
                        
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
                        $depth ++;
                        $item = new xmlListNode("");
                        $item->name = $startTagName;
                        $item->lineInFile = $linenum;
                        $item->parent = $head;
                        $item->depth = $depth;
                        //echo $item->name.":".$depth."\n";
                        //echo "start-1:+++++".$head->name.":".$message."+++++\n";
                        if($head->next!=NULL)
                        {
                            $head = $head->next;
                            while($head->youngerBrother)
                            {
                                $head->youngerBrother->olderBrother = $head;
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
            die();
        }
        fclose($handle);
        //$this->depth_first_trace($this->xmlTreeHead->next, $this->xmlTreeHead, $this->xmlTree);
        //$this->breadth_first_trace($this->xmlTreeHead->next, $this->xmlTreeHead, $this->xmlTreeArray);
        return $this->xmlTreeHead;
    }
    
    public function MySimpleXml_generate_xml_array() {
        if(!$this->xmlTreeHead)
        {
            $this->MySimpleXml_load_file();
        }
        $this->breadth_first_trace($this->xmlTreeHead->next, $this->xmlTreeHead, $this->xmlTreeArray);
        return $this->xmlTreeArray;
    }
    
    public function MySimpleXml_get_xmlNode(&$treeHead, $targetParentName, $targetName, $targetData, &$xmlNode) {
        //echo $treeHead->name;
        //echo $targetName;
        $this->depth_first_search($treeHead, NULL, $targetParentName, $targetName, $targetData, $xmlNode);
        return $xmlNode;
    }
    
    public function MySimpleXml_create_xmlNode(xmlListNode &$xmlNode) {
    }
    
    public function MySimpleXml_insert_childXmlNode_to(xmlListNode &$xmlNode, xmlListNode &$toXmlNode){
        $child = $toXmlNode;
        while($child->next)
        {
            $child = $child->next;
        }
        $child->next = $xmlNode;
        $xmlNode->parent = $child;
    }
    
    public function MySimpleXml_insert_youngerBrotherXmlNode_to(xmlListNode &$xmlNode, xmlListNode &$toXmlNode)
    {
        $child = $toXmlNode; 
        while($child->youngerBrother)
        {
            $child->youngerBrother->olderBrother = $child;
            $child = $child->youngerBrother;
        }
        $child->youngerBrother = $xmlNode;
        $xmlNode->olderBrother = $child;
        $xmlNode->parent = $child->parent;
    }
    
    /* Add a photo to xml database */
    public function MySimpleXml_inser_xmlPhotoNode($targetParentName, $targetName, $tagNameToAdd, xmlPhotoElement $xmlElement){
        $photoIDXmlNode = new xmlListNode("");
        $this->MySimpleXml_get_xmlNode($this->xmlTreeHead, "root","photoId", "", $photoIDXmlNode);
        $photoIDXmlNode->data += 1;
        //echo $photoIDXmlNode->data;
        //$this->xmlTreeArray["root"]["photoId"] +=1 ;
        
        /* Method 1 to generate and insert a photo node +++++ */
        /*$photoXmlNode = new xmlListNode("");
        $photoXmlNode->name = "photo";
        $photoXmlNode->next = new xmlListNode($xmlElement->name);//name
        $photoXmlNode->next->name = "name";
        $photoXmlNode->next->next = NULL;
        
        $photoXmlNode->next->youngerBrother = new xmlListNode($photoIDXmlNode->data);//id
        $photoXmlNode->next->youngerBrother->name = "id";
        $photoXmlNode->next->youngerBrother->next = NULL;
        
        $photoXmlNode->next->youngerBrother->youngerBrother = new xmlListNode($xmlElement->time);//time
        $photoXmlNode->next->youngerBrother->youngerBrother->name = "time";
        $photoXmlNode->next->youngerBrother->youngerBrother->next = NULL;
        
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother = new xmlListNode("");//owner
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->name = "owner";
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->youngerBrother = NULL;
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->next = new xmlListNode($xmlElement->owner->firstName);//owner:first name
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->next->name = "first_name";
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->next->youngerBrother = new xmlListNode($xmlElement->owner->lastName);//owner:first name
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->next->youngerBrother->name = "last_name";
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->next->youngerBrother->youngerBrother = NULL;
        $photoXmlNode->next->youngerBrother->youngerBrother->youngerBrother->next->youngerBrother->next = NULL;
        
        $lastPhoto = new xmlListNode(""); 
        if($xmlNode->next)
        {
            $lastPhoto = $xmlNode->next;
        }
        while($lastPhoto->youngerBrother)
        {
            $lastPhoto = $lastPhoto->youngerBrother;
        }
        $lastPhoto->youngerBrother = $photoXmlNode;
        $photoXmlNode->parent = $lastPhoto->youngerBrother->parent;
        $photoXmlNode->youngerBrother = NULL;*/
        /* Method 1 to generate and insert a photo node ----- */
        
        /* Method 2 to generate and insert a photo node +++++ */
        $xmlPhotoNode = new xmlListNode("");
        $xmlPhotoNode->name = "photo";
        
        $xmlPhotoNameNode = new xmlListNode($xmlElement->name);
        $xmlPhotoNameNode->name = "name";
        
        $this->MySimpleXml_insert_childXmlNode_to($xmlPhotoNameNode, $xmlPhotoNode);
        
        $xmlPhotoIDNode = new xmlListNode($photoIDXmlNode->data);
        $xmlPhotoIDNode->name = "id";
        
        $this->MySimpleXml_insert_youngerBrotherXmlNode_to($xmlPhotoIDNode, $xmlPhotoNameNode);
        $xmlPhotoOwnerNode = new xmlListNode("");
        $xmlPhotoOwnerNode->name = "owner";
        
        $xmlPhotoOwnerFirstNameNode = new xmlListNode($xmlElement->owner->firstName);
        $xmlPhotoOwnerFirstNameNode->name = "first_name";
        
        $this->MySimpleXml_insert_childXmlNode_to($xmlPhotoOwnerFirstNameNode, $xmlPhotoOwnerNode);
        
        $xmlPhotoOwnerLastNameNode = new xmlListNode($xmlElement->owner->lastName);
        $xmlPhotoOwnerLastNameNode->name = "last_name";
        
        $this->MySimpleXml_insert_youngerBrotherXmlNode_to($xmlPhotoOwnerLastNameNode, $xmlPhotoOwnerFirstNameNode);
        
        $this->MySimpleXml_insert_youngerBrotherXmlNode_to($xmlPhotoOwnerNode, $xmlPhotoIDNode);
        
        //$xmlPhotoTreeArray = array();
        //$this->reverse_breadth_first_trace($xmlPhotoNode->next, $xmlPhotoNode, $xmlPhotoTreeArray);
        //$this->breadth_first_trace($xmlPhotoNode->next, $xmlPhotoNode, $xmlPhotoTreeArray);
        //print_r($xmlPhotoTreeArray);
        
        /* Method 1 to add photo to xml tree +++++ */
        $this->MySimpleXml_insert_youngerBrotherXmlNode_to($xmlPhotoNode, $this->xmlTreeHead->next->next);
        /* Method 1 to add photo to xml tree ----- */
        
        /* Method 2 to add photo to xml tree +++++ */
        /* $head__ = $this->xmlTreeHead->next->next;
        while($head__->youngerBrother)
        {
            $head__ = $head__->youngerBrother;
        }
        $head__->youngerBrother = $xmlPhotoNode;*/
        /* Method 2 to add photo to xml tree ----- */
        /* Method 2 to generate and insert a photo node +++++ */

        $xmlPhotoTreeArray = array();
        $this->breadth_first_trace($this->xmlTreeHead->next, $this->xmlTreeHead, $xmlPhotoTreeArray);
        //print_r($xmlPhotoTreeArray);
        
        return $this->MySimpleXml_save_file('database.xml');
    }
    
    public function MySimpleXml_inser_childrenXmlNode(xmlListNode &$treeHead, $targetParentName, $targetName, $tagNameToAdd, $value, xmlListNode &$xmlNode){
        //echo $targetName;
        $this->MySimpleXml_get_xmlNode($treeHead, $targetParentName, $targetName, $value, $xmlNode);
        //echo $xmlNode->name;
        /*$child = new xmlListNode(""); 
        if($xmlNode->next)
        {
            $child = $xmlNode->next;
            while($child->youngerBrother)
            {
                $child = $child->youngerBrother;
            }
        }
        
        $child->youngerBrother = new xmlListNode($value);
        $child->youngerBrother->name = $tagNameToAdd;
        $child->youngerBrother->youngerBrother = NULL;
        $child->youngerBrother->next = NULL;
        $child->youngerBrother->parent = $child->parent;
        //return $xmlNode;
        $_xmlNode = $child;*/
    }
    
    /* Add a photo to xml database by write xml file directly */
    public function MySimpleXml_inser_xmlNode_by_contents($appendContents, $appendToTagName){
        $contentQueue = new myQueue();
        $handle = fopen($this->file, "r");
        $newFileHandle = fopen("new_test.xml","w");
        $contents = '';
        $message = '';
        $CommentStart = false;
        $linenum = 1;

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
            if($message=="</".$appendToTagName.">")
            {
                fwrite($newFileHandle, $appendContents, strlen($appendContents));
            }
            fwrite($newFileHandle, $message, strlen($message));
        }
        fclose($handle);
        fclose($newFileHandle);
        
        //It needs to take an action to save the new xml file back to the old one
        if (!copy("new_test.xml", "test"))
        {
            echo "failed to copy xml file ...\n";
        }
    }
    
    /* Save xml contents to a file */
    public function MySimpleXml_save_file($file)
    {
        $content = '';
        $this->create_xml_by_DFS($this->xmlTreeHead->next, $this->xmlTreeHead, $content);
        //echo $content;
        if (is_writable($file))
        {
            if (!$handle = fopen($file, 'w'))
            {
                echo "Cannot open file ($file)";
                return -1;
            }

            if (fwrite($handle, $content) === FALSE) {
                echo "Cannot write to file ($file)";
                return -2;
            }
            fclose($handle);
            return 1;
        }
        else
        {
            echo "The file $file is not writable";
            return -3;
        }
    }
    
    /* Generate xml contents from xml node list */
    public function create_xml_by_DFS($root, $xmlParentNode, &$xmlContent)
    {
        if($root)
        {
            $xmlContent .= "<".$root->name.">";
            if($root->next)
            {
                $this->create_xml_by_DFS($root->next, $root, $xmlContent);
            }
            $xmlContent .= $root->data;
            $xmlContent .= "</".$root->name.">";
            if($root->youngerBrother)
            {
                $this->create_xml_by_DFS($root->youngerBrother, $xmlParentNode, $xmlContent);
            }
        }
    }
    
    /* Depth First search xml node in list by parent name and node name (ongoing) */
    public function depth_first_search($root, $rootParent, $parentName, $elementName, $elementData, xmlListNode &$xmlNode)
    {
        if($root)
        {
            if($root->name == $elementName && ($root->data == $elementData || $elementData == "") )
            {
                if($parentName=='')
                {
                    $xmlNode = $root;
                    return $root;
                }
                else
                {
                    if($root->parent && $root->parent->name == $parentName)
                    {
                        //echo $root->parent->name."\n";
                        $xmlNode = $root;
                        return $root;
                    }
                }
            }
            else
            {
                if($root->next)
                {
                    $this->depth_first_search($root->next, $root, $parentName, $elementName, $elementData, $xmlNode);
                }
                if($root->youngerBrother)
                {
                    $this->depth_first_search($root->youngerBrother, $rootParent, $parentName, $elementName, $elementData, $xmlNode);
                }
            }
        }
        //$xmlNode =  NULL;
    }
    
    /* Depth First trace xml node list and convert it to an array (not done) */
    public function depth_first_trace(xmlListNode $root, xmlListNode $xmlParentNode, &$parentArray)
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
                $this->depth_first_trace($root->next, $root, $childArray);
            }
            if($root->youngerBrother)
            {
                $this->depth_first_trace($root->youngerBrother, $xmlParentNode, $parentArray);
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
    
    /* Depth First trace xml node list and convert it to an array */
    public function breadth_first_trace(xmlListNode $root, xmlListNode $xmlParentNode, &$parentArray)
    {
        if($root)
        {
            $repeat = false;
            if($root->data!='')
            {
                $childArray[$root->name] = $root->data;
            }
            else
            {
                $childArray = array();
            }
            /*echo "parent = ".$xmlParentNode->name.", child = ".$root->name."\n";
            echo "+++++ child array +++++\n";
            print_r($childArray);
            echo "----- child array -----\n";
            echo "+++++ parent array +++++\n";
            print_r($parentArray);
            echo "----- parent array -----\n\n";*/
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
            foreach($parentArray as $key => $value)
            {
                if($key == $root->name)
                {
                    //echo $root->name."\n";
                    $repeat = true;
                    break;
                    //print_r($parentArray);
                }
            }
            if(!$repeat)
            {
                if($root->data!='')
                {
                    $parentArray[$root->name] = $root->data;
                }
                else
                {
                    $parentArray[$root->name] = array_reverse($childArray);
                }
            }
            else
            {
                if (!empty($parentArray[$root->name][0]))
                {
                    $tempForRepeat = array();
                    foreach($parentArray[$root->name] as $key => $value)
                    {
                        $tempForRepeat[] = $value;
                        unset($parentArray[$root->name][$key]);
                    }
                    $i=0;
                    $parentArray[$root->name][$i++] = (($root->data!='')?$root->data:array_reverse($childArray));
                    for($i=0;$i<count($tempForRepeat);$i++)
                    {
                        $parentArray[$root->name][$i+1] = $tempForRepeat[$i];
                    }
                }
                else
                {
                    $tempForRepeat = $parentArray[$root->name];
                    unset($parentArray[$root->name]);
                    $parentArray[$root->name][] = (($root->data!='')?$root->data:array_reverse($childArray));
                    $parentArray[$root->name][] = $tempForRepeat;
                }
                $repeat = false;
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
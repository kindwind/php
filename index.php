<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
	<meta name="Author" content=""/>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<form action="upload.php" method="post" enctype="multipart/form-data">
檔案名稱:<input type="file" name="file" id="file" /><br />
<input type="submit" name="submit" value="upload" />
</form>
    

    
<form action="add_album.php" method="post">
相簿名稱:<input type="text" name="name_album" id="id_album" /><br />
<input type="submit" name="name_submit" value="add" />
</form>
    
</body>
</html>
<?
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
include("xmllib.php");

if (file_exists('test.xml')) {
    $xml = simplexml_load_file('test.xml');
    //print_r($xml);
    $myxml = new mySimpleXml('test.xml');
    $myxml->mySimpleXml_load_file();
} else {
    exit('Failed to open test.xml.');
}

?>
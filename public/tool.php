<?php

include_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Textualization\RISCOVID\Compiler as Compiler;

$KNOWN = array('en' => "Click here for English", 'es' => "Use este link para español");

if(array_key_exists('lang',$_REQUEST)){
    $LANG = $_REQUEST['lang'];
}else{
    $LANG = "";
   
    foreach (\Teto\HTTP\AcceptLanguage::get() as $locale) {
        if (isset($KNOWN[$locale['language']])) {
            $LANG=$locale['language'];
            break;
        }
    }
    $LANG = $LANG ?: 'en';
}

$strings = Yaml::parse(file_get_contents(dirname(__DIR__) . "/resources/"  . $LANG . ".yaml"));

$done = FALSE;
$error = FALSE;
$base_yaml = file_get_contents(dirname(__DIR__) . "/resources/form."  . $LANG . ".yaml");
if(array_key_exists('yaml',$_REQUEST)){
    $yaml = $_REQUEST['yaml'];
    $form = Yaml::parse($yaml);
    $res = Compiler::build($form, $LANG, $strings, Yaml::parse($base_yaml));
    if($res[1]){
        $error = $res[1];
    }else{
        echo $res[0];
        $done = TRUE;
    }
}else{
    $yaml = $base_yaml;
}
if(! $done) {
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <title><?php echo $strings['custom_tool'] ?></title>
  <link href="style.css" rel="stylesheet" />
</head>                   
<body>
<h1><?php echo $strings['custom_tool'] ?></h1>

<?php
foreach($KNOWN as $lang => $text) {
    if($lang != $LANG){
        echo '<p><a href="?lang=' . $lang  .'"> ' . $text . '</a></p>' ."\n";
    }
}
?>

<?php
   foreach($strings['tool_main'] as $para) {
       echo '<p>' . $para . '</p>' ."\n";
   }

   if($error){
       echo '<p></p><p><b>' . $strings['error'] . ': ' . $error .'</b></p>'. "\n";
   }
?>

<form method="POST">
<p>
<textarea rows="60" cols="120" name="yaml">
<?php echo $yaml; ?>
</textarea>
</p>
<p><input type='submit' value='<?php echo $strings["submit"]?>'/></p>
</form>

<?php
if($error){
    echo '<pre>';
    print_r($form);
    echo '</pre>';
}
?>

<hr>
<address><p><a href="http://textualization.com"><?php echo $strings['brought'] ?></a><br/>
<?php echo $strings['vancouver'] ?></p>                                                   
</address>
  </body>
</html>
<?php
}
?>

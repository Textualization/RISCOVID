<?php

include_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

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
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <title><?php echo $strings['title'] ?></title>
  <link href="style.css" rel="stylesheet" />
</head>                   
<body>
<h1><?php echo $strings['title'] ?></h1>

<?php
foreach($KNOWN as $lang => $text) {
    if($lang != $LANG){
        echo '<p><a href="?lang=' . $lang  .'"> ' . $text . '</a></p>' ."\n";
    }
}
?>

<?php
   foreach($strings['main'] as $para) {
       echo '<p>' . $para . '</p>' ."\n";
   }
?>

<h2><?php echo $strings['available_title'] ?></h2>
<p><?php echo $strings['available'] ?></p>
<ul>
<?php
foreach($KNOWN as $lang => $text) {
    echo '<li>';
    if($lang == $LANG) { echo '<b>'; }
    echo '<a href="riscovid.' . $lang . '.html">' . $strings[$lang] . '</a>';
    if($lang == $LANG) { echo '</b>'; }
    echo '</li>' ."\n";
}
?>
</ul>
</p>

<p><?php echo $strings['translations']?> <a href="https://github.com/Textualization/RISCOVID"><?php echo $strings['github'] ?></a></p>                                                                                                        
<h2><?php echo $strings['customized_title']?></h2>
<p><?php echo  $strings['customized'] ?> <a href="tool.php?lang=<?php echo $LANG ?>"><?php echo  $strings['custom_tool'] ?></a></p>

<address>
<hr>
<p><a href="http://textualization.com"><?php echo $strings['brought'] ?></a><br/>
<?php echo $strings['vancouver'] ?></p>                                                   
</address>
  </body>
</html>

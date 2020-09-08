<?php

// Copyright (C) 2020 Textualization Software Ltd., distributed under AGPLv3

namespace Textualization\RISCOVID;

class Compiler {

    private static function get($key, $yaml, $strings) {
        foreach ($yaml as $entry){
            if(array_key_exists($key, $entry)){
                return array($entry[$key], "");
            }
        }
        return array("", $strings['E01'] . ": " . $key);
    }

    private static function same($key, $yaml1, $yaml2) {
        $entry1 = "";
        foreach ($yaml1 as $entry){
            if(array_key_exists($key, $entry)){
                $entry1 = $entry[$key];
                break;
            }
        }
        if(! $entry1) return FALSE;
        
        $entry2 = "";
        foreach ($yaml2 as $entry){
            if(array_key_exists($key, $entry)){
                $entry2 = $entry[$key];
                break;
            }
        }
        if(! $entry2) return FALSE;

        if($entry1['caption'] != $entry2['caption']) return FALSE;
        if(count($entry1['options']) != count($entry2['options'])) return FALSE;
        foreach ($entry1['options'] as $key => $value) {
            if($entry1['options'][$key]['text']  != $entry2['options'][$key]['text'])  return FALSE;
            if($entry1['options'][$key]['gloss'] != $entry2['options'][$key]['gloss']) return FALSE;
        }
        if(array_key_exists('extra', $entry1) != array_key_exists('extra', $entry2)) return FALSE;
        if(array_key_exists('extra', $entry1)){
            if($entry1['extra']['text']  != $entry2['extra']['text'])  return FALSE;
            if($entry1['extra']['gloss'] != $entry2['extra']['gloss']) return FALSE;
        }
        return TRUE;
    }

    private static function sangloss($gloss) {
        return str_replace('"', '\"', $gloss);
    }
    
    public static function build($yaml, $lang, $strings, $base_yaml){
        $res = "";
        $js1 = "";
        $js_blobs = array();
        $err = "";

        $res .= '<!DOCTYPE html>' . "\n";
        $res .= '<html lang="' . $lang .'">' . "\n";
        $res .=
<<<HERE
  <head>
    <meta charset="utf-8"/>
    <title>
HERE
;
        $title = Compiler::get('title', $yaml, $strings);
        if($title[1]){
            return $title;
        }
        $title = $title[0];
        $res .= $title;
        $res .=
<<<HERE
</title>
<style>
body {
  background: #eee;
  font-family: 'Open Sans', sans-serif, Arial, Helvetica;
  font-size: 2em;
  padding-left: 10%;
  padding-right: 10%;
  text-size-adjust: none;
  -webkit-text-size-adjust: none;
  -moz-text-size-adjust: none;
  -ms-text-size-adjust: none;
}
input[type="radio" i]{
  font-size: 2em;
  width: 0.5em;
  height: 0.5em;
  vertical-align: middle;
}
input {
  font-size: 2em;
  vertical-align: middle;
}
label { 
  padding-left: 0.25em; 
}

</style>
  </head>
  <body>
    <h1>
HERE
;
        $res .= $title . '</h1>' . "\n";
        $text = Compiler::get('text', $yaml, $strings);
        if($text[1]){
            return $text;
        }
        $text = $text[0];
        foreach($text as $para){
            $res .= '<p>' . $para . '</p>' ."\n";
        }
        $res .=    
<<<HERE
    <form>

      <ol>

HERE
;
        $hoax_gloss = "";
        $KNOWN = array('title' => 1, 'text' => 1, 'summary' => 1, 'summarize' => 1);
        
        foreach ($yaml as $yaml_entry){
            $mainkey = "";
            foreach ($yaml_entry as $main => $entries){
                if(array_key_exists($main, $KNOWN)){
                    break;
                }
                if($mainkey != ""){
                    return array("", $strings['E02'] . ": " . $mainkey);
                }
                $mainkey = preg_replace('/[^a-zA-Z]/', '_', $main);
                $js = "";
                
                if(! array_key_exists('caption', $entries)){
                    return array("", $strings['E01'] . '(' . $mainkey . '): caption');
                }
                $res.= '	<li><p>' . $entries['caption'] .'<br/>'."\n";
                if(! array_key_exists('options', $entries)){
                    return array("", $strings['E01'] . '(' . $mainkey . '): options');
                }
                $opt_count = 0;
                foreach ($entries['options'] as $opt){
                    $opt_count += 1;
                    $optkey = $mainkey . '_' . $opt_count;
                    if(! array_key_exists('text', $opt)){
                        return array("", $strings['E01'] . '(' . $optkey . '): text');
                    }
                    
                    $res .= '<input type="radio" id="' . $optkey . '" name="' . $mainkey . '" /><label for="' .$optkey . '">' . $opt['text'] . '</label><br/>'."\n";
                    
                    
                    if(! array_key_exists('gloss', $opt)){
                        return array("", $strings['E01'] . '(' . $optkey . '): gloss');
                    }
                    $gloss = Compiler::sangloss($opt['gloss']);
                    $js1 .= '      var '. $optkey . ' = f.' . $optkey .'.checked;'. "\n";
                    if($gloss) {
                        $js  .= '      if(' . $optkey . ') summary += "' . $gloss . ' ";' . "\n";
                    }
                    if($mainkey == "hoax" && $opt_count == 1) {
                        $hoax_gloss = $gloss;
                    }
                }
                if(array_key_exists('postscript', $entries)){
                    $res .= '</p><p>' . $entries['postscript'] . '</p>' ."\n";
                }
                if(array_key_exists('extra', $entries)) {
                    $extra = $entries['extra'];
                    if(! array_key_exists('text', $extra)) {
                        return array("", $strings['E01'] . '(' . $mainkey  . '/extra): text');
                    }
                    if(! array_key_exists('gloss', $extra)) {
                        return array("", $strings['E01'] . '(' . $mainkey  . '/extra): gloss');
                    }
                    $res .= '</p><p>' . $extra['text'] . ': <input type="text" id="' . $mainkey . '_extra" name="' . $mainkey . '_extra" /></p>' ."\n";
                    $js1 .= '      var ' . $mainkey . '_extra = f.' .  $mainkey . '_extra.value;' . "\n";
                    $js  .= '      if(' . $mainkey . '_extra != "") summary += "' . Compiler::sangloss($extra['gloss']) . ': " + ' . $mainkey . '_extra + ". ";' . "\n";
                }
                
                $res .= '	</p></li>'."\n\n";
                $js_blobs[] = array( 'key' => $mainkey, 'js' => $js );
            }
        }
      $res .=
<<<HERE
      </ol>      
      <p>
	<input type="button" id="action" name="action" onclick="summarize()"
HERE
;
        $summarize = Compiler::get('summarize', $yaml, $strings);
        if($summarize[1]){
            return $summarize;
        }
        $summarize = $summarize[0];
        $res .= ' value="' . $summarize . '" />' ."\n";
        $res .= '      </p>' . "\n";

        $summary_title = Compiler::get('summary', $yaml, $strings);
        if($summary_title[1]){
            return $summary_title;
        }
        $summary_title = $summary_title[0];
        $res .= '      <p>' . $summary_title . '</p>' . "\n\n";
        $res .=             
<<<HERE
      <textarea id="summary" name="summary" rows="20" cols="60">
      </textarea>
    </form>
    <script>
      function summarize(){
      var f=document.forms[0];
      var summary="";

HERE
;
        if($hoax_gloss){
            $res .= '      if(f.hoax_1.checked){ summary="' . $hoax_gloss . "\" }\n      else\n      {\n";
        }
        $res .= $js1;

        // NLG bit
        $GENLANGS = array('en' => 1); # only English generator so far
        // to keep the tables small, work only on pairs, these pairs do nice aggregation
        $GENPAIRS = array( 'care-carefor' => 1, 'size-bubble' => 1, 'outside-classes' => 1,
                           'iwear-uwear' => 1,'transit-flight' => 1);
        $generator = null;

        $prev_generated = FALSE;
        for($main_idx=1; $main_idx < count($js_blobs); $main_idx+=1) {
            if($prev_generated){ # work in pairs
                $prev_generated = FALSE;
                continue;
            }
            $key1 = $js_blobs[$main_idx-1]['key'];
            $key2 = $js_blobs[$main_idx]['key'];
            // is a generation pair and the input haven't change? generate
            if(isset($GENLANGS[$lang]) && isset($GENPAIRS[$key1 ."-". $key2]) &&
               Compiler::same($key1, $yaml, $base_yaml) &&
               Compiler::same($key2, $yaml, $base_yaml)) {
                $entry1 = Compiler::get($key1, $base_yaml, $strings); $entry1 = $entry1[0];
                $entry2 = Compiler::get($key2, $base_yaml, $strings); $entry2 = $entry2[0];
                $count1 = count($entry1['options']);
                $count2 = count($entry2['options']);
                $has_extra1 = array_key_exists('extra', $entry1);
                $has_extra2 = array_key_exists('extra', $entry2);
                // guard
                $res .= '      if((';
                for($idx=1; $idx <= $count1; $idx +=1){
                    if($idx > 1){
                        $res .= ' || ';
                    }
                    $res .= $key1 . '_' . $idx;
                }
                $res .= ') && (';
                for($idx=1; $idx <= $count2; $idx +=1){
                    if($idx > 1){
                        $res .= ' || ';
                    }
                    $res .= $key2 . '_' . $idx;
                }
                $res .= ')){' . "\n";

                if(! $generator) {
                    $frame_repo  = file_get_contents(dirname(__DIR__) . "/resources/frames.json");
                    $lexicon     = file_get_contents(dirname(__DIR__) . "/resources/lexicon_" . $lang . '.json');
                    
                    $generator = RiscovidGenerator::NewSealed($frame_repo, array($lang => $lexicon));
                }
                $res .= '        var table = ['."\n";
                
                for($idx1=0; $idx1 < $count1 * ($has_extra1 ? 2 : 1); $idx1 +=1){
                    $res .= '          ['."\n";
                    for($idx2=0; $idx2 < $count2 * ($has_extra2 ? 2 : 1); $idx2 +=1){
                        $data = array($key1 => $idx1 % $count1, $key2 => $idx2 % $count2);
                        if($has_extra1) {
                            if($idx1 >= $count1){
                                $data[$key1 . '_extra'] = $key1 . '_EXTRA';
                            }
                        }
                        if($has_extra2) {
                            if($idx2 >= $count2){
                                $data[$key2 . '_extra'] = $key2 . '_EXTRA';
                            }
                        }
                        $res .= '            "' . Compiler::sangloss($generator->generate($data, array('lang'=>$lang))) . '"';
                        if($idx2 != ($count2  * ($has_extra2 ? 2 : 1))-1){
                            $res .= ",";
                        }
                        $res .= "\n";
                    }
                    $res .= '          ]';
                    if($idx1 != ($count1  * ($has_extra1 ? 2 : 1))-1){
                        $res .= ",";
                    }
                    $res .= "\n";
                }
                $res .= '        ];'."\n";
                // get the indexes with some JS hack
                $res .= '        var idx1 = 0';
                for($idx=1; $idx <= $count1; $idx +=1){
                    $res .= ' + ' . $key1 . '_' . $idx . ' * ' . ($idx - 1);
                }
                $res .= "\n";
                if($has_extra1) {
                    $res .= '        if(' . $key1 . '_extra != ""){ idx1 += ' . $count1 . '};' . "\n";
                }
                
                $res .= '        var idx2 = 0';
                for($idx=1; $idx <= $count2; $idx +=1){
                    $res .= ' + ' . $key2 . '_' . $idx . ' * ' . ($idx - 1);
                }
                $res .= "\n";
                if($has_extra2) {
                    $res .= '        if(' . $key2 . '_extra != ""){ idx2 += ' . $count2 . '};' . "\n";
                }
                $res .= '        var text = table[idx1][idx2];'."\n";
                if($has_extra1) {
                    $res .= '        if(' . $key1 . '_extra != ""){ text = text.replace("' . $key1 . '_EXTRA", ' . $key1 . '_extra) }'."\n";
                }
                if($has_extra2) {
                    $res .= '        if(' . $key2 . '_extra != ""){ text = text.replace("' . $key2 . '_EXTRA", ' . $key2 . '_extra) }'."\n";
                }
                $res .= '        summary += text;'."\n";
                $res .= '      }else{'."\n";
                // fall back to glosses
                $res .= $js_blobs[$main_idx-1]['js'];
                $res .= $js_blobs[$main_idx]['js'];
                $res .= '      }'."\n";
                $prev_generated = TRUE;
            }else{
                // fall back to glosses
                $res .= $js_blobs[$main_idx-1]['js'];
            }
        }
        if(! $prev_generated){
            $res .= $js_blobs[count($js_blobs)-1]['js'];
        }

        if($hoax_gloss){
            $res .= '      }' . "\n";
        }
        $res .=
<<<HERE
      document.forms[0].summary.innerHTML=summary;
      return 1;
      }
    </script>

    <hr>
HERE
;             
        $res .= '<address><p><a href="http://textualization.com">' .  $strings['brought'].'</a><br/>' ."\n";
        $res .= '<a href="https://creativecommons.org/licenses/by-sa/2.0/ca/">' . $strings['licensed'] . '</a><br/>' ."\n";
        $res .= '<b>' . $strings['may_contain'] . '</b><br/>' ."\n";
        $res .= $strings['getyours'] . ': <a href="http://textualization.com/riscovid">http://textualization.com/riscovid</a><br/>' ."\n";
        $res .= $strings['vancouver'] . '</p>' . "\n";
        $res .=
<<<HERE
</address>
  </body>
</html>
HERE
;             
        return array( $res, $err);
    }
}

<?php

// Copyright (C) 2020 Textualization Software Ltd., distributed under AGPLv3

namespace Textualization\RISCOVID;

use NLGen\Generator;

class RiscovidGenerator extends Generator {
    function top($data){
        // enhance input
        $preds = array();
        foreach($data as $key => $value){
            if(strpos($key, '_extra')){
                continue;
            }else{
                $preds[$key] = new Predicate($key, array($value));
            }
        }
        foreach($preds as $key => $pred){
            if(array_key_exists($key . "_extra", $data)){
                $pred->args[] = $data[$key . "_extra"];
            }
        }
        
        // plan
        $PLAN = array('care', 'carefor', 'size', 'bubble', 'outside', 'classes', 'iwear', 'uwear', 'transit', 'flight');
        $frames = array();
        foreach($PLAN as $key){
            if(array_key_exists($key, $preds)) {
                $pred = $preds[$key];
                // this generator uses the "ontology" as a frame repo
                $frame = $this->onto->find($key);
                if(! $frame){
                    die("Missing " . $key);
                }
                $frame = $frame[$pred->args[0]];
                if(! $frame){
                    die("For key " . $key . " missing value " . $pred->args[0]);
                }
                if(!array_key_exists('type', $frame)){
                    $frame['type'] = $key;
                }
                if(count($pred->args) > 1){
                    $frame['extra'] = $pred->args[1];
                }
                $frames[] = $frame;
            }
        }
        
        // aggregate and add connectives
        $new_frames = array();
        $prev = null;
        foreach($frames as $frame){
            if($prev){
                if($frame['type'] == 'carefor' && $prev['type'] == 'care'){
                    if($prev['care'] == 'yes' && $frame['carefor'] == 'yes'){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'justify',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }elseif ($prev['care'] == 'no' && $frame['carefor'] == 'yes'){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'concession',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }elseif($prev['care'] == 'no' && $frame['carefor'] == 'no'){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'justify',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }else{
                        // ignore negative carefor
                        $prev = null;
                    }
                }
                if($frame['type'] == 'bubble' && $prev['type'] == 'size'){
                    if($prev['degree'] == 'large' && $frame['degree'] == 'large'){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'elaboration',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }elseif($prev['degree'] != $frame['degree']){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'concession',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }else{
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'elaboration',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }                      
                }
                if($frame['type'] == 'classes' && $prev['type'] == 'outside'){
                    if($prev['outside'] == 'no' &&  $frame['classes'] == 'no'){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'list', 'polarity' => 'negated', 'who' => 'none',
                                                                     'length' => 2, 'l1' => $prev, 'l2' => $frame );
                        $prev = null;
                    }else if($prev['risk'] ==  $frame['risk']){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'elaboration',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }elseif($prev['risk'] != $frame['risk']){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'concession',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }                      
                }
                if($frame['type'] == 'wear' && $prev['type'] == 'wear'){
                    if($prev['wear'] ==  $frame['wear']){
                        $new_frames[count($new_frames) - 1]['who'] = array($prev['who'], $frame['who']);
                        $prev = null;
                    }else{
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'concession',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }
                }
                if($frame['type'] == 'flight' && $prev['type'] == 'transit'){
                    if(($prev['transit'] == 'never' || $prev['transit'] == 'monthly') &&  $frame['flight'] == 'no'){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'list', 'polarity' => 'negated', 'who' => 'none',
                                                                     'length' => 2,'l1' => $prev, 'l2' => $frame );
                        $prev = null;
                    }elseif($prev['risk'] ==  $frame['risk']){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'elaboration',
                                                                     'n' => $prev, 's' => $frame );
                        $prev = null;
                    }elseif($prev['risk'] != $frame['risk']){
                        $new_frames[count($new_frames) - 1] = array( 'type' => 'relation', 'rel' => 'concession',
                                                                 'n' => $prev, 's' => $frame );
                        $prev = null;
                    }
                }
            }else{
                $new_frames[] = $frame;
                $prev = $frame;
            }
        }
        
        $res = "";
        foreach($new_frames as $frame) {
            $res .= ucfirst(trim($this->gen($frame['type'], $frame))) . ". ";
        }
        return $res;
    }
    
    protected function relation($frame){
        return $this->gen($frame['rel'] . "_orig", array($frame['n'], $frame['s']));
    }
    protected function justify($n, $s){
        //TODO more information can be added to the lexicon to allow for fronting or other constructions
        $nv = $this->gen($n['type'], $n);
        $sv = $this->gen($s['type'], $s);
        if(trim($sv) == "")
            return $nv;
        return $nv . " " . $this->lex->string_for_id('conj_just') . " " . $sv;
    }
    protected function concession($n, $s){
        return $this->gen($n['type'], $n) . " " . $this->lex->string_for_id('conj_conc') . " " . $this->gen($s['type'], $s);
    }
    protected function elaboration($n, $s){
        return $this->gen($n['type'], $n) . " " . $this->lex->string_for_id('conj_elab') . " " . $this->gen($s['type'], $s);
    }
    protected function list($frame){
        $res = "";
        if($frame['polarity'] != 'negated'){
            for($idx=1; $idx <= $frame['length']; $idx += 1){
                $elem = $frame["l" . $idx];
                if($idx > 1){
                    $res .= ", ";
                }
                $res .= $this->gen($elem['type'], $elem);
            }
        }else{
            $res = $this->lex->string_for_id("who_none");
            for($idx=1; $idx <= $frame['length']; $idx++){ 
                $elem = $frame["l" .$idx];
                if($idx > 1){
                    $res.= ' ' . $this->lex->string_for_id("not_list_sep");
                }
                $res .= " " . $this->gen($elem['type'] . "NegHeadless", $elem);
            }
        }
        return $res;
    }
    protected function care($frame){
        return $this->lex->string_for_id("care_" . $frame['degree']);
    }
    protected function carefor($frame){
        if($frame['carefor'] == "no") return "";
        if(isset($frame['extra'])){
            return $this->lex->string_for_id("carefor_extra"). ' ' . $frame['extra'];
        }
        return $this->lex->string_for_id("carefor");
    }
    protected function size($frame){
        //TODO agreement between size and bubble (size_one vs. "our buble")
        return array("text" => $this->lex->string_for_id("size_" . $frame['size']),
                     "sem" => array("num" => $frame['size'] == "one" ? "1" : "pl"));
    }
    protected function bubble($frame){
        $semcount = count($this->semantics);
        if($semcount > 2){
            $sem = $this->semantics[$semcount - 3];
            if(isset($sem['size']) && isset($sem['size']['size_orig']['num']) && $sem['size']['size_orig']['num'] == "1")
                return $this->lex->string_for_id("bubble_" . $frame['bubble'] . "_1");
        }
        return $this->lex->string_for_id("bubble_" . $frame['bubble']);
    }
    protected function outside($frame){
        return $this->lex->string_for_id("outside_" . $frame['outside']);
    }
    protected function outsideNegHeadless($frame){
        return $this->lex->string_for_id("outside_neg_headless");
    }
    protected function classes($frame){
        return $this->lex->string_for_id("classes_" . $frame['classes']);
    }
    protected function classesNegHeadless($frame){
        return $this->lex->string_for_id("classes_neg_headless");
    }
    protected function wear($frame){
        if(is_array($frame['who'])){
            $this->context['wear_setup'] = 1;
            $res = $this->lex->string_for_id("wear_setup")
                 . " " . $this->lex->string_for_id("wear_"  . $frame['who'][0])
                 . " " . $this->lex->string_for_id("wear_"  . $frame['wear']);
            if(isset($frame['depends'])){
                $res .= " " . $this->lex->string_for_id("wear_depends_"  . $frame['depends']);
            }
               
            $res .= " " . $this->lex->string_for_id("wear_" . $frame['who'][1] . "_end");
        }else{
            // check the history to see if wear_setup has been generated
            $res =  "";
            if(! isset($this->context['wear_setup'])) {
                $res = $this->lex->string_for_id("wear_setup") . " ";
                $this->context['wear_setup'] = 1; // TODO use semantics for this 
            }
            $mixed = $this->lex->find("wear_" . $frame['who'] . "_" . $frame['wear']);
            if($mixed){
                $res .= $mixed['string'];
            }else{
                $res .= $this->lex->string_for_id("wear_"  . $frame['who'])
                     . " " . $this->lex->string_for_id("wear_"  . $frame['wear']);
                if(isset($frame['depends'])){
                    $res .= " " . $this->lex->string_for_id("wear_depends_"  . $frame['depends']);
                }
            }
        }
        return $res;
    }
    protected function transit($frame){
        return $this->lex->string_for_id("transit_" . $frame['transit']);
    }
    protected function transitNegHeadless($frame){
        return $this->lex->string_for_id("transit_neg_headless");
    }
    protected function flight($frame){
        return $this->lex->string_for_id("flight_" . $frame['flight']);
    }
    protected function flightNegHeadless($frame){
        return $this->lex->string_for_id("flight_neg_headless");
    }    
}

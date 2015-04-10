<?php

namespace CM\AppBundle\Helpers;

/**
 * HTMLHelper
 */
class HTMLHelper
{
	private static $defaultBoard = array(
								    		array('w_r','w_n','w_b','w_q','w_k','w_b','w_n','w_r'),
								    		array('w_p','w_p','w_p','w_p','w_p','w_p','w_p','w_p'),
								    		array(false, false, false, false, false, false, false, false),
								    		array(false, false, false, false, false, false, false, false),
								    		array(false, false, false, false, false, false, false, false),
								    		array(false, false, false, false, false, false, false, false),
								    		array('b_p','b_p','b_p','b_p','b_p','b_p','b_p','b_p'),
								    		array('b_r','b_n','b_b','b_q','b_k','b_b','b_n','b_r')
								    	);
	private static $unicode = array( 
						    			'b_r' => '&#9820;',
						    			'b_n' => '&#9822;',
						    			'b_b' => '&#9821;',
						    			'b_q' => '&#9819;',
						    			'b_k' => '&#9818;',
						    			'b_p' => '&#9823;',
						    			'w_r' => '&#9814;',
						    			'w_n' => '&#9816;',
						    			'w_b' => '&#9815;',
						    			'w_q' => '&#9813;',
						    			'w_k' => '&#9812;',
						    			'w_p' => '&#9817;'
						    		);
	
   /**
    * Get unicode pieces and HTML selector ids
    * 
    * @param array|bool $board
    * @return array
    */ 
    public static function getUnicodePieces($board = false) {
    	if (!$board) {
	    	$board = self::$defaultBoard;
    	}
    	$pieces = array();
    	foreach ($board as $row => $cols) {
    		$pieces[$row] = array();
    		foreach ($cols as $col => $piece) {
    			if ($piece) {
    				$pieces[$row][$col] = array('id' => $piece.'_'.$row.$col, 'img' => self::$unicode[$piece]);     				
    			} else {
    				$pieces[$row][$col] = false;
    			}
    		}
    	}

    	return $pieces;
    }
	
   /**
    * Get unicode taken pieces and HTML selector ids
    * 
    * @param array $taken
    * @return array
    */ 
    public static function getUnicodeTakenPieces($taken) {
    	$wTaken = array();
    	$bTaken = array();
    	foreach ($taken as $piece => $count) {
    		if ($piece[0] == 'w') {
    			$wTaken[] = array('id' => $piece.'_t', 'img' => self::$unicode[$piece], 'count' => $count);
    		} else {
    			$bTaken[] = array('id' => $piece.'_t', 'img' => self::$unicode[$piece], 'count' => $count);
    		}
    	}

    	return array($wTaken, $bTaken);
    }	
}
<?php

namespace CM\InterfaceBundle\Helpers;

/**
 * HTMLHelper
 */
class HTMLHelper
{
	private static $defaultBoard = array(
								    		array('w_rook','w_knight','w_bishop','w_queen','w_king','w_bishop','w_knight','w_rook'),
								    		array('w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn','w_pawn'),
								    		array(false, false, false, false, false, false, false, false),
								    		array(false, false, false, false, false, false, false, false),
								    		array(false, false, false, false, false, false, false, false),
								    		array(false, false, false, false, false, false, false, false),
								    		array('b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn','b_pawn'),
								    		array('b_rook','b_knight','b_bishop','b_queen','b_king','b_bishop','b_knight','b_rook')
								    	);
	private static $unicode = array( 
						    			'b_rook' => '&#9820;',
						    			'b_knight' => '&#9822;',
						    			'b_bishop' => '&#9821;',
						    			'b_queen' => '&#9819;',
						    			'b_king' => '&#9818;',
						    			'b_pawn' => '&#9823;',
						    			'w_rook' => '&#9814;',
						    			'w_knight' => '&#9816;',
						    			'w_bishop' => '&#9815;',
						    			'w_queen' => '&#9813;',
						    			'w_king' => '&#9812;',
						    			'w_pawn' => '&#9817;'
						    		);
	
   /**
    * Get Unicode pieces and HTML selector ids
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
    				$pieces[$row][$col] = array('id' => $piece.'_'.$col, 'img' => self::$unicode[$piece]);     				
    			} else {
    				$pieces[$row][$col] = false;
    			}
    		}
    	}

    	return $pieces;
    }	
}
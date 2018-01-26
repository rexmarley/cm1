<?php

namespace CM\AppBundle\Tests\Helpers\Validation;

use CM\AppBundle\Helpers\Validation\KnightValidator;

class KnightValidatorTest extends \PHPUnit\Framework\TestCase
{	
	private $helper;
	private $game;
	private $board;
	 
	public function setUp() {
		$this->helper = new KnightValidator();
		$this->game = $this->getMockBuilder('CM\AppBundle\Entity\Game')
							->disableOriginalConstructor()->getMock();
	}
	
    public function testMoves()
    {
    	$this->board = $this->getBoard();
    	
    	$this->helper->setGlobals($this->game, $this->board);
    	//valid moves
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(0,1), 'to' => array(2,0))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(0,1), 'to' => array(2,2))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(0,6), 'to' => array(2,5))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(0,6), 'to' => array(2,7))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(7,1), 'to' => array(5,0))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(7,1), 'to' => array(5,2))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(7,6), 'to' => array(5,5))));
    	$this->assertEquals(true, $this->helper->validatePiece(array('from' => array(7,6), 'to' => array(5,7))));
    	//invalid moves
    	$this->assertEquals(false, $this->helper->validatePiece(array('from' => array(0,1), 'to' => array(0,3))));
    	$this->assertEquals(false, $this->helper->validatePiece(array('from' => array(0,6), 'to' => array(1,5))));
    	$this->assertEquals(false, $this->helper->validatePiece(array('from' => array(7,1), 'to' => array(7,4))));
    	$this->assertEquals(false, $this->helper->validatePiece(array('from' => array(7,6), 'to' => array(5,2))));
    }
    
    private function getBoard() {
    	return array(
			array('R','N','B','Q','K','B','N','R'),
    		array(false, false, false, false, false, false, false, false),
    		array(false, false, false, false, false, false, false, false),
    		array(false, 'P', false, false, false, 'P', false, false),
    		array(false, 'q', false, 'p', false, false, false, false),
    		array(false, false, false, false, false, false, false, 'p'),
    		array(false, false, false, false, false, false, false, false),
    		array('r','n','b',false,'k','b','n','r')
    	);
    }
    
    public function tearDown() {
    	unset($this->board);
    	unset($this->game);
    	unset($this->helper);
    }
}

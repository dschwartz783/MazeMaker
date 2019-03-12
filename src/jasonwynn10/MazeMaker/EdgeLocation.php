<?php
declare(strict_types=1);
namespace jasonwynn10\MazeMaker;

use pocketmine\math\Vector2;

class EdgeLocation extends Vector2 {
	public $orientation;

	public function __construct(int $orientation, int $x, int $z) {
		$this->orientation = $orientation;
		parent::__construct($x, $z);
	}
}
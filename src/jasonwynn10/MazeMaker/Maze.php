<?php
declare(strict_types=1);
namespace jasonwynn10\MazeMaker;

use pocketmine\math\Vector2;

class Maze {
	CONST EMPTY = 0;
	CONST FILLED = 1;

	CONST VERTICAL = 0;
	CONST HORIZONTAL = 1;

	CONST WALL = 0;
	CONST OPEN = 1;
	CONST LOOP = 2;
	CONST TWIST = 3;

	CONST TOPOLOGY_RECTANGLE = "rectangle";
	CONST TOPOLOGY_OUTDOOR = "outdoor";
	CONST TOPOLOGY_CYLINDER = "cylinder";
	CONST TOPOLOGY_TORUS = "torus";
	CONST TOPOLOGY_MOBIUS = "mobius";

	protected $topology = self::TOPOLOGY_OUTDOOR;
	protected $sizeX = 16;
	protected $sizeZ = 16;
	protected $hallSize = 2;
	protected $blockPool = [];
	public $vertexFilled = [];

	protected $topologyX = self::OPEN;
	protected $topologyZ = self::OPEN;
	protected $outRoom = 16*16;

	/**
	 * Maze constructor.
	 *
	 * @param string $topology
	 * @param int $sizeX
	 * @param int $sizeZ
	 * @param int $hallSize
	 * @param int[] $blockPool
	 */
	public function __construct(string $topology, int $sizeX, int $sizeZ, int $hallSize, array $blockPool) {
		$this->topology = $topology;
		switch($topology) {
			case self::TOPOLOGY_RECTANGLE:
				$this->topologyX = self::WALL;
				$this->topologyZ = self::WALL;
			break;
			case self::TOPOLOGY_OUTDOOR:
				$this->topologyX = self::OPEN;
				$this->topologyZ = self::OPEN;
			break;
			case self::TOPOLOGY_CYLINDER:
				$this->topologyX = self::LOOP;
				$this->topologyZ = self::WALL;
			break;
			case self::TOPOLOGY_TORUS:
				$this->topologyX = self::LOOP;
				$this->topologyZ = self::LOOP;
			break;
			case self::TOPOLOGY_MOBIUS:
				$this->topologyX = self::TWIST;
				$this->topologyZ = self::WALL;
			break;
		}
		$this->sizeX = $sizeX;
		$this->sizeZ = $sizeZ;
		$this->hallSize = $hallSize;
		if($this->topologyX === self::OPEN and $this->topologyZ === self::OPEN) {
			$this->outRoom = $sizeX * $sizeZ;
		}
	}

	public function getEdgeCount() : int {
		return (
			($this->sizeX * ($this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ))) +
			($this->sizeZ * ($this->sizeX + $this->getEdgeCountAdjustment($this->topologyX)))
		);
	}
	public function getEdgeFromLocation(int $orientation, int $x, int $z) : int {
		$x = $this->modForTopology($x, $this->sizeX, $this->topologyX);
		$z = $this->modForTopology($z, $this->sizeZ, $this->topologyZ);
		if($orientation === self::HORIZONTAL) {
			$horizontalEdgeMajorSize = $this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ);
			if($this->topologyZ === self::OPEN) {
				$z += 1;
			}
			if($this->topologyX === self::TWIST and $x > $this->sizeX) {
				$x -= $this->sizeX;
				$z = $horizontalEdgeMajorSize - 1 - $z;
			}
			return $x * $horizontalEdgeMajorSize + $z;
		}else{
			if($this->topologyX === self::OPEN) {
				$x += 1;
			}
			if($this->topologyX === self::TWIST and $x >= $this->sizeX) {
				$x -= $this->sizeX;
				$z = $this->sizeZ - 1 - $z;
			}
			return $this->getHorizontalEdgeCount() + $x * $this->sizeZ + $z;
		}
	}
	public function getHorizontalEdgeCount() : int {
		return $this->sizeX * ($this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ));
	}
	public function getEdgeLocation($edge) {
		$horizontalEdgeCount = $this->getHorizontalEdgeCount();
		$horizontalEdgeMajorSize = $this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ);
		if($edge < $horizontalEdgeCount) {
			$orientation = self::HORIZONTAL;
			$x = (int) floor($edge / $horizontalEdgeMajorSize);
			$z = $edge % $horizontalEdgeMajorSize;
			if($this->topologyZ === self::OPEN) {
				$z -= 1;
			}
		}else{
			$edge -= $horizontalEdgeCount;
			$orientation = self::VERTICAL;
			$x = (int) floor($edge / $this->sizeZ);
			$z = $edge % $this->sizeZ;
			if($this->topologyZ === self::OPEN) {
				$x -= 1;
			}
		}
		return new EdgeLocation($orientation, $x, $z);
	}

	/**
	 * @return int
	 */
	public function getVertexCount() : int {
		return (
			($this->sizeX + $this->getEdgeCountAdjustment($this->topologyX)) *
			($this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ))
		);
	}

	/**
	 * @param int $vertex
	 *
	 * @return Vector2
	 */
	public function getVertexLocation(int $vertex) : Vector2 {
		$majorSize = $this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ);
		$x = (int) floor($vertex / $majorSize);
		$z = $vertex % $majorSize;
		if($this->topologyX === self::OPEN) {
			$x -= 1;
		}
		if($this->topologyZ === self::OPEN) {
			$z -= 1;
		}
		return new Vector2($x, $z);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getVertexFromLocation(int $x, int $z) : int {
		$majorSize = $this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ);
		$x = $this->modForTopology($x, $this->sizeX, $this->topologyX);
		$z = $this->modForTopology($z, $this->sizeZ, $this->topologyZ);
		if($this->topologyX === self::OPEN) {
			$x += 1;
		}
		if($this->topologyZ === self::OPEN) {
			$z += 1;
		}
		if($this->topologyX === self::TWIST and $x >= $this->sizeX) {
			$x -= $this->sizeX;
			$z = $this->sizeZ + $this->getEdgeCountAdjustment($this->topologyZ) - 1 - $z;
		}
		return $majorSize * $x + $z;
	}

	/**
	 * @param int $vertex
	 *
	 * @return int[]
	 */
	public function vertexToEdges(int $vertex) : array {
		switch($this->topology) {
			case self::TOPOLOGY_RECTANGLE:
			case self::TOPOLOGY_CYLINDER:
			case self::TOPOLOGY_TORUS:
			case self::TOPOLOGY_MOBIUS:
				$vertexLocation = $this->getVertexLocation($vertex);
				$x = (int) $vertexLocation->x;
				$z = (int) $vertexLocation->y;
				return [
					$this->getEdgeFromLocation(self::VERTICAL, $x, $z),
					$this->getEdgeFromLocation(self::VERTICAL, $x, $z + 1),
					$this->getEdgeFromLocation(self::HORIZONTAL, $x, $z),
					$this->getEdgeFromLocation(self::HORIZONTAL, $x + 1, $z)
				];
			break;
			case self::TOPOLOGY_OUTDOOR:
				return array_map(function(array $branch) {
					// TODO
				}, $this->vertexToBranches($vertex));
		}
	}

	/**
	 * @param int $vertex
	 *
	 * @return int[][]
	 */
	public function vertexToBranches(int $vertex) : array {
		$vertexLocation = $this->getVertexLocation($vertex);
		$x = (int)$vertexLocation->x;
		$z = (int)$vertexLocation->y;
		switch($this->topology) {
			case self::TOPOLOGY_RECTANGLE:
				break;
			case self::TOPOLOGY_OUTDOOR:
				$branches = [];
				if($x < $this->sizeX - 1) {
					$tmp = [];
					$tmp["vertex"] = $this->getVertexFromLocation($x + 1, $z);
					$tmp["edge"] = $this->getEdgeFromLocation(self::HORIZONTAL, $x + 1, $z);
					$branches[] = $tmp;
				}
				if($x > -1) {
					$tmp = [];
					$tmp["vertex"] = $this->getVertexFromLocation($x - 1, $z);
					$tmp["edge"] = $this->getEdgeFromLocation(self::HORIZONTAL, $x, $z);
					$branches[] = $tmp;
				}
				if($z < $this->sizeZ - 1) {
					$tmp = [];
					$tmp["vertex"] = $this->getVertexFromLocation($x, $z + 1);
					$tmp["edge"] = $this->getEdgeFromLocation(self::HORIZONTAL, $x, $z + 1);
					$branches[] = $tmp;
				}
				if($z > -1) {
					$tmp = [];
					$tmp["vertex"] = $this->getVertexFromLocation($x, $z - 1);
					$tmp["edge"] = $this->getEdgeFromLocation(self::HORIZONTAL, $x, $z);
					$branches[] = $tmp;
				}
				return $branches;
		}
	}

	/**
	 * @return int[][]
	 */
	public function getBorderBranches() : array {
		switch($this->topology) {
			case self::TOPOLOGY_RECTANGLE:
				$branches = [];
				if($this->sizeZ > 1) {
					for($x = 0; $x < $this->sizeX - 1; $x++) {
						$tmp = [];
						$tmp["vertex"] = $this->getVertexFromLocation($x, 0);
						$tmp["edge"] = $this->getEdgeFromLocation(self::VERTICAL, $x, 0);
						$branches[] = $tmp;
						$tmp["vertex"] = $this->getVertexFromLocation($x, $this->sizeZ - 2);
						$tmp["edge"] = $this->getEdgeFromLocation(self::VERTICAL, $x, $this->sizeZ - 1);
						$branches[] = $tmp;
					}
				}
				if($this->sizeX > 1) {
					for($z = 0; $z < $this->sizeZ - 1; $z++) {
						$tmp = [];
						$tmp["vertex"] = $this->getVertexFromLocation(0, $z);
						$tmp["edge"] = $this->getEdgeFromLocation(self::HORIZONTAL, 0, $z);
						$branches[] = $tmp;
						$tmp["vertex"] = $this->getVertexFromLocation($this->sizeX - 2, $z);
						$tmp["edge"] = $this->getEdgeFromLocation(self::HORIZONTAL, $this->sizeX - 1, $z);
						$branches[] = $tmp;
					}
				}
				return $branches;
			break;
			case self::TOPOLOGY_OUTDOOR:
			case self::TOPOLOGY_TORUS:
				return [];
			case self::TOPOLOGY_CYLINDER:
			case self::TOPOLOGY_MOBIUS:
				$branches = [];
				if($this->sizeZ > 1) {
					for($x = 0; $x < $this->sizeX; $x++) {
						$tmp = [];
						$tmp["vertex"] = $this->getVertexFromLocation($x, 0);
						$tmp["edge"] = $this->getEdgeFromLocation(self::VERTICAL, $x, 0);
						$branches[] = $tmp;
						$tmp["vertex"] = $this->getVertexFromLocation($x, $this->sizeZ - 2);
						$tmp["edge"] = $this->getEdgeFromLocation(self::VERTICAL, $x, $this->sizeZ - 1);
						$branches[] = $tmp;
					}
				}
				return $branches;
		}
	}

	public function getEdgeCountAdjustment(int $topology) : int {
		switch($topology) {
			case self::WALL: return -1;
			case self::OPEN: return 1;
			case self::LOOP: return 0;
			case self::TWIST: return 0;
			default:
				throw new \OutOfBoundsException();
		}
	}
	public function modForTopology(int $value, int $size, int $topology) : int {
		switch($topology) {
			case self::WALL: return $value;
			case self::OPEN: return $value;
			case self::LOOP:
				$tmp = $value % $size;
				if($tmp < 0) $tmp += $size;
				return $tmp;
			case self::TWIST:
				$size *= 2;
				$tmp = $value % $size;
				if($tmp < 0) $tmp += $size;
				return $tmp;
			default:
				throw new \OutOfBoundsException();
		}
	}

	public function getRandomBlockId() : int {
		return $this->blockPool[array_rand($this->blockPool)];
	}
}
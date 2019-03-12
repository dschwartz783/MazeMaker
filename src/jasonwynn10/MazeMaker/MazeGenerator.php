<?php
declare(strict_types=1);
namespace jasonwynn10\MazeMaker;

use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;

class MazeGenerator extends Generator {
	private $settings = [];
	private $vertexVisited = [];
	private $availableBranches = [];
	/** @var Maze $maze */
	private $maze;

	/**
	 * @param array $settings
	 *
	 * @throws InvalidGeneratorOptionsException
	 */
	public function __construct(array $settings = []) {
		parent::__construct($settings);
		$this->settings = $settings;
	}

	public function generateChunk(int $chunkX, int $chunkZ) : void {
		$chunk = $this->level->getChunk($chunkX, $chunkZ);
		for($x = 0; $x <= 15; $x++) {
			for($z = 0; $z <= 15; $z++) {
				$chunk->setBlockId($x, 0, $z, Block::BEDROCK);
				$chunk->setBlockId($x, 1, $z, Block::DIRT);
				$chunk->setBlockId($x, 2, $z, Block::DIRT);
				$chunk->setBlockId($x, 3, $z, Block::DIRT);
				$chunk->setBlockId($x, 4, $z, Block::GRASS);
			}
		}
		$this->maze = new Maze(Maze::TOPOLOGY_OUTDOOR, 15, 15, 2, []);
		$this->availableBranches = $this->maze->getBorderBranches();
		if(count($this->availableBranches) === 0) {
			$vertexCount = $this->maze->getVertexCount();
			if($vertexCount > 0) {
				$startingVertex = (lcg_value() * $vertexCount);
				$this->visitVertex($startingVertex);
			}
		}
		foreach($this->maze->vertexFilled as $vertex => $state) {
			$vector = $this->maze->getVertexLocation($vertex);
			for($y = 5; $y <= 11; $y++) {
				$chunk->setBlockId((int) $vector->x, $y, (int) $vector->y, $this->maze->getRandomBlockId());
			}
		}
	}

	public function visitVertex(int $vertex) {
		$this->vertexVisited[$vertex] = true;
		$this->maze->vertexFilled[$vertex] = Maze::FILLED;
		$branches = $this->maze->vertexToBranches($vertex);
		$this->availableBranches = array_merge($this->availableBranches, $branches);
	}

	public function populateChunk(int $chunkX, int $chunkZ) : void {
		// TODO: Implement populateChunk() method.
		// TODO: biome and
	}

	public function getSettings() : array {
		return $this->settings;
	}

	public function getName() : string {
		return "Maze";
	}

	public function getSpawn() : Vector3 {
		return new Vector3(0, 6, 0);
	}
}
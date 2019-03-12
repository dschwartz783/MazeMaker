<?php
declare(strict_types=1);
namespace jasonwynn10\MazeMaker;

use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;

class MazeV2Generator extends Generator {
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
		if(isset($settings["preset"])) {
			$settings = json_decode($settings["preset"], true);
			if($settings === false or is_null($settings)) {
				$settings = [];
			}
		}else{
			$settings = [];
		}
		// TODO: block type settings for custom walls
		$this->settings = $settings;
	}

	public function generateChunk(int $chunkX, int $chunkZ) : void {
		$chunk = $this->level->getChunk($chunkX, $chunkZ);

		// set bottom 5 blocks to be constant
		for($x = 0; $x <= 15; $x++) {
			for($z = 0; $z <= 15; $z++) {
				$chunk->setBlockId($x, 0, $z, Block::BEDROCK);
				$chunk->setBlockId($x, 1, $z, Block::DIRT);
				$chunk->setBlockId($x, 2, $z, Block::DIRT);
				$chunk->setBlockId($x, 3, $z, Block::DIRT);
				$chunk->setBlockId($x, 4, $z, Block::GRASS);
			}
		}

		// randomly  choose start point in 16x16 chunk area
		$randomStart = mt_rand(0, 15*15);
		$this->availableBranches = $this->getAvailableBranchesOfVertex($randomStart);
		$this->vertexVisited[] = $randomStart;

		while(!empty($this->availableBranches)) { // continue generating new branches until no more space
			$key = array_rand($this->availableBranches);
			$branchId = $this->availableBranches[$key];
			unset($this->availableBranches[$key]);
			$this->vertexVisited[] = $branchId;
			$this->availableBranches = array_merge($this->availableBranches, $this->getAvailableBranchesOfVertex($branchId));
		}

		//no more available branches
		$i = 0;
		for($x = 0; $x <= 15; $x++) {
			for($z = 0; $z <= 15; $z++) {
				if(in_array($i, $this->vertexVisited)) {
					for($y = 5; $y <= 11; $y++) {
						$chunk->setBlockId($x, $y, $z, $this->maze->getRandomBlockId());
					}
				}
				$i++;
			}
		}

		$chunk->setChanged();
		$chunk->setGenerated();
	}

	/**
	 * @param int $point 0-256 range
	 *
	 * @return int[]
	 */
	protected function getAvailableBranchesOfVertex(int $point) : array {
		// TODO: return empty array if no more branches available
		return [];
	}

	public function populateChunk(int $chunkX, int $chunkZ) : void {
		// TODO: Implement populateChunk() method.
		$chunk = $this->level->getChunk($chunkX, $chunkZ);
		$chunk->setPopulated();
	}

	public function getSettings() : array {
		return $this->settings;
	}

	public function getName() : string {
		return "MazeV2";
	}

	public function getSpawn() : Vector3 {
		return new Vector3(0, 12, 0); // TODO: set next to maze wall in air space or generated room
	}
}
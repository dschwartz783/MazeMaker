<?php
declare(strict_types=1);
namespace jasonwynn10\MazeMaker;

use pocketmine\level\generator\GeneratorManager;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {
	public function onLoad() {
		GeneratorManager::addGenerator(MazeGenerator::class, "maze", true);
		GeneratorManager::addGenerator(MazeV2Generator::class, "mazev2", true);
	}
}
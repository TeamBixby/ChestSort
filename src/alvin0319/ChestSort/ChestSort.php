<?php

declare(strict_types=1);

namespace alvin0319\ChestSort;

use pocketmine\block\Chest as BlockChest;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Chest as TileChest;
use function array_filter;
use function array_values;
use function count;

class ChestSort extends PluginBase implements Listener{

	protected array $sortModes = [];

	public function onEnable() : void{
		$this->sortModes = []; // prevent reload
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @param PlayerInteractEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}
		$player = $event->getPlayer();
		if(!isset($this->sortModes[$player->getName()])){
			return;
		}
		$block = $event->getBlock();
		if(!$block instanceof BlockChest){
			return;
		}
		$chestTile = $block->getLevel()->getTile($block);
		if(!$chestTile instanceof TileChest){
			return;
		}
		$inv = $chestTile->getInventory();
		if($inv === null){
			return;
		}
		$inv->setContents($this->sortInventory(array_values($inv->getContents(false))));
		$event->setCancelled();
		$player->sendMessage("Chest was sorted.");
	}

	/**
	 * @param Item[] $contents
	 *
	 * @return Item[]
	 */
	protected function sortInventory(array $contents) : array{
		for($i = 0; $i < count($contents); $i++){
			for($j = 0; $j < $i; $j++){
				if($contents[$i]->equals($contents[$j], true, true) && !$contents[$i]->isNull()){
					$maxStackSize = $contents[$j]->getMaxStackSize();
					$total = $contents[$j]->getCount() + $contents[$i]->getCount();
					if($total > $maxStackSize){
						$contents[$i]->setCount($contents[$i]->getCount() - ($maxStackSize - $contents[$j]->getCount()));
						$contents[$j]->setCount($maxStackSize);
					}else{
						$contents[$j]->setCount($contents[$i]->getCount() + $contents[$j]->getCount());
						$contents[$i]->setCount(0);
					}
				}
			}
		}
		return array_values(
			array_filter($contents, function(Item $item) : bool{
				return !$item->isNull();
			})
		);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage("You can't execute this command on console.");
			return false;
		}
		$bool = isset($this->sortModes[$sender->getName()]);
		if($bool){
			unset($this->sortModes[$sender->getName()]);
		}else{
			$this->sortModes[$sender->getName()] = true;
		}
		$sender->sendMessage("You are now " . ($bool ? "not " : "") . "sort mode!");
		return true;
	}
}
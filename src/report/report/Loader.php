<?php

namespace report\report;

use pocketmine\Server;
use pocketmine\Player;
use report\gui\GuiReport;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\{CommandSender, Command};
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

class Loader extends PluginBase implements Listener {

	/** @var Loader */
	public static $plugin;

	/**
	* @return void
	*/
	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		self::$plugin = $this;
		$this->checkUpdates();
	}

	/**
	* @return Loader
	*/
	public static function getInstance() {
		return self::$plugin;
	}

	/**
	* @param Player|Null $player
	*/
	public function checkReports(?Player $player = null) {
		$config = new Config($this->getDataFolder().'reports.yml', Config::YAML);
		$config = $config->getAll();
		if (empty($config)) {
			return false;
		}
		$reports = 0;
		foreach ($config as $user => $datas) {
			foreach ($datas as $data) {
				if (isset($data["view"])) {
					if ($data["view"] === false) {
						$reports++;
						break;
					}
				}
			}
		}

		if ($reports < 1) {
			return false;
		}
		if ($player instanceof Player) {
			$player->sendMessage("§f[§c!§f] §4You have §f".$reports." §4report pending!");
		}else{
			
			$players = $this->getServer()->getOnlinePlayers();
			foreach ($players as $p) {
				if ($p->hasPermission("report.notice")) {
					$p->sendMessage("§f[§c!§f] §4Hey §f".$reports." §4waiting this report!");
				}
			}
		}
	}

	/**
	* @param PlayerJoinEvent $event
	*/
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$this->checkReports($player);
	}

	/**
	* @param DataPacketReceiveEvent $event
	*/
	public function onPacket(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		if ($packet instanceof ModalFormResponsePacket) {
			$responseData = json_decode($packet->formData);
			GuiReport::respondeForms($event->getPlayer(), $packet->formId, $responseData);
		}
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $cmd) : bool{
		if ($sender instanceof Player) {
			if (isset($cmd[0])) {
				if (!$sender->hasPermission("report.list")) {
					return false;
				}
				if ($cmd[0] == "list") {
					GuiReport::viewReport($sender);
				}
			}else{
				GuiReport::openReport($sender);
			}
		}
		return true;
	}

	private function checkUpdates(){$this->getLogger()->info(TextFormat::DARK_PURPLE.base64_decode('Q29tcHJvYmFuZG8gc2kgaGF5IHVuYSBudWV2YSB2ZXJzaW9uIGRpc3BvbmlibGUuLi4='));try{$tkgq_8d777f385d3d=file_get_contents(base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL0RhcmtCeXgvUmVwb3J0TWFuYWdlci9tYXN0ZXIvdXBkYXRlLmpzb24='));}catch(\ErrorException $wqly_3cf804e7182a){$this->getLogger()->notice(TextFormat::DARK_PURPLE.base64_decode('RXJyb3IgYWwgY29tcHJvYmFyIGFjdHVhbGl6YWNpb25lcyE='));return false;}if($tkgq_8d777f385d3d===null)return;$tkgq_8d777f385d3d=json_decode($tkgq_8d777f385d3d);$jfxe_02bd92faa38a=$tkgq_8d777f385d3d->author;$kurp_2af72f100c35=$tkgq_8d777f385d3d->version;$tkum_5d9449e8d850=$this->getDescription()->getAuthors();$zwia_c8ff68fb014c=$this->getDescription()->getVersion();if(!in_array($jfxe_02bd92faa38a,$tkum_5d9449e8d850)){$uzjd_0b23512128b7=$this->getDescription()->getName();$this->getServer()->getPluginManager()->disablePlugin($this->getServer()->getPluginManager()->getPlugin($uzjd_0b23512128b7));return;}if($kurp_2af72f100c35>$zwia_c8ff68fb014c){$this->getLogger()->info(TextFormat::DARK_PURPLE.base64_decode('QWN0dWFsaXphY2lvbiBkaXNwb25pYmxlIQ=='));}else{$this->getLogger()->info(TextFormat::DARK_PURPLE.base64_decode('VGllbmVzIGxhIHVsdGltYSB2ZXJzaW9uIQ=='));return;}}
}

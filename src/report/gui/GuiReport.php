<?php

namespace report\gui;

use pocketmine\Player;
use pocketmine\Server;
use report\report\Loader;
use pocketmine\level\Level;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TE;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

class GuiReport{

	/** @var int */
	public const REPORT_CREATE_ID = 151515;
	public const REPORT_VIEW_ID = 161616;
	public const REPORT_SUCCES_ID = 111111;
	public const REPORT_FAILED_ID = 121212;
	public const REPORT_CONFIRM_ID = 131313;

	/** @var string */
	private static $reportlist = [];
	private static $reportview = [];
	private static $bannedReport = [];

	/** @var string */
	private static $reports = ['Griefing', 'Hacking', 'Using Bug', 'Staff Abuse', 'Advertise', 'Underhand'];

	/**
	* @param Player $player
	*/
	public static function openReport(Player $player) {
		$packet = new ModalFormRequestPacket();
		$packet->formId = self::REPORT_CREATE_ID;
		$players = [];
		foreach (Server::getInstance()->getOnlinePlayers() as $p) {
			if ($p->getName() == $player->getName()) continue;
			$players[] = $p->getName();
		}
		$formData = [
			'type' => 'custom_form',
			'title' => "§l§cREPORT",
			'content' => [],
		];
		$formData["content"][] = ["type" => "dropdown", "text" => TE::YELLOW."Players\n", "options" => $players];
		$formData["content"][] = ["type" => "dropdown", "text" => TE::YELLOW."Reason\n", "options" => self::$reports];
		$formData["content"][] = ["type" => "input", "text" => TE::YELLOW."Explain in detail\n"];
		$packet->formData = json_encode($formData);
		$player->sendDataPacket($packet, true);
		self::$reportlist[$player->getName()] = $players;
	}


	/**
	* @param Player     $player
	* @param null|array $reponde
	* @return void
	*/
	private static function sendReport(Player $player, ?array $responde) : void {
		if ($responde === null) {
			return;
		}

		$players = self::$reportlist[$player->getName()];
		$repuser = false;
		$reason = self::$reports[$responde[1]];
		$comment = $responde[2] ?? "...";
		if (isset($responde[0])) {
			$pid = $responde[0];
			if (isset($players[$pid])) {
				$repuser = $players[$pid];
			}
		}
		if ($repuser === false) {
			self::failedReport($player, $reason, $comment);
			return;
		}
		self::confirmReport($player, $repuser, $reason, $comment);
		unset(self::$reportlist[$player->getName()]);
	}

	/**
	* @param Player $player
	* @param string $reportuser
	* @param string $reason
	* @param string $comment
	*/
	private static function confirmReport(Player $player, string $reportuser, string $reason, string $comment) {
		$packet = new ModalFormRequestPacket();
		$packet->formId = self::REPORT_SUCCES_ID;
		$formData = [
			'type' => 'custom_form',
			'title' => "§l§cREPORT",
			'content' => [],
		];
		$hora = date('h:i:s a');
		$fecha = date('d-m-Y');
		$line = "\n";
		$reportdata = "§aCulprit: §c".$reportuser.$line;
		$reportdata .= TE::YELLOW."§aReason: §c".$reason.$line;
		$reportdata .= TE::YELLOW."§aDetail Reason: §c".$comment.$line;
		$reportdata .= TE::YELLOW."§aReport by: §c".$player->getName().$line.$line;
		$reportdata .= TE::YELLOW."§aReport Submission: ".$line;
		$reportdata .= TE::YELLOW."§f- §aDate: §c".$fecha.$line;
		$reportdata .= TE::YELLOW."§f- §aTime: §c".$hora.$line;
		$formData["content"][] = ["type" => "label", "text" => TE::YELLOW."§aReport status: §cSuccses!".$line.$reportdata];
		$packet->formData = json_encode($formData);
		$player->sendDataPacket($packet, true);

		$config = new Config(Loader::getInstance()->getDataFolder().'reports.yml', Config::YAML);
		$data = $config->get($reportuser, []);
		$reportID = "Report #".(count($data) + 1);
		$data[$reportID] = ["reported" => $player->getName(), "reason" => $reason, "comment" => $comment, "fecha" => $fecha, "hora" => $hora, "view" => false];
		$config->set($reportuser, $data);
		$config->save();
		$player->addTitle(TE::GREEN.TE::BOLD."SUCCESS", TE::YELLOW.TE::BOLD."Thank you for report!");
		Loader::getInstance()->checkReports();
	}

	/**
	* @param Player $player
	* @param string $reason
	* @param string $comment
	*/
	private static function failedReport(Player $player, string $reason, string $comment) {
		$packet = new ModalFormRequestPacket();
		$packet->formId = self::REPORT_FAILED_ID;
		$formData = [
			'type' => 'custom_form',
			'title' => "§l§cREPORT",
			'content' => [],
		];
		$tiempo = date('h:i:s a');
		$fecha = date('d-m-Y');
		$line = "\n";
		$reportdata = TE::YELLOW."§aReport status: §Ignore".$line;
		$reportdata .= TE::YELLOW."§aCulprit: §cUnknown".$line;
		$reportdata .= TE::YELLOW."§aReason: §c".$reason.$line;
		$reportdata .= TE::YELLOW."§aDetail Reason: §c".$comment.$line;
		$reportdata .= TE::YELLOW."§aReport by: §c".$player->getName().$line;
		$reportdata .= TE::YELLOW."§aReport Submission: ".$line;
		$reportdata .= TE::YELLOW."§f- §aDate: §c".$fecha.$line;
		$reportdata .= TE::YELLOW."§f- §aTime: §c".$tiempo.$line;
		$formData["content"][] = ["type" => "label", "text" => $reportdata];
		$packet->formData = json_encode($formData);
		$player->sendDataPacket($packet, true);
	}

	/**
	* @param Player $player
	*/
	public static function viewReport(Player $player) {
		$config = new Config(Loader::getInstance()->getDataFolder().'reports.yml', Config::YAML);
		$users = array_keys($config->getAll());
		$data = [
			'type' => 'form',
			'title' => "§c§lREPORT",
			'content' => "",
			"buttons" => []
		];
		foreach ($users as $user) {
			$data["buttons"][] = ["text" => "§a".$user.self::getNewReport($user)."\n§7Click to view!"];
		}
		$packet = new ModalFormRequestPacket();
		$packet->formId = self::REPORT_VIEW_ID;
		$packet->formData = json_encode($data);
		$player->sendDataPacket($packet, true);
		self::$reportview[$player->getName()] = $users;
	}

	/**
	* @param Player $player
	* @param int  $responde
	*/
	private static function respondeReport(Player $player, $responde) {
		if (!isset(self::$reportview[$player->getName()])) {
			return;
		}
		$players = self::$reportview[$player->getName()];
		if (!isset($players[$responde])) {
			return;
		}
		$reportuser = $players[$responde];
		$config = new Config(Loader::getInstance()->getDataFolder().'reports.yml', Config::YAML);
		$reports = $config->get($reportuser, []);

		$packet = new ModalFormRequestPacket();
		$packet->formId = self::REPORT_CONFIRM_ID;
		$formData = [
			'type' => 'custom_form',
			'title' => "§l§cREPORT",
			'content' => [],
		];
		$formData["content"][] = ["type" => "toggle", "default" => false, "text" => "§aDelete this report?"];
		$formData["content"][] = ["type" => "toggle", "default" => false, "text" => "§cBanned offender: §a".$reportuser."?"];
		$line = "\n";
		foreach ($reports as $reportid => $data) {
			$reportinfo = "§aCulprit: §c".$reportuser.$line;
			$reportinfo .= "§aReason: §c".$data["reason"].$line;
			$reportinfo .= "§aDetail reason: §c".$data["comment"].$line;
			$reportinfo .= "§aReport by: §c".$data["reported"].$line.$line;
			$reportinfo .= "§aReport Submission: ".$line;
			$reportinfo .= "§f- §aDate: §c".$data["fecha"].$line;
			$reportinfo .= "§f- §aTime: §c".$data["hora"].$line;
			$formData["content"][] = ["type" => "label", "text" => TE::GRAY.$reportid.$line.$reportinfo];
			$config->setNested("$reportuser.$reportid.view", true);
			$config->save();
		}
		$packet->formData = json_encode($formData);
		$player->sendDataPacket($packet, true);
		unset(self::$reportview[$player->getName()]);
		self::$bannedReport[$player->getName()] = $reportuser;
	}

		/**
	* @param Player $player
	* @param array  $responde
	*/
	private static function fineshedReport(Player $player, array $responde) {
		if (!isset(self::$bannedReport[$player->getName()])) {
			return;
		}
		$userban = self::$bannedReport[$player->getName()];
		$remove = $responde[0];
		$banned = $responde[1];
		if ($remove and $player->hasPermission("report.remove")) {
			$config = new Config(Loader::getInstance()->getDataFolder().'reports.yml', Config::YAML);
			$config->remove($userban);
			$config->save();
			$player->sendMessage("§cReport: §a".$userban." §chas deleted!");
			$player->addTitle("§a§lSUCCESS", "§eReport deleted!");
		}
		if ($banned and $player->hasPermission("report.banned")) {
			$player->addTitle("§l§cBANNED", $userban." §aHas banned!");
			$banplayer = Server::getInstance()->getPlayer($userban);
			if ($banplayer instanceof Player) {
				$banplayer->setBanned(true);
			}else{
				Server::getInstance()->getNameBans()->addBan($userban, null, null, null);
			}
		}
	}

	/**
	* @param string $username
	* @return string $message
	*/
	private static function getNewReport(string $username) : string{
		$config = new Config(Loader::getInstance()->getDataFolder().'reports.yml', Config::YAML);
		$datas = $config->get($username);
		if (empty($datas)) {
			return "";
		}
		foreach ($datas as $data) {
			if (isset($data["view"])) {
				if ($data["view"] == false) {
					return "   §l§cNew'";
				}
			}
		}
		return "";
	}

	/**
	* @param Player $player
	* @param int    $formId
	* @param array  $responde
	*/
	public static function respondeForms(Player $player, int $formId, $responde) {
		if ($responde === null) {
			return false;
		}
		if ($formId == self::REPORT_CREATE_ID) {
			return self::sendReport($player, $responde);
		}
		if ($formId == self::REPORT_VIEW_ID) {
			return self::respondeReport($player, $responde);
		}
		if ($formId == self::REPORT_CONFIRM_ID) {
			return self::fineshedReport($player, $responde);
		}
	}
}

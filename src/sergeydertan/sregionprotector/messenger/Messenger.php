<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\messenger;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\util\Utils;

class Messenger
{
    const MESSAGE_TYPE_MESSAGE = TextPacket::TYPE_CHAT;
    const MESSAGE_TYPE_TIP = TextPacket::TYPE_TIP;
    const MESSAGE_TYPE_POPUP = TextPacket::TYPE_POPUP;

    const DEFAULT_LANGUAGE = "eng";
    /**
     * @var Messenger
     */
    private static $INSTANCE;
    /**
     * current language
     * @var string
     */
    private $language;
    /**
     * code -> message
     * @var string[]
     */
    private $messages;

    public function __construct()
    {
        $lang = null;
        if (file_exists($cnf = SRegionProtectorMain::getInstance()->getMainFolder() . "config.yml")) {
            $n = (new Config($cnf, Config::YAML))->get("language");
            if ($n !== null && strcasecmp($n, "default") !== 0) {
                $lang = $n;
            }
        }
        if ($lang === null) {
            $lang = Server::getInstance()->getLanguage()->getLang();
        }
        if (!Utils::resourceExists("lang/$lang.yml")) $lang = self::DEFAULT_LANGUAGE;

        Utils::copyResource("lang/$lang.yml");

        $this->language = $lang;

        $this->messages = (new Config(SRegionProtectorMain::getInstance()->getLangFolder() . "$lang.yml"))->getAll();
        self::$INSTANCE = $this;
    }

    public static function getInstance(): ?Messenger
    {
        return self::$INSTANCE;
    }

    public static function messageTypeFromString(string $name): int
    {
        switch (strtolower($name)) {
            case "message":
            case "msg":
            default:
                return self::MESSAGE_TYPE_MESSAGE;
                break;
            case "tip":
                return self::MESSAGE_TYPE_TIP;
                break;
            case "pop":
            case "popup":
                return self::MESSAGE_TYPE_POPUP;
                break;
        }
    }

    public function sendMessage(CommandSender $target, string $msg, array $search = [], array $replace = [], int $messageType = self::MESSAGE_TYPE_MESSAGE): void
    {
        $msg = $this->getMessage($msg, $search, $replace);
        switch ($messageType) {
            case self::MESSAGE_TYPE_MESSAGE:
            default:
                $target->sendMessage($msg);
                break;
            case self::MESSAGE_TYPE_TIP:
                if (!$target instanceof Player) {
                    $this->sendMessage($target, $msg, $search, $replace);
                    return;
                }
                $target->sendTip($msg);
                break;
            case self::MESSAGE_TYPE_POPUP:
                if (!$target instanceof Player) {
                    $this->sendMessage($target, $msg, $search, $replace);
                    return;
                }
                $target->sendPopup($msg);
                break;
        }
    }

    public function getMessage(string $msg, array $search = [], array $replace = []): string
    {
        foreach ($search as $key => $value) {
            if ($value{0} !== '{') $value = '{' . $value;
            if ($value{strlen($value) - 1} !== '}') $value .= '}';
            $search[$key] = $value;
        }
        return isset($this->messages[$msg]) ?
            str_replace($search, $replace, $this->messages[$msg])
            : $msg;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}

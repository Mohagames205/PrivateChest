<?php

namespace mohagames\PrivateChest;

use mohagames\PlotArea\utils\Member;
use mohagames\PlotArea\utils\PermissionManager;
use mohagames\PlotArea\utils\Plot;
use mohagames\PrivateChest\event\EventListener;
use mohagames\PrivateChest\handler\SessionHandler;
use mohagames\PrivateChest\utils\PrivateChest;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use SQLite3;

class Main extends PluginBase

{

    private static $db;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        self::$db = new SQLite3($this->getDataFolder() . "PrivateChest.db");
        $stmt = self::$db->prepare("CREATE TABLE IF NOT EXISTS chests(chest_id INTEGER PRIMARY KEY AUTOINCREMENT, chest_members TEXT, chest_location TEXT, chest_world TEXT, plot_id INTEGER)");
        $stmt->execute();
        $stmt->close();

    }


    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch($command->getName())
        {
            case "chest":
                if(!isset($args[0]))
                {
                    $sender->sendMessage("§cGelieve een argument op te geven. /chest <arg>\n§4/chest lock\n/chest unlock\n/chest members\n/chest removemember\n/chest addmember\n");
                    return true;
                }
                switch($args[0])
                {
                    case "lock":
                        $plot = Plot::get($sender);

                        if(is_null($plot))
                        {
                            $sender->sendMessage("§cU staat niet op een plot!");
                            return true;
                        }
                        if(!$plot->isOwner($sender->getName()) && !$sender->hasPermission("chest.admin"))
                        {
                            $sender->sendMessage("§cU hebt geen permissions.");
                            return true;
                        }


                        SessionHandler::$createSession[$sender->getName()] = true;
                        $sender->sendMessage("§aDruk nu op de kist die je wilt vergrendelen.");

                        break;


                    case "unlock":
                        $plot = Plot::get($sender);
                        if(is_null($plot))
                        {
                            $sender->sendMessage("§cU staat niet op een plot!");
                            return true;
                        }
                        if(!$plot->isOwner($sender->getName()) && !$sender->hasPermission("chest.admin"))
                        {
                            $sender->sendMessage("§cU hebt geen permissions.");
                            return true;
                        }

                        SessionHandler::$deleteSession[$sender->getName()] = true;
                        $sender->sendMessage("§aDruk nu op de kist die je wilt ontgrendelen.");

                        break;


                    case "addmember":

                        $plot = Plot::get($sender);

                        if(is_null($plot))
                        {
                            $sender->sendMessage("§cU staat niet op een plot!");
                            return true;
                        }

                        if(!$plot->isOwner($sender->getName()) && !$sender->hasPermission("chest.admin"))
                        {
                            $sender->sendMessage("§cU hebt geen permissions.");
                            return true;
                        }

                        if(!isset($args[1]))
                        {
                            $sender->sendMessage("§cGelieve een member op te geven.");
                            return true;
                        }

                        if(!Member::exists($args[1]))
                        {
                            $sender->sendMessage("§cDe opgegeven speler bestaat niet!");
                            return true;
                        }

                        if(!$plot->isMember($args[1]))
                        {
                            $sender->sendMessage("§cDe speler is geen lid van het plot!");
                            return true;
                        }


                        $sender->sendMessage("§aDruk nu op de kist waaraan je de member wilt toevoegen.");
                        SessionHandler::$addMemberSession[$sender->getName()] = $args[1];

                        break;



                    case "removemember":

                        $plot = Plot::get($sender);
                        if(is_null($plot))
                        {
                            $sender->sendMessage("§cU staat niet op een plot!");
                            return true;
                        }

                        if(!$plot->isOwner($sender->getName()) && !$sender->hasPermission("chest.admin"))
                        {
                            $sender->sendMessage("§cU hebt geen permissions.");
                            return true;
                        }

                        if(!isset($args[1]))
                        {
                            $sender->sendMessage("§cGelieve een member op te geven.");
                            return true;
                        }

                        if(!Member::exists($args[1]))
                        {
                            $sender->sendMessage("§cDe opgegeven speler bestaat niet!");
                            return true;
                        }

                        if(!$plot->isMember($args[1]))
                        {
                            $sender->sendMessage("§cDe speler is geen lid van het plot!");
                            return true;
                        }


                        $sender->sendMessage("§aDruk nu op de kist waar je de member wilt deleten");
                        SessionHandler::$removeMemberSession[$sender->getName()] = $args[1];

                        break;

                    case "members":

                        $plot = Plot::get($sender);
                        if(is_null($plot))
                        {
                            $sender->sendMessage("§cU staat niet op een plot!");
                            return true;
                        }

                        if(!$plot->isOwner($sender->getName()) && !$sender->hasPermission("chest.admin"))
                        {
                            $sender->sendMessage("§cU hebt geen permissions");
                            return true;
                        }

                        SessionHandler::$memberInfoSession[$sender->getName()] = true;
                        $sender->sendMessage("§aDruk nu op de kist waar je info over wilt.");
                        break;


                    default:
                        $sender->sendMessage("§cDeze command bestaat niet!\n§4/chest lock\n/chest unlock\n/chest members\n/chest removemember\n/chest addmember");
                        return true;

                }
            default:
                return false;
        }
    }

    public static function getDb() : SQLite3
    {
        return self::$db;
    }


}
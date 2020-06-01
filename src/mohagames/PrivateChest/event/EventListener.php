<?php

namespace mohagames\PrivateChest\event;

use mohagames\PlotArea\events\PlotAddMemberEvent;
use mohagames\PlotArea\events\PlotDeleteEvent;
use mohagames\PlotArea\events\PlotRemoveMemberEvent;
use mohagames\PlotArea\utils\Member;
use mohagames\PlotArea\utils\Plot;
use mohagames\PrivateChest\handler\SessionHandler;
use mohagames\PrivateChest\utils\PrivateChest;
use pocketmine\block\Chest;
use \pocketmine\tile\Chest as ChestTile;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;

class EventListener implements Listener
{

    /**
     * TODO: Paired chests fixen!
     *
     * @param PlayerInteractEvent $e
     */
    public function onLockedChestInteract(PlayerInteractEvent $e)
    {

        if(!$e->getBlock() instanceof Chest) return;




        $player = $e->getPlayer();

        if(isset(SessionHandler::$removeMemberSession[$player->getName()]) || isset(SessionHandler::$addMemberSession[$player->getName()]) ||
            isset(SessionHandler::$deleteSession[$player->getName()]) || isset(SessionHandler::$createSession[$player->getName()]) ||
            isset(SessionHandler::$memberInfoSession[$player->getName()])) return;


        $chest = PrivateChest::get($e->getBlock());

        if(is_null($chest)) return;


        $plot = $chest->getPlot();

        if(!$plot->isOwner($player->getName()) && !$chest->isMember($player->getName()) && !$player->hasPermission("chest.admin")){
            $e->setCancelled();
            $player->sendMessage("§cDeze kist is vergrendeld.");
        }

    }

    public function onMemberRemove(PlotRemoveMemberEvent $e)
    {
        $chests = PrivateChest::getPlotChests($e->getPlot());
        if(is_null($chests)) return;

        foreach($chests as $chest)
        {
            if ($chest->isMember($e->getMember())){
                $chest->removeMember($e->getMember());
            }
        }
    }

    public function onMemberInfo(PlayerInteractEvent $e)
    {
        $player = $e->getPlayer();
        if(!isset(SessionHandler::$memberInfoSession[$player->getName()])) return;

        $e->setCancelled();

        unset(SessionHandler::$memberInfoSession[$player->getName()]);
        $chest = PrivateChest::get($e->getBlock());

        if(is_null($chest)) return;



        $members = $chest->getMembers();
        $member_string = join(", ", $members);
        $member_string = !empty($members) ? $member_string : "§cGeen leden";
        $player->sendMessage("§aChest members: §2$member_string");


    }


    public function onPlotDelete(PlotDeleteEvent $e)
    {
        $chests = PrivateChest::getPlotChests($e->getPlot());

        if(is_null($chests)) return;

        foreach($chests as $chest)
        {
            $chest->delete();
        }


    }

    public function chestCreationInteract(PlayerInteractEvent $e)
    {

        if(!$e->getBlock() instanceof Chest) return;

        $player = $e->getPlayer();
        if(!isset(SessionHandler::$createSession[$player->getName()])) return;

        unset(SessionHandler::$createSession[$player->getName()]);
        $e->setCancelled();

        if(is_null(Plot::get($e->getBlock())))
        {
            $player->sendMessage("§cDeze kist staat niet op een plot.");
            return;
        }

        if(!is_null(PrivateChest::get($e->getBlock())))
        {
            $player->sendMessage("§cDeze chest is al vergrendeld.");
            return;
        }


        PrivateChest::save($e->getBlock());
        $player->sendMessage("§aDe kist is succesvol vergrendeld!");

    }

    public function addMemberInteract(PlayerInteractEvent $e)
    {
        if(!$e->getBlock() instanceof Chest) return;

        $player = $e->getPlayer();

        if(!isset(SessionHandler::$addMemberSession[$player->getName()])) return;

        $chest = PrivateChest::get($e->getBlock());
        $member = SessionHandler::$addMemberSession[$player->getName()];
        unset(SessionHandler::$addMemberSession[$player->getName()]);
        $e->setCancelled();

        $plot = Plot::get($e->getBlock());
        if(is_null($plot))
        {
            $player->sendMessage("§cDeze kist staat niet op een plot.");
            return;
        }

        if(!$plot->isMember($member))
        {
            $player->sendMessage("§cDeze speler is geen member van het plot.");
            return;
        }

        if(is_null($chest))
        {
            $player->sendMessage("§cDit is geen privé chest!");
            return;
        }

        if($chest->isMember($member))
        {
            $player->sendMessage("§cDeze speler is al een member van de chest.");
            return;
        }


        $chest->addMember($member);
        $player->sendMessage("§aHet lid is succesvol toegevoegd!");

    }

    public function removeMemberInteract(PlayerInteractEvent $e)
    {

        if(!$e->getBlock() instanceof Chest) return;

        $player = $e->getPlayer();

        if(!isset(SessionHandler::$removeMemberSession[$player->getName()])) return;

        $chest = PrivateChest::get($e->getBlock());
        $member = SessionHandler::$removeMemberSession[$player->getName()];
        unset(SessionHandler::$removeMemberSession[$player->getName()]);
        $e->setCancelled();

        $plot = Plot::get($e->getBlock());

        if(is_null($plot))
        {
            $player->sendMessage("§cU staat niet op een plot.");
            return;
        }

        if(is_null($chest))
        {
            $player->sendMessage("§cDit is geen privé chest!");
            return;
        }

        if(!Member::exists($member))
        {
            $player->sendMessage("§cDe opgegeven speler bestaat niet!");
            return;
        }

        if(!$chest->isMember($member))
        {
            $player->sendMessage("§cDeze persoon is geen member van de chest.");
            return;
        }

        $chest->removeMember($member);
        $player->sendMessage("§aHet lid is succesvol verwijderd!");

    }

    public function onChestBreak(BlockBreakEvent $e)
    {
        if(!$e->getBlock() instanceof Chest) return;

        if(!$e->getPlayer()->hasPermission("chest.admin"))
        {
            $e->getPlayer()->sendMessage("§cHeyla! U heeft geen permissions om dit te doen.");
            return;
        }

        $chest = PrivateChest::get($e->getBlock());
        if(!is_null($chest))
        {
            $chest->delete();
            $e->getPlayer()->sendMessage("§aDe chest is succesvol ontgrendeld!");
        }



    }

    public function chestDeletionInteract(PlayerInteractEvent $e)
    {
        if(!$e->getBlock() instanceof Chest) return;

        if(!$e->getPlayer()->hasPermission("chest.admin"))
        {
            $e->getPlayer()->sendMessage("§cHeyla! U heeft geen permissions om dit te doen.");
            return;
        }
        $player = $e->getPlayer();

        if(!isset(Sessionhandler::$deleteSession[$player->getName()])) return;

        unset(SessionHandler::$deleteSession[$player->getName()]);

        $e->setCancelled();

        $chest = PrivateChest::get($e->getBlock());
        if(is_null($chest))
        {
            $player->sendMessage("§cDeze kist is niet vergrendeld!");
            return;
        }

        $chest->delete();
        $player->sendMessage("§cDe kist is succesvol ontgrendeld!");
    }




}
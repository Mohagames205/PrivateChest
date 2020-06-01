<?php

namespace mohagames\PrivateChest\utils;

use mohagames\PlotArea\utils\Plot;
use mohagames\PrivateChest\Main;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\tile\Chest as ChestTile;

class PrivateChest extends Position
{


    public $plot;


    public function __construct(Position $pos, Plot $plot)
    {
        parent::__construct($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $pos->getLevel());
        $this->plot = $plot;

    }

    public function getPlot() : Plot
    {
        return $this->plot;
    }


    public static function get(Position $pos, $checkPaired = true)
    {

        $chest_world = $pos->getLevel()->getFolderName();
        $chest_location = json_encode([$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()]);

        $stmt = Main::getDb()->prepare("SELECT * FROM chests WHERE chest_world = :chest_world and chest_location = :chest_location");
        $stmt->bindParam("chest_world", $chest_world);
        $stmt->bindParam("chest_location", $chest_location);
        $res = $stmt->execute()->fetchArray();
        $stmt->close();

        if(!$res)
        {
            if(!$checkPaired) return null;
            $tile = $pos->getLevel()->getTile($pos);
            if(!$tile instanceof ChestTile) return null;

            $pair = $tile->getPair();

            if(is_null($pair)) return null;

            return self::get($pair, false);
        }

        $plot = Plot::getPlotById($res["plot_id"]);
        if(is_null($plot)) return null;

        $location = json_decode($res["chest_location"], true);
        $level = Server::getInstance()->getLevelByName($res["chest_world"]);
        return new PrivateChest(new Position($location[0], $location[1], $location[2], $level), $plot);

    }

    public static function checkPair(Chest $block)
    {
        $possibleChest = PrivateChest::get($block);
        $tile = $block->getLevel()->getTile($block);

        if($tile instanceof ChestTile)
        {
            if(!is_null($tile->getPair())) $pairedchest = PrivateChest::get($tile->getPair()->asPosition());
        }

        $chest = isset($pairedchest) && !is_null($pairedchest) ? $pairedchest : $possibleChest;

        return $chest;
    }



    public static function save(Position $pos)
    {
        $location =  json_encode([$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()]);
        $worldname = $pos->getLevel()->getFolderName();
        $emptyarray = json_encode([]);
        $plot = Plot::get($pos);
        if(is_null($plot)) return;

        $plot_id = $plot->getId();

        $stmt  = Main::getDb()->prepare("INSERT INTO chests (chest_members, chest_location, chest_world, plot_id) values(:chest_members, :chest_location, :chest_world, :plot_id)");
        $stmt->bindParam("chest_members", $emptyarray);
        $stmt->bindParam("chest_location", $location);
        $stmt->bindParam("chest_world", $worldname);
        $stmt->bindParam("plot_id", $plot_id);
        $stmt->execute();
        $stmt->close();

    }

    public function getMembers()
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("SELECT chest_members FROM chests WHERE chest_id = :chest_id");
        $stmt->bindParam("chest_id", $id);
        $res = $stmt->execute()->fetchArray();
        $stmt->close();

        return json_decode($res["chest_members"], true);
    }

    public function isMember(string $member)
    {
        $members = $this->getMembers();

        return in_array(strtolower($member), $members);


    }

    public function addMember(string $member)
    {
        $members = $this->getMembers();
        if($this->isMember($member)){
            return;
        }

        $id = $this->getId();

        array_push($members, strtolower($member));
        $members = json_encode($members, true);

        $stmt = Main::getDb()->prepare("UPDATE chests SET chest_members = :chest_members WHERE chest_id = :chest_id");
        $stmt->bindParam("chest_members", $members);
        $stmt->bindParam("chest_id", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function removeMember(string $member)
    {
        if(!$this->isMember($member)) return;

        $id = $this->getId();
        $members = $this->getMembers();

        $members = json_encode(array_diff($members, array(strtolower($member))));

        $stmt = Main::getDb()->prepare("UPDATE chests SET chest_members = :chest_members WHERE chest_id = :chest_id");
        $stmt->bindParam("chest_members", $members);
        $stmt->bindParam("chest_id", $id);
        $stmt->execute();
        $stmt->close();


    }

    public function delete()
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("DELETE FROM chests WHERE chest_id = :chest_id");
        $stmt->bindParam("chest_id", $id);
        $stmt->execute();
        $stmt->close();

    }

    public function getId()
    {

        $location = json_encode([$this->getFloorX(), $this->getFloorY(), $this->getFloorZ()]);
        $world = $this->getLevel()->getFolderName();

        $stmt = Main::getDb()->prepare("SELECT chest_id FROM chests WHERE chest_location = :chest_location and chest_world = :chest_world");
        $stmt->bindParam("chest_location", $location);
        $stmt->bindParam("chest_world", $world);
        $res = $stmt->execute()->fetchArray();
        $stmt->close();
        if(!isset($res["chest_id"])){
            throw new \Exception("Chest ID not found! This will result in further errors. Please shut down the server and remove the plugin.");
        }
        return $res["chest_id"];

    }


    /**
     * @param Plot $plot
     * @return PrivateChest[]|null
     */
    public static function getPlotChests(Plot $plot) : ?array
    {
        $plot_id = $plot->getId();

        $stmt = Main::getDb()->prepare("SELECT * FROM chests WHERE plot_id = :plot_id");
        $stmt->bindParam("plot_id", $plot_id);
        $res = $stmt->execute();



        while($row = $res->fetchArray())
        {
            $pos = json_decode($row["chest_location"], true);
            $level = Server::getInstance()->getLevelByName($row["chest_world"]);
            $chests[] = PrivateChest::get(new Position($pos[0], $pos[1], $pos[2], $level));
        }
        $stmt->close();

        return isset($chests) ? $chests : null;


    }


}
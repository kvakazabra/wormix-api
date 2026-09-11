<?php

namespace App\Console\Commands\Game;

use App\Models\Wormix\CraftedEquipment;
use App\Models\Wormix\Upgrade;
use App\Models\Wormix\DailyBonus;
use App\Models\Wormix\Level;
use App\Models\Wormix\Mission;
use App\Models\Wormix\Race;
use App\Models\Wormix\Reagent;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\Equipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InitGameData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add base data from game to DB';

    private array $messages = [];

    private function parseNames() : void
    {
        $this->info('Parsing items.xml');
        $itemsMessagesPath = resource_path('game/items.xml');
        if (!File::exists($itemsMessagesPath))
        {
            $this->warn("Can't find items.xml in resources");
            return;
        }

        $messagesArray = simplexml_load_file($itemsMessagesPath);
        $messagesObject = [];
        foreach ($messagesArray->children() as $message)
        {
            $messagesObject[(string)$message['name']] = (string)$message['value'];
        }
        $this->messages = $messagesObject;
    }

    private function translate(string $key) : string
    {
        return $this->messages[$key] ?? $key;
    }

    private function parseWeapons() : void
    {
        $this->info('Parsing weapons.json');
        $weaponsPath = resource_path('game/weapons.json');
        if (!File::exists($weaponsPath))
        {
            $this->error("Can't find weapons.json in resources");
            return;
        }

        $count = 0;
        $weaponsArray = json_decode(file_get_contents($weaponsPath), true);
        foreach ($weaponsArray as $weapon)
        {
            try
            {
                $infinite = $weapon['infinite'] ?? false;
                $maxShots = $weapon['maxShots'] ?? -1;
                if (is_array($infinite))
                {
                    $maxShots = $infinite['maxShots'] ?? -1;
                    $infinite = true;
                }

                DB::beginTransaction();
                Weapon::insert(
                    [
                        'id' => $weapon['id'],

                        'name' => $this->translate($weapon['name'] ?? ""),
                        'description' => $this->translate($weapon['description'] ?? ""),
                        'note' => $this->translate($weapon['note'] ?? ""),
                        'hint' => $this->translate($weapon['hint'] ?? ""),

                        'is_starter' => in_array((int)$weapon['id'], config('wormix.starter.weapons')),
                        'hide_in_shop' => $weapon['hide_in_shop'] ?? false,
                        'boss_weapon' => $weapon['bossWeapon'] ?? false,
                        'temporal' => $weapon['temporal'] ?? false,

                        'price' => $weapon['price'] ?? 0,
                        'real_price' => $weapon['realprice'] ?? 0,
                        'sell_price' => $weapon['sellPrice'] ?? 0,

                        'infinite' => $infinite,
                        'max_shots' => $maxShots,

                        'required_friends' => $weapon['requiredFriends'] ?? 0,
                        'required_level' => $weapon['requiredLevel'] ?? 0,
                    ]
                );
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                $this->error("Error in {$weapon['id']}: {$ex->getMessage()}");
                DB::rollBack();
            }
        }

        $this->info("{$count} weapons parsed");
    }

    private function parseHats() : void
    {
        $this->info('Parsing hats.json');
        $hatsPath = resource_path('game/hats.json');
        if (!File::exists($hatsPath))
        {
            $this->error("Can't find hats.json in resources");
            return;
        }

        $count = 0;
        $hatsArray = json_decode(file_get_contents($hatsPath), true);
        foreach ($hatsArray as $hat)
        {
            try
            {
                DB::beginTransaction();
                Equipment::insert(
                    [
                        'id' => $hat['id'],
                        'name' => $this->translate($hat['name'] ?? ""),

                        'hide_in_shop' => $hat['hideInShop'] ?? false,
                        'price' => $hat['price'] ?? 0,
                        'real_price' => $hat['realprice'] ?? 0,
                        'duration' => ($hat['oneDay'] ?? false) ? 23 : 0,

                        'required_scenario' => $hat['requiredScenario'] ?? 0,
                        'required_rating' => $hat['requiredRating'] ?? 0,
                        'required_level' => $hat['requiredLevel'] ?? 0,
                    ]
                );
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                DB::rollBack();
                $this->error("Error in {$hat['id']}: {$ex->getMessage()}");
            }
        }

        $this->info("{$count} hats parsed");
    }

    private function parseArtifacts() : void
    {
        $this->info('Parsing artifacts.json');
        $artifactsPath = resource_path('game/artifacts.json');
        if (!File::exists($artifactsPath))
        {
            $this->error("Can't find artifacts.json in resources");
            return;
        }

        $count = 0;
        $artifactsArray = json_decode(file_get_contents($artifactsPath), true);
        foreach ($artifactsArray as $artifact)
        {
            try
            {
                DB::beginTransaction();
                Equipment::insert(
                    [
                        'id' => $artifact['id'],
                        'name' => $this->translate($artifact['name'] ?? ""),
                        'hide_in_shop' => $artifact['hideInShop'] ?? false,
                        'price' => $artifact['price'] ?? 0,
                        'real_price' => $artifact['realprice'] ?? 0,
                        'duration' => $artifact['duration'] ?? 0,
                        'required_scenario' => $artifact['requiredScenario'] ?? 0,
                        'required_rating' => $artifact['requiredRating'] ?? 0,
                        'required_level' => $artifact['requiredLevel'] ?? 0,
                    ]
                );
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                $this->error("Error in {$artifact['id']}: {$ex->getMessage()}");
                DB::rollBack();
            }
        }

        $this->info("{$count} artifacts parsed");
    }

    private function parseCraftedEquipment() : void
    {
        $this->info('Parsing crafted equipment from crafts.json');
        $craftsPath = resource_path('game/crafts.json');
        if (!File::exists($craftsPath))
        {
            $this->error("Can't find crafts.json in resources");
            return;
        }

        $count = 0;
        $craftedEquipmentArray = json_decode(file_get_contents($craftsPath), true);
        foreach ($craftedEquipmentArray as $crafted_equipment)
        {
            try
            {
                DB::beginTransaction();
                CraftedEquipment::insert([
                    'family_id' => $crafted_equipment['familyId'],
                    'name' => $this->translate($crafted_equipment['name'] ?? ""),
                    'hide_in_shop' => $crafted_equipment['hideInShop'] ?? true,
                    'hide_in_craft' => $crafted_equipment['hideInCraft'] ?? false,
                    'duration' => $crafted_equipment['duration'] ?? 0,
                    'craft_cost' => json_encode($crafted_equipment['craftCost']),
                    'remake_cost' => json_encode($crafted_equipment['remakeCost']),
                ]);
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                $this->error("Error in {$crafted_equipment['familyId']}: {$ex->getMessage()}");
                DB::rollBack();
            }
        }

        $this->info("{$count} crafted equipment parsed");
    }

    private function parseGifts() : void
    {
        $this->info('Parsing gifts.json');
        $giftsPath = resource_path('game/gifts.json');
        if (!File::exists($giftsPath))
        {
            $this->error("Can't find gifts.json in resources");
            return;
        }

        $count = 0;
        $giftsArray = json_decode(file_get_contents($giftsPath), true);
        foreach ($giftsArray as $gift)
        {
            try
            {
                DB::beginTransaction();
                DailyBonus::insert([
                    'login_sequence' => $gift['sequence'],
                    'bonus_type' => $gift['type'],
                    'bonus_value' => $gift['value'],
                    'random_gift' => $gift['random']
                ]);
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                DB::rollBack();
                $this->error("Error in {$gift['sequence']}: {$ex->getMessage()}");
            }
        }

        $this->info("{$count} gifts parsed");
    }

    private function parseRaces() : void
    {
        $this->info('Parsing races.json');
        $racesPath = resource_path('game/races.json');
        if (!File::exists($racesPath))
        {
            $this->error("Can't find races.json in resources");
            return;
        }

        $count = 0;
        $racesArray = json_decode(file_get_contents($racesPath), true);
        foreach ($racesArray as $race)
        {
            try
            {
                DB::beginTransaction();
                Race::insert([
                    'race_id' => $race['raceId'],
                    'race_name' => $race['configName'],

                    'price' => $race['price'],
                    'real_price' => $race['realPrice'],

                    'required_level' => $race['requiredLevel'],
                    'playable' => $race['playable'] ?? false,
                ]);
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                DB::rollBack();
                $this->error("Error in {$race['raceId']}: {$ex->getMessage()}");
            }
        }

        $this->info("{$count} races parsed");
    }

    private function addStartItems() : void
    {
        $this->info('Parsing weapons_start.json');
        $startWeaponsPath = resource_path('game/weapons_start.json');
        if (!File::exists($startWeaponsPath))
        {
            $this->error("Can't find weapons_start.json in resources");
            return;
        }

        $startItems = json_decode(file_get_contents($startWeaponsPath), true);
        if ($startItems == null)
        {
            $this->error("Can't parse weapons_start.json");
        }

        try
        {
            DB::beginTransaction();
            $updateCount = Weapon::query()
                ->whereIn('id', $startItems)
                ->update([
                    'is_starter' => 1
                ]);
            DB::commit();
            $this->info("Set [{$updateCount}] items ".json_encode($startItems)." as starter");
        }
        catch (\Exception $exception)
        {
            DB::rollBack();
            $this->error("Error {$exception->getMessage()}");
        }
    }

    private function parseLevelAwards() : void
    {
        $this->info('Parsing level_awards.json');
        $levelsPath = resource_path('game/level_awards.json');
        if (!File::exists($levelsPath))
        {
            $this->error("Can't find weapons_start.json in resources");
            return;
        }

        $count = 0;
        $levelsArray = json_decode(file_get_contents($levelsPath), true);
        foreach ($levelsArray as $level)
        {
            try
            {
                DB::beginTransaction();
                Level::insert([
                    'required_experience' => $level['required_experience'],
                    'max_worms_count' => $level['max_worms_count'],
                    'awards' => json_encode($level['reward_weapons']),
                ]);
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                DB::rollBack();
                $this->error("Error in {$level['level']}: {$ex->getMessage()}");
            }
        }

        $this->info("{$count} levels parsed");
    }

    private function parseMissions() : void
    {
        $this->info('Parsing missions_awards.json');
        $missionsPath = resource_path('game/missions_awards.json');
        if (!File::exists($missionsPath))
        {
            $this->error("Can't find missions_awards.json in resources");
            return;
        }

        $count = 0;
        $missionsArray = json_decode(file_get_contents($missionsPath), true);
        try
        {
            DB::beginTransaction();
            foreach ($missionsArray as $mission)
            {
                $m = new Mission();
                $m->mission_id = $mission['id'];
                $m->awards = $mission['awards'];
                $m->required_level = $mission['required_level'];
                $m->save();
                $count++;
            }
            DB::commit();
        }
        catch (\Exception $exception)
        {
            $this->error("Error while adding missions: {$exception->getMessage()}");
            DB::rollBack();
        }

        $this->info("{$count} missions parsed");
    }

    private function parseUpgrades() : void
    {
        $this->info('Parsing recipes.json');
        $recipesPath = resource_path('game/recipes.json');
        if (!File::exists($recipesPath))
        {
            $this->error("Can't find recipes.json in resources");
            return;
        }

        $count = 0;
        $recipes = json_decode(file_get_contents($recipesPath), true);
        foreach ($recipes as $recipe)
        {
            try
            {
                DB::beginTransaction();
                $upgrade = new Upgrade();
                $upgrade->id = $recipe['id'];
                $upgrade->description = $recipe['description'] ?? "";
                $upgrade->upgrade_id = $recipe['upgradeId'];
                $upgrade->prev_upgrade_id = $recipe['prevUpgradeId'];
                $upgrade->reagents = $recipe['reagents'];
                $upgrade->level = $recipe['level'];
                $upgrade->required_level = $recipe['requiredLevel'];
                $upgrade->save();
                DB::commit();
                $count++;
            }
            catch (\Exception $exception)
            {
                DB::rollBack();
                $this->error("Error in {$recipe['description']}: {$exception->getMessage()}");
            }
        }

        $this->info("{$count} recipes parsed");
    }

    private function parseReagents() : void
    {
        $this->info('Parsing reagents.json');
        $reagentsPath = resource_path('game/reagents.json');
        if (!File::exists($reagentsPath))
        {
            $this->error("Can't find weapons_start.json in resources");
            return;
        }

        $count = 0;
        $reagentsArray = json_decode(file_get_contents($reagentsPath), true);
        foreach ($reagentsArray as $reagent)
        {
            try
            {
                DB::beginTransaction();
                Reagent::insert([
                    'reagent_id' => $reagent['id'],
                    'name' => $this->translate($reagent['name'] ?? ""),
                    'reagent_price' => $reagent['price'],
                ]);
                DB::commit();
                $count++;
            }
            catch (\Exception $ex)
            {
                DB::rollBack();
                $this->error("Error in reagent {$reagent['id']}: {$ex->getMessage()}");
            }
        }

        $this->info("{$count} reagents parsed");
    }

    /**
     * Execute the console command.
     */
    public function handle() : void
    {
        $this->parseNames();

        $this->parseWeapons();

        $this->parseHats();

        $this->parseArtifacts();

        $this->parseCraftedEquipment();

        $this->addStartItems();

        $this->parseGifts();

        $this->parseRaces();

        $this->parseLevelAwards();

        $this->parseReagents();

        $this->parseMissions();

        $this->parseUpgrades();

        $this->info('SETUP IS COMPLETED');
    }
}

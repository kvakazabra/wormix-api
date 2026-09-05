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
use Mockery\Exception;

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
        $items_messages_path = resource_path('game/items.xml');
        if (!File::exists($items_messages_path))
        {
            $this->warn("Can't find items.xml in resources");
            return;
        }

        $messages_array = simplexml_load_file($items_messages_path);
        $messages_object = [];
        foreach ($messages_array->children() as $message)
        {
            $messages_object[(string)$message['name']] = (string)$message['value'];
        }
        $this->messages = $messages_object;
    }

    private function parseWeapons() : void
    {
        $this->info('Parsing weapons.json');
        $weapons_path = resource_path('game/weapons.json');
        if (!File::exists($weapons_path))
        {
            $this->error("Can't find weapons.json in resources");
            return;
        }

        $count = 0;
        $weapons_array = json_decode(file_get_contents($weapons_path), true);
        foreach ($weapons_array as $weapon)
        {
            if (array_key_exists('name', $weapon))
            {
                try
                {
                    DB::beginTransaction();
                    Weapon::insert(
                        [
                            'id' => $weapon['id'],
                            'name' => $this->messages[$weapon['name']] ?? $weapon['name'],
                            'hide_in_shop' => $weapon['hide_in_shop'] ?? false,
                            'is_starter' => in_array((int)$weapon['id'], config('wormix.starter.weapons')),
                            'price' => $weapon['price'] ?? 0,
                            'real_price' => $weapon['realprice'] ?? 0,
                            'required_friends' => $weapon['requiredFriends'] ?? 0,
                            'required_level' => $weapon['requiredLevel'] ?? 0,
                            'infinity' => $weapon['infinity'] ?? 0,
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
            else if (array_key_exists('refId', $weapon))
            {
                try
                {
                    DB::beginTransaction();
                    Weapon::insert(
                        [
                            'id' => $weapon['id'],
                            'ref_id' =>  $weapon['refId'],
                            'hide_in_shop' => $weapon['hideInShop'] ?? true,
                            'price' => $weapon['price'],
                            'required_friends' => $weapon['requiredFriends'] ?? 0,
                            'required_level' => $weapon['requiredLevel'] ?? 0,
                        ]
                    );
                    DB::commit();
                    $count++;
                }
                catch (Exception $ex)
                {
                    $this->error("Error in {$weapon['id']}: {$ex->getMessage()}");
                    DB::rollBack();
                }
            }
        }

        $this->info("{$count} weapons parsed");
    }

    private function parseHats() : void
    {
        $this->info('Parsing hats.json');
        $hats_path = resource_path('game/hats.json');
        if (!File::exists($hats_path))
        {
            $this->error("Can't find hats.json in resources");
            return;
        }

        $count = 0;
        $hats_array = json_decode(file_get_contents($hats_path), true);
        foreach ($hats_array as $hat)
        {
            try
            {
                DB::beginTransaction();
                Equipment::insert(
                    [
                        'id' => $hat['id'],
                        'name' => $this->messages[$hat['name']] ?? $hat['name'],

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
        $art_path = resource_path('game/artifacts.json');
        if (!File::exists($art_path))
        {
            $this->error("Can't find artifacts.json in resources");
            return;
        }

        $count = 0;
        $artifacts_array = json_decode(file_get_contents($art_path), true);
        foreach ($artifacts_array as $artifact)
        {
            try
            {
                DB::beginTransaction();
                Equipment::insert(
                    [
                        'id' => $artifact['id'],
                        'name' => $this->messages[$artifact['name']] ?? $artifact['name'],
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
        $crafts_path = resource_path('game/crafts.json');
        if (!File::exists($crafts_path))
        {
            $this->error("Can't find crafts.json in resources");
            return;
        }

        $count = 0;
        $crafted_equipment_array = json_decode(file_get_contents($crafts_path), true);
        foreach ($crafted_equipment_array as $crafted_equipment)
        {
            try
            {
                DB::beginTransaction();
                CraftedEquipment::insert([
                    'family_id' => $crafted_equipment['familyId'],
                    'name' => $this->messages[$crafted_equipment['name']] ?? $crafted_equipment['name'],
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
        $gifts_path = resource_path('game/gifts.json');
        if (!File::exists($gifts_path))
        {
            $this->error("Can't find gifts.json in resources");
            return;
        }

        $count = 0;
        $gifts_array = json_decode(file_get_contents($gifts_path), true);
        foreach ($gifts_array as $gift)
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
        $races_path = resource_path('game/races.json');
        if (!File::exists($races_path))
        {
            $this->error("Can't find races.json in resources");
            return;
        }

        $count = 0;
        $races_array = json_decode(file_get_contents($races_path), true);
        foreach ($races_array as $race)
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
            catch (Exception $ex)
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
        $start_weapons_path = resource_path('game/weapons_start.json');
        if (!File::exists($start_weapons_path))
        {
            $this->error("Can't find weapons_start.json in resources");
            return;
        }

        $start_items = json_decode(file_get_contents($start_weapons_path), true);
        if ($start_items == null)
        {
            $this->error("Can't parse weapons_start.json");
        }

        try
        {
            DB::beginTransaction();
            $update_count = Weapon::query()
                ->whereIn('id', $start_items)
                ->update([
                    'is_starter' => 1
                ]);
            DB::commit();
            $this->info("Set [{$update_count}] items ".json_encode($start_items)." as starter");
        }
        catch (Exception $exception)
        {
            DB::rollBack();
            $this->error("Error {$exception->getMessage()}");
        }
    }

    private function parseLevelAwards() : void
    {
        $this->info('Parsing level_awards.json');
        $levels_path = resource_path('game/level_awards.json');
        if (!File::exists($levels_path))
        {
            $this->error("Can't find weapons_start.json in resources");
            return;
        }

        $count = 0;
        $levels = json_decode(file_get_contents($levels_path), true);
        foreach ($levels as $level)
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
        $missions_path = resource_path('game/missions_awards.json');
        if (!File::exists($missions_path))
        {
            $this->error("Can't find missions_awards.json in resources");
            return;
        }

        $count = 0;
        $missions = json_decode(file_get_contents($missions_path), true);
        try
        {
            DB::beginTransaction();
            foreach ($missions as $mission)
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
        catch (Exception $exception)
        {
            $this->error("Error while adding missions: {$exception->getMessage()}");
            DB::rollBack();
        }

        $this->info("{$count} missions parsed");
    }

    private function parseUpgrades() : void
    {
        $this->info('Parsing recipes.json');
        $recipes_path = resource_path('game/recipes.json');
        if (!File::exists($recipes_path))
        {
            $this->error("Can't find recipes.json in resources");
            return;
        }

        $count = 0;
        $recipes = json_decode(file_get_contents($recipes_path), true);
        foreach ($recipes as $recipe)
        {
            try
            {
                DB::beginTransaction();
                $craft = new Upgrade();
                $craft->id = $recipe['id'];
                $craft->description = $recipe['description'];
                $craft->upgrade_id = $recipe['upgradeId'];
                $craft->prev_upgrade_id = @$recipe['prevUpgradeId'];
                $craft->reagents = $recipe['reagents'];
                $craft->level = $recipe['level'];
                $craft->required_level = $recipe['requiredLevel'];
                $craft->save();
                DB::commit();
                $count++;
            }
            catch (Exception $exception)
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
        $reagents_path = resource_path('game/reagents.json');
        if (!File::exists($reagents_path))
        {
            $this->error("Can't find weapons_start.json in resources");
            return;
        }

        $count = 0;
        $reagents = json_decode(file_get_contents($reagents_path), true);
        foreach ($reagents as $reagent)
        {
            try
            {
                DB::beginTransaction();
                Reagent::insert([
                    'reagent_id' => $reagent['id'],
                    'name' => $this->messages[$reagent['name']] ?? $reagent['name'],
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

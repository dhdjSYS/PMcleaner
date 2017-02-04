<?php
namespace PMCleaner;
//调用PM核心模块，应该支持0.12以后的版本
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\level\LevelEvent;
use pocketmine\scheduler\CallbackTask;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\PluginTask;
use pocketmine\entity\DroppedItem;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\MainLogger;
use pocketmine\entity\Creature;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\utils\Utils;
use pocketmine\Player;
use pocketmine\Server;
//创建配置文件和默认配置
class Main extends PluginBase implements Listener
{
	public function onEnable()
	{
		@mkdir($this->getDataFolder());
 		$this->cfg=new Config($this->getDataFolder()."config.yml",Config::YAML,array());
		if(!$this->cfg->exists("CleanDelay"))
		{
			$this->cfg->set("CleanDelay","500");
			$this->cfg->save();
		}
		$this->CleanDelay=$this->cfg->get("CleanDelay")*20;
		$this->cleaner=new cleaner($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->cleaner, 1);
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $arg)
	{
		if(!isset($arg[0])){unset($sender,$cmd,$label,$arg);return false;};
		switch($arg[0])
		{
		case "clean":
		case "cl":
		case "c":
			$this->removeMobs();
			$this->clean();
			break;
		case "reload":
			$this->cfg->reload();//重启插件命令
			if(!$this->cfg->exists("CleanDelay"))
			{
				$this->cfg->set("CleanDelay","300");
				$this->cfg->save();
			}
			$this->CleanDelay=$this->cfg->get("CleanDelay")*20;
			$this->cleaner->tmp=$this->CleanDelay;
			$sender->sendMessage("[ PMCleaner ] restart完成");
			break;
		default:
			unset($sender,$cmd,$label,$arg);
			return false;
			break;
		}
		unset($player,$killer,$event,$name1,$name2);
		return true;
	}

    public function clean(){
        $i = 0;
        foreach($this->getServer()->getLevels() as $level){
            foreach($level->getEntities() as $entity){//清理掉落物模块
                if(!$this->isEntityExempted($entity) && !($entity instanceof Creature)){
                    $entity->close();
                    $i++;
                }
            }
        }
Server::getInstance()->broadcastMessage("[ PMcleaner ] 共清理{$i}个掉落物，清理内存完成！");
      unset($i,$entity);
    }
	public function onTimer()
    {//内存清理模块
		foreach($this->getServer()->getLevels() as $level){
			$level->doChunkGarbageCollection();
			$level->unloadChunks(\true);
			$level->clearCache(\true);
			$s=str_repeat('1',255); //unset函数清理内存
            unset($s); //unset函数清理内存
		}
	}

	    public function exemptEntity(Entity $entity){
        $this->exemptedEntities[$entity->getID()] = $entity;
    }
    
    public function isEntityExempted(Entity $entity){
        return isset($this->exemptedEntities[$entity->getID()]);
    }
	public function removeMobs(){
        $i = 0;
        foreach($this->getServer()->getLevels() as $level){
            foreach($level->getEntities() as $entity){
                if(!$this->isEntityExempted($entity) && $entity instanceof Creature && !($entity instanceof Human)){
                    $entity->close();
                    $i++;
                }
            }
        }
        Server::getInstance()->broadcastMessage("[ PMcleaner] 共杀掉{$i}个生物，内存清理完成！");
    }
	public function onTimer()
    {//内存清理模块
		foreach($this->getServer()->getLevels() as $level){
			$level->doChunkGarbageCollection();
			$level->unloadChunks(\true);
			$level->clearCache(\true);
			$s=str_repeat('1',255); //unset函数清理内存
            unset($s); //unset函数清理内存
		}
	}

}
class cleaner extends PluginTask
{
	//public players;
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->tmp=$plugin->CleanDelay;
    }
    public function onRun($currentTick)
    {
    	$this->plugin = $this->getOwner();
    	$this->tmp--;
    	if($this->tmp<=0)
    	{
    		$this->plugin->removeMobs();
    		$this->plugin->clean();
    		$this->tmp=$this->plugin->CleanDelay;
    	}
    	if($this->tmp==100)
    	{
    		Server::getInstance()->broadcastMessage("[ PMCleaner ] 将在5秒后清除所有掉落物和生物");
    	}
    	if($this->tmp==200)
    	{
    		Server::getInstance()->broadcastMessage("[ PMcleaner ] 将在10秒后清除所有掉落物和生物");
    	}
    	if($this->tmp==400)
    	{
    		Server::getInstance()->broadcastMessage("[ PMCleaner ] 将在20秒后清除所有掉落物和生物");
    	}
    }

}
?>

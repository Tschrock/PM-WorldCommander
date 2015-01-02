World-Commander
---------------
Take command of your worlds!
 - Control pvp, spawnprotection, etc. on a per-world basis.
 - Define regions for more control on where things are allowed.
 - Easily create and load new worlds.
 - Teleport between all of your worlds with a simple command.

##Flags
WorldCommander works like the Bukkit plugin WorldGuard: You control things in worlds/regions by setting flags.
Flags can be set using `/wc flag <world/region> <flag> <arguments>`
Available flags:
 - `pvp <true/false>`
    - Sets whether or not players can pvp in an area. 
    - Defaults to the value in `server.properties`.
 - `gamemode <survival/creative>`
    - Sets the gamemode of a world.
    - Defaults to the value in `server.properties`.
 - `spawnprotection <radius>`
    - Sets the radius of spawnprotection in a world.
    - Defaults to the value in `server.properties`.
    - If the value in `server.properties` is larger, it will override this flag.
 - `time <time/equation>`
    - Sets the time.
    - Can be set to "sunrise", "morning", "day", "noon", "afternoon", "evening", "sunset", "night", "midnight", "realtime", "auto", or "now".
    - You can also use an equation. (For ex. "auto*2" would be double normal time).
    - Is unset by default.
    - Can't be bypassed and will override normal `/time` commands.
 - `build <true/false> <exceptions>`
    - Sets whether or not people can build in a region.
    - Can set a list of exceptions.
    - Is unset by default.

##Regions
Regions allow you to set flags for a small area instead of a whole world. Not all flags work in regions (For ex. `time` can only be used for a world). Region flags override world flags, and regions with a higher priority override regions with a lower priority.

Defining a region:
 - Use `/region pos1` and `/region pos2` to select a cuboid for your region (Like worldedit).
 - Then use `/region create <name> <priority>` to create a region from your selection. 

##Commands
 - `/wc` - Main WorldCommander command.
 - `/wc create` - Create a new world.
 - `/wc load` - Load a world.
 - `/wc unload` - Unload a world.
 - `/wc flags` - Manage flags.
 - `/wc regions` - Manage regions.

##Aliases
 - `/wc create` => `/wcc`
 - `/wc load` => `/wcl`
 - `/wc unload` => `/wcu`
 - `/wc flags` => `/wcf`
 - `/wc regions` => `/wcr` or `/regions`

##Permissions

 - `tschrock.worldcommander.all` - Allows the user to use all WorldCommander commands.<br /><br />
 - `tschrock.worldcommander.worlds` - Allows the user to manage worlds.
 - `tschrock.worldcommander.worlds.create` - Allows the user to create worlds.
 - `tschrock.worldcommander.worlds.load` - Allows the user to load worlds.
 - `tschrock.worldcommander.worlds.unload` - Allows the user to unload worlds.<br /><br />
 - `tschrock.worldcommander.tp` - Allows the user to teleport himself or others to different worlds.
 - `tschrock.worldcommander.tp.self` - Allows the user to teleport to different worlds.
 - `tschrock.worldcommander.tp.other` - Allows the user to teleport other players to differant worlds.<br /><br />
 - `tschrock.worldcommander.flags` - Allows the user to change and bypass all flags in all areas.
 - `tschrock.worldcommander.flags.edit` - Allows the user to change all flags in all areas.
 - `tschrock.worldcommander.flags.bypass` - Allows the user to bypass all flags in all areas.<br /><br />
 - `tschrock.worldcommander.regions` - Allows the user to manage regions.
 - `tschrock.worldcommander.regions.create` - Allows the user to create regions.
 - `tschrock.worldcommander.regions.delete` - Allows the user to delete regions.<br /><br />
 - `tschrock.worldcommander.flag.<Flag Name>.edit` - Allow a person to change a flag in all areas.
 - `tschrock.worldcommander.flag.<Flag Name>.bypass` - Allow a person to bypass a flag in all areas.
 - `tschrock.worldcommander.area.<Area Name>.edit` - Allow a person to change all flags in an area.
 - `tschrock.worldcommander.area.<Area Name>.bypass` - Allow a person to bypass all flags in an area.
 - `tschrock.worldcommander.areaflag.<Area Name>.<Flag Name>.edit` - Allow a person to change a flag in an area.
 - `tschrock.worldcommander.areaflag.<Area Name>.<Flag Name>.bypass` - Allow a person to bypass a flag in an area.

##API/Custom Flags
Plugins can provide custom flags to WorldCommander:

1. Create a class that extends `tschrock\worldcommander\flag\Flag`.
2. In your plugin's `onEnable()`:
    - Get a reference to WorldCommander:
       - `$this->wCommander = $this->getServer()->getPluginManager()->getPlugin("WorldCommander");`
    - Create a new instance of your flag:
       - `$this->yourCustomFlag = new CustomFlag($this->wCommander, $this);` 
    - Register your flag with WorldCommander:
       - `$this->wCommander->registerFlag($this->yourCustomFlag);`
3. In your plugin's `onDisable()`:
    - Unregister your flag from WorldCommander:
       - `$this->wCommander->unregisterFlag($this->yourCustomFlag);`

Custom flags should provide their own functionality (registering event handlers, etc). A good example is `tschrock\worldcommander\flag\PvPFlag`.

Common functions:
 - `$this->wCommander->getFlagHelper()->canEditFlag($player, $areaOrPosition, $flag);`
    - Gets whether or not a player can edit a flag.
 - `$this->wCommander->getFlagHelper()->canBypassFlag($player, $areaOrPosition, $flag);`
    - Gets whether or not a player can bypass a flag.
 - `$this->wCommander->getFlagHelper()->getFlagValue($areaOrPosition, $flag);`
    - Gets the value of a flag for an area.
 - `$this->wCommander->getFlagHelper()->setFlagValue($area, $flag, $value);`
    - Sets the value of a flag for an area.
 - `$this->wCommander->getDataProvider()->getRegion($position);`
    - Gets a list of regions that `$position` is in, ordered by priority.
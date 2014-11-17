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
    - Sets wether or not players can pvp in an area. Defaults to the value in server.properties.
 - `gamemode <survival/creative>`
    - Sets the gamemode of a world. Defaults to the value in server.properties.
 - `spawnprotection <radius>`
    - Sets the radius of spawnprotection in a world. Defaults to the value in server.properties.
 - `time <time/equation>`
    - Sets the time. Defaults to "auto".
    - Can be set to "sunrise", "morning", "day", "noon", "afternoon", "evening", "sunset", "night", "midnight", "realtime", "auto", or "now".
    - You can also use an equation. (For ex. "auto*2" would be double normal time).
 - `build <true/false> <exceptions>`
    - Sets wether or not people can build in a region.
    - Can set a list of exceptions.
    - Is unset by default.

##Regions
Regions allow you to set flags for a small area instead of a whole world. Not all flags work in regions (For ex. `time` can only be used for a world). Region flags override world flags, and regions with a higher priority override regions with a lower priority.

Defining a region:
 - Use `/region pos1` and `/region pos2` to select a cuboid for your region (Like worldedit).
 - Then use `/region create <name> <priority>` to create a region from your selection. 

##Commands

##Aliases

##Permissions


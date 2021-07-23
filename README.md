[![](https://poggit.pmmp.io/shield.state/StaffMode)](https://poggit.pmmp.io/p/StaffMode)
[![](https://poggit.pmmp.io/shield.api/StaffMode)](https://poggit.pmmp.io/p/StaffMode)

<img src="https://www.gstatic.com/images/branding/product/1x/admin_512dp.png" width="120" height="120" align="left"></img>

# StaffMode
StaffMode is an all-in-one Pocketmine-MP (PMMP) moderation plugin made to simplify the life of staff members.

# Setup Guide
1. To start using the plugin, download the StaffMode.phar file from poggit and put it into your server's plugins folder. 
2. Then, you have to do is set up your staff rank permissions. Look at the **Risk** tab in the following tables to decide which permissions is more suitable for which staff ranks to prevent possible abuse. It is recommended to give permissions with the *High* risk only to trusted staff members.
3. The `staffmode.command.staffmode` or `staffmode.*` permission is necessary in order for staff to enter staff mode.

| Permissions | Description | Default | Risk |
| --- | --- | --- | --- |
| `staffmode.*` | Allows usage of all staffmode features (including all the tools). | `op` | High |
| `staffmode.command.staffmode` | Allows usage of the "/staffmode" command. | `op` | - |

4. The folowing permissions are to access the tools within staffmode.

| Permissions | Description | Default | Risk |
| --- | --- | --- | --- |
| `staffmode.silent` | Allows user to join and leave the server with no broadcasted message. | `op` | - |
| `staffmode.tools.teleport` | Allows usage of the teleportation tool (compass) in staffmode. | `op` | Low |
| `staffmode.tools.playerinfo` | Allows usage of the playerinfo tool (book) in staffmode. | `op` | - |
| `staffmode.tools.warn` | Allows usage of the warning tool (paper) in staffmode. | `op` | - |
| `staffmode.tools.freeze` | Allows usage of the freezing tool (ice block) in staffmode. | `op` | Medium |
| `staffmode.tools.mute` | Allows usage of the muting tool (gold hoe) in staffmode. | `op` | Medium |
| `staffmode.tools.kick` | Allows usage of the kicking tool (gold sword) in staffmode. | `op` | Medium |
| `staffmode.tools.ban` | Allows usage of the banning tool (gold axe) in staffmode. | `op` | High |
| `staffmode.tools.exit` | Allows usage of the exit tool (redstone torch) in staffmode. | `everyone` | - |

5. After setting up everything, if done correctly staff members will be able to do the /staffmode command and access all their allowed tools. 

# Support
Join the [discord server](https://discord.gg/YJZNhwhyMQ) for quick support, bug reports and feature requests.
If you do not have discord please create an issue on github. Please keep in mind that it will take longer for me to reply on github. If you are reporting an issue with the plugin, be sure to include as many details as possible and steps to reproduce the issue.

# Features
- Features:
  - [x] /staffmode command
  - [x] /report command
  - [x] Teleportation tool
  - [x] Playerinfo tool
  - [x] Invsee tool
  - [x] Warn tool
  - [x] Freeze tool
  - [x] Hit to freeze
  - [x] Unfreeze tool
  - [x] Mute tool
  - [x] Custom mute system
  - [x] Kick tool
  - [x] Ban tool
  - [x] Custom ban system
  - [x] Tool to exit staff mode
  - [x] Return to original location with the original inventory after exiting staffmode.
  - [x] Silent Join & Leave (Editable in config)
  - [x] Fake Join & Leave when entering/leaving StaffMode (Editable in config)
- Coming soon...
  - [ ] Edit mute & bans
  - [ ] Close reports
  - [ ] Staff Chat
  - [ ] More customization in config (Make all messages configurable)
  - [ ] Staff abuse prevention (Prevent staff from doing commands while in staffmode, prevent staff from banning other staff, etc.)
  - [ ] Please suggest anything you want me to add

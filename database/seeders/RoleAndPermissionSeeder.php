<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
//use Spatie\Permission\Models\Permission;
//use Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** ------------- PERMISSIONS -------------- **/
        $permissions = [
            "retrieve artists", //
            "update artists", //
            "delete artists", // destroy
            "create artist", //
            "retrieve artist", //
            "update artist", //
            "delete artist", //
            "view artist", // show
            "accept the organizer's invitation",
            "apply as an event artist",
            "accept invitations from the organizer",
            "upload artist media",
            "view hiring artist",
            "send a proposal to the artist",

            "retrieve members",
            "update members",
            "delete members",
            "retrieve member",
            "update member",
            "delete member",
            "view member",
            "add member",
            "deactivate member",

            "retrieve users",
            "update users",
            "delete users",
            "create user",
            "retrieve user",
            "update user",
            "delete user",
            "view user",

            "retrieve organizers",
            "update organizers",
            "delete organizers",
            "create organizer",
            "retrieve organizer",
            "update organizer",
            "delete organizer", // destroy
            "view organizer",
            "ban organizer",
            "accept artist application",

            "retrieve genres",
            "update genres",
            "delete genres",
            "create genre",
            "retrieve genre",
            "update genre",
            "delete genre", // destroy
            "view genre",
            "assign genres",

            "retrieve song types",
            "update song types",
            "delete song types",
            "create song type",
            "retrieve song type",
            "update song type",
            "delete song type",
            "view song type",

            "retrieve artist types",
            "update artist types",
            "delete artist types",
            "create artist type",
            "retrieve artist type",
            "update artist type",
            "delete artist type",
            "view artist type",

            "retrieve supported languages",
            "update supported languages",
            "create supported language",
            "retrieve supported language",
            "update supported language",
            "delete supported language",
            "view supported language",

            "retrieve durations",
            "update durations",
            "delete durations",
            "create duration",
            "retrieve duration",
            "update duration",
            "delete duration",
            "view duration",

            "retrieve purposes",
            "update purposes",
            "delete purposes",
            "create purpose",
            "retrieve purpose",
            "update purpose",
            "delete purpose",
            "view purpose",

            "retrieve event types",
            "update event types",
            "delete event types",
            "create an event type",
            "retrieve event type",
            "update event type",
            "delete event type",
            "view an event type",

            "retrieve event pricing",
            "update event pricing",
            "delete event pricing",
            "create event pricing",
            "view event pricing",

            "retrieve song requests",
            "update song requests",
            "delete song requests",
            "create song requests", // (request a custom song)
            "retrieve song request",
            "update song request",
            "delete song request",
            "view song request",
            "cancel song request",
            "deny the submitted song request",
            "approve a song request",
            "decline a song request",
            "modify song request status",
            "review song request",
            "validate artist song submission",
            "request resubmission", // (request a custom song to be edited)

            "create profiles",
            "retrieve profiles",
            "update profiles",
            "delete profiles",
            "view profile",

            "retrieve events",
            "update events",
            "delete events",
            "publish events",
            "create event",
            "retrieve event",
            "update event",
            "delete event",
            "view event",
            "publish event",
            "cancel event",

            "retrieve event participants",
            "update event participants",
            "delete event participants",
            "create event participant",
            "retrieve event participant",
            "update event participant",
            "delete event participant",
            "view event participant",

            "retrieve albums",
            "update albums",
            "delete albums",
            "create album",
            "update album",
            "delete album",
            "view album",

            "retrieve tracks",
            "update tracks",
            "delete tracks",
            "create track",
            "retrieve track",
            "update track",
            "delete track",
            "assign track",

            "create likes",
            "retrieve likes",
            "update like",
            "delete like",
            "retrieve the like counts",

            "retrieve followers",
            "update followers",
            "delete followers",
            "create follower",
            "update follower",
            "delete follower",
            "view follower",

            "retrieve services providers",
            "update services providers",
            "delete services providers",
            "create services provider",
            "update services provider",
            "delete services provider",
            "view service provider",

            "retrieve service categories",
            "update services categories",
            "delete services categories",
            "create a services category",
            "update the services category",
            "delete the services category",
            "view the service category",

            "create services offered", // create, store
            "retrieve services offered",
            "update services offered",
            "delete services offered", // destroy
            "view the service offered", // show

            "retrieve subscription types",
            "update subscription types",
            "delete subscription types",
            "create a subscription type",
            "update subscription type",
            "delete subscription type",
            "view subscription type",
            "subscribe monthly",
            "subscribe yearly",
            "upgrade subscription",
            "downgrade subscription",
            "cancel subscription",

            "update the site setting",
            "create a site setting",
            "retrieve site settings",

            "create community", // create, store
            "delete community", // destroy
            "retrieve communities", // index
            "delete communities",
            "view community", // show

            "retrieve request reviews",
            "retrieve artist reviews",

        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission,]);
            Permission::create(['name' => $permission, 'guard_name' => 'api',]);
        }

        /** ROLES **/
        // Super Admin
        Role::create(['name' => 'super-admin',])->givePermissionTo($permissions);
        Role::create(['name' => 'super-admin', 'guard_name' => 'api',])->givePermissionTo($permissions);

        // Service Providers
        Role::create(['name' => 'service-provider',])->givePermissionTo($permissions);
        Role::create(['name' => 'service-provider', 'guard_name' => 'api',])->givePermissionTo($permissions);

        // Organizers
        Role::create(['name' => 'organizer',])->givePermissionTo($permissions);
        Role::create(['name' => 'organizer', 'guard_name' => 'api',])->givePermissionTo($permissions);

        // Artists
        Role::create(['name' => 'artists',])->givePermissionTo([
            "retrieve artist", "create artist", "update artist",
            "delete artist", "view artist", "accept the organizer's invitation",
            "apply as an event artist", "accept invitations from the organizer",
            "upload artist media", "view hiring artist", "send a proposal to the artist",

            "retrieve members", "update members", "delete members",
            "retrieve member", "update member", "delete member",
            "view member", "add member", "deactivate member",

        ]);

        Role::create(['name' => 'artists', 'guard_name' => 'api',])->givePermissionTo([
            "retrieve artist", "create artist", "update artist",
            "delete artist", "view artist", "accept the organizer's invitation",
            "apply as an event artist", "accept invitations from the organizer",
            "upload artist media", "view hiring artist", "send a proposal to the artist",

            "retrieve members", "update members", "delete members",
            "retrieve member", "update member", "delete member",
            "view member", "add member", "deactivate member",

        ]);

        // Customers
        Role::create(['name' => 'customers',])->givePermissionTo($permissions);
        Role::create(['name' => 'customers', 'guard_name' => 'api',])->givePermissionTo($permissions);
    }
}

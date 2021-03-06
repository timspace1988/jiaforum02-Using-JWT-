<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

class SeedRolesAndPermissionsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Clear cache, otherwise will get errors
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        //Create permissions
        Permission::create(['name' => 'manage_contents']);
        Permission::create(['name' => 'manage_users']);
        Permission::create(['name' => 'edit_settings']);

        //Create the role 'founder' and give him some permissions
        $founder = Role::create(['name' => 'Founder']);
        $founder->givePermissionTo('manage_contents');
        $founder->givePermissionTo('manage_users');
        $founder->givePermissionTo('edit_settings');

        //Create the role 'maintainer' and give him some permissions
        $maintainer = Role::create(['name' => 'Maintainer']);
        $maintainer->givePermissionTo('manage_contents');

        //In in the heroku environment, we need to create a user as administrator
        if(getenv('IS_IN_HEROKU')){
            Model::unguard();
            User::create([
                'name' => 'JIA',
                'email' => 'timspace1988@hotmail.com',
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
                'introduction' => 'This is JIA',
                'created_at' => now(),
                'updated_at' => now(),
                'avatar' => 'https://cdn.learnku.com/uploads/images/201710/14/1/ZqM7iaP4CR.png',
                /*
                $user = User::find(1);
                $user->name = 'JIA';
                $user->email = 'timspace1988@hotmail.com';
                $user->avatar = 'https://cdn.learnku.com/uploads/images/201710/14/1/ZqM7iaP4CR.png';
                $user->save();
                //Set first user as 'Founder' assignRole is defined in HasRoles trait, we have used it in User class
                $user->assignRole('Founder');
                */
            ]);
            Model::reguard();

            $user = User::find(1);
            // $user->email_verified_at = now();
            // $user->remember_token = Str::random(10);
            // $user->save();
            $user->assignRole('Founder');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Clear cache, otherwise errors will pop up
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        //Clear all permission related tables
        $tableNames = config('permission.table_names');

        Model::unguard();
        DB::table($tableNames['role_has_permissions'])->delete();
        DB::table($tableNames['model_has_roles'])->delete();
        DB::table($tableNames['model_has_permissions'])->delete();
        DB::table($tableNames['roles'])->delete();
        DB::table($tableNames['permissions'])->delete();
        Model::reguard();
    }
}

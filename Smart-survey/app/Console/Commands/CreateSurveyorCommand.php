<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSurveyorCommand extends Command
{
    protected $signature = 'surveyor:create {--email=} {--name=} {--license=} {--password=}';
    protected $description = 'Create the licensed surveyor (admin) account without exposing PII in repository';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Enter surveyor email address');
        $name = $this->option('name') ?: $this->ask('Enter surveyor full name');
        $license = $this->option('license') ?: $this->ask('Enter surveyor license number');
        $password = $this->option('password') ?: $this->secret('Enter surveyor password');

        $validator = Validator::make([
            'email'    => $email,
            'name'     => $name,
            'license'  => $license,
            'password' => $password,
        ], [
            'email'    => 'required|email|unique:users,email',
            'name'     => 'required|string|max:255',
            'license'  => 'required|string|max:50',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $surveyor = User::create([
            'name'            => $name,
            'email'           => $email,
            'license_number'  => $license,
            'password'        => Hash::make($password),
            'role'            => 'admin',
            'whatsapp_number' => '0770000000',
            'language'        => 'en',
        ]);

        $this->info("Surveyor account [{$surveyor->email}] created successfully with role 'admin'.");
        return self::SUCCESS;
    }
}

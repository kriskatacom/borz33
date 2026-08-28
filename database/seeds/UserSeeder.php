<?php

declare(strict_types=1);

use App\Database\Factories\UserFactory;
use App\Models\User;
use Phinx\Seed\AbstractSeed;

final class UserSeeder extends AbstractSeed
{
    public function run(): void
    {
        require_once dirname(__DIR__) . '/bootstrap.php';

        $count = max(1, (int) (getenv('USER_SEED_COUNT') ?: 80));

        User::withTrashed()
            ->where('email', 'like', '%@seed.borz33.local')
            ->forceDelete();

        $customers = (int) max(1, round($count * 0.75));
        $admins = max(2, (int) round($count * 0.08));
        $inactive = max(3, (int) round($count * 0.1));
        $trashed = max(3, $count - $customers - $admins - $inactive);

        UserFactory::new()->customer()->state(['is_active' => true, 'deleted_at' => null])->insert($customers);
        UserFactory::new()->admin()->insert($admins);
        UserFactory::new()->inactive()->customer()->insert($inactive);
        UserFactory::new()->trashed()->customer()->insert($trashed);
    }
}

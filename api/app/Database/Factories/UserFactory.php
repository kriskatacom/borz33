<?php

declare(strict_types=1);

namespace App\Database\Factories;

use App\Models\User;
use App\Services\Auth\PasswordHasher;
use DateTimeInterface;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Support\Carbon;

final class UserFactory
{
    public const PASSWORD = 'password';

    private static ?string $passwordHash = null;

    private int $sequence = 0;

    /** @var array<string, mixed> */
    private array $overrides = [];

    private function __construct(
        private readonly Generator $faker
    ) {
    }

    public static function new(): self
    {
        return new self(FakerFactory::create('bg_BG'));
    }

    public static function passwordHash(): string
    {
        return self::$passwordHash ??= (new PasswordHasher())->hash(self::PASSWORD);
    }

    public function admin(): self
    {
        return $this->state([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'deleted_at' => null,
        ]);
    }

    public function customer(): self
    {
        return $this->state(['role' => User::ROLE_CUSTOMER]);
    }

    public function inactive(): self
    {
        return $this->state([
            'is_active' => false,
            'deleted_at' => null,
        ]);
    }

    public function trashed(): self
    {
        return $this->state([
            'deleted_at' => Carbon::now()->subDays(random_int(1, 20)),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    public function state(array $attributes): self
    {
        $clone = clone $this;
        $clone->overrides = array_merge($clone->overrides, $attributes);

        return $clone;
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $this->sequence++;
        $hasLogin = $this->faker->boolean(65);
        $loginAt = $hasLogin ? $this->faker->dateTimeBetween('-80 days', 'now') : null;

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => sprintf('seed.%d.%s@seed.borz33.local', $this->sequence, bin2hex(random_bytes(2))),
            'email_verified_at' => $this->faker->boolean(90) ? Carbon::now() : null,
            'password' => self::passwordHash(),
            'phone' => $this->faker->boolean(75) ? $this->faker->numerify('+359 8# ### ####') : null,
            'role' => $this->faker->boolean(88) ? User::ROLE_CUSTOMER : User::ROLE_ADMIN,
            'is_active' => $this->faker->boolean(86),
            'remember_token' => null,
            'last_login_at' => $loginAt,
            'last_login_ip' => $hasLogin ? $this->faker->ipv4() : null,
            'created_at' => Carbon::now()->subDays(random_int(0, 120)),
            'updated_at' => Carbon::now(),
            'deleted_at' => $this->faker->boolean(8) ? Carbon::now()->subDays(random_int(1, 30)) : null,
        ];
    }

    /** @return array<string, mixed> */
    public function makeOne(): array
    {
        $row = array_merge($this->definition(), $this->overrides);

        foreach (['email_verified_at', 'last_login_at', 'created_at', 'updated_at', 'deleted_at'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = $this->formatTimestamp($row[$field]);
            }
        }

        $row['is_active'] = (int) (bool) $row['is_active'];

        return $row;
    }

    public function create(): User
    {
        $user = new User();
        $user->forceFill($this->makeOne())->save();

        return $user->fresh() ?? $user;
    }

    /** @return list<array<string, mixed>> */
    public function make(int $count): array
    {
        $rows = [];

        for ($index = 0; $index < $count; $index++) {
            $rows[] = $this->makeOne();
        }

        return $rows;
    }

    public function insert(int $count, int $chunkSize = 150): int
    {
        $inserted = 0;

        foreach (array_chunk($this->make($count), max(1, $chunkSize)) as $chunk) {
            User::query()->insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Profile;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Domain\Profile\Entity\Profile;
use Infrastructure\DB\Hydration\CarbonImmutableHydration;
use Infrastructure\Profile\ProfileHydration;
use PHPUnit\Framework\TestCase;

final class ProfileHydrationTest extends TestCase
{
    private ProfileHydration $hydration;

    protected function setUp(): void
    {
        $carbonHydration = new CarbonImmutableHydration();
        $this->hydration = new ProfileHydration($carbonHydration);

        // Mock time to a fixed point for predictable tests
        Carbon::setTestNow('2023-01-01 12:00:00');
    }

    protected function tearDown(): void
    {
        // Reset time mocking after each test
        Carbon::setTestNow();
    }

    public function testDehydrateWithNewProfile(): void
    {
        $createdAt = CarbonImmutable::parse('2023-01-01 10:00:00');

        $profile = new Profile();
        $profile->setName('John Doe');
        $profile->setId('123e4567-e89b-12d3-a456-426614174000');
        $profile->setCreatedAt($createdAt);

        $result = $this->hydration->dehydrate($profile);

        self::assertEquals('123e4567-e89b-12d3-a456-426614174000', $result['id']);
        self::assertEquals('John Doe', $result['name']);
        self::assertEquals('2023-01-01 10:00:00', $result['created_at']);
        self::assertArrayHasKey('updated_at', $result);
        self::assertNull($result['updated_at']);
        self::assertArrayHasKey('deleted_at', $result);
        self::assertNull($result['deleted_at']);
    }

    public function testDehydrateWithUpdatedProfile(): void
    {
        $createdAt = CarbonImmutable::parse('2023-01-01 10:00:00');
        $updatedAt = CarbonImmutable::parse('2023-01-02 15:30:00');

        $profile = new Profile();
        $profile->setName('Jane Smith');
        $profile->setId('456e7890-e89b-12d3-a456-426614174001');
        $profile->setCreatedAt($createdAt);
        $profile->setUpdatedAt($updatedAt);

        $result = $this->hydration->dehydrate($profile);

        self::assertEquals('456e7890-e89b-12d3-a456-426614174001', $result['id']);
        self::assertEquals('Jane Smith', $result['name']);
        self::assertEquals('2023-01-01 10:00:00', $result['created_at']);
        self::assertEquals('2023-01-02 15:30:00', $result['updated_at']);
        self::assertNull($result['deleted_at']);
    }

    public function testHydrateWithCompleteData(): void
    {
        $data = [
            'id'         => '789e0123-e89b-12d3-a456-426614174002',
            'name'       => 'Bob Johnson',
            'created_at' => '2023-03-01 08:15:30',
            'updated_at' => '2023-03-05 14:45:20',
        ];

        $profile = $this->hydration->hydrate($data);

        // PHPStan knows this is Profile from the hydrate return type
        self::assertEquals('789e0123-e89b-12d3-a456-426614174002', $profile->getId());
        self::assertEquals('Bob Johnson', $profile->getName());
        self::assertNotNull($profile->getCreatedAt());
        self::assertEquals('2023-03-01 08:15:30', $profile->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertNotNull($profile->getUpdatedAt());
        self::assertEquals('2023-03-05 14:45:20', $profile->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function testHydrateWithNullUpdatedAt(): void
    {
        $data = [
            'id'         => '012e3456-e89b-12d3-a456-426614174003',
            'name'       => 'Alice Brown',
            'created_at' => '2023-04-01 12:00:00',
            'updated_at' => null,
        ];

        $profile = $this->hydration->hydrate($data);

        self::assertEquals('012e3456-e89b-12d3-a456-426614174003', $profile->getId());
        self::assertEquals('Alice Brown', $profile->getName());
        self::assertNotNull($profile->getCreatedAt());
        self::assertEquals('2023-04-01 12:00:00', $profile->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertNull($profile->getUpdatedAt());
    }

    public function testHydrateWithMissingOptionalFields(): void
    {
        $data = [
            'name' => 'Charlie Wilson',
        ];

        $profile = $this->hydration->hydrate($data);

        self::assertEquals('Charlie Wilson', $profile->getName());
        self::assertNull($profile->getCreatedAt());
        self::assertNull($profile->getUpdatedAt());
        self::assertNull($profile->getDeletedAt());
    }

    public function testDehydrateWithMinimalProfile(): void
    {
        $profile = new Profile();
        $profile->setName('Minimal Profile');

        $result = $this->hydration->dehydrate($profile);

        self::assertNull($result['id']);
        self::assertEquals('Minimal Profile', $result['name']);
        self::assertNull($result['created_at']);
        self::assertNull($result['updated_at']);
        self::assertNull($result['deleted_at']);
    }

    public function testSoftDeleteUsesCurrentTime(): void
    {
        $profile = new Profile();
        $profile->setName('Test Profile');
        $profile->softDelete();

        $result = $this->hydration->dehydrate($profile);

        // Should use the mocked time from setUp()
        self::assertEquals('2023-01-01 12:00:00', $result['deleted_at']);
        self::assertTrue($profile->isDeleted());
    }

    public function testUpdateExistingProfile(): void
    {
        $profile = new Profile();
        $profile->setName('Original Name');
        $profile->setId('345e6789-e89b-12d3-a456-426614174004');

        $updateData = [
            'name'       => 'Updated Name',
            'updated_at' => '2023-05-01 16:20:10',
        ];

        $this->hydration->update($profile, $updateData);

        self::assertEquals('Updated Name', $profile->getName());
        self::assertNotNull($profile->getUpdatedAt());
        self::assertEquals('2023-05-01 16:20:10', $profile->getUpdatedAt()->format('Y-m-d H:i:s'));
        // ID should remain unchanged
        self::assertEquals('345e6789-e89b-12d3-a456-426614174004', $profile->getId());
    }

    public function testDehydrateWithSoftDeletedProfile(): void
    {
        $createdAt = CarbonImmutable::parse('2023-01-01 10:00:00');
        $deletedAt = CarbonImmutable::parse('2023-01-15 14:30:00');

        $profile = new Profile();
        $profile->setName('Deleted User');
        $profile->setId('999e8888-e89b-12d3-a456-426614174999');
        $profile->setCreatedAt($createdAt);
        $profile->setDeletedAt($deletedAt);

        $result = $this->hydration->dehydrate($profile);

        self::assertEquals('999e8888-e89b-12d3-a456-426614174999', $result['id']);
        self::assertEquals('Deleted User', $result['name']);
        self::assertEquals('2023-01-01 10:00:00', $result['created_at']);
        self::assertNull($result['updated_at']);
        self::assertEquals('2023-01-15 14:30:00', $result['deleted_at']);
    }

    public function testHydrateWithSoftDeletedProfile(): void
    {
        $data = [
            'id'         => '888e7777-e89b-12d3-a456-426614174888',
            'name'       => 'Soft Deleted Profile',
            'created_at' => '2023-02-01 09:00:00',
            'updated_at' => null,
            'deleted_at' => '2023-02-15 16:45:00',
        ];

        $profile = $this->hydration->hydrate($data);

        self::assertEquals('888e7777-e89b-12d3-a456-426614174888', $profile->getId());
        self::assertEquals('Soft Deleted Profile', $profile->getName());
        self::assertNotNull($profile->getCreatedAt());
        self::assertEquals('2023-02-01 09:00:00', $profile->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertNull($profile->getUpdatedAt());
        self::assertNotNull($profile->getDeletedAt());
        self::assertEquals('2023-02-15 16:45:00', $profile->getDeletedAt()->format('Y-m-d H:i:s'));
        self::assertTrue($profile->isDeleted());
    }
}

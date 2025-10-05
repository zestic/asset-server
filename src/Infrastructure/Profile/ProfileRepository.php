<?php

declare(strict_types=1);

namespace Infrastructure\Profile;

use Carbon\CarbonImmutable;
use Domain\Profile\Entity\Profile;
use Domain\Profile\Repository\ProfileRepositoryInterface;
use PDO;

final class ProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProfileHydration $hydration
    ) {
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE profiles SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    public function findById(string $id): ?Profile
    {
        $stmt = $this->pdo->prepare('SELECT * FROM profiles WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        if (! $row) {
            return null;
        }

        return $this->hydration->hydrate($row);
    }

    public function restore(string $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE profiles SET deleted_at = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function save(Profile $profile): void
    {
        if (empty($profile->getId())) {
            $this->insert($profile);
        } else {
            $this->update($profile);
        }
    }

    private function insert(Profile $profile): void
    {
        $data = $this->hydration->dehydrate($profile);

        $stmt = $this->pdo->prepare(
            'INSERT INTO profiles (name) VALUES (:name) RETURNING id, created_at'
        );

        $stmt->execute(['name' => $data['name']]);

        $result = $stmt->fetch();
        $profile->setId($result['id']);
        $profile->setCreatedAt(CarbonImmutable::parse($result['created_at']));
    }

    private function update(Profile $profile): void
    {
        $data = $this->hydration->dehydrate($profile);

        $stmt = $this->pdo->prepare(
            'UPDATE profiles SET name = :name WHERE id = :id RETURNING updated_at'
        );

        $stmt->execute([
            'name' => $data['name'],
            'id'   => $data['id'],
        ]);

        $result = $stmt->fetch();
        if ($result) {
            $profile->setUpdatedAt(CarbonImmutable::parse($result['updated_at']));
        }
    }
}

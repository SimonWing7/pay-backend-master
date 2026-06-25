<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class GroupService extends Service
{
    public function getAllByMerchant(int $merchantId): Collection
    {
        return Group::where('merchant_id', $merchantId)->get();
    }

    public function getById(int $id, ?int $merchantId = null): ?Group
    {
        $query = Group::where('id', $id);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->first();
    }

    public function create(array $data): Group
    {
        return Group::create($data);
    }

    public function update(Group $group, array $data): Group
    {
        $group->update($data);
        return $group->fresh();
    }

    public function delete(Group $group): bool
    {
        return $group->delete();
    }
}


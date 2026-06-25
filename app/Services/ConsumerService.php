<?php

namespace App\Services;

use App\Models\Consumer;
use Illuminate\Database\Eloquent\Collection;

class ConsumerService extends Service
{
    public function getAllByMerchant(int $merchantId, array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 15)
    {
        $query = Consumer::where('merchant_id', $merchantId)->with('groups');

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        if (isset($filters['group_id'])) {
            $query->whereHas('groups', function ($q) use ($filters) {
                $q->where('groups.id', $filters['group_id']);
            });
        }

        // Apply sorting
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        return $query->paginate($perPage)->withQueryString();
    }

    public function getById(int $id, ?int $merchantId = null): ?Consumer
    {
        $query = Consumer::where('id', $id);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->with('groups')->first();
    }

    public function create(array $data): Consumer
    {
        $groupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);

        $consumer = Consumer::create($data);
        
        if (!empty($groupIds)) {
            $consumer->groups()->sync($groupIds);
        }

        return $consumer->load('groups');
    }

    public function update(Consumer $consumer, array $data): Consumer
    {
        $groupIds = $data['group_ids'] ?? null;
        unset($data['group_ids']);

        $consumer->update($data);
        
        if ($groupIds !== null) {
            $consumer->groups()->sync($groupIds);
        }

        return $consumer->fresh()->load('groups');
    }

    public function delete(Consumer $consumer): bool
    {
        return $consumer->delete();
    }
}


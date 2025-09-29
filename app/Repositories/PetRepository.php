<?php

namespace App\Repositories;

use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PetRepository implements PetRepositoryInterface
{
    protected $model;

    public function __construct()
    {
        $this->model = new Pet();
    }

    public function getAllWithBranch()
    {
        return $this->model->with('branch')->get();
    }

    public function getPaginatedPets(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $query = $this->model->query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('pet_name', 'like', '%' . $search . '%')
                    ->orWhere('pet_species', 'like', '%' . $search . '%')
                    ->orWhere('pet_breed', 'like', '%' . $search . '%');
            });
        }

        if ($perPage === 'all') {
            $allPets = $query->get();
            $total = $allPets->count();
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            return new LengthAwarePaginator(
                $allPets,
                $total,
                $total,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return $query->paginate((int)$perPage)->appends(['perPage' => $perPage]);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $pet = $this->findById($id);
        $pet->update($data);
        return $pet;
    }

    public function delete(int $id)
    {
        return $this->model->destroy($id) > 0;
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }
}
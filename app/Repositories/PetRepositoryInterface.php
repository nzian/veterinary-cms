<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\Pet;

interface PetRepositoryInterface
{
    public function getAllWithBranch();
    public function getPaginatedPets(Request $request);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function findById(int $id);
}
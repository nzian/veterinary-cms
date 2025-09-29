<?php

namespace App\Services;

class PetValidationService
{
    /**
     * Get validation rules for creating a pet
     *
     * @return array
     */
    public static function createRules(): array
    {
        return [
            'pet_name' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
            'pet_weight' => 'required|numeric|min:0|max:200',
            'pet_species' => 'required|string|in:Dog,Cat',
            'pet_breed' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
            'pet_age' => [
                'required',
                'regex:/^[0-9]+\s?(month|months|year|years)(\s[0-9]+\s?(month|months))?$/i',
            ],
            'pet_gender' => 'required|in:Male,Female',
            'pet_temperature' => 'required|numeric|min:30|max:45',
            'pet_registration' => 'required|date',
            'own_id' => 'required|exists:tbl_own,own_id',
        ];
    }

    /**
     * Get validation rules for updating a pet
     *
     * @return array
     */
    public static function updateRules(): array
    {
        return [
            'pet_name' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
            'pet_weight' => 'nullable|numeric|min:0|max:200',
            'pet_species' => 'required|string|in:Dog,Cat',
            'pet_breed' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
            'pet_age' => [
                'required',
                'regex:/^[0-9]+\s?(month|months|year|years)(\s[0-9]+\s?(month|months))?$/i',
            ],
            'pet_gender' => 'required|in:Male,Female',
            'pet_temperature' => 'nullable|numeric|min:30|max:45',
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    public static function messages(): array
    {
        return [
            'pet_name.regex' => 'Pet name must only contain letters, numbers, spaces, or dashes.',
            'pet_breed.regex' => 'Breed must only contain letters, numbers, spaces, or dashes.',
            'pet_age.regex' => 'Age format must be like: "3 months", "1 year", or "1 year 2 months".',
            'pet_species.in' => 'Species must be Dog or Cat.',
            'pet_gender.in' => 'Gender must be Male or Female.',
            'pet_temperature.min' => 'Temperature must be realistic (minimum 30°C).',
            'pet_temperature.max' => 'Temperature must be realistic (maximum 45°C).',
            'own_id.exists' => 'Selected owner does not exist.',
        ];
    }
}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pet->pet_name }} - Health Card</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Define the Custom Colors for easier use */
        .color-blue-main { color: #0f7ea0; }
        .bg-blue-light { background-color: #f0f8ff; }
        .bg-blue-main { background-color: #0f7ea0; }
        .color-orange-main { color: #ea580c; } /* Tailwind orange-600 */
        .bg-orange-light { background-color: #fff7ed; }

        /*
         * Print-specific styles for A4 trifold layout
         */
        @page {
            size: 11in 8.5in; /* US Letter Landscape for trifold standard */
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .trifold-page {
            width: 11in;
            height: 8.5in;
            page-break-after: always;
            box-shadow: 0 0 0.5in rgba(0, 0, 0, 0.1);
        }

        .trifold-page:last-child {
            page-break-after: avoid;
        }

        /* Dashed divider line for print */
        .border-dashed-print {
            border-right: 2px dashed #9ca3af; /* gray-400 */
        }
        
        /* Dynamic print styles */
        .print-input {
            border: none;
            padding: 0;
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #1f2937; 
            background-color: transparent;
        }
        
        /* Force colors to print */
        .bg-blue-main, .color-blue-main, .bg-orange-main, .color-orange-main, .bg-gray-100 {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Table header styles */
        .table-header-blue th {
            background-color: #0f7ea0; 
            color: white;
            font-weight: bold;
        }
        
        /* --- START FIX FOR FULL TABLE HEIGHT AND SPACING --- */
        /* This class is used for the container around the tables (Deworming & Vaccination) */
        .record-panel {
            height: 100%;
            overflow-y: hidden;
            flex: 1; /* Ensures it takes up all remaining vertical space in its column */
        }

        .table-full-height {
            height: 100%;
            table-layout: fixed; /* Ensures column widths are respected */
        }

        .table-full-height tbody {
            height: 100%;
            display: table-row-group; /* Forces tbody to participate in table height calculation */
        }

        .table-full-height tr {
            /* Evenly distributes the 10 rows vertically */
            height: calc(100% / 10);
            min-height: 48px; /* Fallback min height for space */
        }

        .table-full-height td {
            vertical-align: middle; /* Centers content vertically */
            /* Add significant padding for sticker space, overriding default p-1 */
            padding-top: 0.75rem !important; /* py-3 equivalent */
            padding-bottom: 0.75rem !important; /* py-3 equivalent */
            padding-left: 0.25rem !important; /* px-1 equivalent */
            padding-right: 0.25rem !important; /* px-1 equivalent */
        }
        /* --- END FIX FOR FULL TABLE HEIGHT AND SPACING --- */
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body>

    {{-- ================================================================= --}}
    {{-- FRONT SIDE (When folded: Panel 1 - Back Cover, Panel 2 - Inside, Panel 3 - Front Cover) --}}
    {{-- ================================================================= --}}
    <div class="trifold-page bg-white shadow-lg">
        <div class="grid grid-cols-3 h-full">
            
            {{-- PANEL 1: BACK FOLD (Deworming/Heartworm History) --}}
            <div class="border-dashed-print p-6 flex flex-col bg-blue-light">
                <div class="mb-4">
                    <h4 class="color-orange-main font-bold text-xl mb-3 border-b border-orange-300 pb-2">
                        <i class="fas fa-pills mr-2"></i> DEWORMING HISTORY & HEARTWORM PREVENTION
                    </h4>
                </div>

                {{-- **FIXED** Using record-panel and table-full-height classes --}}
                <div class="record-panel"> 
                    <table class="w-full border-collapse text-xs table-full-height">
                        <thead>
                            <tr class="table-header-blue">
                                <th class="border border-gray-400 p-1 text-center" style="width: 25%">DATE</th>
                                <th class="border border-gray-400 p-1 text-center" style="width: 20%">WEIGHT</th>
                                <th class="border border-gray-400 p-1 text-center" style="width: 55%">MANUFACTURER</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $dewormingMaxRows = 10; // Fixed to 10 rows
                                $dewormingDataCount = count($deworming); 
                            @endphp
                            
                            {{-- Data loop --}}
                            @foreach($deworming as $record)
                                <tr>
                                    <td class="border border-gray-400 px-1">{{ \Carbon\Carbon::parse($record->visit_date)->format('M d, Y') }}</td>
                                    <td class="border border-gray-400 px-1">{{ $record->weight ?? '--' }} kg</td>
                                    
                                    {{-- Manufacturer/Sticker column --}}
                                    <td class="border border-gray-400 px-1">
                                        {{ Str::limit($record->treatment, 35) }}
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- Filler rows --}}
                            @for($i = $dewormingDataCount; $i < $dewormingMaxRows; $i++)
                                <tr>
                                    <td class="border border-gray-400"></td>
                                    <td class="border border-gray-400"></td>
                                    <td class="border border-gray-400"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PANEL 2: INNER FOLD (Notes) --}}
            <div class="border-dashed-print p-6 flex flex-col bg-white">
                <h3 class="color-blue-main font-bold text-xl mb-4 border-b border-blue-300 pb-2">
                    <i class="fas fa-clipboard-list mr-2"></i> NOTES / MEMO
                </h3>
                <div class="border border-gray-300 flex-1 p-3 space-y-2 text-sm overflow-hidden">
                    @for($i = 0; $i < 20; $i++)
                        <div class="border-b border-gray-200 h-5"></div>
                    @endfor
                </div>
            </div>

            {{-- PANEL 3: FRONT COVER (Logo, Pet Name, Clinic Info) --}}
            <div class="p-6 flex flex-col justify-between bg-orange-light border-l-4 border-orange-600">
                <div class="text-center">
                    <div class="mb-6">
                        <br><br>
                        <h4 class="text-2xl font-extrabold color-blue-main mb-1">
                            PET HEALTH CARD
                        </h4>
                        {{-- Assuming asset('images/pets2go.png') is a valid path to your logo --}}
                        <img src="{{ $clinicInfo['logo_url'] ?? asset('images/default_logo.png') }}" alt="Clinic Logo" 
                             class="max-h-30 object-contain mx-auto mb-4 p-1">
                    </div>

                    <div class="p-5 bg-white rounded-lg shadow-inner border border-gray-200">
                        <label class="text-sm color-orange-main block font-bold mb-1">PET'S NAME</label>
                        <div class="print-input text-3xl font-black color-blue-main uppercase border-b-2 border-dashed border-blue-300 pb-2">
                            {{ $pet->pet_name }}
                        </div>
                        <div class="mt-4 flex justify-between text-sm">
                            <div class="text-left">
                                <label class="text-xs text-gray-500 block">PET ID</label>
                                <span class="print-input font-bold color-orange-main">{{ $pet->pet_id }}</span>
                            </div>
                            <div class="text-right">
                                <label class="text-xs text-gray-500 block">ISSUED DATE</label>
                                <span class="print-input font-bold color-blue-main">{{ now()->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t-2 border-blue-main pt-4 space-y-2 text-xs text-center">
                    <h4 class="color-blue-main font-bold text-sm mb-2">CLINIC CONTACT DETAILS</h4>
                    
                    @forelse ($branches as $branch)
                        <div class="space-y-0.5 mb-2">
                            <p class="font-bold color-orange-main uppercase text-sm leading-none">{{ $branch->branch_name }}</p>
                            <p class="text-gray-700 leading-tight">{{ $branch->branch_address }}</p>
                            <p class="font-semibold color-blue-main leading-tight">Tel: {{ $branch->branch_contactNum }}</p>
                        </div>
                        @if (!$loop->last)
                            <div class="h-px bg-gray-300 w-1/2 mx-auto"></div>
                        @endif
                    @empty
                        <p class="font-bold color-blue-main text-sm">
                            Contact: {{ $clinicInfo['contact'] ?? '___________________________' }}
                        </p>
                        <p class="text-gray-600">Branch details not yet configured.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================= --}}
    {{-- BACK SIDE (When folded: Panel 4 - Pet/Owner Info, Panel 5 & 6 - Vaccination Record) --}}
    {{-- ================================================================= --}}
    <div class="trifold-page bg-white shadow-lg">
        <div class="grid grid-cols-3 h-full">
            
            {{-- PANEL 4: PET AND OWNER INFORMATION --}}
            <div class="border-dashed-print p-6 flex flex-col bg-orange-light">
                <div class="mb-4 text-center">
                    <h3 class="color-blue-main font-bold text-xl mb-4 border-b border-blue-300 pb-2">
                        <i class="fas fa-user-friends mr-2"></i> PET OWNER RECORD
                    </h3>
                    <br><br>
                    @if($pet->pet_photo)
                        <img src="{{ asset('storage/' . $pet->pet_photo) }}" alt="Pet Photo" 
                             class="w-32 h-32 object-cover mx-auto border-4 border-orange-600 mb-4 rounded-full shadow-md">
                    @else
                        <div class="w-32 h-32 mx-auto border-4 border-gray-300 bg-gray-50 flex items-center justify-center mb-4 rounded-full">
                            <span class="text-gray-400 text-xs text-center">PET PHOTO</span>
                        </div>
                    @endif
                </div>

                <div class="space-y-3 flex-1 text-sm">
                    <h4 class="font-bold color-orange-main border-b border-orange-300 pb-1">PET DETAILS</h4>
                    <div class="space-y-1">
                        <p class="font-semibold text-gray-800">{{ $pet->pet_species }} / {{ $pet->pet_breed }}</p>
                        <p class="text-gray-600 text-xs">Species / Breed</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-gray-800">
                            {{ $pet->pet_birthdate ? \Carbon\Carbon::parse($pet->pet_birthdate)->format('M d, Y') : 'N/A' }} 
                            ({{ $pet->pet_age }})
                        </p>
                        <p class="text-gray-600 text-xs">Date of Birth / Age</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-gray-800">{{ $pet->pet_gender }}</p>
                        <p class="text-gray-600 text-xs">Gender</p>
                    </div>
                    
                    <h4 class="font-bold color-orange-main border-b border-orange-300 pt-3 pb-1">OWNER DETAILS</h4>
                    <div class="space-y-1">
                        <p class="font-semibold color-blue-main">{{ $pet->owner->own_name ?? 'N/A' }}</p>
                        <p class="text-gray-600 text-xs">Owner Name</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-gray-800">{{ $pet->owner->own_contactnum ?? 'N/A' }}</p>
                        <p class="text-gray-600 text-xs">Contact No.</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-gray-800">{{ $pet->owner->own_location ?? 'N/A' }}</p>
                        <p class="text-gray-600 text-xs">Address</p>
                    </div>
                </div>
            </div>

            {{-- PANEL 5 & 6 (Combined): Vaccination Record (2/3 width) --}}
            <div class="col-span-2 p-6 flex flex-col bg-blue-light">
                <div class="mb-4">
                    <h3 class="color-orange-main font-bold text-xl mb-3 border-b border-orange-300 pb-2 text-center">
                        <i class="fas fa-syringe mr-2"></i> VACCINATION RECORD
                    </h3>
                </div>

                <div class="record-panel">
                    <table class="w-full border-collapse text-xs table-full-height">
                        <thead>
                            <tr class="table-header-blue">
                                <th class="border border-gray-400 p-1 text-center" style="width: 15%">DATE GIVEN</th>
                                <th class="border border-gray-400 p-1 text-center" style="width: 25%">AGAINST</th>
                                
                                <th class="border border-gray-400 p-1 text-center" style="width: 30%">MANUFACTURER</th>
                                
                                <th class="border border-gray-400 p-1 text-center" style="width: 15%">DATE DUE</th>
                                <th class="border border-gray-400 p-1 text-center" style="width: 20%">VETERINARIAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // We MUST ensure exactly 10 rows are generated.
                                $maxRows = 10;
                                $dataCount = count($vaccinations);
                            @endphp
                            
                            {{-- 1. Display actual records --}}
                            @foreach($vaccinations as $record)
                                <tr>
                                    {{-- Rely on .table-full-height td for padding/height --}}
                                    <td class="border border-gray-400 px-1">{{ \Carbon\Carbon::parse($record->visit_date)->format('M d, Y') }}</td>
                                    <td class="border border-gray-400 px-1">{{ Str::limit($record->diagnosis, 30) }}</td>
                                    
                                    {{-- Empty space for the sticker --}}
                                    <td class="border border-gray-400 px-1"></td> 
                                    
                                    <td class="border border-gray-400 px-1 font-bold color-orange-main">
                                        {{ $record->follow_up_date ? \Carbon\Carbon::parse($record->follow_up_date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="border border-gray-400 px-1">{{ Str::limit($record->veterinarian_name, 10) }}</td>
                                </tr>
                            @endforeach

                            {{-- 2. Fill remaining rows up to 10 --}}
                            @for($i = $dataCount; $i < $maxRows; $i++)
                                <tr>
                                    {{-- Rely on .table-full-height td to provide height and padding --}}
                                    <td class="border border-gray-400"></td> 
                                    <td class="border border-gray-400"></td>
                                    <td class="border border-gray-400"></td>
                                    <td class="border border-gray-400"></td>
                                    <td class="border border-gray-400"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Apply the necessary height settings for the print layout to work
        document.addEventListener('DOMContentLoaded', function() {
            const panels = document.querySelectorAll('.record-panel');
            panels.forEach(panel => {
                // Set the height of the wrapper to utilize remaining space within the flex-col parent
                panel.style.flex = '1';
                panel.style.height = '100%'; // Ensure flex container utilizes all available height
            });
        });
        
        window.onload = function() {
            // Give a slight delay for all assets (like images) to load before printing
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
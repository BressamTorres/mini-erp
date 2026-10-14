<?php

namespace App\Http\Controllers;

use App\Services\PersonService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonPhotoController extends Controller
{
    public function __construct(private PersonService $persons) {}

    public function show(int $person): StreamedResponse
    {
        return $this->persons->photo($person);
    }
}

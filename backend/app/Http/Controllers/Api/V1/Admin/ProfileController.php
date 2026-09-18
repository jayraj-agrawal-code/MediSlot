<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Return the currently authenticated admin.
     */
    public function show(Request $request): AdminResource
    {
        return AdminResource::make($request->user('admin'));
    }
}

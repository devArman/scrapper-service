<?php

namespace App\Http\Controllers;


use App\Http\Requests\ResearchStoreRequest;
use App\Jobs\Scrapper\ScrapAmazon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ResearchController extends Controller
{
    /**
     * @return void
     */
    public function index()
    {
//
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
//
    }


    /**
     * @param ResearchStoreRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ResearchStoreRequest $request)
    {
        $data = [];
        if ($request->has('url')){
            $data['url'] = $request->get('url');

        }elseif ($request->has('categoryId')){
            $data['category_id'] = $request->get('categoryId');
        }
        $data['pages'] = $request->get('pages',1);
//        $research = Auth::user()->researches()->create(
        $research = User::find(1)->researches()->create(
            [
                'name' => $request->get('name'),
                'data' => $data,
                'account_id' => $request->get('accountId'),
            ]
        );
        $scanJob = (new ScrapAmazon($research->id))->onQueue('default');
        $this->dispatch($scanJob);

        return Redirect::route('research')->with('success',  "{$research->name} created successfully.");
    }

    /**
     * @return void
     */
    public function show()
    {
        //
    }

    /**
     * @return void
     */
    public function edit()
    {
//
    }

}

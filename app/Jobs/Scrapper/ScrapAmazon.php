<?php

namespace App\Jobs\Scrapper;

use App\Models\Research;
use App\Services\AmazonCategoryScrapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapAmazon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $totalPages = 1;
    private $researchId = null;

    /**
     * Create a new job instance.
     *
     * @param $researchId
     */
    public function __construct($researchId)
    {
        $this->researchId = $researchId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = time();

        $research = Research::find($this->researchId);
        if (!empty($research)){
            if (!empty($research->data['pages'])){
                $this->totalPages = $research->data['pages'];
            }
            $amazonCategoryScrapper = new AmazonCategoryScrapper($research);
            if (!empty($research['data']['url'])){
                $data = $amazonCategoryScrapper->scrapUrlPaginate(1,$this->totalPages,[]);
            }elseif (!empty($research['data']['category_id'])){
                $data = $amazonCategoryScrapper->scrapCategoryPaginate(1,$this->totalPages,[]);
            }
        }



        $totalTime = time() - $startTime;

        return $totalTime;
    }
}

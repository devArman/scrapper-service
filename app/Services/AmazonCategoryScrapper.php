<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 10/27/2021
 * Time: 1:41 PM
 */

namespace App\Services;


use App\Jobs\Scrapper\ScrapAmazonSingleProduct;
use App\Models\Product;

class AmazonCategoryScrapper
{

    private $research = null;
    public function __construct($research)
    {
        $this->research = $research;
    }

    private $categoryId = null;
    private $page = 1;

    private function getClient(){
        return (new ScrapperClient())->getClient();
    }

    /**
     * @param $node
     * @return array
     */
    private function scrapOneCategoryProduct($node)
    {

        $tmp_dom = new \DOMDocument();

        $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        $innterFinder = new \DOMXPath($tmp_dom);

        $asinQuery = $innterFinder->query('//div');
        $asin = $asinQuery->item(0)->getAttribute('data-asin');
        if (empty($asin)) {
            return [];
        }
        $priceQuery = $innterFinder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'a-offscreen')]");
        $imageQuery = $innterFinder->query('//img');
        $nameQuery = $innterFinder->query('//h2/a/span');
        $ratingQuery = $innterFinder->query('//a/i/span');
        $totalReviewsQuery = $innterFinder->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'a-size-small')]/span[@aria-label][last()]");
        $isPrimeQuery = $innterFinder->query('//i[@aria-label="Amazon Prime"][last()]');
        $url = "https://www.amazon.co.uk/dp/{$asin}/ref=sspa_dk_detail_3&th=1&psc=1?th=1&psc=1";
        $name = null;
        if (!empty($nameQuery->item(0))) { //        output.title = $(`span[id="productTitle"]`).text() || $('.qa-title-text').text();

            $name = strip_tags($nameQuery->item(0)->C14N());
            $name = htmlspecialchars_decode(trim(str_replace(["\n\n", "\r\r"], '', $name)));
        }
        $totalReviews = 0;
        if (!empty($totalReviewsQuery->item(0))) { //        output.title = $(`span[id="productTitle"]`).text() || $('.qa-title-text').text();

            $totalReviews = $totalReviewsQuery->item(0)->getAttribute('aria-label');
            $totalReviews = (int)str_replace(',', '.', $totalReviews);
        }
        $productDetails = [
            'name' => $name,
            'asin' => $asin,
            'research_id' => $this->research['id'],
            'cat_id' => $this->categoryId,
            'amazon_product_list_scrapped' => true,
            'on_amazon_page' => $this->page,
            'url' => $url,
            'price' => (!empty($priceQuery->item(0))) ?  str_replace("£", '', strip_tags($priceQuery->item(0)->C14N())) : null,
            'image' => (!empty($imageQuery->item(0))) ? $imageQuery->item(0)->getAttribute('src') : null,
            'prime' => (!empty($isPrimeQuery->item(0))) ? true : false,
            'rating' => (!empty($ratingQuery->item(0))) ? (float)substr(strip_tags($ratingQuery->item(0)->C14N()), 0, 3) : null,
            'total_reviews' => $totalReviews,
        ];
        $product = Product::updateOrCreate(['asin'=>$asin],$productDetails);
        if (!$product->amazon_single_product_scrapped){
            $singleProductJob = (new ScrapAmazonSingleProduct($asin))->onQueue('low');
            dispatch($singleProductJob);
        }


        return $product;
    }

    /**
     * @param $categoryAmazonId
     * @param int $page
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function scrapCategoryPageByAmazonId( $page = 1)
    {

        $this->page = $page;
        if (empty($this->research['data']['category_id'])){
            return [];
        }

        $this->categoryId = $this->research['data']['category_id'];
        if ($page == 1) {
            $url = "https://www.amazon.co.uk/b?node={$this->research['data']['category_id']}";
            $url = "https://www.amazon.co.uk/s?rh=n:{$this->research['data']['category_id']}&fs=true&ref=lp_{$this->research['data']['category_id']}_sar";
        } else {
            $url = "https://www.amazon.co.uk/s?rh=n%3A{$this->research['data']['category_id']}&_encoding=UTF8&c=ts&ts_id={$this->research['data']['category_id']}&ref=sr_pg_{$page}&page={$page}";
        }
        $client = $this->getClient();
        $rr = $client->get($url);
        $body = $rr->getBody()->getContents();
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        @$doc->loadHTML($body);


        $className = 's-result-item';
        $finder = new \DOMXPath($doc);

        $totalPagesQuery = $finder->query("//ul[@class=\"a-pagination\"]/li[@class=\"a-disabled\"][last()]");
        if (!empty($totalPagesQuery->item(0))){
            $totalPages = strip_tags($totalPagesQuery->item(0)->C14N());
        }elseif (!empty($finder->query("//ul[@class=\"a-pagination\"]/li[@class=\"a-normal\"][last()]")->item(0))){
            $totalPages = strip_tags($finder->query("//ul[@class=\"a-pagination\"]/li[@class=\"a-normal\"][last()]")->item(0)->C14N());
        }else{
            $totalPages = 0;

        }
        $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' s-result-item ')]");
//        dd($nodes->item(0)->C14N());
        $data = [];
        foreach ($nodes as $key => $node) {
            $item = $this->scrapOneCategoryProduct($node);

            if (!empty($item)) {
                $item['page'] = $page;
                $item['category'] = $this->research['data']['category_id'];
                $data[$item['asin']] = $item;
            }

        }


        return [
            'page' => $page,
            'total_pages' => $totalPages,
            'data' => $data
        ];
    }


    public function scrapUrlPageByAmazonId($page){
        $this->page = $page;
        if (empty($this->research['data']['url'])){
            return [];
        }
        $url = $this->research['data']['url'];
        // https://www.amazon.co.uk/s?keywords=Men%27s+Eau+de+Toilette&i=beauty&rh=n%3A2790130031%2Cp_72%3A184329031%2Cp_36%3A122961031%2Cp_89%3ACalvin+Klein%7CCarolina+Herrera%7CFerrari%7CHugo+Boss&dc&c=ts&qid=1643278539&rnid=1632651031&ts_id=2790130031&ref=sr_nr_p_89_15
        parse_str(parse_url($url, PHP_URL_QUERY),$urlQuery);
        $this->categoryId = null;


        if ($page > 1){
            $urlHost = parse_url($url, PHP_URL_HOST);
            $urlPath = parse_url($url, PHP_URL_PATH);
            $urlQuery['ref'] = "sr_pg_{$page}";
            $urlQuery['page'] = $page;
            $queryEncode = http_build_query($urlQuery);
            $url = "https://{$urlHost}{$urlPath}?{$queryEncode}";

        }
        $client = $this->getClient();
        $rr = $client->get($url);
        $body = $rr->getBody()->getContents();
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        @$doc->loadHTML($body);


        $className = 's-result-item';
        $finder = new \DOMXPath($doc);

        $totalPagesQuery = $finder->query("//ul[@class=\"a-pagination\"]/li[@class=\"a-disabled\"][last()]");
        if (!empty($totalPagesQuery->item(0))){
            $totalPages = strip_tags($totalPagesQuery->item(0)->C14N());
        }elseif (!empty($finder->query("//ul[@class=\"a-pagination\"]/li[@class=\"a-normal\"][last()]")->item(0))){
            $totalPages = strip_tags($finder->query("//ul[@class=\"a-pagination\"]/li[@class=\"a-normal\"][last()]")->item(0)->C14N());
        }else{
            $totalPages = 0;

        }
        $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' s-result-item ')]");
//        dd($nodes->item(0)->C14N());
        $data = [];
        foreach ($nodes as $key => $node) {
            $item = $this->scrapOneCategoryProduct($node);

            if (!empty($item)) {
                $item['page'] = $page;
                $item['category'] = $this->categoryId;
                $data[$item['asin']] = $item;
            }

        }


        return [
            'page' => $page,
            'total_pages' => $totalPages,
            'data' => $data
        ];
    }

    public function scrapCategoryPaginate($currentPage=1, $totalPages = 2,$data = []){

        if ($currentPage <=$totalPages){
            $pageData = $this->scrapCategoryPageByAmazonId($currentPage);
            $data = array_merge($data,$pageData['data']);
            $currentPage++;
            return $this->scrapCategoryPaginate($currentPage,$totalPages,$data);
        }

        return $data;

    }
    public function scrapUrlPaginate($currentPage=1, $totalPages = 2,$data = []){

        echo $currentPage;
        if ($currentPage <=$totalPages){
            $pageData = $this->scrapUrlPageByAmazonId($currentPage);
            if (empty($pageData['data'])){
                return $data;
            }
            $data = array_merge($data,$pageData['data']);
            $currentPage++;
            return $this->scrapUrlPaginate($currentPage,$totalPages,$data);
        }
        return $data;

    }
}

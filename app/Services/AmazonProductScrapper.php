<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 11/10/2021
 * Time: 11:00 AM
 */

namespace App\Services;


use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImages;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\IFTTTHandler;

class AmazonProductScrapper
{

    private function getClient()
    {
        return (new ScrapperClient())->getClient();
    }

    public function loadProductAsin($asin = null)
    {
        if (empty($asin)) {
            throw new \Exception('Product asin is required!');
        }
        $url = "https://www.amazon.co.uk/dp/{$asin}/ref=sspa_dk_detail_3&th=1&psc=1?th=1&psc=1";
//        $url = "https://ipapi.co/json/";
//        $url = "https://www.amazon.co.uk/gp/aod/ajax/?asin=B08518GFFJ&filters=%257B%2522all%2522%253Atrue%252C%2522new%2522%253Atrue%252C%2522primeEligible%2522%253Atrue%257D&isonlyrenderofferlist=false&pageno=1";
        $client = $this->getClient();
        $rr = $client->get($url);
        $body = $rr->getBody()->getContents();
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        @$doc->loadHTML($body);

        $innterFinder = new \DOMXPath($doc);
        $name = null;
        if (!empty($innterFinder->query('//*[@id="productTitle"]')->item(0))) { //        output.title = $(`span[id="productTitle"]`).text() || $('.qa-title-text').text();

            $name = strip_tags($innterFinder->query('//*[@id="productTitle"]')->item(0)->C14N());
            $name = htmlspecialchars_decode(trim(str_replace(["\n\n", "\r\r"], '', $name)));
        }

        $price = null;
        if (!empty($innterFinder->query('//*[@id="price"]')->item(0))) {
            $price = strip_tags($innterFinder->query('//*[@id="price"]')->item(0)->C14N());

        } elseif (!empty($innterFinder->query("//span[contains(concat(' ', normalize-space(@class), ' '), 'apexPriceToPay')]/span")->item(0))) {
            $price = strip_tags($innterFinder->query("//span[contains(concat(' ', normalize-space(@class), ' '), 'apexPriceToPay')]/span")->item(0)->C14N());
        }elseif (!empty($innterFinder->query('//*[@id="corePrice_feature_div"]/div/span/span[1]')->item(0))) {
            $price = strip_tags($innterFinder->query('//*[@id="corePrice_feature_div"]/div/span/span[1]')->item(0)->C14N());
        }


        $description = '';
        if (!empty($innterFinder->query('//*[@id="productDescription"]')->item(0))) {
            $description = strip_tags($innterFinder->query('//*[@id="productDescription"]')->item(0)->C14N());
        } elseif (!empty($innterFinder->query('//*[@id="bookDescription_feature_div"]')->item(0))) {
            $description = strip_tags($innterFinder->query('//*[@id="bookDescription_feature_div"]')->item(0)->C14N());

        }
        $feature_bullets = $this->extractProductFeatures($innterFinder);
        if (!empty($innterFinder->query('//*[@id="availability"]/span')->item(0))){
            $availability = trim(strip_tags($innterFinder->query('//*[@id="availability"]/span')->item(0)->C14N()));
            $availability = preg_split('/\r\n|\r|\n/', $availability);
            if (!empty($availability)){
                $availability = $availability[0];
            }
        }else{
            $availability = "Out of stock";

        }

        if (!empty($description)) {
            $description = htmlspecialchars_decode(trim(str_replace(["\n\n", "\r\r"], '', $description)));
        }
        $rating = null;
        if (!empty($innterFinder->query('//*[@id="acrPopover"]')->item(0))) {
            $rating = $innterFinder->query('//*[@id="acrPopover"]')->item(0)->getAttribute('title');
            $rating = substr($rating, 0, 3);

        }
        $totalReviews = 0;
        if (!empty($innterFinder->query('//*[@id="acrCustomerReviewText"]')->item(0))) {
            $totalReviews = trim(strip_tags($innterFinder->query('//*[@id="acrCustomerReviewText"]')->item(0)->C14N()));
            $totalReviews = trim(str_replace('ratings', '', $totalReviews));
            $totalReviews = trim(str_replace('rating', '', $totalReviews));
            $totalReviews = str_replace(',', '', $totalReviews);
            $totalReviews = preg_replace('/\D/', '', $totalReviews);
        }
        $seller = [];
        $sellerInfoNodes = $innterFinder->query('//*[@id="tabular-buybox"]/div/div[@class="tabular-buybox-text"]');
        if (!empty($sellerInfoNodes) && count($sellerInfoNodes) > 1){

            $seller['dispatches_from'] = trim(strip_tags($sellerInfoNodes->item(0)->C14N()));
            $seller['sold_by'] = trim(strip_tags($sellerInfoNodes->item(1)->C14N()));
        }else{
            $sellerInfoNodes = $innterFinder->query('//*[@id="shipsFromSoldByMessage_feature_div"]/div/span');
            if (!empty($sellerInfoNodes->item(0)) && strpos($sellerInfoNodes->item(0)->C14N(), 'Dispatched from and sold by Amazon') !== false){
                $seller['dispatches_from'] = "Amazon";
                $seller['sold_by'] = "Amazon";
            }
        }
//            $soldBy = trim(strip_tags($innterFinder->query('//*[@id="acrCustomerReviewText"]')->item(0)->C14N()));
////            $totalReviews = trim(str_replace('ratings', '', $totalReviews));
//
//        }

        $amazonChoice = 0;
        if ($innterFinder->query('//*[@id="acBadge_feature_div"]/div')->item(0)) {
            $amazonChoice = 1;

        }
        $images = $this->extractImages($body);
        $categories = $this->extractProductCategories($innterFinder);
        $variants = $this->extractProductVariants($innterFinder);
        $ebayProducts = $this->extractSimilarProductsFromEbay($name,$price);
        $url = "https://www.amazon.co.uk/dp/{$asin}/ref=sspa_dk_detail_3&th=1&psc=1?th=1&psc=1";

        $productData = [
            'asin' => $asin,
            'name' => $name,
            'url' => $url,
            'amazon_single_product_scrapped' => true,
            'currency' => "GBP",
            'description' => $description,
            'feature_bullets' => $feature_bullets,
            'availability' => $availability,
            'rating' => $rating,
            'total_reviews' => $totalReviews,
            'amazon_choice' => $amazonChoice,
            'dispatches_from' => $seller['dispatches_from'],
            'sold_by' => $seller['sold_by'],
            'variants' => json_encode($variants),
            'possible_profit_min' => $ebayProducts['possible_profit']['min'],
            'possible_profit_max' => $ebayProducts['possible_profit']['max'],
            'ebay_products' => json_encode($ebayProducts['filtered_ebay_products']),
        ];
        if (!empty($price)){
            $productData['price'] = str_replace("£", '', $price);
        }
            if (!empty($categories)) {
                $lastCategory = end($categories);
                $storedCategory = Category::where('cat_id', $lastCategory['cat_id'] )->first();

                if (!empty($storedCategory)) {
                    $productData['cat_id'] = $lastCategory['cat_id'];
                }
            }
        $product = Product::updateOrCreate(['asin'=>$asin],$productData);
        ProductImages::where('product_id' , $product->id)->delete();
        foreach ($images as $image) {
            ProductImages::create([
                'product_id' => $product->id,
                'asin' => $asin,
                'hi_res' => (empty($image['hiRes'])) ? $image['large'] : $image['hiRes'],
                'large' => $image['large'],
                'thumb' => $image['thumb'],
            ]);
        }
        $product = Product::with('images')->find($product->id);

        return $product;

        }




    public function extractImages($body)
    {
        $images = [];


        /**
         * If for example book item does have only one image
         * then {imageGalleryData} won't exist and we will use different way of extracting required data
         * Product types: books
         */


//        if (!images.length) {
//            const imageData = $('#imgBlkFront')[0] || $('#ebooksImgBlkFront')[0];
//            if (imageData) {
//                const data = imageData.attribs['data-a-dynamic-image'];
//                const json = JSON.parse(data);
//                const keys = Object.keys(json);
//                const imageIdregex = /\/([\w-+]{9,13})\./.exec(keys[0]);
//                if (imageIdregex) {
//                    images.push(`https://images-na.ssl-images-amazon.com/images/I/${imageIdregex[1]}.jpg`);
//                }
//            }
//        }
        /**
         * Extract images from other types of products
         * Product types: all other
         */

//            $asinQuery = $innterFinder->query('//*[@id="imageBlock_feature_div"]/script[1]')->item(0);
//            $images = strip_tags($asinQuery->C14N());
        preg_match("/(?<='initial': )(.*)(?=},
  *'colorToAsin)/", $body, $matches);
        if (!empty($matches)) {
            $data = json_decode($matches[0], true);
            foreach ($data as $item){
                $images[] = [
                    'hiRes' => $item['hiRes'],
                    'large' => $item['large'],
                    'thumb' => $item['thumb']
                ];
            }

        }
        /**
         *  Some product have all the images located in the imageGalleryData array
         *  We will check if this array exists, if exists then we will extract it and collect the image url's
         *  Product types: books
         * */
        if (!count($images)) {

            preg_match("/(?<='imageGalleryData' : )(.*)(?=,\n'centerColMargin')/", $body, $matches);
            if (!empty($matches)) {
                try {
                    $imageGalleryData = json_decode($matches[0]);
                    $images = $imageGalleryData[0]['mainUrl'];
                    $images[] = [
                        'hiRes' => $imageGalleryData[0]['mainUrl'],
                        'large' => $imageGalleryData[0]['mainUrl'],
                        'thumb' => $imageGalleryData[0]['thumbUrl'],
                    ];
                } catch (\Exception $exception) {
                    Log::error('extractImages', [$exception->getMessage()]);
                    // continue regardless of error
                }
            }
        }

        return $images;
    }

    /**
     * Extract product features
     * @param $innterFinder
     * @return array
     */
    public function extractProductFeatures($innterFinder)
    {
        $featureBullets = [];

        try {
            $nodes = $innterFinder->query('//*[@id="feature-bullets"]/ul/li');
            foreach ($nodes as $key => $node) {
                $text = strip_tags($node->C14N());
                $text = trim(str_replace(["\n\n", "\r\r"], '', $text));
                 if (!empty($text)){
                     $featureBullets[] = htmlspecialchars_decode($text);
                 }
            }

        } catch (\Exception $exception) {
            // continue regardless of error
        }

        // Features on some items can be hidden with the expander tag
        // We will try to extract them
        return $featureBullets;
    }


    /**
     * Extract product category/subcategory
     * @param $innterFinder
     * @return array
     */
    public function extractProductCategories($innterFinder)
    {
        $categories = [];
        $nodes = $innterFinder->query('//*[@id="wayfinding-breadcrumbs_feature_div"]/ul/li/span/a');
        $parent_id = 0;
        foreach ($nodes as $key => $node) {
            $url = parse_url($node->getAttribute('href'), PHP_URL_QUERY);

            $parts = parse_url($url);
            parse_str($parts['path'], $output);
            $text = strip_tags($node->C14N());
            $text = htmlspecialchars_decode(trim(str_replace(["\n\n", "\r\r"], '', $text)));
            $categories[] = [
                'name' => $text,
                'cat_id' => $output['node'],
                'parent_id' => $parent_id,
                'last_category' => false
            ];
            $parent_id = $output['node'];

        }
        if (!empty($categories)){
            $categories[count($categories) - 1]['last_category'] = true;
        }
        foreach ($categories as $category){
            $storedCategory = Category::where('cat_id', $category['cat_id'])->first();

            if (empty($storedCategory)) {
                Category::create([
                    'name' => $category['name'],
                    'cat_id' => $category['cat_id'],
                    'parent_id' => $category['parent_id'],
                ]);
            }
        }
        return $categories;
    }

    /**
     * @param $title
     * @param $price
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
private function extractSimilarProductsFromEbay($title,$price){
    $ebayProducts =  (new EbaySearchScrapper())->searchByKeyword($title);

    $possibleProfit = ['min' => 0,'max' => 0];
    $filteredEbayProducts = [];
    $ebayPrices = [];
    foreach ($ebayProducts as $key => $ebay_product){
        if(!is_array($ebay_product['price']) && $price > $ebay_product['price']){
            continue;
        }
        if(is_array($ebay_product['price']) && $price > min($ebay_product['price'])){
            continue;
        }

        if (count($filteredEbayProducts) >= 10){
            break;
        }
        if (is_array($ebay_product['price'])){
            $ebayPrices = array_merge($ebayPrices,$ebay_product['price']);
        }else{
            $ebayPrices[] = $ebay_product['price'];

        }
        $filteredEbayProducts[] = $ebay_product;
    }
    if (!empty($ebayPrices)){
        $possibleProfit['max'] = max($ebayPrices);
        $possibleProfit['min'] = min($ebayPrices);
    }

    return [
        'possible_profit' => $possibleProfit,
        'filtered_ebay_products' => $filteredEbayProducts
    ];

}
    /**
     * Extract products from the variants section (different color,size and etc)
     * @param $innterFinder
     * @return array
     */
public function extractProductVariants($innterFinder) {
    $variants = [];

    $nodes = $innterFinder->query('//*[@id="twister"]/div');
    foreach ($nodes as  $node){
        $nodeHtml = $node->C14N();
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        @$doc->loadHTML($nodeHtml);

        $nodeFinder = new \DOMXPath($doc);
        $variant = [
            'name' => str_replace(':','',trim(strip_tags($nodeFinder->query('//label')->item(0)->C14N()))),
            'variants' => $this->extractProductVariant($nodeFinder)
        ];
        $variants[] = $variant;

    }

    return $variants;

    }

private function extractProductVariant($nodeFinder){

    $variants = [];
    if (!empty($nodeFinder->query('//select[@data-action="a-dropdown-select"]/option')->item(0))){
        $items = $nodeFinder->query('//select[@data-action="a-dropdown-select"]/option');

        foreach ($items as $key => $item){
            $value = $item->getAttribute('value');
            if ($value == '-1'){
                continue;
            }

             // selected
            $cssClass = $item->getAttribute('class');
            $value = explode(',',$value);
            $variants[] = [
                'name' => trim(strip_tags($item->C14N())),
                'asin' => end($value),
                'type' => 'select',
                'current' => (str_contains(strtolower($cssClass),'swatchselect')) ? true : false,
                'status' => (str_contains(strtolower($cssClass),'unavailable')) ? 'unavailable' : 'available'
            ];

        }

    }elseif ($nodeFinder->query('//ul//li')->item(0)){
        $items = $nodeFinder->query('//ul//li');

        foreach ($items as $key => $item){
            $asin = $item->getAttribute('data-defaultasin');
            if (empty($asin)){
                $asin = $item->getAttribute('data-dp-url');
                $asin = explode('/',$asin);
                if(empty($asin[2])){
                   continue;
                }

                $asin = $asin[2];
            }
            $doc = new \DOMDocument();
            $doc->preserveWhiteSpace = false;
            @$doc->loadHTML($item->C14N());

            $innterFinder = new \DOMXPath($doc);
            $cssClass = $item->getAttribute('class');

            $variant = [
                'asin' => trim($asin),
                'status' => (str_contains(strtolower($cssClass),'unavailable')) ? 'unavailable' : 'available'
            ];
            $image = $innterFinder->query('//img');

            if (!empty($image->item(0))){
                $variant['name'] = trim(strip_tags($image->item(0)->getAttribute('alt')));
                $variant['image'] = trim(strip_tags($image->item(0)->getAttribute('src')));
                $variant['current'] = (str_contains(strtolower($cssClass),'swatchselect')) ? true : false;

                $variant['type'] = 'image';
            }else{
//                $variant['name'] = trim(strip_tags($image->item(0)->C14N())); // need to be fixed
                $variant['type'] = 'text';
                $variant['current'] = (str_contains(strtolower($cssClass),'swatchselect')) ? true : false;
            }
            $variants[] = $variant;

        }
    }
    return $variants;

}

public function getKeppaResponse($asin = []){
    return (new KeepaService())->getProduct($asin);
}
}

<?php
namespace app\components;

use app\models\Currency;
use Yii;
use Zend\Http;
use app\models\Film;
use yii\helpers\Json;
use app\models\Proxy;
use yii\helpers\ArrayHelper;

class KinopoiskParser extends Parser
{
    protected $_data;

    protected $content;

    public $cache = true;

    public $proxy_id;

    public $not_proxy_ids = [];

    public function getHtml($url)
    {
        for($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setHeaders(array_merge(self::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                //Proxy::rand()->apply($client);
                $response = $client->send();
                break;
            } catch(\Exception $e) {}
        }
        return $response->getBody();
    }

    public function clearContent()
    {
        $this->content = null;
        \phpQuery::$documents = [];
        \phpQuery::$defaultDocumentID = null;
    }

    public function getCacheFile()
    {
        return Yii::getAlias('@storage/kinopoisk/' . md5($this->url) . '.html');
    }

    public function clearCache()
    {
        $fileName = $this->getCacheFile();
        if(file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function loadContent()
    {
        if($this->content !== null) {
            return null;
        }
        //$this->content = $this->getHtml($this->url);
        $filePath = $this->getCacheFile();
        if(file_exists($filePath) && $this->cache) {
            $content = file_get_contents($filePath);
        } else {
            $content = $this->getHtml($this->url);
            if($this->cache) {
                file_put_contents($filePath, $content);
            }
        }
        $this->content = $content;
        \phpQuery::newDocumentHTML($this->content, 'utf-8');
    }

    public function getName()
    {
        $this->loadContent();

        pq('.moviename-big span')->remove();
        $name = pq('.moviename-big')->text();
        $name = preg_replace('#([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])#u', ' ', $name);
        return trim($name);
    }

    public function getTrailerUrl()
    {
        $this->loadContent();

        $url = pq('#trailerinfo iframe#movie-trailer')->attr('src');
        return ($url) ? $url : null;
    }

    public function getNewTrailerUrl()
    {
        $this->loadContent();

        $button = pq('#movie-trailer-button');
        $film_id = pq($button)->attr('data-film-id');
        $trailer_id = pq($button)->attr('data-trailer-id');
        if($film_id && $trailer_id) {
            return "https://widgets.kinopoisk.ru/discovery/film/$film_id/trailer/$trailer_id";
        } else {
            return null;
        }
    }

    public function getOriginalName()
    {
        $this->loadContent();

        $name = pq('#headerFilm [itemprop="alternativeHeadline"]')->text();
        $name = preg_replace('#([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])#u', ' ', $name);
        return trim($name);
    }

    public function getYear()
    {
        $content = $this->getDataContent('год');
        if($content) {
            $year = pq('a[href*=year]', $content)->text();

            return $year;
        }
    }

    public function getIsSeries()
    {
        return $this->getCountSeasons() ? 1 : 0;
    }

    public function getCountSeasons()
    {
        $content = $this->getDataContent('год');
        if($content) {
            $episodes = pq('a[href*=episodes]', $content);
            if($episodes) {
                $text = pq($episodes)->text();
                $text = str_replace('сезонов', '', $text);
                $text = str_replace('сезона', '', $text);
                $text = str_replace('сезон', '', $text);
                $text = trim($text, '() ');

                return (int) $text;
            }
        }
    }

    public function getCounties()
    {
        $content = $this->getDataContent('страна');
        if($content == null) {
            return [];
        }
        $counties = [];
        foreach(pq('a', $content) as $a) {
            $key = pq($a)->attr('href');
            $key = substr($key, 26, -1);

            $counties[$key] = pq($a)->text();
        }
        return $counties;
    }

    public function getDirectors()
    {
        return $this->getDataContentNames('режиссер');
    }

    public function getScreenwriters()
    {
        return $this->getDataContentNames('сценарий');
    }

    public function getProducers()
    {
        return $this->getDataContentNames('продюсер');
    }

    public function getOperators()
    {
        return $this->getDataContentNames('оператор');
    }

    public function getSlogan()
    {
        $content = $this->getDataContent('слоган');
        if($content) {
            $name = pq($content)->text();
            return mb_substr($name, 1, -1, 'utf-8');
        }
    }

    public function getBudgetText()
    {
        $content = $this->getDataContent('бюджет');
        if($content) {
            $budget = pq($content)->text();
            return trim($budget);
        }
    }

    public function getBudget()
    {
        $currency = $this->getBudgetCurrencyModel();

        $budget = $this->getBudgetText();
        $budget = preg_replace('#[^0-9]+#', '', $budget);
        if(!empty($budget) && $currency !== null) {
            return ceil($budget*$currency->rate);
        }
        return null;
    }

    public function getBudgetCurrency()
    {
        $model = $this->getBudgetCurrencyModel();
        return $model ? $model->iso : null;
    }

    /**
     * @return Currency|null
     */
    protected function getBudgetCurrencyModel()
    {
        $text = $this->getBudgetText();
        /** @var Currency[] $models */
        $models = Currency::find()->all();
        foreach($models as $model) {
            if(mb_strpos($text, $model->iso, 0, 'utf-8') !== false) {
                return $model;
            }
            if(mb_strpos($text, $model->name, 0, 'utf-8') !== false) {
                return $model;
            }
        }
        return null;
    }

    public function getBoxOffice()
    {
        $content = $this->getDataContent('сборы в мире');
        $text = pq('a:eq(0)', $content)->text();
        $pieces = explode('=', $text);
        $boxOffice = end($pieces);
        return $boxOffice;
    }

    public function getPremiere()
    {
        $content = $this->getDataContent('премьера (мир)');
        if($content) {
            $date = pq('a:eq(0)', $content)->text();
            if ($date) {
                return $this->dateToTimestamp($date);
            }
        }
        $content = $this->getDataContent('премьера (РФ)');
        if($content) {
            $date = pq('a:eq(0)', $content)->text();
            if($date) {
                return $this->dateToTimestamp($date);
            }
        }
        return null;
    }

    public function getReleaseDVD()
    {
        $content = $this->getDataContent('релиз на DVD');
        if($content) {
            $date = pq('a:eq(0)', $content)->text();
            if($date) {
                return $this->dateToTimestamp($date);
            }
        }
    }

    public function getReleaseBluRay()
    {
        $content = $this->getDataContent('релиз на Blu-Ray');
        if($content) {
            $date = pq('a:eq(0)', $content)->text();
            if($date) {
                return $this->dateToTimestamp($date);
            }
        }
        return null;
    }

    public function getDescription()
    {
        $this->loadContent();

        $description = pq('.film-synopsys')->text();
        return $description;
    }

    public function getKpMark()
    {
        $this->loadContent();
        return pq('.rating_stars #block_rating .block_2 .rating_ball')->text();
    }

    public function getKpMarkVotes()
    {
        $this->loadContent();
        $text = pq('.rating_stars #block_rating .block_2 .ratingCount')->text();
        return preg_replace('#[^0-9]#', '', $text);
    }

    public function getImdbMark()
    {
        $this->loadContent();
        $text = pq('.rating_stars #block_rating .block_2 div:eq(1)')->text();
        preg_match('#IMDb: ([0-9.]+) \(([0-9 ]+)\)#ui', $text, $matches);
        if($matches) {
            return ArrayHelper::getValue($matches, 1);
        }
    }

    public function getImdbMarkVotes()
    {
        $this->loadContent();
        $text = pq('.rating_stars #block_rating .block_2 div:eq(1)')->text();
        preg_match('#IMDb: ([0-9.]+) \(([0-9 ]+)\)#ui', $text, $matches);
        if($matches) {
            $text = ArrayHelper::getValue($matches, 2);
            return preg_replace('#[^0-9]#', '', $text);
        }
    }

    public function getCriticRating()
    {
        $this->loadContent();

        $positive = 0;
        $negative = 0;
        foreach(pq('.criticsRating') as $criticsRating) {
            $positive += (int) pq('.insider .sum .el1', $criticsRating)->text();
            $negative += (int) pq('.insider .sum .el2', $criticsRating)->text();
        }
        if($positive == 0 && $negative == 0) {
            return null;
        }
        return round($positive / ($positive + $negative) * 100);
    }

    public function getUserReviewRating()
    {
        $this->loadContent();
        $negative = (int) pq('.resp_type .neg b')->text();
        $positive = (int) pq('.resp_type .pos b')->text();
        if($positive == 0 && $negative == 0) {
            return null;
        }
        return round($positive / ($positive + $negative) * 100);
    }

    public function getUserReviewVotes()
    {
        $this->loadContent();
        return pq('.resp_type .all b')->text();
    }

    public function getAwaitRating()
    {
        $this->loadContent();
        $await_percent = pq('#await_percent')->text();
        return (int) substr($await_percent, 0, -1);
    }

    public function getAwaitVotes()
    {
        $this->loadContent();
        $votes = pq('#await_percent')->next()->text();
        return preg_replace('#[^0-9]+#', '', $votes);
    }

    public function getCriticVotes()
    {
        $this->loadContent();

        $count = 0;
        foreach(pq('.criticsRating') as $criticsRating) {
            $count += (int) pq('.insider .sum .el1', $criticsRating)->text();
            $count += (int) pq('.insider .sum .el2', $criticsRating)->text();
        }
        return $count;
    }

    public function getInternalId()
    {
        if(preg_match('#kinopoisk.ru/level/1/film/([0-9]+)/$#', $this->url, $matches)) {
            return $matches[1];
        } elseif(preg_match('#kinopoisk.ru/film/([0-9]+)/$#', $this->url, $matches)) {
            return $matches[1];
        } elseif(preg_match('#kinopoisk.ru.+?\-([0-9]+)/$#', $this->url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getGenres()
    {
        $content = $this->getDataContent('жанр');
        if($content == null) {
            return [];
        }
        pq('.wordLinks', $content)->remove();
        $names = [];
        foreach(pq('a', $content) as $a) {
            $key = pq($a)->attr('href');
            $key = substr($key, 24, -1);

            $name = pq($a)->text();
            if($name == '...') {
                continue;
            }
            $names[$key] = $name;
        }
        return $names;
    }

    public function getActors()
    {
        $this->loadContent();
        $names = [];
        foreach(pq('#actorList ul:eq(0) li a') as $a) {
            $key = pq($a)->attr('href');
            $key = substr($key, 6, -1);
            $key = preg_replace('#[^0-9]+#', '', $key);
            $key = (int) $key;

            $name = pq($a)->text();
            if($name == '...') {
                continue;
            }
            $names[$key] = $name;
        }
        return $names;
    }

    public function getImageUrl()
    {
        $this->loadContent();
        $image_url = pq('#photoBlock img[itemprop="image"]')->attr('src');
        if($image_url == 'https://st.kp.yandex.net/images/movies/poster_none.png') {
            $image_url = null;
        }
        return $image_url;
    }

    public function getStatus()
    {
        $this->loadContent();

        $status = null;
        foreach(pq('#syn .news') as $td) {
            $label = trim(pq($td)->text());
            if($label == 'статус производства:') {
                $status = pq($td)->next()->text();
            }
        }
        if(empty($status) && pq('#await_hands')->length) {
            $status = 'неизвестно';
        }
        if(empty($status) && ($premiere = $this->getPremiere()) && $premiere > time()) {
            $status = 'неизвестно';
        }
        return ArrayHelper::getValue([
            'производство завершено' => Film::STATUS_PRODUCTION_COMPLETED,
            'пост-продакшн' => Film::STATUS_POST_PRODUCTION,
            'подготовка к съемкам' => Film::STATUS_PREPARING_FOR_SHOOTING,
            'съемочный процесс' => Film::STATUS_SHOOTING_PROCESS,
            'проект объявлен' => Film::STATUS_ANNOUNCED,
            'неизвестно' => Film::STATUS_UNKNOWN,
        ], $status, Film::STATUS_CAME_OUT);
    }

    protected function getDataContentNames($name)
    {
        $content = $this->getDataContent($name);
        if($content == null) {
            return [];
        }
        $names = [];
        foreach(pq('a', $content) as $a) {
            $key = pq($a)->attr('href');
            $key = substr($key, 6, -1);
            $key = preg_replace('#[^0-9]+#', '', $key);
            $key = (int) $key;

            $name = pq($a)->text();
            if($name == '...') {
                continue;
            }
            $names[$key] = $name;
        }
        return $names;
    }

    protected function getDataContent($name)
    {
        $this->loadContent();

        if($this->_data === null) {
            $this->_data = [];
            foreach(pq('#infoTable table.info tr') as $tr) {
                $label = pq('td:eq(0)', $tr)->text();
                $content = pq('td:eq(1)', $tr);

                $this->_data[$label] = $content;
            }
        }
        return ArrayHelper::getValue($this->_data, $name);
    }

    protected function dateToTimestamp($date)
    {
        $date = trim($date);
        if(mb_strlen($date, 'utf-8') == 4) {
            return strtotime('01-01-' . $date);
        }
        $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',

            '01-01' => 'Январь', '01-02' => 'Февраль', '01-03' => 'Март', '01-04' => 'Апрель', '01-05' => 'Май', '01-06' => 'Июнь', '01-07' => 'Июль', '01-08' => 'Август',
            '01-09' => 'Сентябрь', '01-10' => 'Октябрь', '01-11' => 'Ноябрь', '01-12' => 'Декабрь',
        ];
        foreach($months as $key => $month) {
            $key = str_pad ($key, 2,"0",STR_PAD_LEFT);
            $date = str_replace($month, $key, $date);
        }
        $date = preg_replace('#[^0-9]#u', '-', $date);
        return strtotime($date);
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return [
            'url' => $this->url,
            'kp_internal_id' => $this->getInternalId(),
            'name' => $this->getName(),
            'original_name' => $this->getOriginalName(),
            'year' => $this->getYear(),
            'count_seasons' => $this->getCountSeasons(),
            'is_series' => $this->getIsSeries(),
            'slogan' => $this->getSlogan(),
            'budget' => $this->getBudget(),
            'budget_currency' => $this->getBudgetCurrency(),
            'box_office' => $this->getBoxOffice(),
            'trailer_url' => $this->getTrailerUrl(),
            'new_trailer_url' => $this->getNewTrailerUrl(),
            'premiere' => $this->getPremiere(),
            'release_dvd' => $this->getReleaseDVD(),
            'release_blu_ray' => $this->getReleaseBluRay(),
            'kp_mark' => $this->getKpMark(),
            'kp_mark_votes' => $this->getKpMarkVotes(),
            'imdb_mark' => $this->getImdbMark(),
            'imdb_mark_votes' => $this->getImdbMarkVotes(),
            'critic_rating' => $this->getCriticRating(),
            'critic_votes' => $this->getCriticVotes(),
            'user_review_rating' => $this->getUserReviewRating(),
            'user_review_votes' => $this->getUserReviewVotes(),
            'await_rating' => $this->getAwaitRating(),
            'await_votes' => $this->getAwaitVotes(),
            'image_url' => $this->getImageUrl(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus(),
            'budget_text' => $this->getBudgetText(),
        ];
    }

    public static function headers()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, sdch, br',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,be;q=0.2,mk;q=0.2,uk;q=0.2',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        ];
    }

    /**
     * @param Http\Client $client
     * @param $login
     * @param $password
     */
    public static function login(Http\Client $client, $login, $password)
    {
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');
        $client->setHeaders(self::headers());

        $client->setUri('https://www.kinopoisk.ru/');
        $response = $client->send();

        foreach($response->getCookie() as $cookie) {
            $client->addCookie($cookie->getName(), $cookie->getValue());
        }
        $client->setUri('https://plus.kinopoisk.ru/embed/login/?retPath=https%3A%2F%2Fwww.kinopoisk.ru%2F');
        $client->setHeaders(array_merge(self::headers(), [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Referer' => 'https://www.kinopoisk.ru/',
            'Upgrade-Insecure-Requests' => '1',
            'Cookie' => 'autoFit=1; domino=7; yandex_login=support@recrm.com.ua; _ym_uid=1495224430162120502; fuid01=56533f7c5a6d30de.PrJIQq5X4S10bm897-gsVO3dBr8m-bOdkf0dJu9vkDfU-_gigOGOwIB7aQd_WtB-4mt1l-ALLyhiREvWmCDeNCHc9P6eyqG5WuMXb2j85ViiH5JInh8T7mtBAbr1SwkL; __gads=ID=db575f9aff47d003:T=1497525042:S=ALNI_MZYqAZxX2_n_ZaAp_N-pWv9NsKRcA; mustsee_sort_v5=01.10.200.21.31.41.121.131.51.61.71.81.91.101.111; mustsee_sort=a%3A15%3A%7Bs%3A7%3A%22default%22%3Bs%3A4%3A%22desc%22%3Bs%3A4%3A%22name%22%3Bs%3A3%3A%22asc%22%3Bs%3A5%3A%22oname%22%3Bs%3A3%3A%22asc%22%3Bs%3A6%3A%22rating%22%3Bs%3A4%3A%22desc%22%3Bs%3A11%3A%22rating_imdb%22%3Bs%3A4%3A%22desc%22%3Bs%3A11%3A%22rating_user%22%3Bs%3A4%3A%22desc%22%3Bs%3A12%3A%22rating_await%22%3Bs%3A4%3A%22desc%22%3Bs%3A13%3A%22rating_critic%22%3Bs%3A4%3A%22desc%22%3Bs%3A7%3A%22premier%22%3Bs%3A4%3A%22desc%22%3Bs%3A11%3A%22premier_rus%22%3Bs%3A4%3A%22desc%22%3Bs%3A4%3A%22year%22%3Bs%3A4%3A%22desc%22%3Bs%3A3%3A%22dvd%22%3Bs%3A4%3A%22desc%22%3Bs%3A6%3A%22bluray%22%3Bs%3A4%3A%22desc%22%3Bs%3A7%3A%22runtime%22%3Bs%3A4%3A%22desc%22%3Bs%3A5%3A%22votes%22%3Bs%3A4%3A%22desc%22%3B%7D; my_perpages=%7B%2248%22%3A200%7D; yandexuid=3451214911445871614; refresh_yandexuid=3451214911445871614; header_v2_popup_hidden=yes; mobile=no; tickets_promo_popup_shown=1; PHPSESSID=lb8bf75f1jq1uhsbsi1s1h80a3; user_country=ua; yandex_gid=143; tc=49; last_visit=2017-12-11+13%3A31%3A49; noflash=true; _ym_visorc_22663942=b; _ym_isad=2; csrftoken=s%3AJCxNIAqOaWVn3bcwj3UeCPZE.%2BkbJRa7%2FMCj8wEvdZY2Y0EOktLP2Z7pDWeWg22GFydk; _ym_visorc_32993479=w',
        ]));

        $response = $client->send();

        $cookies = $response->getCookie();
        $cookies = $cookies ? $cookies : [];
        foreach($cookies as $cookie) {
            $client->addCookie($cookie->getName(), $cookie->getValue());
        }

        preg_match('#data-bem="{&quot;page&quot;:{&quot;csrf&quot;:&quot;(.+?)&quot;,&quot;debug&quot;:false}}"#', $response->getBody(), $matches);
        $token = $matches[1];

        $client->resetParameters();

        $client->setUri('https://plus.kinopoisk.ru/user/resolve-by-password/?retPath=https%3A%2F%2Fwww.kinopoisk.ru%2F');
        $client->setHeaders(ArrayHelper::merge(self::headers(), [
            'Referer' => 'https://plus.kinopoisk.ru/embed/login/?retPath=https%3A%2F%2Fwww.kinopoisk.ru%2F',
            'X-CSRF-Token' => $token,
            'X-Requested-With' => 'XMLHttpRequest',
        ]));
        $client->setMethod('POST');
        $client->setParameterPost([
            'login' => $login,
            'password' => $password,
        ]);
        $client->setOptions(['maxredirects' => 0]);
        $response = $client->send();

        $json = Json::decode($response->getBody());
        if($json['status'] == 'ok') {
            foreach($response->getCookie() as $cookie) {
                $client->addCookie($cookie->getName(), $cookie->getValue());
            }
            return true;
        } else {
            return false;
        }
    }
}
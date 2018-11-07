<?php
namespace app\components;

use app\models\Proxy;
use Yii;
use Zend\Http;
use yii\helpers\ArrayHelper;

class NnmClubParser extends TorrentParser
{
    public $content;

    protected static $_bad_proxy_id = [];

    protected $_data;

    public function getHtml($url)
    {
        for ($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setAdapter('Zend\Http\Client\Adapter\Curl');
                $client->setUri($url);
                $client->setHeaders(array_merge(self::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                if($this->proxy) {
                    Proxy::rand()->apply($client);
                }
                $response = $client->send();
                break;
            } catch (\Exception $e) {}
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
        return Yii::getAlias('@storage/nnm-club/' . md5($this->url) . '.html');
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
        if ($this->content !== null) {
            return null;
        }
        $filePath = $this->getCacheFile();
        if (file_exists($filePath) && $this->cache) {
            $content = file_get_contents($filePath);
        } else {
            $content = $this->getHtml($this->url);
            if($this->cache) {
                file_put_contents($filePath, $content);
            }
        }
        $this->content = $content;

        \phpQuery::newDocumentHTML($content, 'utf-8');
    }

    protected $_title;

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function getTitle()
    {
        if($this->_title === null) {
            $this->loadContent();
            $text = pq('.maintitle')->text();
            $this->_title = trim($text);
        }
        return $this->_title;
    }

    public function getImageUrl()
    {
        $this->loadContent();
        return pq('var.postImg')->attr('title');
    }

    public function getSizeText()
    {
        $text = $this->torrentValue('Размер');
        $text = preg_replace('#\(.*?\)#u', '', $text);
        $text = trim($text);
        return $text;
    }

    public function getSize()
    {
        $text = $this->torrentValue('Размер');
        if(empty($text)) {
            return null;
        }

        $text = preg_replace('#\(.*?\)#u', '', $text);
        $text = trim($text);
        $unit = substr($text, -2);
        $value = substr($text, 0, -3);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('#[^0-9.]#', '', $value);
        $size = ArrayHelper::getValue([
            'KB' => $value / 1024,
            'MB' => $value,
            'GB' => $value * 1024,
        ], $unit);

        return round($size);
    }

    public function getCreated()
    {
        $created = $this->torrentValue('Зарегистрирован');
        if ($created) {
            $months = [1 => 'Янв', 2 => 'Фев', 3 => 'Мар', 4 => 'Апр', 5 => 'Май', 6 => 'Июн', 7 => 'Июл', 8 => 'Авг', 9 => 'Сен', 10 => 'Окт', 11 => 'Ноя', 12 => 'Дек'];
            $created = trim($created);
            foreach ($months as $num => $name) {
                $created = str_replace(' ' . $name . ' ', '-' . $num . '-', $created);
            }
            $created = strtotime($created);
        }

        return $created;
    }

    protected $_torrent_data;

    public function torrentData()
    {
        $this->loadContent();
        if ($this->_torrent_data === null) {
            $data = [];

            foreach (pq('.btTbl:eq(0) tr') as $tr) {
                $key = pq('td:eq(0)', $tr)->text();
                $key = preg_replace('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', ' ', $key);
                $key = trim($key, ' :');

                $value = pq('td:eq(1)', $tr)->text();
                $value = preg_replace('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', ' ', $value);
                $data[$key] = trim($value);
            }

            $this->_torrent_data = $data;
        }
        return $this->_torrent_data;
    }

    public function getYear()
    {
        $text = $this->value(['Год выпуска', 'Год']);
        $text = preg_replace('#[^0-9]+#', '', $text);
        $text = substr($text, 0, 4);

        if(empty($text)) {
            preg_match('#(20|19)[0-9]{2}#', $this->getTitle(), $matches);
            $text = ArrayHelper::getValue($matches, 0);
        }

        return $text ? $text : null;
    }

    public function getDirector()
    {
        return $this->value(['Режиссер', 'Режиссёр', 'Режисcер']);
    }

    public function getQualityText()
    {
        $text = $this->value(['Качество видео', 'Качество']);
        return $text;
    }

    public function getQuality()
    {
        $maxQuality = 6;
        $subtitles = $this->value('Субтитры');
        if($this->inStr($subtitles, ['and', 'корейские', ['or', 'неотключаемые', 'вшитые']])) {
            return $maxQuality = 4;
        }

        $text = $this->value(['Качество видео', 'Качество']);
        $text = mb_strtolower($text, 'utf-8');
        $text = preg_replace('#[^0-9a-zа-я]#u', '', $text);

        $terms = [
            'CAMRip' => 1, 'TS' => 1,
            'VHSRip' => 2, 'WP' => 2, 'SCR' => 2, 'VHSScr' => 2, 'DVDScr' => 2, 'TC' => 2, 'VideoCD' => 2,
            'LDRip' => 3, 'TVRip' => 3, 'SATRip' => 3, 'DVBRip' => 3, 'DTVRip' => 3, 'PDTV' => 3, 'PDTVRip' => 3, 'DVB' => 3,
            'DVDRip' => 4, 'DVD' => 4, 'DVD5' => 4, 'DVD9' => 4, 'DVD10' => 4, 'DVD18' => 4, 'DVDCustom' => 4, 'WEBRip' => 4,
            'HDRip' => 5, 'HDTVRip' => 5, 'WEBDLRip' => 5, 'HDDVDRip' => 5, 'BDRip' => 5, 'ВDRip' => 5, 'НDRip' => 5, 'DTheater' => 5,
            'HDTV' => 6, 'WEBDL' => 6, 'HDDVDRemux' => 6, 'HDDVD' => 6, 'BDRemux' => 6, 'BluRay' => 6, 'DCPRip' => 6, 'UHDStRip' => 6,
            'HDRemux' => 6,
        ];
        uksort($terms, function ($a, $b) {
            return mb_strlen($a, 'utf-8') < mb_strlen($b, 'utf-8') ? 1 : -1;
        });
        foreach ($terms as $term => $quality) {
            $term = mb_strtolower($term, 'utf-8');
            if (mb_strpos($text, $term, 0, 'utf-8') !== false) {
                return min($quality, $maxQuality);
            }
        }
        $text = $this->getTitle();
        $text = mb_strtolower($text, 'utf-8');
        $text = preg_replace('#[^0-9a-zа-я]#u', '', $text);
        foreach ($terms as $term => $quality) {
            $term = mb_strtolower($term, 'utf-8');
            if (mb_strpos($text, $term, 0, 'utf-8') !== false) {
                return min($quality, $maxQuality);
            }
        }
        return null;
    }

    public function getTransferText()
    {
        return $this->value('Перевод');
    }

    public function getAdvertText()
    {
        return $this->value(['Реклама']);
    }

    public function getHasAdvert()
    {
        $text = $this->value(['Реклама']);
        $text = trim($text);
        $text = mb_strtolower($text, 'utf-8');
        if($text == 'нет') {
            return 0;
        }
        if(empty($text)) {
            return 0;
        }
        if($this->inStr($text, ['or', 'присут', 'присутствуют', 'Имеется', 'Аудио вставки', 'Звуковые вставки', 'Есть реклама', 'Вставки'])) {
            return 1;
        }
        if($this->inStr($text, ['or', 'отсутствует', 'Без рекламы', 'рекламы нет', 'отсутствуют', 'Отсутсвует'])) {
            return 0;
        }
        return null;
    }

    public function getTransfer()
    {
        $text = $this->value(['Перевод']);
        if(empty($text)) {
            return null;
        }

        $termManyVoices = ['or', 'многоголосый', 'многоголосный', 'многолосый', 'многоголосое', 'многоголосовой'];
        $termTwoVoices = ['or', 'двухголосый', 'двуголосый', 'двухголосный', 'двуголосный'];
        $termOneVoice = ['or', 'одноголосый', 'одноголосный'];

        $transfer = null;
        if($this->inStr($text, ['or', 'TS'])) {
            $transfer = 3;
        } else if($this->inStr($text, ['or', 'дубляж', 'дублированное', 'дублированый', 'дублирование', 'дублированный'])) {
            $transfer = 7;
        } else if($this->inStr($text, ['профессиональный', $termManyVoices])) {
            $transfer = 7;
        } else if($this->inStr($text, ['профессиональный', $termTwoVoices])) {
            $transfer = 6;
        } else if($this->inStr($text, ['любительский', $termManyVoices])) {
            $transfer = 5;
        } else if($this->inStr($text, ['авторский', ['or', $termOneVoice, $termTwoVoices, $termManyVoices]])) {
            $transfer = 5;
        } else if($this->inStr($text, ['любительский', $termTwoVoices])) {
            $transfer = 4;
        } else if($this->inStr($text, ['любительский', $termOneVoice])) {
            $transfer = 3;
        } else if($this->inStr($text, ['оригинал', 'русский'])) {
            $transfer = 7;
        } else if($this->inStr($text, ['or', 'не требуется', 'немой'])) {
            $transfer = 7;
        } else if($this->inStr($text, $termManyVoices)) {
            $transfer = 5;
        } else if($this->inStr($text, ['профессиональный'])) {
            $transfer = 6;
        } else if($this->inStr($text, ['любительский'])) {
            $transfer = 4;
        } else if($this->inStr($text, ['авторский'])) {
            $transfer = 5;
        } else if($this->inStr($text, ['or', 'оригинальный', 'оригинал', 'отсутствует'])) {
            $transfer = 1;
        } else if($this->inStr($text, ['субтитры'])) {
            $transfer = 2;
        }
        if($this->getQuality() < 3) {
            $transfer = min(3, $transfer);
        }
        return $transfer;
    }

    protected function inStr($text, $search)
    {
        if(is_string($search)) {
            $text = mb_strtolower($text, 'utf-8');
            $search = mb_strtolower($search, 'utf-8');
            return (mb_strpos($text, $search, 0, 'utf-8') !== false);
        }
        $compare = ArrayHelper::remove($search, 0);
        if(!in_array($compare, ['or', 'and'])) {
            $search[] = $compare;
            $compare = 'and';
        }
        $present = ($compare == 'or') ? false : true;
        foreach($search as $term) {
            if($compare == 'or') {
                $present = $present || $this->inStr($text, $term);
            } else {
                $present = $present && $this->inStr($text, $term);
            }
        }
        return $present;
    }

    public function getName()
    {
        $title = $this->getTitle();
        preg_match('#(.+?)/(.+?)\([0-9]{4}[^0-9].*#ui', $title, $matches);
        if($matches) {
            return trim($matches[1]);
        }
        preg_match('#(.+?)\([0-9]{4}[^0-9].*#ui', $title, $matches);
        if($matches) {
            return trim($matches[1]);
        }
        return null;
    }

    public function getOriginalName()
    {
        $title = $this->getTitle();
        preg_match('#(.+?)/(.+?)\([0-9]{4}[^0-9].*#ui', $title, $matches);
        if($matches) {
            return trim($matches[2]);
        }
        return null;
    }

    public function getAttributes()
    {
        return [
            'title' => $this->getTitle(),
            'name' => $this->getName(),
            'original_name' => $this->getOriginalName(),
            'year' => $this->getYear(),
            'size' => $this->getSize(),
            'size_text' => $this->getSizeText(),
            'quality' => $this->getQuality(),
            'quality_text' => $this->getQualityText(),
            'transfer' => $this->getTransfer(),
            'transfer_text' => $this->getTransferText(),
            'advert_text' => $this->getAdvertText(),
            'has_advert' => $this->getHasAdvert(),
            'director' => $this->getDirector(),
            'created_at' => $this->getCreated(),
            'kp_internal_ids' => $this->getKpInternalIds(),
            'imdb_internal_ids' => $this->getImdbInternalIds(),
            'season' => $this->getSeason(),
            'episode' => $this->getEpisode(),
        ];
    }

    public function getImdbInternalIds()
    {
        $this->loadContent();
        $internal_ids = [];
        foreach(pq('.postbody:eq(0) a[href*="imdb.com/title"]') as $a) {
            $url = pq($a)->attr('href');
            preg_match('#title/([a-z0-9]+)/?#', $url, $matches);
            if($matches) {
                $internal_ids[] = $matches[1];
            }
        }
        $internal_ids = array_unique($internal_ids);
        return empty($internal_ids) ? null : $internal_ids;
    }

    public function getKpInternalIds()
    {
        $this->loadContent();
        $internal_ids = [];
        foreach(pq('.postbody:eq(0) a[href*="kinopoisk.ru/film"]') as $a) {
            $url = pq($a)->attr('href');
            preg_match('#film/([0-9]+)/?#', $url, $matches);
            if($matches) {
                $internal_ids[] = $matches[1];
            }
        }
        $internal_ids = array_unique($internal_ids);
        return empty($internal_ids) ? null : $internal_ids;
    }

    /**
     * @return array
     */
    public function data()
    {
        $this->loadContent();
        if ($this->_data === null) {
            $data = [];

            $postbody = pq('.postbody:eq(0)')->html();
            $postbody = str_replace('<div class="clear"></div>', '<br>', $postbody);
            $postbody = str_replace('<hr>', '<br>', $postbody);

            $rows = explode('<br>', $postbody);

            foreach ($rows as $row) {
                $row = strip_tags($row);
                if (($pos = strpos($row, ':')) !== false) {
                    $key = substr($row, 0, $pos);
                    $key = trim($key);
                    if (mb_strlen($key, 'utf-8') <= 32) {
                        $value = substr($row, $pos + 1);
                        $value = trim($value);
                        $data[$key] = $value;
                    }
                }
            }
            $this->_data = $data;
        }
        return $this->_data;
    }

    /**
     * @param $keys
     * @return mixed|null
     */
    public function value($keys)
    {
        is_array($keys) or $keys = [$keys];
        foreach ($keys as $key) {
            $value = ArrayHelper::getValue($this->data(), $key);
            if (!empty($value)) {
                return $value;
            }
        }
        return null;
    }

    protected function torrentValue($keys)
    {
        is_array($keys) or $keys = [$keys];
        foreach ($keys as $key) {
            $value = ArrayHelper::getValue($this->torrentData(), $key);
            if (!empty($value)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param Http\Client $client
     * @param $login
     * @param $password
     */
    public static function login(Http\Client $client, $login, $password)
    {
        $client->setUri('http://nnm-club.me/forum/login.php');
        $response = $client->send();

        \phpQuery::newDocumentHTML($response->getBody());

        $client->resetParameters();

        $client->setUri('http://nnm-club.me/forum/login.php');
        $client->setMethod('POST');
        $client->setParameterPost([
            'username' => $login,
            'password' => $password,
            'autologin' => 'on',
            'code' => pq('#loginFrm input[name="code"]')->val(),
            'login' => 'Вход',
        ]);
        $client->setOptions(['maxredirects' => 0]);
        $response = $client->send();

        foreach ($response->getCookie() as $cookie) {
            $client->addCookie($cookie->getName(), $cookie->getValue());
        }
    }

    /**
     * @param $term
     * @return array
     */
    public static function checkSeeds($term)
    {
        $data = [];
        try {
            $response = (new Http\Client)
                ->setUri('http://nnm-club.me/forum/tracker.php')
                ->setParameterGet(['nm' => $term])
                ->send();

            \phpQuery::newDocumentHtml($response->getBody());
            foreach(pq('a[href*=viewtopic]') as $a) {
                $url = trim(pq($a)->attr('href'));
                if(empty($url)) {
                    continue;
                }
                $seeders = (int) pq($a)->parent()->parent()->find('td:eq(6)')->text();
                $url = 'http://nnm-club.me/forum/' . $url;
                if($seeders) {
                    $data[$url] = $seeders;
                }
            }
        } catch(\Exception $e) {}
        return $data;
    }

    public function getSeeders()
    {
        header('Content-Type: text/html; charset=windows-1251');

        $client = new Http\Client;
        NnmClubParser::login($client, 'taral14', '9924054');

        $client->resetParameters();

        $client->setUri($this->url);
        $response = $client->send();

        \phpQuery::newDocumentHTML($response->getBody());

        $text = pq('.forumline .seed:last-child')->text();

        preg_match('#Seeders\:\s*\[\s*([0-9]+)#', $text, $matches);
        if($matches) {
            return (int) $matches[1];
        }
        return 0;
    }

    public function getTorrentFile()
    {
        $client = new Http\Client;
        NnmClubParser::login($client, 'taral14', '9924054');

        $client->resetParameters();

        $client->setUri($this->url);
        $response = $client->send();

        \phpQuery::newDocumentHTML($response->getBody());

        $downloadUrl = 'http://nnm-club.me/forum/' . pq('.genmed a[href*="download.php?id="]')->attr('href');

        $client->resetParameters();
        $client->setOptions(['maxredirects' => 1]);
        $client->setHeaders(array_merge(NnmClubParser::headers(), [
            'Referer' => $this->url,
        ]));
        $client->setUri($downloadUrl);
        $response = $client->send();

        $headers = ($response->getHeaders()->toArray());

        $content = null;
        for($i=0; $i<5; $i++) {
            try {
                $client->resetParameters();
                $client->setOptions(['timeout' => 10]);
                $client->setUri('http:' . $headers['Location']);
                $response = $client->send();
                $content = $response->getBody();
                break;
            } catch(\Exception $e) {}
        }
        return $content;
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
}
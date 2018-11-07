<?php
namespace app\components;

use Yii;
use app\models\Proxy;
use Zend\Http;

class KinopoiskNewsParser extends Parser
{
    protected $content;

    public static function headers()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, sdch, br',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,be;q=0.2,mk;q=0.2,uk;q=0.2',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        ];
    }

    public function loadContent()
    {
        if($this->content !== null) {
            return null;
        }
        $this->content = $this->getHtml($this->url);
        \phpQuery::newDocumentHTML($this->content, 'utf-8');
    }

    public function getHtml($url)
    {
        for($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setHeaders(array_merge(self::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                /** @var Proxy $proxy */
                $proxy = Proxy::rand();
                $proxy->apply($client);
                $response = $client->send();
                break;
            } catch(\Exception $e) {
                throw $e;
            }
        }
        return $response->getBody();
    }

    public function getFullText()
    {
        $this->loadContent();
        $content = pq('#newsTopBox .article__content')->html();
        $content = str_replace('https://st.kp.yandex.net/images', 'http://filmun.net/news/image?url=https://st.kp.yandex.net/images', $content);
        return trim($content);
    }

    public function getPreview()
    {
        $this->loadContent();
        $preview = pq('.article__slider img:first-child')->attr('src');
        if(empty($preview)) {
            $style = pq('.article__slider .mediaSlider .pic:first-child')->attr('style');
            preg_match('#url\((.+?)\)#', $style, $matches);
            if($matches) {
                $preview = $matches[1];
            }
        }
        return $preview;
    }

    public function clearContent()
    {
        $this->content = null;
        \phpQuery::$documents = [];
        \phpQuery::$defaultDocumentID = null;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return [
            'title' => $this->params['title'],
            'description' => $this->params['description'],
            'kp_url' => $this->url,
            'public_at' => $this->params['public_at'],
            'kp_preview' => $this->getPreview(),
            'content' => $this->getFullText(),
        ];
    }
}
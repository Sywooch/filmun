<?php
namespace app\components;

use Yii;
use Zend\Http;
use yii\helpers\ArrayHelper;

class NnmClubSearchIterator extends ParserIterator
{
    protected $term;

    public function __construct($term, array $config = [])
    {
        $this->term = $term;
        parent::__construct('http://nnm-club.me/forum/tracker.php', $config);
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        return 1;
    }

    /**
     * Fetches the next batch of data.
     * @return array the data fetched
     */
    protected function fetchData()
    {
        $page = $this->_page;
        $this->_page++;

        if($page > $this->getTotalPages()) {
            return [];
        }

        $client = new Http\Client($this->baseUrl);
        $client->setParameterGet(['nm' => $this->term, 'start' => ($page-1)*50]);
        for($i = 1; $i < 10; $i++) {
            try {
                $response = $client->send();
                break;
            } catch(\Exception $e) {}
        }

        $data = [];

        \phpQuery::newDocumentHtml($response->getBody());
        foreach(pq('.topictitle') as $a) {
            $url = trim(pq($a)->attr('href'));
            if(empty($url)) {
                continue;
            }
            $tr = pq($a)->parent()->parent();
            pq('td:eq(5) u', $tr)->remove();
            $data[] = new NnmClubParser('http://nnm-club.me/forum/' . $url, [
                'title' => pq($a)->text(),
                'created_at'  => pq('td:eq(9) u', $tr)->text(),
                'seeders'  => pq('td:eq(6)', $tr)->text(),
                'size'  => pq('td:eq(5)', $tr)->text(),
            ]);
        }
        return $data;
    }
}

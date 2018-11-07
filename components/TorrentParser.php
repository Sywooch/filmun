<?php
namespace app\components;

use Yii;

abstract class TorrentParser extends Parser
{
    public $cache = true;

    public $proxy = false;

    public abstract function getTitle();

    public abstract function getImageUrl();

    public abstract function getCreated();

    public abstract function getTorrentFile();

    public abstract function getAttributes();

    public abstract function getKpInternalIds();

    public abstract function getImdbInternalIds();

    public abstract function getSeeders();

    public function getSeason()
    {
        $title = $this->getTitle();

        $patterns = [
            '#[\s(.\[]+([0-9]+)[~\-\s]*сезон#ui',
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                return $matches[1];
            }
        }

        $patterns = [
            '#с[:~\-\s]*([0-9]+)[:~\-\s]*по[:~\-\s]*([0-9]+)[:~\-\s]*сезон#ui',
            '#([0-9]+)[~\-\s]+([0-9]+)[:~\-\s]*сезон#ui',
            '#сезоны[:~\-\s]*([0-9]+)[~\-\s]+([0-9]+)#ui',
            '#сезон[:~\-\s]*([0-9]+)[~\-\s]+([0-9]+)#ui',
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                return $this->fromRange($matches[1], $matches[2]);
            }
        }

        $patterns = [
            '#([0-9]+)[~\-\s]*сезонов#ui',
            '#сезонов[~\-\s:]*([0-9]+)#ui'
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                return $this->fromRange(1, $matches[1]);
            }
        }

        $patterns = [
            '#([0-9]+)[~\-\s]*сезон#ui',
            '#сезон[~\-\s]*([0-9]+)#ui',
            '#([0-9]+)[:~\-\s]*сезон#ui',
            '#сезон[:~\-\s]*([0-9]+)#ui',
            '#([0-9]+)[:~\-\s]*сезоны#ui',
            '#сезоны[:~\-\s]*([0-9]+)#ui',
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                return $matches[1];
            }
        }

        $patterns = [
            '#([0-9,]+)[~\-\s]*сезон#ui',
            '#сезон[~\-\s]*([0-9,]+)#ui',
            '#([0-9,]+)[:~\-\s]*сезон#ui',
            '#сезон[:~\-\s]*([0-9]+)#ui',
            '#([0-9,]+)[:~\-\s]*сезоны#ui',
            '#сезоны[:~\-\s]*([0-9,]+)#ui',
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                return trim($matches[1], ', ');
            }
        }

        $patterns = [
            '#([0-9~\-\s]+)\s*сезон#ui',
            '#сезон[\s-:]*([0-9~\-\s]+)#ui',
            '#сезоны[\s-:]*([0-9~\-\s]+)#ui',
            '#([0-9-\s]+)[\s-:]*сезон#ui',
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                $value = $matches[1];
                $value = preg_replace('#[~\-\s]+#u', '-', $value);
                $value = preg_replace('#[^0-9-]#u', '', $value);
                $value = trim($value, '-');
                if($value) {
                    return $value;
                }
            }
        }
        return null;
    }

    protected function fromRange($from, $till)
    {
        $data = [];
        for($i = $from; $i <= $till; $i ++) {
            $data[] = $i;
        }
        return implode(',', $data);
    }

    public function getEpisode()
    {
        $title = $this->getTitle();
        $patterns = [
            '#серии[\s-:]*([0-9~\-\s]+)#ui',
            '#серия[\s-:]*([0-9~\-\s]+)#ui'
        ];
        foreach($patterns as $pattern) {
            preg_match($pattern, $title, $matches);
            if($matches) {
                $value = $matches[1];
                $value = preg_replace('#[~\-\s]+#u', '-', $value);
                $value = preg_replace('#[^0-9-]#u', '', $value);
                $value = trim($value, '-');
                if($value) {
                    return $value;
                }
            }
        }
        return null;
    }
}
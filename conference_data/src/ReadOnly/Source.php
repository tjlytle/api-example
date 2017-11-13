<?php
namespace WorldSpeakers\ReadOnly;

use WorldSpeakers\SourceInterface;

class Source implements SourceInterface
{
    protected $data;

    public function __construct($file)
    {
        $this->data = json_decode(file_get_contents($file), true);
        if(!$this->data){
            throw new \UnexpectedValueException('could not load data from: ' . $file);
        }
    }

    public function getSpeakers($page, $size)
    {
        $pos = $page * $size;
        $speakers = array_slice($this->data['speakers'], $pos, $size);
        return array_values($speakers);
    }

    public function getSpeakerById($id)
    {
        if(!isset($this->data['speakers'][$id])){
            throw new \InvalidArgumentException('can not find speaker: ' . $id);
        }

        return $this->data['speakers'][$id];
    }

    public function getTalks($page, $size, \DateTime $after = null, \DateTime $before = null)
    {
        $pos = $page * $size;
        $current = -1;
        $talks = [];

        //hey, _this_ is why we use databases
        foreach($this->data['talks'] as $talk)
        {
            $current++;

            if($pos > $current){
                continue;
            }

            if($after && $after->getTimestamp() > $talk['date']){
                continue;
            }

            if($before && $before->getTimestamp() < $talk['date']){
                continue;
            }

            $talks[] = $talk;

            if(count($talks) == $size){
                break;
            }
        }

        return $talks;
    }

    public function setFavoriteTalk($user, $id)
    {
        throw new ReadOnlyException(__METHOD__);
    }

    public function setFavoriteSpeaker($user, $id)
    {
        throw new ReadOnlyException(__METHOD__);
    }

    public function getFavorites($user)
    {
        throw new ReadOnlyException(__METHOD__);
    }
}
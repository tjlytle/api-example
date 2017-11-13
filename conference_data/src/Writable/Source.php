<?php
namespace WorldSpeakers\Writable;

use WorldSpeakers\SourceInterface;

class Source implements SourceInterface
{
    /**
     * @var \SQLite3
     */
    protected $db;

    /**
     * @var \SQLite3Stmt
     */
    protected $listSpeaker;

    /**
     * @var \SQLite3Stmt
     */
    protected $getSpeaker;

    /**
     * @var \SQLite3Stmt
     */
    protected $listTalk;

    /**
     * @var \SQLite3Stmt
     */
    protected $getTalk;

    public function __construct($filename, $sqlPath)
    {
        $this->db = new \SQLite3($filename);

        $this->listSpeaker = $this->db->prepare(file_get_contents($sqlPath . '/list-speaker.sql'));
        $this->getSpeaker = $this->db->prepare(file_get_contents($sqlPath . '/get-speaker.sql'));

        $this->listTalk = $this->db->prepare(file_get_contents($sqlPath . '/list-talk.sql'));
        $this->getTalk = $this->db->prepare(file_get_contents($sqlPath . '/get-talk.sql'));

    }

    public function getSpeakers($page, $size)
    {
        $pos = $page * $size;

        $this->listSpeaker->bindValue(':pos', $pos);
        $this->listSpeaker->bindValue(':size', $size);

        $results = $this->listSpeaker->execute();
        $this->listSpeaker->reset();

        $speakers = [];
        while($speaker = $results->fetchArray(SQLITE3_ASSOC)){
            $speakers[] = $speaker;
        }

        return $speakers;
    }

    public function getSpeakerById($id)
    {
        $this->getSpeaker->bindValue(':id', $id);
        $speaker = $this->getSpeaker->execute()->fetchArray(SQLITE3_ASSOC);

        if(!$speaker){
            throw new \InvalidArgumentException('can not find speaker: ' . $id);
        }

        return $speaker;
    }

    public function getTalks($page, $size, \DateTime $after = null, \DateTime $before = null)
    {
        if(is_null($after)){
            $after = new \DateTime('1/1/2016');
        }

        if(is_null($before)){
            $before = new \DateTime('1/1/2018');
        }

        $pos = $page * $size;

        $this->listTalk->bindValue(':pos', $pos);
        $this->listTalk->bindValue(':size', $size);
        $this->listTalk->bindValue(':before', $before->format('Y-m-d H:i:s'));
        $this->listTalk->bindValue(':after', $after->format('Y-m-d H:i:s'));

        $results = $this->listTalk->execute();
        $this->listTalk->reset();

        $talks = [];
        while($talk = $results->fetchArray(SQLITE3_ASSOC)){
            $talks[] = $talk;
        }

        return $talks;
    }

    public function setFavoriteTalk($user, $id)
    {
        // TODO: Implement setFavoriteTalk() method.
    }

    public function setFavoriteSpeaker($user, $id)
    {
        // TODO: Implement setFavoriteSpeaker() method.
    }

    public function getFavorites($user)
    {
        // TODO: Implement getFavorites() method.
    }
}